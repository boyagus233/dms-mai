<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping, ShouldAutoSize};
use Illuminate\Support\Facades\DB;

class DocumentUplodExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
  /**
   * @return \Illuminate\Support\Collection
   */
  protected $filters;

  public function __construct($filters)
  {
    $this->filters = $filters;
  }
  public function collection()
  {
    //work in mysql version 8.0 later
    // $sql = "
    //     SELECT ranked.id, document_no, document_name, mdt.document_type, departement_name,
    //     rev_no, reason_revisi, creator_name, checker_name, approver_name, document_status, ranked.created_at
    //     FROM (
    //         SELECT *,
    //               ROW_NUMBER() OVER (PARTITION BY document_no ORDER BY rev_no DESC) AS rn
    //         FROM vw_document_upload
    //     ) ranked
    //     join m_document_type mdt on ranked.document_type = mdt.code
    //     WHERE 1=1
    // ";
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

    // You can add dynamic filters
    $conditions = [];
    //dd($this->filters);

    if (!empty($this->filters['start_date'])) {
      $conditions[] = "DATE(ranked.created_at) >= '" . addslashes($this->filters['start_date']) . "'";
    }

    if (!empty($this->filters['end_date'])) {
      $conditions[] = "DATE(ranked.created_at) <= '" . addslashes($this->filters['end_date']) . "'";
    }

    if ($this->filters['status'] === 'exclrevisi') {
      $conditions[] = "ranked.rn = 1";
    }

    if (!empty($conditions)) {
      $sql .= " AND " . implode(" AND ", $conditions);
    }
    //dd($this->filters, $sql);

    // Execute raw query
    $rows = DB::select($sql);

    // Convert to collection
    return collect($rows);
  }

  public function headings(): array
  {
    return ['Document No', 'Document Name', 'Type', 'Departement', 'Rev. No', 'Reason Revisi', 'Creator', 'Checker', 'Approver', 'Document Status', 'Created At'];
  }

  public function map($row): array
  {
    return [
      $row->document_no,
      $row->document_name,
      $row->document_type,
      $row->departement_name,
      (int) $row->rev_no,
      $row->reason_revisi,
      $row->creator_name,
      $row->checker_name,
      $row->approver_name,
      $row->document_status,
      date('Y-m-d H:i:s', strtotime($row->created_at)),
    ];
  }
}
