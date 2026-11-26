<?php

namespace App\Http\Controllers;

use App\Exports\AuditTrailListExport;
use App\Exports\DocumentLogExport;
use App\Models\AdhocDocument;
use App\Models\AuditTrail;
use App\Models\DocumentAuditLog;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DocumentLogController extends Controller
{
    /**
     * Authorize access to an All Document Approval record:
     * only Admin (ADM), creator, or an assigned signer.
     */
    protected function authorizeAdhocAccess(AdhocDocument $document): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if ($user->hasRole('ADM') || (int) $document->created_by === (int) $user->id) {
            return;
        }

        $isSigner = $document->relationLoaded('signers')
            ? $document->signers->contains('user_id', $user->id)
            : $document->signers()->where('user_id', $user->id)->exists();

        if (!$isSigner) {
            abort(403, 'Anda tidak memiliki akses ke audit trail dokumen ini.');
        }
    }

    public function index(Request $request)
    {
        // Jika ada parameter export, jalankan fungsi export Doc Control
        if ($request->has('export')) {
            return Excel::download(new AuditTrailListExport(), 'AuditTrailList.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        $tab = $request->input('tab', 'doc_control');
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole('ADM');

        // 1. Doc Control Audit Trail (existing)
        DB::statement("SET @rownum := 0;");
        DB::statement("SET @prev_trans := '';");
        $sql = "SELECT
                    ranked.id, document_no, document_name, mdt.document_type, departement_name, rev_no, reason_revisi, creator_name, checker_name, approver_name,
                    document_status, file_name, file_attachment, is_stamp_by_creator, is_checked_by_dc, is_checked, is_approved,
                    is_obsolete, obsolete_date, ranked.created_at
                FROM (
                    SELECT
                        v.*,
                        @rownum := IF(@prev_trans = v.document_no COLLATE utf8mb4_unicode_ci, @rownum + 1, 1) AS rn,
                        @prev_trans := v.document_no COLLATE utf8mb4_unicode_ci
                    FROM vw_document_upload v
                    WHERE 1=1
                    ORDER BY v.document_no, v.rev_no DESC
                ) ranked
                join m_document_type mdt on ranked.document_type = mdt.code
                WHERE 1=1";
        $bindings = [];

        $sql .= " ORDER BY created_at DESC";
        $documentUploads = DB::select($sql, $bindings);

        // 2. All Document Approval (Ad-Hoc E-Sign) Audit Trail (isolated per account unless ADM)
        $adhocDocuments = AdhocDocument::with(['creator', 'signers.user'])
            ->withCount('auditLogs')
            ->when(!$isAdmin, function ($query) use ($user) {
                if (!$user) {
                    $query->whereRaw('1 = 0');
                    return;
                }
                $query->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                        ->orWhereHas('signers', function ($sq) use ($user) {
                            $sq->where('user_id', $user->id);
                        });
                });
            })
            ->latest()
            ->get();

        return view('content.document-log.index', [
            'documentUploads' => $documentUploads,
            'adhocDocuments' => $adhocDocuments,
            'tab' => $tab,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function getLogs($id)
    {
        try {
            if (!$id || !is_numeric($id)) {
                return response()->json(['error' => 'Invalid transaction ID'], 400);
            }

            $logs = DB::table('tr_audit_trails')
                ->join('users', 'tr_audit_trails.user_id', '=', 'users.id')
                ->where('tr_audit_trails.document_id', $id)
                ->select('tr_audit_trails.*', 'users.user_name', 'users.name')
                ->orderBy('tr_audit_trails.created_at', 'desc')
                ->get();

            return response()->json($logs);
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get chronological audit trail logs for an All Document Approval document.
     */
    public function getAdhocLogs($id)
    {
        if (!$id || !is_numeric($id)) {
            return response()->json(['error' => 'Invalid document ID'], 400);
        }

        $document = AdhocDocument::with(['signers', 'auditLogs'])->findOrFail((int) $id);
        $this->authorizeAdhocAccess($document);

        $logs = $document->auditLogs->map(function ($log) use ($document) {
            return [
                'document_name' => $document->title,
                'created_at' => $log->created_at ? $log->created_at->toIso8601String() : null,
                'actor_name' => $log->actor_name,
                'actor_email' => $log->actor_email,
                'action' => $log->action,
                'ip_address' => $log->ip_address ?: '-',
                'notes' => $log->description ?: '-',
            ];
        });

        return response()->json([
            'document' => [
                'id' => $document->id,
                'uuid' => $document->uuid,
                'title' => $document->title,
            ],
            'logs' => $logs,
        ]);
    }

    /**
     * Export all accessible All Document Approval audit logs as CSV.
     */
    public function exportAllAdhoc()
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole('ADM');

        $logs = DocumentAuditLog::where(function ($q) {
                $q->where('document_type', AdhocDocument::DOCUMENT_TYPE)
                    ->orWhereNull('document_type');
            })
            ->whereHas('adhocDocument', function ($docQuery) use ($isAdmin, $user) {
                if (!$isAdmin) {
                    if (!$user) {
                        $docQuery->whereRaw('1 = 0');
                        return;
                    }
                    $docQuery->where(function ($q) use ($user) {
                        $q->where('created_by', $user->id)
                            ->orWhereHas('signers', fn($sq) => $sq->where('user_id', $user->id));
                    });
                }
            })
            ->with(['adhocDocument.creator'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $fileName = 'Audit_Trail_All_Document_Approval_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, [
                'Document Title',
                'File Name',
                'Status',
                'Uploader',
                'Event At',
                'Actor Name',
                'Actor Email',
                'Action',
                'IP Address',
                'Notes',
            ]);

            foreach ($logs as $log) {
                $doc = $log->adhocDocument;
                fputcsv($file, [
                    $doc?->title ?: '-',
                    $doc?->original_file_name ?: '-',
                    $doc?->status ?: '-',
                    $doc?->creator?->name ?: '-',
                    $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '-',
                    $log->actor_name ?: '-',
                    $log->actor_email ?: '-',
                    $log->action,
                    $log->ip_address ?: '-',
                    $log->description ?: '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function export($id)
    {
        $filters = $id;
        return Excel::download(new DocumentLogExport($filters), 'documentLog.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    public function exportAll()
    {
        return Excel::download(new AuditTrailListExport(), 'AuditTrailList.csv', \Maatwebsite\Excel\Excel::CSV);
    }
}
