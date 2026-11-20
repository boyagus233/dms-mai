<?php

namespace App\Http\Controllers;

use Elibyy\TCPDF\Facades\TCPDF as PDF;
use setasign\Fpdi\Tcpdf\Fpdi;
use App\Enums\DocumentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\DB;


use App\Models\Departement;
use App\Models\AuditTrail;
use App\Models\DocumentRequest;
use App\Models\DocumentUpload;
use App\Models\Employee;

use Exception;
use RealRashid\SweetAlert\Facades\Alert;

class DocumentAcknowledgeController extends Controller
{

  public function index()
  {
    $user = Auth::user();
    $userId = $user->id;
    $nik = $user->user_name;

    $documentUploads = DB::select("
        SELECT
            tdu.id,
            tdu.document_no,
            tdu.document_name,
            tdu.document_status,
            tdu.departement_name,
            GROUP_CONCAT(mds.name ORDER BY mds.name SEPARATOR ', ') as distribution_departement,
            tdu.rev_no,
            tdu.checker_name,
            tdu.approver_name,
            tdu.creator_name,
            tdu.created_by author_id,
            me.id author_emp_id,
            me.nik,
            COALESCE(tat.employee_id,0) as read_employee_id
        FROM trx_document_upload tdu
        JOIN m_departement md
            ON FIND_IN_SET(md.id, tdu.department_ids)
        JOIN m_departement mds
            ON FIND_IN_SET(mds.id, tdu.department_ids)
        JOIN (
            SELECT DISTINCT
                t.id,
                t.nik,
                t.name,
                SUBSTRING_INDEX(SUBSTRING_INDEX(t.departement_tags, ',', numbers.id), ',', -1) AS departement
            FROM (
                SELECT
                    id,
                    nik,
                    name,
                    CONCAT_WS(',', departement, departement_tags) AS departement_tags
                FROM m_employee
            ) t
            JOIN (
                SELECT id FROM m_departement
            ) numbers
                ON numbers.id <= (
                    LENGTH(t.departement_tags) - LENGTH(REPLACE(t.departement_tags, ',', '')) + 1
                )
            WHERE 1=1
        ) me
            ON md.id = me.departement
        LEFT JOIN tr_audit_trails tat
            ON (
                tdu.id = tat.document_id
                AND me.id = tat.employee_id
                AND tat.action = 'MARK_AS_READ'
            )
        WHERE tdu.created_by <> :creator
            AND me.nik = :nik
            AND tdu.checker_name <> me.name
            AND tdu.approver_name <> me.name
            AND COALESCE(tat.employee_id,0) = 0
            AND tdu.document_status = 'APPROVED'
            AND me.id NOT IN (
                SELECT me.id
                FROM m_employee me
                INNER JOIN users us ON me.id = us.employee_id
                INNER JOIN m_user_role mur ON us.id = mur.user_id
                INNER JOIN m_role mr ON mur.role_id = mr.id
                WHERE mr.code = 'DOCUMENT_CONTROL'
            )
        GROUP BY
            tdu.id,
            tdu.document_no,
            tdu.document_name,
            tdu.document_status,
            tdu.departement_name,
            tdu.rev_no,
            tdu.checker_name,
            tdu.approver_name,
            tdu.creator_name,
            tdu.created_by,
            me.id,
            me.nik,
            COALESCE(tat.employee_id,0)
    ", [
      'creator' => $userId,
      'nik' => $nik
    ]);

    return view('content.document-acknowledge.index', ['documentUploads' => $documentUploads]);
  }

  public function show($id)
  {
    $documentUpload = DocumentUpload::find($id);
    $departmentIds = explode(',', $documentUpload->department_ids); // e.g. [1,3,5]
    $departement = Departement::active()->get();
    $departmentNames = Departement::whereIn('id', $departmentIds)->pluck('name')->toArray();
    $auditrail = DB::table('tr_audit_trails')
      ->where('document_id', $id)
      ->where('action', 'MARK_AS_READ')
      ->get();
    return view('content.document-acknowledge.view', ['documentUpload' => $documentUpload, 'departement' => $departement, 'departmentNames' => $departmentNames, 'auditrail' => $auditrail]);
  }

  public function markAsRead(Request $request)
  {
    $data = $request->all();
    $userId = Auth::id();
    $employeedata = DB::select("SELECT usr.id user_id, me.id employee_id, me.nik nik, me.name employee_name, d.id departement_id,
                          d.name departement_name
                          FROM users usr
                          inner join m_employee me on usr.user_name = me.nik
                          inner join m_departement d on me.departement = d.id
                          WHERE usr.id = ? LIMIT 1", [$userId]);

    $employee = $employeedata[0] ?? null;
    $employeeId = $employee ? $employee->employee_id : 0;
    $employeeName = $employee ? $employee->employee_name : 'System';
    $departementId = $employee ? $employee->departement_id : 0;
    $departementName = $employee ? $employee->departement_name : 'N/A';

    //dd($request->all());
    $id = $data['id'];
    $documentNo = $data['document_no'];
    $documentName = $data['document_name'];
    $action = DocumentStatus::MARK_AS_READ;

    $message = 'Document updated as marked.';
    try {
      $documentUpload = DocumentUpload::find($id);

      $audittrail = new AuditTrail;
      $audittrail->document_id = $id;
      $audittrail->document_no = $documentNo;
      $audittrail->document_name = $documentName;
      $audittrail->action = $action;
      $audittrail->employee_id = $employeeId;
      $audittrail->employee_name = $employeeName;
      $audittrail->departement_id = $departementId;
      $audittrail->departement_name = $departementName;
      $audittrail->user_id = $userId;
      $audittrail->save();
      Alert::success("Success", $message);
    } catch (Exception $e) {
      DB::rollBack();
      $message = $e->getMessage();
      Alert::error("error", $message);
    }
    return redirect()->route('document-acknowledge.index');
  }
}
