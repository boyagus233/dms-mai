<?php

namespace App\Services;

use App\Models\DocumentAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pencatat event lifecycle dokumen ke tabel document_audit_logs.
 * Generik untuk semua modul e-sign (Surat Jalan, Ad-Hoc, Kalibrasi, ...).
 */
class DocumentAuditLogger
{
    // Daftar action standar (dipakai juga untuk label di Audit History)
    public const DOCUMENT_CREATED = 'DOCUMENT_CREATED';
    public const DOCUMENT_UPDATED = 'DOCUMENT_UPDATED';
    public const SUBMITTED_SIGNED = 'SUBMITTED_SIGNED';
    public const NOTIFICATION_SENT = 'NOTIFICATION_SENT';
    public const NOTIFICATION_FAILED = 'NOTIFICATION_FAILED';
    public const NOTIFICATION_SKIPPED = 'NOTIFICATION_SKIPPED';
    public const DOCUMENT_VIEWED = 'DOCUMENT_VIEWED';
    public const APPROVED_SIGNED = 'APPROVED_SIGNED';
    public const REJECTED = 'REJECTED';
    public const COMPLETED = 'COMPLETED';
    public const DOCUMENT_DOWNLOADED = 'DOCUMENT_DOWNLOADED';
    public const AUDIT_EXPORTED = 'AUDIT_EXPORTED';
    public const DOCUMENT_DELETED = 'DOCUMENT_DELETED';

    public static function labels(): array
    {
        return [
            self::DOCUMENT_CREATED     => 'Document Created',
            self::DOCUMENT_UPDATED     => 'Draft Updated',
            self::SUBMITTED_SIGNED     => 'Submitted & Signed',
            self::NOTIFICATION_SENT    => 'Notification Sent',
            self::NOTIFICATION_FAILED  => 'Notification Failed',
            self::NOTIFICATION_SKIPPED => 'Notification Skipped',
            self::DOCUMENT_VIEWED      => 'Document Viewed',
            self::APPROVED_SIGNED      => 'Approved & Signed',
            self::REJECTED             => 'Rejected',
            self::COMPLETED            => 'Completed',
            self::DOCUMENT_DOWNLOADED  => 'Document Downloaded',
            self::AUDIT_EXPORTED       => 'Audit History Exported',
            self::DOCUMENT_DELETED     => 'Draft Deleted',
        ];
    }

    public static function label(string $action): string
    {
        return self::labels()[$action] ?? Str::headline(strtolower($action));
    }

    /**
     * Catat satu event. Kegagalan pencatatan tidak boleh menggagalkan proses utama
     * kecuali $strict = true (dipakai di dalam transaksi sign agar konsisten).
     */
    public function log(
        string $documentType,
        int $documentId,
        string $action,
        ?string $description = null,
        array $metadata = [],
        ?User $actor = null,
        bool $strict = true
    ): ?DocumentAuditLog {
        $actor = $actor ?? Auth::user();
        // Artisan/queue tidak punya IP → tercatat sebagai 'system'.
        $request = app()->bound('request') ? request() : null;
        if ($request && !$request->ip() && !$request->userAgent()) {
            $request = null;
        }

        try {
            $employee = $actor?->employee;

            return DocumentAuditLog::create([
                'document_type'  => $documentType,
                'document_id'    => $documentId,
                'action'         => $action,
                'description'    => $description ? Str::limit($description, 495) : null,
                'user_id'        => $actor?->id,
                'actor_name'     => $actor?->name,
                'actor_email'    => $actor?->email ?: $employee?->email,
                'actor_position' => $employee?->positions?->name,
                'ip_address'     => $request?->ip(),
                'user_agent'     => $request ? Str::limit((string) $request->userAgent(), 495) : 'system',
                'metadata'       => $metadata ?: null,
            ]);
        } catch (Throwable $e) {
            if ($strict) {
                throw $e;
            }
            Log::error('Gagal mencatat audit log', [
                'document_type' => $documentType,
                'document_id'   => $documentId,
                'action'        => $action,
                'error'         => $e->getMessage(),
            ]);
            return null;
        }
    }
}
