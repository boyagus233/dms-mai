<?php

namespace App\Services;

use App\Models\AdhocDocument;
use App\Models\DocumentSigner;
use App\Models\DocumentSignatureField;
use App\Models\DocumentAuditLog;
use App\Models\User;
use App\Mail\AdhocSignatureRequestMail;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Tcpdf\Fpdi;
use Smalot\PdfParser\Parser;
use TCPDF;
use Exception;

class AdhocSignerService
{
    /**
     * Validate and securely store newly uploaded PDF document
     */
    public function uploadDocument(UploadedFile $file, array $data, User $user, Request $request): AdhocDocument
    {
        // 1. Strict Security Validation
        if (!$file->isValid()) {
            throw new Exception('File upload error: ' . $file->getErrorMessage());
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension !== 'pdf') {
            throw new Exception('Invalid file extension. Only genuine PDF files are permitted.');
        }

        $realPath = $file->getRealPath();

        // 2. Magic bytes verification (Must start with %PDF-)
        $handle = fopen($realPath, 'rb');
        $header = fread($handle, 5);
        fclose($handle);
        if ($header !== '%PDF-') {
            throw new Exception('Security Error: Uploaded file is not a valid PDF binary.');
        }

        // 3. Deep parser verification & page counting
        try {
            $parser = new Parser();
            $parsedPdf = $parser->parseFile($realPath);
            $pages = $parsedPdf->getPages();
            $pageCount = count($pages);
            if ($pageCount < 1) {
                throw new Exception('PDF file has no readable pages.');
            }
        } catch (Exception $e) {
            throw new Exception('Failed to parse PDF file. The file may be corrupt or encrypted: ' . $e->getMessage());
        }

        // 4. Calculate SHA-256 file hash for integrity audit
        $fileHash = hash_file('sha256', $realPath);
        $fileSize = $file->getSize();

        // 5. Store file with sanitized, cryptographically unguessable filename
        $safeStorageDir = 'adhoc_documents';
        $storedFileName = time() . '_' . Str::random(24) . '.pdf';
        $storedFilePath = $file->storeAs($safeStorageDir, $storedFileName, 'public');

        // 6. Create Database Record
        DB::beginTransaction();
        try {
            $document = AdhocDocument::create([
                'title' => strip_tags($data['title']),
                'description' => isset($data['description']) ? strip_tags($data['description']) : null,
                'original_file_name' => basename($file->getClientOriginalName()),
                'original_file_path' => $storedFilePath,
                'original_file_hash' => $fileHash,
                'file_size' => $fileSize,
                'page_count' => $pageCount,
                'workflow_type' => in_array($data['workflow_type'] ?? '', ['SEQUENTIAL', 'PARALLEL']) ? $data['workflow_type'] : 'SEQUENTIAL',
                'status' => 'DRAFT',
                'created_by' => $user->id,
            ]);

            // Audit log initial upload
            $this->logAudit(
                $document->id,
                $user->id,
                $user->name,
                $user->email,
                'UPLOADED',
                "Document uploaded: '{$document->title}' (Pages: {$pageCount}, SHA-256: {$fileHash})",
                $request
            );

            DB::commit();
            return $document;
        } catch (Exception $e) {
            DB::rollBack();
            if (Storage::disk('public')->exists($storedFilePath)) {
                Storage::disk('public')->delete($storedFilePath);
            }
            throw $e;
        }
    }

    /**
     * Save signers and signature visual tagging fields, then transition document to IN_PROGRESS
     */
    public function setupSignersAndFields(AdhocDocument $document, array $signersData, array $fieldsData, Request $request): void
    {
        DB::beginTransaction();
        try {
            // Delete existing draft signers/fields if re-submitting
            $document->fields()->delete();
            $document->signers()->delete();

            $signerModelMap = []; // temporary index map (e.g., 'signer_0' => DocumentSigner)

            foreach ($signersData as $index => $sData) {
                $orderIndex = ($document->workflow_type === 'SEQUENTIAL') ? ($index + 1) : 1;
                $initialStatus = ($document->workflow_type === 'PARALLEL' || $orderIndex === 1) ? 'PENDING' : 'WAITING';

                $signer = DocumentSigner::create([
                    'document_id' => $document->id,
                    'user_id' => (int) $sData['user_id'],
                    'order_index' => $orderIndex,
                    'role_name' => isset($sData['role_name']) ? strip_tags($sData['role_name']) : 'Signer ' . ($index + 1),
                    'status' => $initialStatus,
                ]);

                $signerKey = $sData['key'] ?? ('signer_' . $index);
                $signerModelMap[$signerKey] = $signer;
                $signerModelMap[$sData['user_id']] = $signer;
            }

            // Save visual tagging coordinates
            foreach ($fieldsData as $fData) {
                $signerRef = $fData['signer_key'] ?? ($fData['user_id'] ?? null);
                if (!isset($signerModelMap[$signerRef])) {
                    continue;
                }
                $assignedSigner = $signerModelMap[$signerRef];

                DocumentSignatureField::create([
                    'document_id' => $document->id,
                    'document_signer_id' => $assignedSigner->id,
                    'field_type' => in_array($fData['field_type'], ['SIGNATURE', 'INITIAL_PARAF', 'SIGN_DATE', 'NAME_TITLE']) ? $fData['field_type'] : 'SIGNATURE',
                    'page_number' => (int) ($fData['page_number'] ?? 1),
                    'x_percent' => round((float) $fData['x_percent'], 4),
                    'y_percent' => round((float) $fData['y_percent'], 4),
                    'width_percent' => round((float) $fData['width_percent'], 4),
                    'height_percent' => round((float) $fData['height_percent'], 4),
                ]);
            }

            $document->status = 'IN_PROGRESS';
            $document->save();

            // Log setup
            $this->logAudit(
                $document->id,
                Auth::id(),
                Auth::user()->name ?? 'System',
                Auth::user()->email ?? '',
                'INVITATION_SENT',
                "Workflow initiated in {$document->workflow_type} mode with " . count($signersData) . " signer(s)",
                $request
            );

            DB::commit();

            // Dispatch notification emails to initial pending signers
            $this->sendNotificationsToPendingSigners($document);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Send email notification to all signers currently in PENDING status
     */
    public function sendNotificationsToPendingSigners(AdhocDocument $document): void
    {
        $pendingSigners = $document->signers()->where('status', 'PENDING')->with('user')->get();

        foreach ($pendingSigners as $signer) {
            if ($signer->user && !empty($signer->user->email)) {
                try {
                    Mail::to($signer->user->email)->send(new AdhocSignatureRequestMail($document, $signer));
                    Log::info("Signature request email sent to: {$signer->user->email} for document #{$document->uuid}");
                } catch (Exception $e) {
                    Log::error("Failed to send signature email to {$signer->user->email}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Process Sign & Approval by designated signer
     */
    public function processSign(AdhocDocument $document, DocumentSigner $signer, ?string $signatureDataUrl, Request $request): void
    {
        if ($signer->status !== 'PENDING') {
            throw new Exception('Signer is not currently eligible to sign this document.');
        }

        if ($document->status !== 'IN_PROGRESS') {
            throw new Exception('Document is not in progress.');
        }

        $user = $signer->user;

        // 1. Handle Signature Graphic
        $signatureFileName = null;

        if (!empty($signatureDataUrl) && str_starts_with($signatureDataUrl, 'data:image')) {
            // Process Live Signature Pad upload
            $signatureFileName = $this->saveSignatureDataUrl($signatureDataUrl, $user);
        } else {
            // Load existing signature from employee or user
            if ($user->employee && !empty($user->employee->signature)) {
                $signatureFileName = $user->employee->signature;
            } elseif (!empty($user->signature)) {
                $signatureFileName = $user->signature;
            }
        }

        if (empty($signatureFileName) || !Storage::disk('public')->exists('signature/' . $signatureFileName)) {
            throw new Exception('Gambar tanda tangan tidak ditemukan. Harap buat tanda tangan terlebih dahulu pada Signature Pad.');
        }

        $signatureFullPath = storage_path('app/public/signature/' . $signatureFileName);

        // 2. Apply Stamping onto PDF via FPDI & TCPDF
        $sourceRelativePath = $document->signed_file_path ?: $document->original_file_path;
        $sourceFullPath = storage_path('app/public/' . $sourceRelativePath);

        if (!file_exists($sourceFullPath)) {
            throw new Exception('Source PDF file not found at: ' . $sourceFullPath);
        }

        $newSignedFileName = 'signed_' . $document->uuid . '_' . time() . '.pdf';
        $newSignedRelativePath = 'adhoc_documents/' . $newSignedFileName;
        $newSignedFullPath = storage_path('app/public/' . $newSignedRelativePath);

        // Stamp fields for this signer
        $this->stampSignerFieldsOnPdf($sourceFullPath, $newSignedFullPath, $document, $signer, $signatureFullPath);

        // Verify newly created file
        if (!file_exists($newSignedFullPath) || filesize($newSignedFullPath) === 0) {
            throw new Exception('Failed to generate signed PDF.');
        }

        $newSignedHash = hash_file('sha256', $newSignedFullPath);

        DB::beginTransaction();
        try {
            // Delete previous intermediate signed file if exists to prevent disk clutter
            if ($document->signed_file_path && Storage::disk('public')->exists($document->signed_file_path)) {
                Storage::disk('public')->delete($document->signed_file_path);
            }

            // Update document
            $document->signed_file_path = $newSignedRelativePath;
            $document->signed_file_hash = $newSignedHash;

            // Update signer
            $signer->status = 'SIGNED';
            $signer->signed_at = now();
            $signer->signature_path = $signatureFileName;
            $signer->ip_address = $request->ip();
            $signer->user_agent = substr($request->userAgent() ?? '', 0, 500);
            $signer->save();

            // Log Audit Event
            $this->logAudit(
                $document->id,
                $user->id,
                $user->name,
                $user->email,
                'SIGNED',
                "Document signed and verified by {$user->name} ({$signer->role_name}). Stamped PDF Hash: {$newSignedHash}",
                $request
            );

            // 3. Workflow Progression
            if ($document->workflow_type === 'SEQUENTIAL') {
                $nextSigner = $document->signers()
                    ->where('order_index', '>', $signer->order_index)
                    ->orderBy('order_index', 'asc')
                    ->first();

                if ($nextSigner) {
                    $nextSigner->status = 'PENDING';
                    $nextSigner->save();
                    $this->logAudit(
                        $document->id,
                        $nextSigner->user_id,
                        $nextSigner->user->name ?? 'Next Signer',
                        $nextSigner->user->email ?? '',
                        'INVITATION_SENT',
                        "Turn passed to next sequential signer: {$nextSigner->user->name} ({$nextSigner->role_name})",
                        $request
                    );
                } else {
                    // All sequential signers completed!
                    $document->status = 'COMPLETED';
                    $this->logAudit(
                        $document->id,
                        null,
                        'System',
                        'system@internal',
                        'COMPLETED',
                        "All signatures collected. Final Document Completed (SHA-256: {$newSignedHash})",
                        $request
                    );
                }
            } else {
                // PARALLEL
                $remainingCount = $document->signers()->where('status', '!=', 'SIGNED')->count();
                if ($remainingCount === 0) {
                    $document->status = 'COMPLETED';
                    $this->logAudit(
                        $document->id,
                        null,
                        'System',
                        'system@internal',
                        'COMPLETED',
                        "All parallel signatures collected. Final Document Completed (SHA-256: {$newSignedHash})",
                        $request
                    );
                }
            }

            $document->save();
            DB::commit();

            // If next signer was activated in sequential mode, trigger email
            if ($document->workflow_type === 'SEQUENTIAL' && isset($nextSigner) && $nextSigner) {
                $this->sendNotificationsToPendingSigners($document);
            }
        } catch (Exception $e) {
            DB::rollBack();
            if (file_exists($newSignedFullPath)) {
                unlink($newSignedFullPath);
            }
            throw $e;
        }
    }

    /**
     * Process Rejection by signer
     */
    public function processReject(AdhocDocument $document, DocumentSigner $signer, string $reason, Request $request): void
    {
        if ($signer->status !== 'PENDING') {
            throw new Exception('Signer is not eligible to take action on this document.');
        }

        DB::beginTransaction();
        try {
            $user = $signer->user;

            $signer->status = 'REJECTED';
            $signer->rejected_at = now();
            $signer->reject_reason = strip_tags($reason);
            $signer->ip_address = $request->ip();
            $signer->user_agent = substr($request->userAgent() ?? '', 0, 500);
            $signer->save();

            $document->status = 'REJECTED';
            $document->save();

            $this->logAudit(
                $document->id,
                $user->id,
                $user->name,
                $user->email,
                'REJECTED',
                "Document rejected by {$user->name} ({$signer->role_name}). Reason: {$reason}",
                $request
            );

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Stamp visual tags (signature, paraf, date, name) onto the PDF pages using FPDI & TCPDF
     */
    protected function stampSignerFieldsOnPdf(string $sourcePdfPath, string $outputPdfPath, AdhocDocument $document, DocumentSigner $signer, string $signatureImgPath): void
    {
        $fields = DocumentSignatureField::where('document_id', $document->id)
            ->where('document_signer_id', $signer->id)
            ->get()
            ->groupBy('page_number');

        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pageCount = $pdf->setSourceFile($sourcePdfPath);

        $emp = $signer->user->employee;
        $signerName = $signer->user->name;
        $signerTitle = ($emp && $emp->positions) ? $emp->positions->name : ($signer->role_name ?: 'Signer');
        $signDateFormatted = now()->format('d/m/Y H:i:s');

        for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
            $templateId = $pdf->importPage($pageNum);
            $size = $pdf->getTemplateSize($templateId);

            $pageWidth = $size['width'];
            $pageHeight = $size['height'];
            $orientation = ($pageWidth > $pageHeight) ? 'L' : 'P';

            $pdf->AddPage($orientation, [$pageWidth, $pageHeight]);
            $pdf->useTemplate($templateId, 0, 0, $pageWidth, $pageHeight);

            if (isset($fields[$pageNum])) {
                foreach ($fields[$pageNum] as $field) {
                    // Convert relative percentage coordinates into actual PDF millimeters
                    $x = ($field->x_percent / 100) * $pageWidth;
                    $y = ($field->y_percent / 100) * $pageHeight;
                    $w = ($field->width_percent / 100) * $pageWidth;
                    $h = ($field->height_percent / 100) * $pageHeight;

                    switch ($field->field_type) {
                        case 'SIGNATURE':
                        case 'INITIAL_PARAF':
                            if (file_exists($signatureImgPath)) {
                                $pdf->Image(
                                    $signatureImgPath,
                                    $x,
                                    $y,
                                    $w,
                                    $h,
                                    '',
                                    '',
                                    '',
                                    false,
                                    300,
                                    '',
                                    false,
                                    false,
                                    0,
                                    'CM'
                                );
                            }
                            break;

                        case 'SIGN_DATE':
                            $pdf->SetFont('helvetica', '', 8);
                            $pdf->SetTextColor(40, 40, 40);
                            $pdf->SetXY($x, $y);
                            $pdf->Cell($w, $h, $signDateFormatted, 0, 0, 'L');
                            break;

                        case 'NAME_TITLE':
                            $pdf->SetFont('helvetica', 'B', 8);
                            $pdf->SetTextColor(30, 30, 30);
                            $pdf->SetXY($x, $y);
                            $displayString = $signerName . ' (' . $signerTitle . ')';
                            $pdf->Cell($w, $h, $displayString, 0, 0, 'L');
                            break;
                    }
                }
            }
        }

        $pdf->Output($outputPdfPath, 'F');
    }

    /**
     * Decode and safely store a base64 DataURL signature image, updating user and employee profiles
     */
    protected function saveSignatureDataUrl(string $dataUrl, User $user): string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $type)) {
            $data = substr($dataUrl, strpos($dataUrl, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                throw new Exception('Invalid signature image type. Only PNG/JPG are accepted.');
            }

            $decoded = base64_decode($data);
            if ($decoded === false) {
                throw new Exception('Failed to decode base64 signature.');
            }

            $sigDir = storage_path('app/public/signature');
            if (!is_dir($sigDir)) {
                mkdir($sigDir, 0755, true);
            }

            $filename = time() . '_' . Str::random(12) . '.' . $type;
            file_put_contents($sigDir . '/' . $filename, $decoded);

            // Also mirror to public/storage/signature if it exists as a directory (Windows symlink fallback)
            $publicSigDir = public_path('storage/signature');
            if (is_dir($publicSigDir)) {
                @file_put_contents($publicSigDir . '/' . $filename, $decoded);
            }

            // Update user & employee records
            $user->signature = $filename;
            $user->save();

            if ($user->employee) {
                $user->employee->signature = $filename;
                $user->employee->save();
            }

            return $filename;
        }

        throw new Exception('Invalid signature DataURL format.');
    }

    /**
     * Record chronological event to document_audit_logs
     */
    public function logAudit(int $documentId, ?int $userId, string $actorName, ?string $actorEmail, string $action, ?string $description, ?Request $request = null): void
    {
        $ip = $request ? $request->ip() : request()->ip();
        $ua = $request ? substr($request->userAgent() ?? '', 0, 500) : substr(request()->userAgent() ?? '', 0, 500);

        DocumentAuditLog::create([
            'document_type' => AdhocDocument::DOCUMENT_TYPE,
            'document_id' => $documentId,
            'user_id' => $userId,
            'actor_name' => $actorName,
            'actor_email' => $actorEmail,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'created_at' => now(),
        ]);
    }

    /**
     * Generate standalone Certificate of Completion (Audit History PDF)
     */
    public function generateAuditCertificatePdf(AdhocDocument $document): string
    {
        $document->load(['creator', 'signers.user.employee.positions', 'signers.user.employee.departements', 'auditLogs']);

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('Internal DMS E-Sign System');
        $pdf->SetAuthor('SYS-DOCS');
        $pdf->SetTitle('Certificate of Completion - ' . $document->title);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $statusColor = match ($document->status) {
            'COMPLETED' => '#28c76f',
            'REJECTED' => '#ea5455',
            'IN_PROGRESS' => '#ff9f43',
            default => '#82868b'
        };

        // Render HTML Content
        $html = view('content.adhoc-document.certificate', [
            'document' => $document,
            'statusColor' => $statusColor,
        ])->render();

        $pdf->writeHTML($html, true, false, true, false, '');

        $pdfContent = $pdf->Output('certificate_' . $document->uuid . '.pdf', 'S');
        return $pdfContent;
    }
}
