<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\DTOs\AuditTrailDTO;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class LogService
{
    /**
     * Insert a log entry into trx_log table.
     *
     * @param AuditTrailDTO $auditTrailDTO
     * @return void
     */
    public function log(AuditTrailDTO $auditTrailDTO): void
    {
      $audittrail = new AuditTrail;
      $audittrail->document_id = $auditTrailDTO->documentId;
      $audittrail->document_no = $auditTrailDTO->documentNo;
      $audittrail->document_name = $auditTrailDTO->documentName;
      $audittrail->action = $auditTrailDTO->action;
      $audittrail->employee_id = $auditTrailDTO->employeeId;
      $audittrail->employee_name = $auditTrailDTO->employeeName;
      $audittrail->departement_id = $auditTrailDTO->departementId;
      $audittrail->departement_name = $auditTrailDTO->departementName;
      $audittrail->notes = $auditTrailDTO->notes;
      $audittrail->file_name = $auditTrailDTO->fileName;
      $audittrail->user_id = Auth::id();
      //$audittrail->crea = $logDTO->userId;
      $audittrail->save();
    }
}
