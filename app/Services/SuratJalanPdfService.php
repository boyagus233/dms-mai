<?php

namespace App\Services;

use App\Models\SuratJalan;
use App\Models\SuratJalanSigner;
use TCPDF;

/**
 * Generator PDF:
 *  1. Surat Jalan resmi: 1 kertas A4 berisi 2 format — atas Surat Jalan (4 kotak TTD berisi
 *     image stamp / QR stamp), bawah template yang sama untuk pengecekan security (tanpa inputan)
 *  2. Audit History (on-demand, dari tabel document_audit_logs)
 */
class SuratJalanPdfService
{
    // Geometri A4 (mm)
    private const PAGE_W = 210;
    private const PAGE_H = 297;
    private const M_LEFT = 12;
    private const M_RIGHT = 12;
    private const CONTENT_W = 186;           // 210 - 12 - 12
    private const FOOTER_H = 14;             // area footer (catatan + nomor halaman)

    // --- Surat Jalan: 1 kertas A4 berisi 2 format (feedback MAI #3) ---
    // Format 1 (atas)  : template Surat Jalan (dibawa vendor setelah TTD basah).
    // Format 2 (bawah) : template yang sama untuk pengecekan security, tanpa inputan.
    private const HALF_H = 148.5;            // batas potong = setengah A4
    private const HALF_PAD_TOP = 5;          // jarak atas tiap format
    private const SIG_BLOCK_H = 44;          // tanggal + 4 kotak TTD + keterangan lembar (format 1)
    private const TABLE_HEAD_H = 6;
    private const ROW_MIN_H = 5.2;
    private const COLS = [12, 86, 28, 60];   // NO, JENIS BARANG, QTY, KETERANGAN

    public function __construct(private SuratJalanService $service)
    {
    }

    /* =====================================================================
     |  1. PDF SURAT JALAN — 1 kertas, 2 format (atas: Surat Jalan, bawah: Security)
     * =================================================================== */

    public function suratJalan(SuratJalan $sj): string
    {
        $sj->loadMissing(['items', 'signers', 'departement', 'creator']);

        $pdf = $this->newPdf('Surat Jalan ' . ($sj->sj_no ?? 'DRAFT'), sprintf(
            'Dokumen elektronik %s.  Doc ID: %s', config('app.name'), $sj->uuid
        ));
        $pdf->SetAutoPageBreak(false);

        // Tinggi area isi tabel per format (sama untuk format 1 & 2 agar kedua format identik).
        $pdf->AddPage();
        $tableTop = $this->measureTableTop($pdf, $sj);
        $tableBottom = self::HALF_H - 3 - self::SIG_BLOCK_H;
        $chunks = $this->chunkItems($pdf, $sj, $tableBottom - ($tableTop + self::TABLE_HEAD_H));

        foreach ($chunks as $i => $items) {
            if ($i > 0) {
                $pdf->AddPage();
            }
            $page = [$i + 1, count($chunks)];

            // Format 1 — Surat Jalan
            $this->drawFormat($pdf, $sj, $items, 0, true, 'LEMBAR VENDOR', $page);
            // Garis potong
            $this->drawCutLine($pdf, self::HALF_H);
            // Format 2 — pengecekan security (template sama, tanpa inputan)
            $this->drawFormat($pdf, $sj, $items, self::HALF_H, false, 'LEMBAR SECURITY', $page);
        }

        if ($sj->status !== SuratJalan::STATUS_COMPLETED) {
            $this->watermark($pdf, $sj->status === SuratJalan::STATUS_IN_PROGRESS ? 'IN PROGRESS' : $sj->status,
                $sj->status === SuratJalan::STATUS_REJECTED ? [220, 38, 38] : [120, 120, 120]);
        }

        return $pdf->Output('', 'S');
    }

    /**
     * Gambar satu format (setengah halaman) mulai dari $y0.
     *
     * @param array{0:int,1:int} $page [halaman ke, total halaman]
     */
    private function drawFormat(TCPDF $pdf, SuratJalan $sj, $items, float $y0, bool $withSignatures, string $copyLabel, array $page): void
    {
        $y = $this->drawCompactLetterhead($pdf, $y0 + self::HALF_PAD_TOP, $copyLabel);
        $y = $this->drawCompactHeader($pdf, $sj, $y + 1.5, $page);
        $tableBottom = $y0 + self::HALF_H - 3 - self::SIG_BLOCK_H;
        $this->drawCompactTable($pdf, $items, $y + 2, $tableBottom);

        if ($withSignatures) {
            $this->drawCompactSignatures($pdf, $sj, $tableBottom + 1.5);
        }
    }

    /** Posisi Y awal tabel (dipakai untuk menghitung kapasitas baris per format). */
    private function measureTableTop(TCPDF $pdf, SuratJalan $sj): float
    {
        $pdf->startTransaction();
        $y = $this->drawCompactLetterhead($pdf, self::HALF_PAD_TOP, '');
        $y = $this->drawCompactHeader($pdf, $sj, $y + 1.5, [1, 1]);
        $pdf->rollbackTransaction(true);   // true = pulihkan objek ini (hasil ukur tidak ikut tercetak)

        return $y + 2;
    }

    /** Bagi item ke beberapa halaman sesuai tinggi area tabel yang tersedia. */
    private function chunkItems(TCPDF $pdf, SuratJalan $sj, float $available): array
    {
        $pdf->SetFont('helvetica', '', 8);
        $chunks = [[]];
        $used = 0;
        foreach ($sj->items as $item) {
            $h = $this->rowHeight($pdf, $item);
            if ($used + $h > $available && !empty($chunks[count($chunks) - 1])) {
                $chunks[] = [];
                $used = 0;
            }
            $chunks[count($chunks) - 1][] = $item;
            $used += $h;
        }
        return $chunks;
    }

    private function rowCells($item): array
    {
        return [(string) $item->line_no, $item->jenis_barang, $item->qtyFormatted(), (string) $item->keterangan];
    }

    private function rowHeight(TCPDF $pdf, $item): float
    {
        $lines = 1;
        foreach ($this->rowCells($item) as $i => $text) {
            $lines = max($lines, $pdf->getNumLines($text, self::COLS[$i] - 2));
        }
        return max(self::ROW_MIN_H, $lines * 3.4 + 1.8);
    }

    /** Kop surat versi ringkas (setengah halaman) + label lembar di pojok kanan atas. */
    private function drawCompactLetterhead(TCPDF $pdf, float $top, string $copyLabel): float
    {
        $company = config('surat_jalan.company');
        $logo = public_path($company['logo']);
        $textX = self::M_LEFT;
        $labelW = 38;

        $pdf->SetTextColor(20, 20, 20);
        if ($copyLabel !== '') {
            $pdf->SetFont('helvetica', 'B', 7.5);
            $pdf->SetFillColor(235, 235, 235);
            $pdf->SetXY(self::PAGE_W - self::M_RIGHT - $labelW, $top - 1);
            $pdf->Cell($labelW, 4.5, $copyLabel, 1, 0, 'C', true);
        }
        if (!empty($company['tagline'])) {
            $pdf->SetFont('helvetica', 'I', 6);
            $pdf->SetXY(self::M_LEFT + $labelW, $top);
            $pdf->Cell(self::CONTENT_W - 2 * $labelW, 3, $company['tagline'], 0, 1, 'C');
            $top += 3.2;
        }

        if (is_file($logo)) {
            $pdf->Image($logo, self::M_LEFT, $top + 0.5, 24, 11, '', '', '', true, 300, '', false, false, 0, 'LM');
            $textX = self::M_LEFT + 26;
        }

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY($textX, $top);
        $pdf->Cell(0, 4.8, $company['name'], 0, 1);
        $pdf->SetFont('helvetica', '', 6.5);
        $pdf->SetX($textX);
        $pdf->MultiCell(self::CONTENT_W - ($textX - self::M_LEFT), 2.8,
            trim($company['address'] . ($company['phone'] ? "\nTelp : " . $company['phone'] : '')), 0, 'L');

        $lineY = max($top + 12, $pdf->GetY() + 1);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(self::M_LEFT, $lineY, self::PAGE_W - self::M_RIGHT, $lineY);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(self::M_LEFT, $lineY + 0.7, self::PAGE_W - self::M_RIGHT, $lineY + 0.7);

        return $lineY + 0.7;
    }

    /** Judul, NO, Kepada, No. Kend & checklist kategori (versi ringkas). */
    private function drawCompactHeader(TCPDF $pdf, SuratJalan $sj, float $y, array $page): float
    {
        $leftW = 120;
        $boxX = self::M_LEFT + $leftW + 4;
        $boxW = self::CONTENT_W - $leftW - 4;

        // Judul + nomor
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetXY(self::M_LEFT, $y + 0.5);
        $pdf->Cell($leftW, 6.5, 'SURAT JALAN', 0, 1, 'C');
        $titleW = $pdf->GetStringWidth('SURAT JALAN');
        $pdf->SetLineWidth(0.35);
        $pdf->Line(self::M_LEFT + ($leftW - $titleW) / 2, $y + 6.8, self::M_LEFT + ($leftW + $titleW) / 2, $y + 6.8);
        $pdf->SetLineWidth(0.2);

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(self::M_LEFT, $y + 7.5);
        $no = 'NO : ' . ($sj->sj_no ?? '( DRAFT )');
        if ($page[1] > 1) {
            $no .= "   (Hal. {$page[0]}/{$page[1]})";
        }
        $pdf->Cell($leftW, 4.5, $no, 0, 1, 'C');

        // Kepada & No. Kend
        $kepadaTipe = config('surat_jalan.kepada_tipe.' . $sj->kepada_tipe);
        $ly = $y + 13.5;
        foreach ([['Kepada', $sj->kepada . ($kepadaTipe ? " ({$kepadaTipe})" : '')], ['No. Kend', $sj->no_kendaraan ?: '-']] as [$label, $value]) {
            $pdf->SetXY(self::M_LEFT, $ly);
            $pdf->SetFont('helvetica', '', 8.5);
            $pdf->Cell(17, 4.5, $label, 0, 0, 'L');
            $pdf->Cell(3, 4.5, ':', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'B', 8.5);
            $pdf->MultiCell($leftW - 20, 4.5, $value, 'B', 'L', false, 1, '', '', true, 0, false, true, 4.5, 'M', true);
            $ly += 5.5;
        }

        // Checklist kategori
        $selected = (array) $sj->kategori;
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetXY($boxX, $y);
        $pdf->Cell($boxW, 4.5, 'Keperluan / Tujuan Barang', 1, 1, 'C', true);
        $cy = $y + 4.5;
        foreach (config('surat_jalan.kategori') as $code => $label) {
            $checked = in_array($code, $selected, true);
            $text = $label . ($code === 'LAIN_LAIN' && $checked && $sj->kategori_lain ? ': ' . $sj->kategori_lain : '');
            $pdf->Rect($boxX + 1.5, $cy + 0.8, 2.6, 2.6);
            if ($checked) {
                $pdf->SetLineWidth(0.45);
                $pdf->Line($boxX + 1.9, $cy + 2.1, $boxX + 2.6, $cy + 3.1);
                $pdf->Line($boxX + 2.6, $cy + 3.1, $boxX + 3.9, $cy + 1.1);
                $pdf->SetLineWidth(0.2);
            }
            $pdf->SetFont('helvetica', $checked ? 'B' : '', 7.2);
            $pdf->MultiCell($boxW - 6, 3.4, $text, 0, 'L', false, 1, $boxX + 5.5, $cy + 0.3, true, 0, false, true, 3.4, 'M', true);
            $cy += 3.5;
        }
        $pdf->Rect($boxX, $y + 4.5, $boxW, $cy - $y - 4.5 + 0.8);

        return max($ly, $cy + 0.8);
    }

    /** Tabel NO / JENIS BARANG / QTY / KETERANGAN + baris kosong sampai $bottom (meniru form bergaris). */
    private function drawCompactTable(TCPDF $pdf, $items, float $y, float $bottom): void
    {
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(235, 235, 235);
        $x = self::M_LEFT;
        foreach (['NO', 'JENIS BARANG', 'QTY', 'KETERANGAN'] as $i => $label) {
            $pdf->MultiCell(self::COLS[$i], self::TABLE_HEAD_H, $label, 1, 'C', true, 0, $x, $y, true, 0, false, true, self::TABLE_HEAD_H, 'M');
            $x += self::COLS[$i];
        }
        $y += self::TABLE_HEAD_H;

        $pdf->SetFont('helvetica', '', 8);
        foreach ($items as $item) {
            $h = $this->rowHeight($pdf, $item);
            $x = self::M_LEFT;
            foreach ($this->rowCells($item) as $i => $text) {
                $align = $i === 0 ? 'C' : ($i === 2 ? 'R' : 'L');
                $pdf->MultiCell(self::COLS[$i], $h, $text, 1, $align, false, 0, $x, $y, true, 0, false, true, $h, 'M');
                $x += self::COLS[$i];
            }
            $y += $h;
        }

        while ($y + self::ROW_MIN_H <= $bottom + 0.01) {
            $x = self::M_LEFT;
            foreach (self::COLS as $w) {
                $pdf->Rect($x, $y, $w, self::ROW_MIN_H);
                $x += $w;
            }
            $y += self::ROW_MIN_H;
        }
    }

    /** "Karawang, tgl" + 4 kotak tanda tangan + keterangan lembar (format 1). */
    private function drawCompactSignatures(TCPDF $pdf, SuratJalan $sj, float $y): void
    {
        $w = self::CONTENT_W / 4;
        $labelH = 4.5;
        $stampH = 16;
        $nameH = 3.8;
        $infoH = 3.2;

        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->SetXY(self::M_LEFT, $y);
        $pdf->Cell(self::CONTENT_W, 4, $sj->location . ', ' . ($sj->sj_date ?? now())->format('d-m-Y'), 0, 0, 'R');
        $y += 4.5;

        $slots = config('surat_jalan.slots');
        foreach (array_values(config('surat_jalan.slot_layout', array_keys($slots))) as $col => $step) {
            $slot = $slots[$step];
            $x = self::M_LEFT + $col * $w;
            $signer = $sj->signerForStep($step);

            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(235, 235, 235);
            $pdf->SetTextColor(20, 20, 20);
            $pdf->MultiCell($w, $labelH, $slot['label'], 1, 'C', true, 0, $x, $y, true, 0, false, true, $labelH, 'M');

            $sy = $y + $labelH;
            $pdf->Rect($x, $sy, $w, $stampH);
            $this->drawStamp($pdf, $sj, $signer, $x, $sy, $w, $stampH);

            $ny = $sy + $stampH;
            $pdf->SetFont('helvetica', 'B', 7);
            $name = $signer ? '( ' . $signer->signer_name . ' )' : '(                    )';
            $pdf->MultiCell($w, $nameH, $name, 'LR', 'C', false, 0, $x, $ny, true, 0, false, true, $nameH, 'M', true);

            $pdf->SetFont('helvetica', '', 6.3);
            $pdf->MultiCell($w, $infoH, $signer?->signer_position ?: $slot['role'], 'LR', 'C', false, 0, $x, $ny + $nameH, true, 0, false, true, $infoH, 'M', true);

            $date = $signer && $signer->isSigned() ? $signer->signed_at->format('d-m-Y H:i:s') : 'Tgl: ...................';
            $pdf->MultiCell($w, $infoH, $date, 'LRB', 'C', false, 0, $x, $ny + $nameH + $infoH, true, 0, false, true, $infoH, 'M', true);
        }
        $pdf->SetTextColor(20, 20, 20);

        // Keterangan distribusi lembar (sesuai template form fisik)
        $ly = $y + $labelH + $stampH + $nameH + 2 * $infoH + 0.8;
        $pdf->SetFont('helvetica', '', 6.3);
        foreach (config('surat_jalan.lembar', []) as $lembar => $penerima) {
            $pdf->SetXY(self::M_LEFT, $ly);
            $pdf->Cell(13, 2.6, $lembar, 0, 0, 'L');
            $pdf->Cell(60, 2.6, ': ' . $penerima, 0, 0, 'L');
            $ly += 2.6;
        }
    }

    /** Garis potong putus-putus di antara format 1 dan format 2. */
    private function drawCutLine(TCPDF $pdf, float $y): void
    {
        $pdf->SetLineStyle(['width' => 0.3, 'dash' => '2,1.5', 'color' => [120, 120, 120]]);
        $pdf->Line(4, $y, self::PAGE_W - 4, $y);
        $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [30, 30, 30]]);

        $pdf->SetFont('helvetica', 'I', 6);
        $pdf->SetTextColor(110, 110, 110);
        $pdf->SetFillColor(255, 255, 255);
        $label = '  potong / sobek di sini  ';
        $w = $pdf->GetStringWidth($label) + 2;
        $pdf->SetXY((self::PAGE_W - $w) / 2, $y - 1.6);
        $pdf->Cell($w, 3.2, $label, 0, 0, 'C', true);
        $pdf->SetTextColor(20, 20, 20);
    }

    /** Kop surat ukuran penuh (dipakai PDF Audit History). */
    private function drawLetterhead(TCPDF $pdf): float
    {
        $company = config('surat_jalan.company');
        $logo = public_path($company['logo']);
        $textX = self::M_LEFT;
        $top = 8;

        $pdf->SetTextColor(20, 20, 20);
        if (!empty($company['tagline'])) {
            $pdf->SetFont('helvetica', 'I', 7);
            $pdf->SetXY(self::M_LEFT, $top);
            $pdf->Cell(self::CONTENT_W, 3.5, $company['tagline'], 0, 1, 'C');
            $top += 4;
        }

        if (is_file($logo)) {
            $pdf->Image($logo, self::M_LEFT, $top + 1, 30, 15, '', '', '', true, 300, '', false, false, 0, 'LM');
            $textX = self::M_LEFT + 33;
        }

        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->SetXY($textX, $top);
        $pdf->Cell(0, 6, $company['name'], 0, 1);
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->SetX($textX);
        $pdf->MultiCell(self::CONTENT_W - ($textX - self::M_LEFT), 3.5, trim($company['address'] . ($company['phone'] ? "\nTelp : " . $company['phone'] : '')), 0, 'L');

        $lineY = max($top + 17, $pdf->GetY() + 1.5);
        $pdf->SetLineWidth(0.6);
        $pdf->Line(self::M_LEFT, $lineY, self::PAGE_W - self::M_RIGHT, $lineY);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(self::M_LEFT, $lineY + 0.9, self::PAGE_W - self::M_RIGHT, $lineY + 0.9);

        return $lineY + 1;
    }

    private function drawStamp(TCPDF $pdf, SuratJalan $sj, ?SuratJalanSigner $signer, float $x, float $y, float $w, float $h): void
    {
        $pad = 2;
        if (!$signer || !$signer->isSigned()) {
            $pdf->SetFont('helvetica', 'I', 7.5);
            if ($signer && $signer->status === SuratJalanSigner::STATUS_REJECTED) {
                $pdf->SetTextColor(220, 38, 38);
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->MultiCell($w, $h, 'REJECTED', 0, 'C', false, 0, $x, $y, true, 0, false, true, $h, 'M');
            } elseif ($signer && $signer->status === SuratJalanSigner::STATUS_PENDING) {
                $pdf->SetTextColor(150, 150, 150);
                $pdf->MultiCell($w, $h, 'Menunggu tanda tangan', 0, 'C', false, 0, $x, $y, true, 0, false, true, $h, 'M');
            }
            $pdf->SetTextColor(20, 20, 20);
            return;
        }

        $file = $this->service->stampAbsolutePath($signer);
        if ($file) {
            // fitbox 'CM' = proporsional, center-middle, tidak keluar dari kotak
            $pdf->Image($file, $x + $pad, $y + $pad, $w - 2 * $pad, $h - 2 * $pad, '', '', '', true, 300, '', false, false, 0, 'CM');
            return;
        }

        // QR stamp: QR di kiri + ringkasan teks di kanan
        $qr = $h - 2 * $pad;
        $pdf->write2DBarcode($signer->qrPayload($sj), 'QRCODE,M', $x + $pad, $y + $pad, $qr, $qr,
            ['border' => false, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => false], 'N');
        $tx = $x + $pad + $qr + 1;
        $pdf->SetFont('helvetica', 'B', 6);
        $pdf->SetTextColor(22, 101, 52);
        $pdf->MultiCell($w - $qr - 2 * $pad - 1, 3, "DIGITALLY\nSIGNED", 0, 'L', false, 1, $tx, $y + $pad + 1);
        $pdf->SetFont('helvetica', '', 5.5);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->MultiCell($w - $qr - 2 * $pad - 1, 2.6, $signer->signed_at->format('d-m-Y') . "\n" . $signer->signed_at->format('H:i:s'),
            0, 'L', false, 1, $tx, $pdf->GetY() + 0.5);
        $pdf->SetTextColor(20, 20, 20);
    }

    /* =====================================================================
     |  2. PDF AUDIT HISTORY (on-demand dari document_audit_logs)
     * =================================================================== */

    public function auditHistory(SuratJalan $sj): string
    {
        $sj->loadMissing(['items', 'signers', 'creator', 'departement']);
        $logs = $sj->auditLogs()->get();

        $pdf = $this->newPdf('Audit History ' . ($sj->sj_no ?? ''), sprintf(
            'Audit History di-generate %s dari tabel document_audit_logs oleh %s. Doc ID: %s',
            now()->format('d-m-Y H:i:s'), auth()->user()?->name ?? '-', $sj->uuid
        ));
        $pdf->AddPage();
        $pdf->SetY($this->drawLetterhead($pdf) + 3);
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->Cell(0, 8, 'AUDIT HISTORY / CERTIFICATE OF COMPLETION', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, 'Surat Jalan ' . ($sj->sj_no ?? '(draft)'), 0, 1, 'C');
        $pdf->Ln(3);

        $e = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $statusColor = [
            SuratJalan::STATUS_COMPLETED => '#15803d',
            SuratJalan::STATUS_REJECTED  => '#dc2626',
        ][$sj->status] ?? '#b45309';

        $created = $logs->firstWhere('action', DocumentAuditLogger::DOCUMENT_CREATED);
        $info = [
            'Document ID / UUID'     => $sj->uuid,
            'Jenis Dokumen'          => 'Surat Jalan (Barang Keluar)',
            'No. Surat Jalan'        => $sj->sj_no ?? '-',
            'Kepada / No. Kendaraan' => $sj->kepada . ' / ' . ($sj->no_kendaraan ?: '-'),
            'Status Akhir'           => '<b style="color:' . $statusColor . '">' . $e($sj->status) . '</b>',
            'Created By'             => $e($sj->creator?->name)
                . ($sj->creator?->email ? ' &lt;' . $e($sj->creator->email) . '&gt;' : '')
                . ' — ' . $e($sj->created_at->format('d-m-Y H:i:s')),
            'Submitted'              => $sj->submitted_at?->format('d-m-Y H:i:s') ?? '-',
            ($sj->status === SuratJalan::STATUS_REJECTED ? 'Rejected' : 'Completed') =>
                ($sj->status === SuratJalan::STATUS_REJECTED
                    ? $e($sj->rejected_at?->format('d-m-Y H:i:s')) . ' — alasan: ' . $e($sj->rejected_reason)
                    : ($sj->completed_at?->format('d-m-Y H:i:s') ?? '-')),
            'Data Fingerprint (SHA-256)' => '<span style="font-family:courier;font-size:7px">' . $this->fingerprint($sj) . '</span>',
        ];

        $html = '<table cellpadding="3" border="0.3" style="font-size:8.5px">';
        foreach ($info as $k => $v) {
            $safe = in_array($k, ['Status Akhir', 'Created By', 'Data Fingerprint (SHA-256)', 'Rejected'], true) ? $v : $e($v);
            $html .= '<tr><td width="32%" style="background-color:#f1f1f1"><b>' . $e($k) . '</b></td><td width="68%">' . $safe . '</td></tr>';
        }
        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        // --- Signer summary
        $pdf->Ln(2);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Signer Summary', 0, 1);
        $html = '<table cellpadding="3" border="0.3" style="font-size:8px">
            <tr style="background-color:#e5e5e5;font-weight:bold">
              <td width="5%" align="center">#</td><td width="14%">Slot</td><td width="22%">Nama</td>
              <td width="20%">Jabatan / Dept.</td><td width="11%">Status</td><td width="14%">Waktu</td><td width="14%">IP / Stamp</td>
            </tr>';
        foreach ($sj->signers as $s) {
            $html .= '<tr>'
                . '<td align="center">' . $s->step . '</td>'
                . '<td>' . $e($s->slotLabel()) . '</td>'
                . '<td>' . $e($s->signer_name) . '<br/><span style="color:#666">' . $e($s->user?->email ?: '-') . '</span></td>'
                . '<td>' . $e($s->signer_position ?: '-') . '<br/><span style="color:#666">' . $e($s->signer_departement ?: '-') . '</span></td>'
                . '<td>' . $e($s->status) . '</td>'
                . '<td>' . $e($s->signed_at?->format('d-m-Y H:i:s') ?? '-') . '</td>'
                . '<td>' . $e($s->ip_address ?: '-') . '<br/><span style="color:#666">' . $e($s->stamp_type ?: '-') . '</span></td>'
                . '</tr>';
        }
        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        // --- Chronological event log
        $pdf->Ln(2);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Chronological Event Log', 0, 1);
        $html = '<table cellpadding="3" border="0.3" style="font-size:7.5px">
            <thead><tr style="background-color:#e5e5e5;font-weight:bold">
              <td width="4%" align="center">#</td><td width="15%">Timestamp</td><td width="15%">Event</td>
              <td width="20%">Actor</td><td width="28%">Keterangan</td><td width="18%">IP / Device</td>
            </tr></thead>';
        foreach ($logs as $i => $log) {
            $html .= '<tr nobr="true">'
                . '<td width="4%" align="center">' . ($i + 1) . '</td>'
                . '<td width="15%">' . $e($log->created_at->format('d-m-Y H:i:s')) . '</td>'
                . '<td width="15%"><b>' . $e(DocumentAuditLogger::label($log->action)) . '</b></td>'
                . '<td width="20%">' . $e($log->actor_name ?: 'System') . '<br/><span style="color:#666">'
                    . $e(collect([$log->actor_email, $log->actor_position])->filter()->implode(' · ')) . '</span></td>'
                . '<td width="28%">' . $e($log->description) . '</td>'
                . '<td width="18%">' . $e($log->ip_address ?: '-') . '<br/><span style="color:#666">' . $e(self::describeUserAgent($log->user_agent)) . '</span></td>'
                . '</tr>';
        }
        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Ln(2);
        $pdf->SetFont('helvetica', 'I', 7.5);
        $pdf->SetTextColor(90, 90, 90);
        $pdf->MultiCell(0, 4, 'Metode autentikasi seluruh aksi: Authenticated Internal Session (login aplikasi, tanpa OTP). '
            . 'Seluruh waktu dalam zona ' . config('app.timezone') . '. Laporan ini dibentuk langsung dari tabel document_audit_logs '
            . '(single source of truth) dan terpisah dari PDF Surat Jalan.', 0, 'L');

        return $pdf->Output('', 'S');
    }

    /** Ringkas user agent → "Chrome · Windows" */
    public static function describeUserAgent(?string $ua): string
    {
        if (!$ua) {
            return '-';
        }
        if ($ua === 'system') {
            return 'System';
        }
        $browser = match (true) {
            str_contains($ua, 'Edg/')     => 'Edge',
            str_contains($ua, 'OPR/')     => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/')  => 'Chrome',
            str_contains($ua, 'Safari/')  => 'Safari',
            default                       => 'Browser',
        };
        $os = match (true) {
            str_contains($ua, 'Windows')                                  => 'Windows',
            str_contains($ua, 'Android')                                  => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')      => 'iOS',
            str_contains($ua, 'Mac OS')                                   => 'macOS',
            str_contains($ua, 'Linux')                                    => 'Linux',
            default                                                       => 'Unknown OS',
        };
        return "{$browser} · {$os}";
    }

    /** Sidik jari isi dokumen (header + item + tanda tangan) untuk bukti integritas data. */
    public function fingerprint(SuratJalan $sj): string
    {
        $sj->loadMissing(['items', 'signers']);
        return hash('sha256', json_encode([
            'uuid'     => $sj->uuid,
            'sj_no'    => $sj->sj_no,
            'kepada'   => $sj->kepada,
            'kend'     => $sj->no_kendaraan,
            'kategori' => $sj->kategori,
            'lain'     => $sj->kategori_lain,
            'items'    => $sj->items->map(fn ($i) => [$i->line_no, $i->jenis_barang, (string) $i->qty, $i->satuan, $i->keterangan])->all(),
            'signers'  => $sj->signers->map(fn ($s) => [$s->step, $s->user_id, $s->status, $s->signed_at?->format('Y-m-d H:i:s.v')])->all(),
        ]));
    }

    /* =====================================================================
     |  Helpers
     * =================================================================== */

    private function newPdf(string $title, string $footerNote): TCPDF
    {
        $pdf = new class('P', 'mm', 'A4', true, 'UTF-8', false) extends TCPDF {
            public string $footerNote = '';

            public function Footer()
            {
                $this->SetY(-12);
                $this->SetFont('helvetica', '', 6.5);
                $this->SetTextColor(110, 110, 110);
                $this->MultiCell(150, 3, $this->footerNote, 0, 'L', false, 0);
                $this->Cell(0, 3, 'Hal. ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');
            }
        };
        $pdf->footerNote = $footerNote;
        $pdf->SetCreator(config('app.name'));
        $pdf->SetAuthor(config('surat_jalan.company.name'));
        $pdf->SetTitle($title);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(self::M_LEFT, 12, self::M_RIGHT);
        $pdf->SetAutoPageBreak(true, self::FOOTER_H);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(30, 30, 30);

        return $pdf;
    }

    private function watermark(TCPDF $pdf, string $text, array $rgb): void
    {
        $pages = $pdf->getNumPages();
        $auto = $pdf->getAutoPageBreak();
        $pdf->SetAutoPageBreak(false);
        for ($p = 1; $p <= $pages; $p++) {
            $pdf->setPage($p);
            // Satu watermark di tengah tiap format (atas & bawah), karena kertas akan dirobek dua.
            foreach ([self::HALF_H / 2, self::HALF_H * 1.5] as $cy) {
                $pdf->SetAlpha(0.12);
                $pdf->SetFont('helvetica', 'B', 46);
                $pdf->SetTextColor(...$rgb);
                $pdf->StartTransform();
                $pdf->Rotate(20, self::PAGE_W / 2, $cy);
                $pdf->SetXY(0, $cy - 10);
                $pdf->Cell(self::PAGE_W, 20, $text, 0, 0, 'C');
                $pdf->StopTransform();
                $pdf->SetAlpha(1);
            }
        }
        $pdf->SetTextColor(20, 20, 20);
        $pdf->SetAutoPageBreak($auto, self::FOOTER_H);
        $pdf->lastPage();
    }
}
