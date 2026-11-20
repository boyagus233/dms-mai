<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DocumentUplodExportWithRevision implements FromCollection, WithHeadings, WithEvents, WithMapping, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $conditions = [];

        if (!empty($this->filters['start_date'])) {
            $conditions[] = "DATE(created_at) >= '" . addslashes($this->filters['start_date']) . "'";
        }

        if (!empty($this->filters['end_date'])) {
            $conditions[] = "DATE(created_at) <= '" . addslashes($this->filters['end_date']) . "'";
        }

        $whereSql = count($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $sql = "
            SELECT 
                @rownum := @rownum + 1 AS no,
                t.document_no,
                t.document_name,
                t.rev_0,
                t.date_rev_0,
                t.rev_1,
                t.date_rev_1,
                t.rev_2,
                t.date_rev_2,
                t.rev_3,
                t.date_rev_3,
                t.rev_4,
                t.date_rev_4,
                t.rev_5,
                t.date_rev_5
            FROM (
                SELECT
                    document_no,
                    document_name,
                    COALESCE(MAX(CASE WHEN rev_no = 0 THEN 0 END), 0) AS rev_0,
                    MAX(CASE WHEN rev_no = 0 THEN created_at END) AS date_rev_0,
                    MAX(CASE WHEN rev_no = 1 THEN 1 END) AS rev_1,
                    MAX(CASE WHEN rev_no = 1 THEN created_at END) AS date_rev_1,
                    MAX(CASE WHEN rev_no = 2 THEN 2 END) AS rev_2,
                    MAX(CASE WHEN rev_no = 2 THEN created_at END) AS date_rev_2,
                    MAX(CASE WHEN rev_no = 3 THEN 3 END) AS rev_3,
                    MAX(CASE WHEN rev_no = 3 THEN created_at END) AS date_rev_3,
                    MAX(CASE WHEN rev_no = 4 THEN 4 END) AS rev_4,
                    MAX(CASE WHEN rev_no = 4 THEN created_at END) AS date_rev_4,
                    MAX(CASE WHEN rev_no = 5 THEN 5 END) AS rev_5,
                    MAX(CASE WHEN rev_no = 5 THEN created_at END) AS date_rev_5
                FROM vw_document_upload
                $whereSql
                GROUP BY document_no, document_name
            ) AS t
            CROSS JOIN (SELECT @rownum := 0) AS r
        ";

        $rows = DB::select($sql);

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            ['No', 'Nomor Document', 'Nama Document', 'Rev.', 'Tanggal Original', 'Revisi', '', 'Revisi', '', 'Revisi', '', 'Revisi', '', 'Revisi', ''],
            ['No', 'Nomor Document', 'Nama Document', 'Rev.', '', 'No', 'Tanggal', 'No', 'Tanggal', 'No', 'Tanggal', 'No', 'Tanggal', 'No', 'Tanggal'],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->mergeCells('A1:A2');
                $event->sheet->mergeCells('B1:B2');
                $event->sheet->mergeCells('C1:C2');
                $event->sheet->mergeCells('D1:D2');
                $event->sheet->mergeCells('E1:E2');

                $event->sheet->mergeCells('F1:G1');
                $event->sheet->mergeCells('H1:I1');
                $event->sheet->mergeCells('J1:K1');
                $event->sheet->mergeCells('L1:M1');
                $event->sheet->mergeCells('N1:O1');

                $event->sheet->getRowDimension(1)->setRowHeight(25);

                $event->sheet->getStyle('A1:O2')
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }

    public function map($row): array
    {
        return [
            $row->no,
            $row->document_no,
            $row->document_name,
            $row->rev_0,
            $this->formatDate($row->date_rev_0),

            $this->formatRevisionNumber($row->rev_1),
            $this->formatDate($row->date_rev_1),

            $this->formatRevisionNumber($row->rev_2),
            $this->formatDate($row->date_rev_2),

            $this->formatRevisionNumber($row->rev_3),
            $this->formatDate($row->date_rev_3),

            $this->formatRevisionNumber($row->rev_4),
            $this->formatDate($row->date_rev_4),

            $this->formatRevisionNumber($row->rev_5),
            $this->formatDate($row->date_rev_5),
        ];
    }

    private function formatDate($value): string
    {
        if (empty($value) || $value === '0000-00-00 00:00:00') {
            return '';
        }

        $timestamp = strtotime($value);

        if ($timestamp === false || $timestamp <= 0) {
            return '';
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function formatRevisionNumber($value): string
    {
        return $value === null || $value === '' ? '' : (string) $value;
    }
}