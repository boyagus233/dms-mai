<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\Departement;
use Illuminate\Http\Request;
use App\Models\DocumentType;
use App\Services\DashboardService;

use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
  public function index(Request $request)
  {
    $documentType = DocumentType::active()->get();
    $departement = Departement::active()->get();
    $currentYear = date('Y');
    $currentMonth = 12;
    $previousYear = $currentYear - 1;
    $dashboardService = new DashboardService;
    $stats = $dashboardService->getStats($currentYear, '0', 'ALL');
    // Optionally set default selected year
    $selectedYear = $request->input('year', $currentYear);
    $years = [$currentYear, $previousYear];
    ////$transactiondata = $this->getFilteredTransactions($currentYear, '0', 'ALL');
    $transactiondata = $dashboardService->getFilteredTransactionsChart($currentYear, '0', 'ALL'); //[]; //
    $transactiondataEksternal = $dashboardService->getFilteredTransactionsDokEksternal($currentYear);
    $userActivityLogs = $dashboardService->getUserActivity();
    //$transactiondata = []; //
    //$transactiondataEksternal = [];
    //$userActivityLogs = [];
    //dd($transactiondataMonthly);
    return view('content.dashboard.dashboards', compact('transactiondata','transactiondataEksternal','userActivityLogs','documentType','departement','selectedYear','currentMonth','stats','years'));
  }

  public function getData(Request $request)
  {
      $department = $request->get('department');
      $tahun = $request->get('tahun');
      $type = $request->get('type');
      //dd($request->all);
      Log::info("get dashboard json data" . $department . '-' . $tahun . '-' . $type);
      $dashboardService = new DashboardService;
      $stats = $dashboardService->getStats($tahun,$department, $type);
      //$stats = $dashboardService->getStats($tahun, '0', 'ALL');
      //Log::info('get dashboard json data return', $stats);

      return response()->json([
          'stats' => $stats,
          'department' => $department
      ]);
  }

  // public function getFilteredTransactions($currentYear, $departement, $documenttype)
  // {
  //     $sql = "SELECT distinct id, tahun, document_no, document_type, document_status, departement_name dept, case when is_obsolete = 0 then 'documentActive' when is_obsolete = 1 and document_status<>'APPROVED' then 'documentActive' when is_obsolete = 1 and document_status='APPROVED' then 'obsolete' end documenttype
  //             ,COALESCE(owner_name,'Admin') as owner, createdAt, updatedAt
  //             from vw_trx_document WHERE tahun = ?";
  //     $bindings = [$currentYear];

  //     if ($departement !== '0') {
  //         $sql .= " AND department = ?";
  //         $bindings[] = $departement;
  //     }

  //     if ($documenttype !== 'ALL') {
  //         $sql .= " AND document_type = ?";
  //         $bindings[] = $documenttype;
  //     }

  //     //$sql .= " ORDER BY created_at DESC";
  //     //dd($sql);

  //     return DB::select($sql, $bindings); // returns array of stdClass
  // }

  // public function getDataChartDepartement($currentYear, $departement, $documenttype)
  // {
  //   $results = DB::table('vw_trx_document')->where('tahun',$currentYear)
  //       ->when($departement !== '0', function ($query) use ($departement) {
  //           $query->where('department', $departement);
  //       })
  //       ->when($documenttype !== 'ALL', function ($query) use ($documenttype) {
  //           $query->where('document_type', $documenttype);
  //       })
  //       ->get();
  //   return $results;
  // }

  // public function getFilteredTransactionsDokEksternal($currentYear)
  // {
  //     $sql = "SELECT document_eksternal_type, left(document_eksternal_type,5) type, mdet.name type_name,
  //             status
  //             from trx_document_eksternal t
  //             inner join m_document_eksternal_type mdet on t.document_eksternal_type = mdet.code
  //             WHERE 1=1
  //             and year(t.created_at) = ?";
  //     $bindings = [$currentYear];

  //     return DB::select($sql, $bindings); // returns array of stdClass
  // }

  // public function getUserActivity()
  // {
  //   $currentDate = now()->format('Y-m-d');
  //   $sql = "SELECT distinct created_at, document_no, document_name, employee_name, action
  //           FROM tr_audit_trails tat WHERE action in ('CREATED','APPROVED')
  //           AND DATE(created_at) = ?";
  //   $bindings = [$currentDate];

  //   $sql .= " ORDER BY document_no";
  //   $documentLogs = DB::select($sql, $bindings);

  //   return $documentLogs;
  // }

  public function getCalendarTransaction($currentYear, $month)
  {
    DB::statement("SET @rownum := 0;");
    DB::statement("SET @prev_trans := '';");
    $sql = "SELECT
                ranked.id, document_no, document_name, mdt.document_type, departement_name, rev_no, reason_revisi, creator_name, checker_name, approver_name,
                document_status, ranked.created_at, ranked.created_at as 'baseDate', ranked.created_at as 'approvedAt'
            FROM (
                SELECT
                    v.*,
                    @rownum := IF(@prev_trans = v.document_no COLLATE utf8mb4_unicode_ci, @rownum + 1, 1) AS rn,
                    @prev_trans := v.document_no COLLATE utf8mb4_unicode_ci
                FROM vw_document_upload v
                WHERE year(created_at) = ? AND MONTH(created_at) = ?
                ORDER BY v.document_no, v.rev_no DESC
            ) ranked
            join m_document_type mdt on ranked.document_type = mdt.code
            WHERE 1=1 AND ranked.rn = 1";
    $bindings = [$currentYear, $month];

    $sql .= " ORDER BY ranked.created_at DESC";
    //dd($sql, $startDate, $endDate);
    $documentUploads = DB::select($sql, $bindings);

    return response()->json([
      'data' => $documentUploads,
    ]);
  }


}
