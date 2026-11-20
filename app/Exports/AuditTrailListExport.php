<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping};
use Illuminate\Support\Facades\DB;

class AuditTrailListExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        DB::statement("SET @rownum := 0;");
        DB::statement("SET @prev_trans := '';");
        $sql = "SELECT
                    ranked.id, document_no, document_name, mdt.document_type, departement_name, rev_no, reason_revisi, creator_name, checker_name, approver_name,
                    document_status, is_obsolete, ranked.created_at
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
                WHERE 1=1
                ORDER BY created_at DESC";

        $rows = DB::select($sql);
        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'Document No',
            'Document Name',
            'Departement',
            'Rev No',
            'Creator',
            'Checker',
            'Approver',
            'Created Date',
            'Document Status'
        ];
    }

    public function map($row): array
    {
        $status = $row->document_status;
        if ($row->is_obsolete == 1) {
            $status = 'Obsolete';
        } elseif ($row->document_status == 'APPROVED') {
            $status = 'Active';
        }

        return [
            $row->document_no,
            $row->document_name,
            $row->departement_name,
            $row->rev_no,
            $row->creator_name,
            $row->checker_name,
            $row->approver_name,
            date('d/m/Y H:i:s', strtotime($row->created_at)),
            $status
        ];
    }
}
