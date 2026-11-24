<?php

namespace App\Services;

use App\Models\Kalibrasi;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Tcpdf\Fpdi;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class KalibrasiPdfService
{
    private const STORAGE_DIR = 'kalibrasi';

    /**
     * Generate unique certificate number: CAL/INT/YYYY/MM/0001 or CAL/EXT/...
     */
    public function generateCertificateNumber(string $type = 'INTERNAL'): string
    {
        $prefix = $type === 'EKSTERNAL' ? 'CAL/EXT' : 'CAL/INT';
        $year = date('Y');
        $month = date('m');

        $latest = Kalibrasi::where('certificate_number', 'LIKE', "{$prefix}/{$year}/{$month}/%")
            ->orderBy('id', 'desc')
            ->first();

        $seq = 1;
        if ($latest) {
            $parts = explode('/', $latest->certificate_number);
            $lastSeq = end($parts);
            if (is_numeric($lastSeq)) {
                $seq = (int) $lastSeq + 1;
            }
        }

        return sprintf('%s/%s/%s/%04d', $prefix, $year, $month, $seq);
    }

    /**
     * Find auto-routing approver for a technician
     */
    public function resolveApprover(User $technician): ?User
    {
        $deptId = $technician->employee?->departement;

        // 1. Try to find user in the same department with position Manager / Head / Spv
        if ($deptId) {
            $approver = User::whereHas('employee', function ($q) use ($deptId) {
                $q->where('departement', $deptId)
                  ->whereHas('positions', function ($pq) {
                      $pq->where('name', 'LIKE', '%Manager%')
                        ->orWhere('name', 'LIKE', '%Head%')
                        ->orWhere('name', 'LIKE', '%Spv%')
                        ->orWhere('name', 'LIKE', '%Supervisor%');
                  });
            })->where('id', '!=', $technician->id)->where('is_active', 1)->first();

            if ($approver) {
                return $approver;
            }
        }

        // 2. Try to find user with role 'MGR' or 'ADM'
        $approver = User::whereHas('roles', function ($q) {
            $q->whereIn('code', ['MGR', 'QA_MGR', 'ADM']);
        })->where('id', '!=', $technician->id)->where('is_active', 1)->first();

        if ($approver) {
            return $approver;
        }

        // 3. Fallback to user ID 1 (Admin) or first active user
        return User::where('is_active', 1)->first() ?: User::first();
    }

    /**
     * Generate 2-Page (or N-Page) Merged PDF:
     * Page 1: Calibration Certificate Template (with or without stamp)
     * Page 2..N: Uploaded manual calculation/worksheet PDF
     *
     * @param Kalibrasi $kalibrasi
     * @param bool $isFinal If true, includes approver's stamp and saves to final_merged_pdf
     * @return string Path to generated PDF file (relative to public storage)
     */
    public function generateMergedPdf(Kalibrasi $kalibrasi, bool $isFinal = false): string
    {
        $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);

        // Document metadata
        $pdf->SetCreator('SYS-DOCS QMS Calibration');
        $pdf->SetAuthor('Quality Assurance Dept');
        $pdf->SetTitle('Sertifikat Kalibrasi - ' . $kalibrasi->certificate_number);
        $pdf->SetSubject('Calibration Certificate');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(false);

        // -------------------------------------------------------------
        // Halaman 1: Template Sertifikat Kalibrasi
        // -------------------------------------------------------------
        $pdf->AddPage();

        // Signature image paths
        $technicianSignPath = $this->getUserSignaturePath($kalibrasi->technician);
        $approverSignPath = $isFinal && $kalibrasi->approver ? $this->getUserSignaturePath($kalibrasi->approver) : null;

        // Render blade view to HTML
        $html = view('content.kalibrasi.pdf.certificate_template', [
            'kalibrasi'          => $kalibrasi,
            'technicianSignPath' => $technicianSignPath,
            'approverSignPath'   => $approverSignPath,
            'isFinal'            => $isFinal,
        ])->render();

        $pdf->writeHTML($html, true, false, true, false, '');

        // -------------------------------------------------------------
        // Halaman 2 dst.: Import attachment PDF (if exists)
        // -------------------------------------------------------------
        if ($kalibrasi->raw_attachment_pdf) {
            $attachmentFullPath = Storage::disk('public')->path($kalibrasi->raw_attachment_pdf);

            if (File::exists($attachmentFullPath)) {
                try {
                    $pageCount = $pdf->setSourceFile($attachmentFullPath);
                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $pdf->importPage($pageNo);
                        $size = $pdf->getTemplateSize($templateId);

                        $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                        $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                        $pdf->useTemplate($templateId);

                        // Header banner on attachment pages indicating reference certificate
                        $pdf->SetFont('helvetica', 'I', 8);
                        $pdf->SetTextColor(120, 120, 120);
                        $pdf->Text(12, 6, "Lampiran Hasil Kalibrasi | No. Sertifikat: {$kalibrasi->certificate_number} | Hal " . ($pageNo + 1));
                    }
                } catch (\Throwable $e) {
                    // If attachment fails to import (e.g. encrypted), proceed gracefully
                }
            }
        }

        // Save PDF to storage
        $subDir = $isFinal ? 'final' : 'previews';
        $dirPath = storage_path("app/public/" . self::STORAGE_DIR . "/{$subDir}");
        if (!File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true, true);
        }

        $safeCertNo = str_replace(['/', '\\'], '_', $kalibrasi->certificate_number);
        $fileName = "{$safeCertNo}_" . ($isFinal ? 'FINAL' : 'DRAFT') . ".pdf";
        $savePath = "{$dirPath}/{$fileName}";

        $pdf->Output($savePath, 'F');

        $relativeStoragePath = self::STORAGE_DIR . "/{$subDir}/{$fileName}";

        return $relativeStoragePath;
    }

    /**
     * Get absolute path of a user's signature file
     */
    private function getUserSignaturePath(?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        $sigFile = $user->signature ?: $user->employee?->signature;
        if ($sigFile && Storage::disk('public')->exists('signature/' . $sigFile)) {
            return Storage::disk('public')->path('signature/' . $sigFile);
        }

        return null;
    }
}
