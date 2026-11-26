<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\SuratJalan;
use App\Models\SuratJalanItem;
use App\Models\SuratJalanSigner;
use App\Models\User;
use App\Services\DocumentAuditLogger;
use App\Services\SuratJalanPdfService;
use App\Services\SuratJalanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RealRashid\SweetAlert\Facades\Alert;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SuratJalanController extends Controller
{
    public function __construct(
        private SuratJalanService $service,
        private SuratJalanPdfService $pdf,
        private DocumentAuditLogger $audit,
    ) {
    }

    /* ------------------------------------------------------------------ */
    /*  List                                                               */
    /* ------------------------------------------------------------------ */

    public function index(Request $request)
    {
        $user = Auth::user();
        $isViewerAll = $user->hasAnyRole(config('surat_jalan.viewer_roles', []));
        $scope = $request->input('scope', $this->pendingCount($user) > 0 ? 'need_sign' : 'mine');

        return view('content.surat-jalan.index', [
            'scope'        => $scope,
            'isViewerAll'  => $isViewerAll,
            'pendingCount' => $this->pendingCount($user),
        ]);
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $scope = $request->input('scope', 'mine');
        $status = $request->input('status', 'ALL');

        $query = SuratJalan::query()
            ->with(['departement:id,code,name', 'creator:id,name', 'signers:id,surat_jalan_id,step,user_id,signer_name,status'])
            ->withCount('items');

        switch ($scope) {
            case 'need_sign':
                $query->awaitingActionBy($user);
                break;
            case 'involved':
                // Ditunjuk langsung, atau member grup kotak (mis. HR-GA untuk Pemeriksa).
                $deptId = $user->employee?->departement;
                $query->where('status', '<>', SuratJalan::STATUS_DRAFT)
                    ->whereHas('signers', fn ($q) => $q->where('step', '>', 1)->where(fn ($s) => $s
                        ->where('user_id', $user->id)
                        ->when($deptId, fn ($s) => $s->orWhere('group_departement_id', $deptId))));
                break;
            case 'all':
                if (!$user->hasAnyRole(config('surat_jalan.viewer_roles', []))) {
                    abort(403);
                }
                $query->where(fn ($q) => $q->where('status', '<>', SuratJalan::STATUS_DRAFT)->orWhere('created_by', $user->id));
                break;
            default: // mine
                $query->where('created_by', $user->id);
        }

        if ($status !== 'ALL') {
            $query->where('status', $status);
        }

        $rows = $query->orderByDesc('created_at')->limit(2000)->get()->map(function (SuratJalan $sj) {
            $current = $sj->currentSigner();
            return [
                'uuid'         => $sj->uuid,
                'sj_no'        => $sj->sj_no ?? '-',
                'sj_date'      => $sj->sj_date?->format('d-m-Y') ?? '-',
                'kepada'       => $sj->kepada,
                'departement'  => $sj->departement?->name ?? '-',
                'kategori'     => collect($sj->kategori)->map(fn ($k) => config('surat_jalan.kategori.' . $k, $k))->implode(', '),
                'items_count'  => $sj->items_count,
                'creator'      => $sj->creator?->name ?? '-',
                'status'       => $sj->status,
                'status_label' => $sj->statusLabel(),
                'status_class' => $sj->statusBadgeClass(),
                'waiting_for'  => $current ? $current->signer_name . ' (' . $current->slotLabel() . ')' : '-',
                'created_at'   => $sj->created_at->format('d-m-Y H:i'),
                'created_ts'   => $sj->created_at->timestamp,
                'url'          => route('surat-jalan.show', $sj->uuid),
            ];
        });

        return response()->json(['data' => $rows]);
    }

    /* ------------------------------------------------------------------ */
    /*  Create / edit draft                                                */
    /* ------------------------------------------------------------------ */

    public function create()
    {
        return view('content.surat-jalan.form', $this->formData(null));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $this->validated($request);
        $sj = $this->service->create($data, $user);

        return $this->afterSave($request, $sj, 'Draft Surat Jalan berhasil disimpan.');
    }

    /**
     * Setelah simpan draft: bila user menekan "Submit & Tanda Tangan", arahkan ke halaman detail
     * dengan pop-up tanda tangan kotak Diserahkan langsung terbuka (submit terjadi saat ia tanda tangan).
     */
    private function afterSave(Request $request, SuratJalan $sj, string $draftMessage)
    {
        if ($request->input('action') === 'submit') {
            Alert::info('Tanda Tangan Diserahkan', 'Draft tersimpan. Tanda tangani kotak "Diserahkan" untuk submit Surat Jalan.');
            return redirect()->route('surat-jalan.show', ['suratJalan' => $sj->uuid, 'sign' => 1]);
        }

        Alert::success('Tersimpan', $draftMessage);
        return redirect()->route('surat-jalan.show', $sj->uuid);
    }

    public function edit(SuratJalan $suratJalan)
    {
        abort_unless($this->service->canEdit($suratJalan, Auth::user()), 403, 'Hanya draft milik Anda yang dapat diubah.');
        $suratJalan->load(['items', 'signers']);

        return view('content.surat-jalan.form', $this->formData($suratJalan));
    }

    public function update(Request $request, SuratJalan $suratJalan)
    {
        $user = Auth::user();
        $data = $this->validated($request);
        $sj = $this->service->update($suratJalan, $data, $user);

        return $this->afterSave($request, $sj, 'Draft Surat Jalan berhasil diperbarui.');
    }

    public function destroy(SuratJalan $suratJalan)
    {
        $this->service->deleteDraft($suratJalan, Auth::user());
        Alert::success('Terhapus', 'Draft Surat Jalan telah dihapus.');

        return redirect()->route('surat-jalan.index');
    }

    /** Pop-up tanda tangan kotak Diserahkan (pemohon) → submit dokumen. */
    public function submit(Request $request, SuratJalan $suratJalan)
    {
        [$pad, $saveToProfile] = $this->signatureInput($request);
        $sj = $this->service->submit($suratJalan->load('signers'), Auth::user(), $pad, $saveToProfile);
        Alert::success('Berhasil', "Surat Jalan {$sj->sj_no} berhasil ditandatangani & dikirim ke Dept. Head.");

        return redirect()->route('surat-jalan.show', $sj->uuid);
    }

    /* ------------------------------------------------------------------ */
    /*  Detail, approve, reject                                            */
    /* ------------------------------------------------------------------ */

    public function show(Request $request, SuratJalan $suratJalan)
    {
        $user = Auth::user();
        $suratJalan->load(['items', 'signers.user', 'departement', 'creator', 'rejecter']);

        if (!$this->service->canView($suratJalan, $user)) {
            // Akun yang login bukan signer/pemilik dokumen ini (mis. link email dibuka akun lain).
            abort(403, 'Akun Anda tidak termasuk penanda tangan / pemilik Surat Jalan ini.');
        }

        $this->service->recordView($suratJalan, $user);

        $canSign = $this->service->canSign($suratJalan, $user);
        $canEdit = $this->service->canEdit($suratJalan, $user);
        $currentSigner = $suratJalan->currentSigner();

        // Kotak yang boleh ditandatangani user ini sekarang (tombol + pop-up muncul di kotak tsb):
        // draft milik pemohon → kotak 1 (Diserahkan = submit); giliran approval → kotak signer aktif.
        $signableStep = $canEdit ? 1 : ($canSign ? $currentSigner?->step : null);

        return view('content.surat-jalan.show', [
            'sj'            => $suratJalan,
            'canSign'       => $canSign,
            'canEdit'       => $canEdit,
            'signableStep'  => $signableStep,
            'autoOpenSign'  => $signableStep && $request->boolean('sign'),
            'currentSigner' => $currentSigner,
            'myStampUrl'    => $this->service->hasStampImage($user)
                ? asset('storage/signature/' . rawurlencode($this->service->profile($user)['signature']))
                : null,
            'myProfile'     => $this->service->profile($user),
            'qrSvgs'        => $this->qrSvgs($suratJalan),
            'logs'          => $suratJalan->auditLogs()->get(),
        ]);
    }

    /** Pop-up tanda tangan kotak Mengetahui / Pemeriksa / Diterima → approve & sign. */
    public function approve(Request $request, SuratJalan $suratJalan)
    {
        [$pad, $saveToProfile] = $this->signatureInput($request);
        $sj = $this->service->approve($suratJalan->load('signers'), Auth::user(), $pad, $saveToProfile);

        $msg = $sj->status === SuratJalan::STATUS_COMPLETED
            ? 'Seluruh tanda tangan lengkap. Surat Jalan berstatus COMPLETED.'
            : 'Dokumen berhasil di-approve. Stamp Anda telah terpasang.';
        Alert::success('Approved', $msg);

        return redirect()->route('surat-jalan.show', $sj->uuid);
    }

    public function reject(Request $request, SuratJalan $suratJalan)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'reason.required' => 'Alasan penolakan wajib diisi.',
            'reason.min'      => 'Alasan penolakan minimal 5 karakter.',
        ]);

        $sj = $this->service->reject($suratJalan->load('signers'), Auth::user(), $data['reason']);
        Alert::warning('Rejected', 'Surat Jalan telah ditolak dan proses dihentikan.');

        return redirect()->route('surat-jalan.show', $sj->uuid);
    }

    /* ------------------------------------------------------------------ */
    /*  Output                                                             */
    /* ------------------------------------------------------------------ */

    public function pdf(Request $request, SuratJalan $suratJalan)
    {
        $user = Auth::user();
        abort_unless($this->service->canView($suratJalan->load('signers'), $user), 403);

        $content = $this->pdf->suratJalan($suratJalan);
        $download = $request->boolean('download');

        if ($download) {
            $this->audit->log(SuratJalan::DOCUMENT_TYPE, $suratJalan->id, DocumentAuditLogger::DOCUMENT_DOWNLOADED,
                $user->name . ' mengunduh PDF Surat Jalan', ['status' => $suratJalan->status], $user, false);
        }

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline') . '; filename="' . $this->fileName($suratJalan, 'SuratJalan') . '"',
            'Cache-Control'       => 'no-store, private',
        ]);
    }

    public function auditHistory(SuratJalan $suratJalan)
    {
        $user = Auth::user();
        abort_unless($this->service->canView($suratJalan->load('signers'), $user), 403);

        // Dicatat dulu agar ekspor ini sendiri ikut tercantum dalam laporan.
        $this->audit->log(SuratJalan::DOCUMENT_TYPE, $suratJalan->id, DocumentAuditLogger::AUDIT_EXPORTED,
            $user->name . ' mencetak Audit History', [], $user, false);

        $content = $this->pdf->auditHistory($suratJalan);

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $this->fileName($suratJalan, 'AuditHistory') . '"',
            'Cache-Control'       => 'no-store, private',
        ]);
    }

    /** Gambar snapshot stamp untuk preview HTML (dilindungi hak akses dokumen). */
    public function stamp(SuratJalan $suratJalan, int $step)
    {
        abort_unless($this->service->canView($suratJalan->load('signers'), Auth::user()), 403);
        $signer = $suratJalan->signerForStep($step);
        $path = $signer ? $this->service->stampAbsolutePath($signer) : null;
        abort_unless($path, 404);

        return response()->file($path, ['Cache-Control' => 'private, max-age=86400']);
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Input dari pop-up tanda tangan.
     * sign_mode=pad → wajib ada gambar signature pad; sign_mode=profile → stamp profil (QR bila tidak ada).
     *
     * @return array{0:?string,1:bool} [padDataUrl, saveToProfile]
     */
    private function signatureInput(Request $request): array
    {
        $data = $request->validate([
            'sign_mode'       => ['required', Rule::in(['pad', 'profile'])],
            'signature_pad'   => ['nullable', 'required_if:sign_mode,pad', 'string', 'max:1400000'],
            'save_to_profile' => ['nullable', 'boolean'],
            'confirm'         => ['accepted'],
        ], [
            'signature_pad.required_if' => 'Silakan gambar tanda tangan Anda terlebih dahulu.',
            'confirm.accepted'          => 'Centang konfirmasi bahwa Anda telah memeriksa isi Surat Jalan.',
        ]);

        $isPad = $data['sign_mode'] === 'pad';

        return [$isPad ? $data['signature_pad'] : null, $isPad && $request->boolean('save_to_profile')];
    }

    private function validated(Request $request): array
    {
        $kategoriCodes = array_keys(config('surat_jalan.kategori'));

        $data = $request->validate([
            'departement_id'        => ['required', 'integer', 'exists:m_departement,id'],
            'kepada_tipe'           => ['nullable', Rule::in(array_keys(config('surat_jalan.kepada_tipe')))],
            'kepada'                => ['required', 'string', 'max:150'],
            'no_kendaraan'          => ['nullable', 'string', 'max:30'],
            'kategori'              => ['required', 'array', 'min:1'],
            'kategori.*'            => ['required', Rule::in($kategoriCodes)],
            'kategori_lain'         => ['nullable', 'string', 'max:150'],
            'items'                 => ['required', 'array', 'min:1', 'max:200'],
            'items.*.jenis_barang'  => ['required', 'string', 'max:200'],
            'items.*.qty'           => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'items.*.satuan'        => ['nullable', 'string', 'max:20'],
            'items.*.keterangan'    => ['nullable', 'string', 'max:255'],
            // Pemeriksa (kotak 3) tidak dipilih: otomatis seluruh member HR-GA.
            'signer_2'              => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', 1)],
            'signer_4'              => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', 1)],
        ], [
            'kategori.required'          => 'Pilih minimal satu kategori / tujuan pengeluaran barang.',
            'items.required'             => 'Minimal satu baris barang harus diisi.',
            'items.*.jenis_barang.required' => 'Jenis barang pada setiap baris wajib diisi.',
            'items.*.qty.required'       => 'Qty pada setiap baris wajib diisi.',
            'items.*.qty.gt'             => 'Qty harus lebih dari 0.',
            'signer_2.required'          => 'Signer Mengetahui (Dept. Head) wajib dipilih.',
            'signer_4.required'          => 'Signer Diterima (Penerima/Security) wajib dipilih.',
        ]);

        if (in_array('LAIN_LAIN', $data['kategori'], true) && blank($data['kategori_lain'] ?? null)) {
            throw ValidationException::withMessages([
                'kategori_lain' => 'Keterangan "Lain-lain" wajib diisi bila kategori Lain-lain dipilih.',
            ]);
        }

        $data['items'] = array_values($data['items']);

        return $data;
    }

    private function formData(?SuratJalan $sj): array
    {
        $user = Auth::user();
        $profile = $this->service->profile($user);

        $users = User::query()
            ->where('users.is_active', 1)
            ->with(['employee.positions:id,name', 'employee.departements:id,code,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'user_name', 'employee_id']);

        $userOptions = $users->map(fn (User $u) => [
            'id'        => $u->id,
            'name'      => $u->employee?->name ?: $u->name,
            'nik'       => $u->user_name,
            'position'  => $u->employee?->positions?->name,
            'dept_id'   => $u->employee?->departement,
            'dept_code' => $u->employee?->departements?->code,
            'dept'      => $u->employee?->departements?->name,
        ])->values();

        // Pemeriksa = seluruh member HR-GA (ditampilkan sebagai info, tidak dipilih).
        $pemeriksaDept = $this->service->pemeriksaDepartement();
        $pemeriksaMembers = $pemeriksaDept
            ? $this->service->groupMembers($pemeriksaDept->id)->map(fn (User $u) => $this->service->profile($u)['name'])->values()
            : collect();

        $jenisBarangSuggestions = SuratJalanItem::query()
            ->select('jenis_barang')
            ->groupBy('jenis_barang')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(300)
            ->pluck('jenis_barang');

        $kepadaSuggestions = SuratJalan::query()
            ->select('kepada')
            ->groupBy('kepada')
            ->orderByRaw('MAX(created_at) DESC')
            ->limit(200)
            ->pluck('kepada');

        return [
            'sj'                     => $sj,
            'departements'           => Departement::where('status', 1)->orderBy('name')->get(['id', 'code', 'name']),
            'defaultDepartementId'   => $sj?->departement_id ?? $user->employee?->departement,
            'creatorProfile'         => $profile,
            'creatorHasStamp'        => $this->service->hasStampImage($user),
            'userOptions'            => $userOptions,
            'pemeriksaDept'          => $pemeriksaDept,
            'pemeriksaMembers'       => $pemeriksaMembers,
            'jenisBarangSuggestions' => $jenisBarangSuggestions,
            'kepadaSuggestions'      => $kepadaSuggestions,
        ];
    }

    private function qrSvgs(SuratJalan $sj): array
    {
        $out = [];
        foreach ($sj->signers as $s) {
            if ($s->isSigned() && !$this->service->stampAbsolutePath($s)) {
                $out[$s->step] = (string) QrCode::format('svg')->size(110)->margin(0)->errorCorrection('M')->generate($s->qrPayload($sj));
            }
        }
        return $out;
    }

    private function pendingCount(User $user): int
    {
        // Termasuk Surat Jalan yang menunggu Pemeriksa bila user member HR-GA.
        return SuratJalan::query()->awaitingActionBy($user)->count();
    }

    private function fileName(SuratJalan $sj, string $prefix): string
    {
        $no = $sj->sj_no ? Str::of($sj->sj_no)->replace('/', '-') : 'DRAFT-' . Str::limit($sj->uuid, 8, '');
        return "{$prefix}_{$no}.pdf";
    }
}
