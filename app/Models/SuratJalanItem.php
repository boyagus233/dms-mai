<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratJalanItem extends Model
{
    public $table = 'trx_surat_jalan_items';

    protected $fillable = [
        'surat_jalan_id',
        'line_no',
        'jenis_barang',
        'qty',
        'satuan',
        'keterangan',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];

    /** Qty tanpa desimal nol berlebih: 10.00 -> "10", 2.50 -> "2,5" */
    public function qtyFormatted(): string
    {
        $qty = rtrim(rtrim(number_format((float) $this->qty, 2, ',', '.'), '0'), ',');
        return trim($qty . ' ' . ($this->satuan ?? ''));
    }

    public function suratJalan()
    {
        return $this->belongsTo(SuratJalan::class, 'surat_jalan_id');
    }
}
