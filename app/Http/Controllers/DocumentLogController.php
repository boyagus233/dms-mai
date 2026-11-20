<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\AuditTrail;


use Exception;
use App\Exports\DocumentLogExport;
use App\Exports\AuditTrailListExport;
use Maatwebsite\Excel\Facades\Excel;

class DocumentLogController extends Controller
{
  public function index(Request $request)
  {
    // Jika ada parameter export, jalankan fungsi export
    if ($request->has('export')) {
        return Excel::download(new AuditTrailListExport(), 'AuditTrailList.csv', \Maatwebsite\Excel\Excel::CSV);
    }

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

    return view('content.document-log.index',['documentUploads' => $documentUploads]);
  }

  public function getLogs($id)
  {
    //return response()->json(['message' => 'Controller reached.'], 200);
    try {
        if (!$id || !is_numeric($id)) {
            return response()->json(['error' => 'Invalid transaction ID'], 400);
        }
        //$logs = AuditTrail::where('document_id', $id)->get();
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
            'message' => $e->getMessage()
        ], 500);
    }
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
