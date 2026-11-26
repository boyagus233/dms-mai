<?php

namespace App\Services;

use App\Mail\SuratJalanNotification;
use App\Models\Departement;
use App\Models\SuratJalan;
use App\Models\SuratJalanSigner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SuratJalanService
{
    private const STAMP_DIR = 'surat-jalan/stamps';
    private const VIEW_LOG_THROTTLE_MINUTES = 30;

    public function __construct(private DocumentAuditLogger $audit)
    {
    }

    /* ---------------------------------------------------------------------
     |  Hak akses
     * ------------------------------------------------------------------- */

    public function canView(SuratJalan $sj, User $user): bool
    {
        if ($sj->created_by === $user->id || $user->hasAnyRole(config('surat_jalan.viewer_roles', []))) {
            return true;
        }
        // Draft belum dikirim: signer belum boleh melihat.
        if ($sj->isDraft()) {
            return false;
        }
        if ($sj->signers->contains('user_id', $user->id)) {
            return true;
        }
        // Member grup (mis. HR-GA untuk kotak Pemeriksa) boleh memantau sejak dokumen di-submit.
        return $sj->signers->contains(fn (SuratJalanSigner $s) => $s->isGroup() && $this->isGroupMember($user, $s->group_departement_id));
    }

    public function canEdit(SuratJalan $sj, User $user): bool
    {
        return $sj->isDraft() && $sj->created_by === $user->id;
    }

    public function canSign(SuratJalan $sj, User $user): bool
    {
        if ($sj->status !== SuratJalan::STATUS_IN_PROGRESS) {
            return false;
        }
        $current = $sj->currentSigner();
        return $current && $this->canActOn($sj, $current, $user);
    }

    /**
     * Apakah $user boleh menandatangani / menolak kotak $signer.
     * - Kotak perorangan: hanya user yang ditunjuk.
     * - Kotak grup (Pemeriksa = HR-GA): siapa pun member aktif departemen grup, cukup satu orang,
     *   asalkan ia belum menandatangani kotak lain di Surat Jalan yang sama (1 orang = 1 kotak).
     */
    public function canActOn(SuratJalan $sj, SuratJalanSigner $signer, User $user): bool
    {
        if (!$signer->isOpenGroup()) {
            return $signer->user_id === $user->id;
        }
        if (!$this->isGroupMember($user, $signer->group_departement_id)) {
            return false;
        }
        return !$sj->signers->contains(fn (SuratJalanSigner $s) => $s->id !== $signer->id
            && $s->user_id === $user->id && $s->isSigned());
    }

    /** Member grup = user aktif yang departemen utamanya (m_employee.departement) = $departementId. */
    public function isGroupMember(User $user, ?int $departementId): bool
    {
        return $departementId
            && (bool) $user->is_active
            && (int) $user->employee?->departement === (int) $departementId;
    }

    /** Daftar member aktif sebuah departemen (penerima notifikasi kotak grup). */
    public function groupMembers(int $departementId)
    {
        return User::query()
            ->where('users.is_active', 1)
            ->whereHas('employee', fn ($q) => $q->where('departement', $departementId))
            ->with(['employee.positions', 'employee.departements'])
            ->orderBy('name')
            ->get();
    }

    /** Departemen grup Pemeriksa (default kode 'HR' = HR-GA), dari config surat_jalan.pemeriksa_departement_code. */
    public function pemeriksaDepartement(): ?Departement
    {
        return Departement::where('code', config('surat_jalan.pemeriksa_departement_code', 'HR'))->first();
    }

    /* ---------------------------------------------------------------------
     |  Create / update draft
     * ------------------------------------------------------------------- */

    /**
     * @param array $data hasil validasi controller: header, kategori[], items[], signer_2..4
     */
    public function create(array $data, User $creator): SuratJalan
    {
        return DB::transaction(function () use ($data, $creator) {
            $sj = SuratJalan::create(array_merge($this->headerAttributes($data), [
                'uuid'         => (string) Str::uuid(),
                'status'       => SuratJalan::STATUS_DRAFT,
                'current_step' => 0,
                'created_by'   => $creator->id,
            ]));

            $this->syncItems($sj, $data['items']);
            $this->syncSigners($sj, $creator, $data);

            $this->audit->log(SuratJalan::DOCUMENT_TYPE, $sj->id, DocumentAuditLogger::DOCUMENT_CREATED,
                'Draft Surat Jalan dibuat oleh ' . $creator->name,
                ['items' => count($data['items']), 'kepada' => $sj->kepada], $creator);

            return $sj;
        });
    }

    public function update(SuratJalan $sj, array $data, User $user): SuratJalan
    {
        $this->ensure($this->canEdit($sj, $user), 'Hanya draft milik Anda yang dapat diubah.');

        return DB::transaction(function () use ($sj, $data, $user) {
            $sj->fill($this->headerAttributes($data));
            $sj->updated_by = $user->id;
            $sj->save();

            $this->syncItems($sj, $data['items']);
            $this->syncSigners($sj, $user, $data);

            $this->audit->log(SuratJalan::DOCUMENT_TYPE, $sj->id, DocumentAuditLogger::DOCUMENT_UPDATED,
                'Draft diperbarui', ['items' => count($data['items'])], $user);

            return $sj;
        });
    }

    public function deleteDraft(SuratJalan $sj, User $user): void
    {
        $this->ensure($this->canEdit($sj, $user), 'Hanya draft milik Anda yang dapat dihapus.');

        DB::transaction(function () use ($sj, $user) {
            // Log tetap disimpan (immutable) sebagai jejak penghapusan draft.
            $this->audit->log(SuratJalan::DOCUMENT_TYPE, $sj->id, DocumentAuditLogger::DOCUMENT_DELETED,
                'Draft dihapus oleh pembuat', ['kepada' => $sj->kepada], $user);
            $sj->items()->delete();
            $sj->signers()->delete();
            $sj->delete();
        });
    }

    private function headerAttributes(array $data): array
    {
        $kategori = array_values(array_unique($data['kategori']));

        return [
            'departement_id' => $data['departement_id'],
            'location'       => $data['location'] ?? config('surat_jalan.default_location'),
            'kepada_tipe'    => $data['kepada_tipe'] ?? null,
            'kepada'         => $data['kepada'],
            'no_kendaraan'   => !empty($data['no_kendaraan']) ? strtoupper($data['no_kendaraan']) : null,
            'kategori'       => $kategori,
            'kategori_lain'  => in_array('LAIN_LAIN', $kategori, true) ? ($data['kategori_lain'] ?? null) : null,
        ];
    }

    private function syncItems(SuratJalan $sj, array $items): void
    {
        $sj->items()->delete();
        $line = 1;
        foreach ($items as $item) {
            $sj->items()->create([
                'line_no'      => $line++,
                'jenis_barang' => trim($item['jenis_barang']),
                'qty'          => $item['qty'],
                'satuan'       => isset($item['satuan']) ? strtoupper(trim($item['satuan'])) : null,
                'keterangan'   => isset($item['keterangan']) ? trim($item['keterangan']) : null,
            ]);
        }
    }

    private function syncSigners(SuratJalan $sj, User $creator, array $data): void
    {
        // Kotak 3 (Pemeriksa) tidak ditujukan ke 1 orang: milik seluruh member HR-GA.
        $assignments = [
            1 => $creator->id,
            2 => (int) $data['signer_2'],
            4 => (int) $data['signer_4'],
        ];
        $pemeriksaDept = $this->pemeriksaDepartement();
        $this->ensure((bool) $pemeriksaDept, 'Departemen Pemeriksa (HR-GA) tidak ditemukan di master departemen.');

        $sj->signers()->delete();
        $users = User::with(['employee.positions', 'employee.departements'])
            ->whereIn('id', array_values($assignments))
            ->get()
            ->keyBy('id');

        foreach ($assignments as $step => $userId) {
            $user = $users->get($userId);
            $this->ensure((bool) $user, "Signer untuk slot {$step} tidak ditemukan.");
            $profile = $this->profile($user);

            $sj->signers()->create([
                'step'               => $step,
                'slot'               => config("surat_jalan.slots.{$step}.code"),
                'user_id'            => $user->id,
                'employee_id'        => $profile['employee_id'],
                'signer_name'        => $profile['name'],
                'signer_position'    => $profile['position'],
                'signer_departement' => $profile['departement'],
                'status'             => SuratJalanSigner::STATUS_WAITING,
            ]);
        }

        $sj->signers()->create([
            'step'                 => 3,
            'slot'                 => config('surat_jalan.slots.3.code'),
            'user_id'              => null,               // diisi oleh member HR-GA yang menandatangani
            'group_departement_id' => $pemeriksaDept->id,
            'signer_name'          => 'Member ' . $pemeriksaDept->name,
            'signer_position'      => null,
            'signer_departement'   => $pemeriksaDept->name,
            'status'               => SuratJalanSigner::STATUS_WAITING,
        ]);
        $sj->unsetRelation('signers');
    }

    /* ---------------------------------------------------------------------
     |  Submit (slot 1 Diserahkan otomatis ter-sign)
     * ------------------------------------------------------------------- */

    /**
     * Submit = pemohon menandatangani kotak "Diserahkan" lewat pop-up tanda tangan.
     *
     * @param string|null $padDataUrl  PNG data URL hasil signature pad; null = pakai stamp profil (atau QR bila tidak ada)
     * @param bool        $saveToProfile simpan gambar pad sebagai tanda tangan profil user
     */
    public function submit(SuratJalan $sj, User $user, ?string $padDataUrl = null, bool $saveToProfile = false): SuratJalan
    {
        $this->ensure($this->canEdit($sj, $user), 'Dokumen ini tidak dapat di-submit.');

        DB::transaction(function () use ($sj, $user, $padDataUrl, $saveToProfile) {
            /** @var SuratJalan $locked */
            $locked = SuratJalan::whereKey($sj->id)->lockForUpdate()->first();
            $this->ensure($locked->isDraft(), 'Dokumen sudah pernah di-submit.');

            // Satu titik waktu untuk nomor, tanggal SJ & waktu submit (konsisten di pergantian bulan).
            $now = now();
            $sj->sj_no = $this->generateNumber($sj, $now);
            $sj->sj_date = $now->toDateString();
            $sj->status = SuratJalan::STATUS_IN_PROGRESS;
            $sj->submitted_at = $now;
            $sj->current_step = 2;
            $sj->updated_by = $user->id;
            $sj->save();

            $first = $sj->signers()->where('step', 1)->lockForUpdate()->firstOrFail();
            $this->sign($sj, $first, $user, $padDataUrl, $saveToProfile);

            $sj->signers()->where('step', 2)->update(['status' => SuratJalanSigner::STATUS_PENDING]);

            $this->audit->log(SuratJalan::DOCUMENT_TYPE, $sj->id, DocumentAuditLogger::SUBMITTED_SIGNED,
                "Dokumen {$sj->sj_no} di-submit & stamp Diserahkan terpasang",
                ['sj_no' => $sj->sj_no, 'slot' => 'DISERAHKAN', 'stamp_type' => $first->stamp_type], $user);
        });

        $sj->refresh()->load('signers');
        $this->notifySigner($sj, $sj->signerForStep(2), $user);

        return $sj;
    }

    /* ---------------------------------------------------------------------
     |  Approve / Sign & Reject
     * ------------------------------------------------------------------- */

    public function approve(SuratJalan $sj, User $user, ?string $padDataUrl = null, bool $saveToProfile = false): SuratJalan
    {
        $completed = false;
        $nextStep = null;

        DB::transaction(function () use ($sj, $user, $padDataUrl, $saveToProfile, &$completed, &$nextStep) {
            $locked = SuratJalan::whereKey($sj->id)->lockForUpdate()->first();
            $this->ensure($locked->status === SuratJalan::STATUS_IN_PROGRESS, 'Dokumen tidak dalam proses approval.');

            $signer = $sj->signers()->where('status', SuratJalanSigner::STATUS_PENDING)->lockForUpdate()->first();
            // Data signer terbaru (setelah lock) agar 2 member HR-GA yang menekan bersamaan tidak sama-sama lolos.
            $sj->setRelation('signers', $sj->signers()->get());
            $this->ensure($signer && $this->canActOn($sj, $signer, $user), $this->notYourTurnMessage($sj, $user));

            // Semua kotak boleh ditandatangani lewat signature pad (pop-up tanda tangan).
            $this->sign($sj, $signer, $user, $padDataUrl, $saveToProfile);

            $this->audit->log(SuratJalan::DOCUMENT_TYPE, $sj->id, DocumentAuditLogger::APPROVED_SIGNED,
                $signer->slotLabel() . ' di-approve & ditandatangani oleh ' . $signer->signer_name,
                [
                    'slot'       => $signer->slot,
                    'step'       => $signer->step,
                    'position'   => $signer->signer_position,
                    'stamp_type' => $signer->stamp_type,
                    'signed_at'  => $signer->signed_at->format('d-m-Y H:i:s.v'),
                    'auth'       => 'Authenticated Internal Session',
                ], $user);

            $next = $sj->signers()->where('step', '>', $signer->step)->orderBy('step')->first();
            if ($next) {
                $next->update(['status' => SuratJalanSigner::STATUS_PENDING]);
                $locked->update(['current_step' => $next->step, 'updated_by' => $user->id]);
                $nextStep = $next->step;
            } else {
                $locked->update([
                    'status'       => SuratJalan::STATUS_COMPLETED,
                    'current_step' => 0,
                    'completed_at' => now(),
                    'updated_by'   => $user->id,
                ]);
                $this->audit->log(SuratJalan::DOCUMENT_TYPE, $sj->id, DocumentAuditLogger::COMPLETED,
                    'Seluruh rangkaian approval selesai', ['sj_no' => $sj->sj_no], $user);
                $completed = true;
            }
        });

        $sj->refresh()->load('signers');

        if ($nextStep) {
            $this->notifySigner($sj, $sj->signerForStep($nextStep), $user);
        }
        if ($completed) {
            $this->notifyCompleted($sj, $user);
        }

        return $sj;
    }

    public function reject(SuratJalan $sj, User $user, string $reason): SuratJalan
    {
        DB::transaction(function () use ($sj, $user, $reason) {
            $locked = SuratJalan::whereKey($sj->id)->lockForUpdate()->first();
            $this->ensure($locked->status === SuratJalan::STATUS_IN_PROGRESS, 'Dokumen tidak dalam proses approval.');

            $signer = $sj->signers()->where('status', SuratJalanSigner::STATUS_PENDING)->lockForUpdate()->first();
            $sj->setRelation('signers', $sj->signers()->get());
            $this->ensure($signer && $this->canActOn($sj, $signer, $user), $this->notYourTurnMessage($sj, $user));

            $request = request();
            $profile = $this->profile($user);
            $signer->update([
                // Kotak grup: catat member yang menolak sebagai pelaku.
                'user_id'            => $user->id,
                'employee_id'        => $profile['employee_id'],
                'signer_name'        => $profile['name'],
                'signer_position'    => $profile['position'],
                'signer_departement' => $profile['departement'],
                'status'     => SuratJalanSigner::STATUS_REJECTED,
                'signed_at'  => now(),
                'note'       => $reason,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 495),
            ]);

            $locked->update([
                'status'          => SuratJalan::STATUS_REJECTED,
                'current_step'    => 0,
                'rejected_reason' => $reason,
                'rejected_by'     => $user->id,
                'rejected_at'     => now(),
                'updated_by'      => $user->id,
            ]);

            $this->audit->log(SuratJalan::DOCUMENT_TYPE, $sj->id, DocumentAuditLogger::REJECTED,
                'Ditolak oleh ' . $signer->signer_name . ' (' . $signer->slotLabel() . '): ' . $reason,
                ['slot' => $signer->slot, 'step' => $signer->step, 'reason' => $reason], $user);
        });

        $sj->refresh()->load('signers', 'creator');
        $this->notifyRejected($sj, $user, $reason);

        return $sj;
    }

    /**
     * Tandai signer sebagai SIGNED + simpan snapshot stamp (agar perubahan
     * tanda tangan di profil di kemudian hari tidak mengubah dokumen yang sudah ditandatangani).
     */
    private function sign(SuratJalan $sj, SuratJalanSigner $signer, User $user, ?string $padDataUrl = null, bool $saveToProfile = false): void
    {
        $request = request();
        $profile = $this->profile($user);
        [$type, $path] = $this->snapshotStamp($sj, $signer, $profile, $padDataUrl);

        if ($saveToProfile && $type === SuratJalanSigner::STAMP_PAD) {
            $this->saveSignatureToProfile($user, Storage::disk('local')->get($path));
        }

        $signer->update([
            'status'             => SuratJalanSigner::STATUS_SIGNED,
            'user_id'            => $user->id,   // kotak grup: member yang benar-benar menandatangani
            'signer_name'        => $profile['name'],
            'signer_position'    => $profile['position'],
            'signer_departement' => $profile['departement'],
            'employee_id'        => $profile['employee_id'],
            'stamp_type'         => $type,
            'stamp_path'         => $path,
            'signed_at'          => now(),
            'ip_address'         => $request->ip(),
            'user_agent'         => Str::limit((string) $request->userAgent(), 495),
        ]);
    }

    /** @return array{0:string,1:?string} [stamp_type, stamp_path] */
    private function snapshotStamp(SuratJalan $sj, SuratJalanSigner $signer, array $profile, ?string $padDataUrl): array
    {
        $dir = self::STAMP_DIR . '/' . $sj->id;
        $base = 'step' . $signer->step . '_' . now()->format('YmdHis');

        if ($padDataUrl) {
            $binary = $this->decodePngDataUrl($padDataUrl);
            $path = "{$dir}/{$base}_pad.png";
            Storage::disk('local')->put($path, $binary);
            return [SuratJalanSigner::STAMP_PAD, $path];
        }

        $file = $profile['signature'];
        if ($file && Storage::disk('public')->exists('signature/' . $file)) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION)) ?: 'png';
            $path = "{$dir}/{$base}.{$ext}";
            Storage::disk('local')->put($path, Storage::disk('public')->get('signature/' . $file));
            return [SuratJalanSigner::STAMP_IMAGE, $path];
        }

        // Tidak ada image stamp di profil → QR Stamp (Nama, Jabatan, Tanggal Sign) dirender saat tampil/cetak.
        return [SuratJalanSigner::STAMP_QR, null];
    }

    /**
     * Simpan tanda tangan hasil signature pad sebagai tanda tangan profil
     * (storage/app/public/signature, dipakai juga modul Document & Ad-Hoc).
     * File lama tidak dihapus karena bisa masih direferensikan dokumen lain.
     */
    private function saveSignatureToProfile(User $user, string $pngBinary): void
    {
        $filename = time() . '_' . Str::random(12) . '.png';
        Storage::disk('public')->put('signature/' . $filename, $pngBinary);

        $user->signature = $filename;
        $user->save();

        if ($user->employee) {
            $user->employee->signature = $filename;
            $user->employee->save();
        }
    }

    private function decodePngDataUrl(string $dataUrl): string
    {
        $this->ensure(str_starts_with($dataUrl, 'data:image/png;base64,'), 'Format tanda tangan tidak valid.');
        $binary = base64_decode(substr($dataUrl, strlen('data:image/png;base64,')), true);
        $this->ensure($binary !== false && strlen($binary) < 1024 * 1024, 'Ukuran tanda tangan tidak valid.');
        $info = @getimagesizefromstring($binary);
        $this->ensure($info !== false && $info[2] === IMAGETYPE_PNG, 'File tanda tangan bukan PNG.');
        return $binary;
    }

    /* ---------------------------------------------------------------------
     |  Penomoran: {No Urut}/{DEPT}/MAI/{Bulan Romawi}/{Tahun}
     |  Contoh 001/QA/MAI/XI/2026. No urut dihitung per departemen dan
     |  mulai lagi dari 001 setiap bulan baru (bulan & tahun = tanggal submit).
     * ------------------------------------------------------------------- */

    private const ROMAN_MONTHS = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    private function generateNumber(SuratJalan $sj, \DateTimeInterface $date): string
    {
        $dept = Departement::find($sj->departement_id);
        $deptCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $dept->code ?? 'GEN')) ?: 'GEN';
        $company = strtoupper(config('surat_jalan.company_code', 'MAI'));
        $suffix = '/' . implode('/', [$deptCode, $company, self::ROMAN_MONTHS[(int) $date->format('n')], $date->format('Y')]);
        $digits = (int) config('surat_jalan.number_digits', 3);

        // Dipanggil di dalam transaksi submit. No urut ada di depan, jadi nomor bulan ini
        // dicari lewat akhiran "/QA/MAI/XI/2026" lalu diambil bagian pertamanya.
        $last = SuratJalan::where('sj_no', 'like', '%' . $suffix)
            ->lockForUpdate()
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(sj_no, '/', 1) AS UNSIGNED)) AS last_seq")
            ->value('last_seq');

        return str_pad((string) (((int) $last) + 1), $digits, '0', STR_PAD_LEFT) . $suffix;
    }

    /* ---------------------------------------------------------------------
     |  View log (dibatasi agar tidak membanjiri audit trail)
     * ------------------------------------------------------------------- */

    public function recordView(SuratJalan $sj, User $user): void
    {
        if ($sj->isDraft()) {
            return;
        }
        $recent = $sj->auditLogs()
            ->where('action', DocumentAuditLogger::DOCUMENT_VIEWED)
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subMinutes(self::VIEW_LOG_THROTTLE_MINUTES))
            ->exists();

        if (!$recent) {
            $signer = $sj->signers->firstWhere('user_id', $user->id);
            $this->audit->log(SuratJalan::DOCUMENT_TYPE, $sj->id, DocumentAuditLogger::DOCUMENT_VIEWED,
                $user->name . ' membuka halaman detail dokumen',
                $signer ? ['slot' => $signer->slot, 'signer_status' => $signer->status] : [],
                $user, false);
        }
    }

    /* ---------------------------------------------------------------------
     |  Notifikasi email
     * ------------------------------------------------------------------- */

    /** Pesan bila user tidak berhak; khusus kotak grup yang sudah diambil member lain. */
    private function notYourTurnMessage(SuratJalan $sj, User $user): string
    {
        $taken = $sj->signers->first(fn (SuratJalanSigner $s) => $s->isGroup() && $s->user_id && $s->user_id !== $user->id
            && $this->isGroupMember($user, $s->group_departement_id));

        if ($taken) {
            $verb = $taken->status === SuratJalanSigner::STATUS_REJECTED ? 'ditolak' : 'ditandatangani';
            return "Kotak {$taken->slotLabel()} sudah {$verb} oleh " . rtrim($taken->signer_name, '.') . '.';
        }

        return 'Saat ini bukan giliran Anda untuk memproses dokumen ini.';
    }

    private function notifySigner(SuratJalan $sj, ?SuratJalanSigner $signer, User $actor): void
    {
        if (!$signer) {
            return;
        }

        // Kotak grup (Pemeriksa): kirim ke semua member HR-GA yang berhak menandatangani.
        if ($signer->isOpenGroup()) {
            $sj->loadMissing('signers');
            foreach ($this->groupMembers($signer->group_departement_id) as $member) {
                if (!$this->canActOn($sj, $signer, $member)) {
                    continue;
                }
                $name = $this->profile($member)['name'];
                $this->sendMail($sj, $member, $name, $actor, new SuratJalanNotification(
                    SuratJalanNotification::TYPE_SIGN_REQUEST, $sj, $name,
                    route('surat-jalan.show', ['suratJalan' => $sj->uuid, 'sign' => 1]), $signer->slotLabel()
                ), ['slot' => $signer->slot, 'step' => $signer->step, 'group' => $signer->signer_departement]);
            }
            return;
        }

        $this->sendMail($sj, $signer->user, $signer->signer_name, $actor, new SuratJalanNotification(
            SuratJalanNotification::TYPE_SIGN_REQUEST, $sj, $signer->signer_name,
            // ?sign=1 → pop-up tanda tangan langsung terbuka saat signer membuka link email
            route('surat-jalan.show', ['suratJalan' => $sj->uuid, 'sign' => 1]), $signer->slotLabel()
        ), ['slot' => $signer->slot, 'step' => $signer->step]);
    }

    private function notifyCompleted(SuratJalan $sj, User $actor): void
    {
        $sj->loadMissing('signers.user');
        // Pemilik barang (pembuat) + seluruh signer menerima pemberitahuan selesai.
        foreach ($sj->signers->unique('user_id') as $signer) {
            $this->sendMail($sj, $signer->user, $signer->signer_name, $actor, new SuratJalanNotification(
                SuratJalanNotification::TYPE_COMPLETED, $sj, $signer->signer_name, route('surat-jalan.show', $sj->uuid)
            ), ['purpose' => 'COMPLETED']);
        }
    }

    private function notifyRejected(SuratJalan $sj, User $actor, string $reason): void
    {
        $creator = $sj->creator;
        $this->sendMail($sj, $creator, $creator?->name ?? '-', $actor, new SuratJalanNotification(
            SuratJalanNotification::TYPE_REJECTED, $sj, $creator?->name ?? '-',
            route('surat-jalan.show', $sj->uuid), null, $actor->name, $reason
        ), ['purpose' => 'REJECTED']);
    }

    private function sendMail(SuratJalan $sj, ?User $recipient, string $recipientName, User $actor, SuratJalanNotification $mail, array $meta): void
    {
        $email = $recipient?->email ?: $recipient?->employee?->email;
        $meta = array_merge($meta, ['recipient' => $recipientName, 'email' => $email]);

        if (!$email) {
            $this->audit->log(SuratJalan::DOCUMENT_TYPE, $sj->id, DocumentAuditLogger::NOTIFICATION_SKIPPED,
                "Email notifikasi ke {$recipientName} tidak dikirim (alamat email kosong)", $meta, $actor, false);
            return;
        }

        try {
            Mail::to($email)->send($mail);
            $this->audit->log(SuratJalan::DOCUMENT_TYPE, $sj->id, DocumentAuditLogger::NOTIFICATION_SENT,
                "Email notifikasi dikirim ke {$recipientName}", $meta, $actor, false);
        } catch (Throwable $e) {
            Log::error('Surat Jalan: gagal kirim email', ['sj' => $sj->id, 'email' => $email, 'error' => $e->getMessage()]);
            $this->audit->log(SuratJalan::DOCUMENT_TYPE, $sj->id, DocumentAuditLogger::NOTIFICATION_FAILED,
                "Email notifikasi ke {$recipientName} gagal dikirim", array_merge($meta, ['error' => Str::limit($e->getMessage(), 200)]), $actor, false);
        }
    }

    /* ---------------------------------------------------------------------
     |  Helpers
     * ------------------------------------------------------------------- */

    /** Data profil penandatangan dari users + m_employee. */
    public function profile(User $user): array
    {
        $user->loadMissing(['employee.positions', 'employee.departements']);
        $employee = $user->employee;

        return [
            'employee_id' => $employee?->id,
            'name'        => $employee?->name ?: $user->name,
            'position'    => $employee?->positions?->name,
            'departement' => $employee?->departements?->name,
            'signature'   => $employee?->signature ?: $user->signature,
        ];
    }

    public function hasStampImage(User $user): bool
    {
        $file = $this->profile($user)['signature'];
        return $file && Storage::disk('public')->exists('signature/' . $file);
    }

    /** Path absolut stamp snapshot (untuk PDF / response image). */
    public function stampAbsolutePath(SuratJalanSigner $signer): ?string
    {
        if (!$signer->stamp_path || !Storage::disk('local')->exists($signer->stamp_path)) {
            return null;
        }
        return Storage::disk('local')->path($signer->stamp_path);
    }

    private function ensure(bool $condition, string $message): void
    {
        if (!$condition) {
            throw ValidationException::withMessages(['surat_jalan' => $message]);
        }
    }
}
