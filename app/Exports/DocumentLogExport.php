<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping};
use Illuminate\Support\Facades\DB;

class DocumentLogExport implements FromQuery, WithHeadings, WithMapping
{
  /**
   * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
   */
  protected $filters;

  public function __construct($filters)
  {
    $this->filters = $filters;
  }

  public function query()
  {
    $query = DB::table('tr_audit_trails')
      ->select([
        'document_no',
        'document_name',
        DB::raw("case
          when action = 'CREATED' then 'Created'
          when action = 'EDIT' then 'Edit'
          when action = 'STAMP' then 'Stamp'
          when action = 'REJECT_BY_DC' then 'Rejected by DC'
          when action = 'PASS_BY_DC' then 'Passed by DC'
          when action = 'REJECT_BY_CHECKER' then 'Rejected by Checker'
          when action = 'CHECKED' then 'Checked'
          when action = 'REJECT_BY_APPROVER' then 'Rejected by Approver'
          when action = 'APPROVED' then 'Approved'
          when action = 'REVISE' then 'Revise'
          when action = 'OBSOLETE' then 'Obsolete'
          else action end as action_label"),
        'notes',
        'created_at',
        'employee_name'
      ])
      ->orderBy('created_at', 'desc');

    if (!empty($this->filters)) {
      $query->where('document_id', $this->filters);
    }

    return $query;
  }

  public function headings(): array
  {
    return ['Document No', 'Document Name', 'Action', 'Notes', 'Created At', 'Created By'];
  }

  public function map($row): array
  {
    return [
      $row->document_no,
      $row->document_name,
      $row->action_label,
      $row->notes,
      $row->created_at,
      $row->employee_name,
    ];
  }
}
