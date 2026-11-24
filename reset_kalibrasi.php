<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AlatUkur;
use App\Models\Kalibrasi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

echo "=== MEMULAI RESET DATA KALIBRASI ===\n";

// 1. Hapus semua riwayat transaksi kalibrasi
$deletedTrans = Kalibrasi::count();
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
Kalibrasi::truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');
echo "1. Dihapus {$deletedTrans} data dari tabel t_kalibrasi.\n";

// 2. Bersihkan file PDF uji coba di storage kalibrasi
Storage::disk('public')->deleteDirectory('kalibrasi');
echo "2. Folder storage/app/public/kalibrasi berhasil dibersihkan.\n";

// 3. Reset semua alat ukur ke tanggal hari ini
$today = Carbon::today()->toDateString();
$updatedTools = AlatUkur::query()->update([
    'next_calibration_date' => $today,
    'last_calibration_date' => null,
    'current_status'        => 'ACTIVE',
]);
echo "3. Diperbarui {$updatedTools} alat ukur: next_calibration_date diset ke {$today}, status ACTIVE, riwayat kosong.\n";

echo "=== SELESAI ===\n";
