<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\DocumentUpload;
use App\Models\User;
use App\DTOs\LogDTO;
use App\DTOs\DocumentUploadDto;
use App\Models\DocumentLevel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Log;

class DocumentUploadService
{

  public function save(DocumentUploadDto $documentUploadDto): DocumentUpload
  {
    $documentUpload = new DocumentUpload;
    $documentUpload->document_type = $documentUploadDto->documentType;
    $documentUpload->document_classification = $documentUploadDto->documentClassification;
    $documentUpload->document_function = $documentUploadDto->documentFunction;
    $documentUpload->document_no = $documentUploadDto->documentNo;
    $documentUpload->document_name = $documentUploadDto->documentName;
    $documentUpload->departement = $documentUploadDto->departement;
    $documentUpload->departement_name = $documentUploadDto->departementName;
    $documentUpload->rev_no = $documentUploadDto->revNo;
    $documentUpload->reason_revisi = $documentUploadDto->reasonRevisi;
    $documentUpload->file_name = $documentUploadDto->fileName;
    $documentUpload->file_attachment = $documentUploadDto->fileAttachmentName;
    $documentUpload->document_status = $documentUploadDto->documentStatus;
    $documentUpload->checker = $documentUploadDto->checker;
    $documentUpload->approver = $documentUploadDto->approver;
    $documentUpload->checker_name = $documentUploadDto->checkerName;
    $documentUpload->approver_name = $documentUploadDto->approverName;
    $documentUpload->creator_name = $documentUploadDto->creatorName;
    $documentUpload->department_ids = $documentUploadDto->departmentIds;
    $documentUpload->is_stamp_by_creator = $documentUploadDto->isStampbyCreator;
    $documentUpload->is_rejected_by_dc = $documentUploadDto->isRejectedbyDc;
    $documentUpload->is_checked_by_dc = $documentUploadDto->isCheckedbyDc;
    $documentUpload->is_rejected_by_appr = $documentUploadDto->isRejectedbyAppr;
    $documentUpload->is_checked = $documentUploadDto->isChecked;
    $documentUpload->is_approved = $documentUploadDto->isApproved;
    $documentUpload->created_by = Auth::id();
    $documentUpload->save();
    return $documentUpload;
  }


  public function getTotalDocumentObsolete($currentYear, $departement, $documenttype)
  {
    $sql = "SELECT count(id) as total
            from vw_trx_document WHERE document_status = 'OBSOLETE' and tahun = ?";
    $bindings = [$currentYear];
    if ($departement !== '0') {
        $sql .= " AND departement = ?";
        $bindings[] = $departement;
    }
    if ($documenttype !== 'ALL') {
        $sql .= " AND document_type = ?";
        $bindings[] = $documenttype;
    }
    $result = DB::select($sql, $bindings);
    $integerValue = $result ? (int) $result[0]->total : 0;
    return $integerValue;
  }

  public function getTotalRequiredCheckByDocControl($userid)
  {
    $sql = "SELECT count(id) as total
            from trx_document_upload WHERE is_stamp_by_creator = 1 and (is_rejected_by_dc = 0 and is_checked_by_dc = 0)";
    $bindings = [];
    $result = DB::select($sql, $bindings);
    $integerValue = $result ? (int) $result[0]->total : 0;
    return $integerValue;
  }
  public function getTotalRequiredCheckByEmployee($userid)
  {
    $sql = "SELECT count(id) as total
            from vw_document_upload WHERE document_status = 'PASS_BY_DC' and user_id_checker = ?";
    $bindings = [$userid];
    $result = DB::select($sql, $bindings);
    $integerValue = $result ? (int) $result[0]->total : 0;
    return $integerValue;
  }
  public function getTotalRequiredApproveByEmployee($userid)
  {
    $sql = "SELECT count(id) as total
            from vw_document_upload WHERE document_status = 'CHECKED' and user_id_approver = ?";
    $bindings = [$userid];
    $result = DB::select($sql, $bindings);
    $integerValue = $result ? (int) $result[0]->total : 0;
    return $integerValue;
  }

  public function getTotalAcknowledgeByEmployee($userid)
  {
    $sql = "SELECT count(id) as total
            from vw_document_acknowledge WHERE user_id = ? and is_read = 0 and (creator_name <> employee_name and checker_name <> employee_name and approver_name <> employee_name)";
    $bindings = [$userid];
    $result = DB::select($sql, $bindings);
    $integerValue = $result ? (int) $result[0]->total : 0;
    return $integerValue;
  }

  public function getListOutstanding($userid)
  {
    $sql = "
    (SELECT id, document_no, document_name, 'review' as outstanding_type from trx_document_upload
     WHERE is_stamp_by_creator = 1 and (is_rejected_by_dc = 0 and is_checked_by_dc = 0)
     ORDER BY document_no)
    UNION
    (SELECT id, document_no, document_name, 'review' as outstanding_type from vw_document_upload
     WHERE document_status = 'PASS_BY_DC' and user_id_checker = ?
     ORDER BY document_no)
    UNION
    (SELECT id, document_no, document_name, 'review' as outstanding_type from vw_document_upload
     WHERE document_status = 'CHECKED' and user_id_approver = ?
     ORDER BY document_no)
    UNION
    (SELECT id, document_no, document_name, 'acknowledge' as outstanding_type from vw_document_acknowledge
     WHERE user_id = ? and is_read = 0 and (creator_name <> employee_name and checker_name <> employee_name and approver_name <> employee_name)
     ORDER BY document_no)
     ORDER BY document_no
    ";

    $transactions = collect(DB::select($sql, [$userid, $userid, $userid]));
    return $transactions;
  }

  public function getDocumentDistributed_return_one_column($documentId)
  {
    $sql = "
    (SELECT employee_name, is_read from vw_document_acknowledge
     WHERE id = ? and (creator_name <> employee_name and checker_name <> employee_name and approver_name <> employee_name)
    )
    ";

    $transactions = collect(DB::select($sql, [$documentId]))
                    ->pluck('employee_name')
                    ->toArray();
    return $transactions;
  }

  public function getDocumentDistributed($documentId)
  {
    $sql = "
    (select
      emp.name employee_name, COALESCE(tat.employee_id,0) as is_read
      from trx_document_upload tdu
      join m_departement md ON FIND_IN_SET(md.id, tdu.department_ids)
      join (
            select id, name, departement from m_employee
            where name not in (select creator_name from trx_document_upload where id = ?
                      union
                      select checker_name from trx_document_upload where id = ?
                      union
                      select approver_name from trx_document_upload where id = ?
                      union
			                select me.name from m_employee me
                      inner join users us on me.id = us.employee_id
                      inner join m_user_role mur on us.id = mur.user_id
                      inner join m_role mr on mur.role_id = mr.id
                      where mr.code = 'DOCUMENT_CONTROL'
                      )
            ) emp on md.id = emp.departement
      join users usr on emp.id = usr.employee_id
      left join tr_audit_trails tat on (emp.id = tat.employee_id and tdu.id = tat.document_id and tat.action = 'MARK_AS_READ')
     WHERE tdu.id = ?
    )
    ";

    $transactions = collect(DB::select($sql, [$documentId, $documentId, $documentId, $documentId]))
                    ->map(function ($item) {
                        return [
                            'employee_name' => $item->employee_name,
                            'is_read' => $item->is_read,
                        ];
                    })
                    ->toArray();
    return $transactions;
  }

  public function getTotalInternalDownload($documentId)
  {
    $sql = "select count(DISTINCT id) as total from tr_audit_trails tat where document_id = ? and action = 'DOWNLOAD_INTERNAL'";
    $bindings = [$documentId];
    $result = DB::select($sql, $bindings);
    $integerValue = $result ? (int) $result[0]->total : 0;
    return $integerValue;
  }

  public function getTotalEksternalDownload($documentId)
  {
    $sql = "select count(DISTINCT id) as total from tr_audit_trails tat where document_id = ? and action = 'DOWNLOAD_EKSTERNAL'";
    $bindings = [$documentId];
    $result = DB::select($sql, $bindings);
    $integerValue = $result ? (int) $result[0]->total : 0;
    return $integerValue;
  }


}
