<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\AuditTrail;
use App\DTOs\LogDTO;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Log;

class DashboardService
{
    public function getStats($currentYear, $departement, $type): array
    {
      Log::info("get dashboard data from service" . $departement . '-' . $currentYear . '-' . $type);
      $totalDocument = $this->getTotalDocument($currentYear, $departement, $type);
      $totalDocumentProcedure = $this->getTotalDocumentProcedure($currentYear, $departement, $type);
      $totalDocumentWI = $this->getTotalDocumentWI($currentYear, $departement, $type);
      $totalDocumentForm = $this->getTotalDocumentForm($currentYear, $departement, $type);
      $totalDocumentActive = $this->getTotalDocumentActive($currentYear, $departement, $type);
      $totalDocumentObsolete = $this->getTotalDocumentObsolete($currentYear, $departement, $type);
      $totalDocumentToApprove = $this->getTotalDocumentToApprove($currentYear, $departement, $type);
      $querytotalBookingGt3days = DB::selectOne("SELECT count(distinct tdr.document_no) total from trx_document_request tdr
                                                  left join trx_document_upload tdu on tdr.document_no = tdu.document_no
                                                  where coalesce(tdu.document_no,'')=''
                                                  and DATEDIFF(CURDATE(), tdr.created_at) > 3");
      $totalBookingGt3days = $querytotalBookingGt3days->total;

      $chartData = $this->getFilteredTransactionsChart($currentYear, $departement, $type);
      $documentEksternals = $this->getFilteredTransactionsDokEksternal($currentYear);
      $userActivityLogs = $this->getUserActivity();
      return [
          'totalBookingGt3days' => $totalBookingGt3days,
          'totalDocument' => $totalDocument,
          'totalDocumentProcedure' => $totalDocumentProcedure,
          'totalDocumentWI' => $totalDocumentWI,
          'totalDocumentForm' => $totalDocumentForm,
          'totalDocumentActive' => $totalDocumentActive,
          'totalDocumentObsolete' => $totalDocumentObsolete,
          'totalDocumentToApprove' => $totalDocumentToApprove,
          'chartData' => $chartData,
          'documentEksternals' => $documentEksternals,
          'userActivityLogs' => $userActivityLogs
      ];

      // return [
      //     'totalBookingGt3days' => 0,
      //     'totalDocument' => 0,
      //     'totalDocumentProcedure' => 0,
      //     'totalDocumentWI' => 0,
      //     'totalDocumentForm' => 0,
      //     'totalDocumentActive' => 0,
      //     'totalDocumentObsolete' => 0,
      //     'totalDocumentToApprove' => 0,
      //     'chartData' => [],
      //     'documentEksternals' => [],
      //     'userActivityLogs' => []
      // ];
    }

    public function getTotalDocument($currentYear, $departement, $documenttype)
    {
      $sql = "SELECT count(distinct document_no) as total
              from vw_trx_document WHERE 1=1";
      //$bindings = [$currentYear];
      $bindings = [];
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

    public function getTotalDocumentProcedure($currentYear, $departement, $documenttype)
    {
      $sql = "SELECT count(distinct document_no) as total
              from vw_trx_document WHERE document_type = 'P'";
      $bindings = [];
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

    public function getTotalDocumentWI($currentYear, $departement, $documenttype)
    {
      $sql = "SELECT count(distinct document_no) as total
              from vw_trx_document WHERE document_type = 'WI'";
      $bindings = [];
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

    public function getTotalDocumentForm($currentYear, $departement, $documenttype)
    {
      $sql = "SELECT count(distinct document_no) as total
              from vw_trx_document WHERE document_type = 'F'";
      $bindings = [];
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

    public function getTotalDocumentActive($currentYear, $departement, $documenttype)
    {
      $sql = "SELECT count(distinct document_no) as total
              from vw_trx_document WHERE document_status = 'APPROVED'";
      $bindings = [];
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

    public function getTotalDocumentToApprove($currentYear, $departement, $documenttype)
    {
      $userId = Auth::id();
      $sql = "SELECT count(distinct tdu.document_no) as total
              from trx_document_upload tdu
              inner join trx_document_request tdr on tdu.document_no = tdr.document_no
              inner join m_departement md on tdu.departement = md.id
              inner join users checker on tdu.checker = checker.employee_id
              inner join users approver on tdu.approver = approver.employee_id
              where is_approved = 0 and (checker.id = ? or approver.id = ?)";
      $bindings = [$userId, $userId];
      if ($departement !== '0') {
          $sql .= " AND departement = ?";
          $bindings[] = $departement;
      }
      if ($documenttype !== 'ALL') {
          $sql .= " AND coalesce(document_form, document_type_code) = ?";
          $bindings[] = $documenttype;
      }
      $result = DB::select($sql, $bindings);
      $integerValue = $result ? (int) $result[0]->total : 0;
      return $integerValue;
    }

    public function getTotalDocumentObsolete($currentYear, $departement, $documenttype)
    {
      $sql = "SELECT count(distinct document_no) as total
              from vw_trx_document WHERE document_status = 'APPROVED' and is_obsolete = 1";
      $bindings = [];
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
    public function getFilteredTransactionsChart($currentYear, $departement, $documenttype)
    {
      $sql = "SELECT distinct id, tahun, document_no, document_type, document_status, departement_name dept, case when is_obsolete = 0 then 'documentActive' when is_obsolete = 1 and document_status<>'APPROVED' then 'documentActive' when is_obsolete = 1 and document_status='APPROVED' then 'obsolete' end documenttype
              ,COALESCE(owner_name,'Admin') as owner, createdAt, updatedAt
              from vw_trx_document WHERE 1=1";
      $bindings = [];
      if ($departement !== '0') {
          $sql .= " AND departement = ?";
          $bindings[] = $departement;
      }

      if ($documenttype !== 'ALL') {
          $sql .= " AND document_type = ?";
          $bindings[] = $documenttype;
      }

      //$sql .= " ORDER BY created_at DESC";

      return DB::select($sql, $bindings); // returns array of stdClass
    }
    public function getDataChartDepartement($currentYear, $departement, $documenttype)
    {
      $results = DB::table('vw_trx_document')
          ->when($departement !== '0', function ($query) use ($departement) {
              $query->where('department', $departement);
          })
          ->when($documenttype !== 'ALL', function ($query) use ($documenttype) {
              $query->where('document_type', $documenttype);
          })
          ->distinct()
          ->get();
      return $results;
    }

  public function getFilteredTransactionsDokEksternal($currentYear)
  {
      $sql = "SELECT document_eksternal_type, left(document_eksternal_type,5) type, mdet.name type_name,
              status
              from trx_document_eksternal t
              inner join m_document_eksternal_type mdet on t.document_eksternal_type = mdet.code
              WHERE 1=1";
      $bindings = [];

      return DB::select($sql, $bindings); // returns array of stdClass
  }

  public function getUserActivity()
  {
    $currentDate = now()->format('Y-m-d');
    $sql = "SELECT distinct created_at, document_no, document_name, employee_name, action
            FROM tr_audit_trails tat WHERE action in ('CREATED','APPROVED')
            AND DATE(created_at) = ?";
    $bindings = [$currentDate];

    $sql .= " ORDER BY document_no";
    $documentLogs = DB::select($sql, $bindings);

    return $documentLogs;
  }
}
