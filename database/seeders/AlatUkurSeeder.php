<?php

namespace Database\Seeders;

use App\Models\AlatUkur;
use App\Models\Kalibrasi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class AlatUkurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultUser = User::first();
        $defaultUserId = $defaultUser ? $defaultUser->id : 1;

        // 1. Seed Master Alat Ukur (598 alat ukur data real)
        $jsonPath = database_path('seeders/data/alat_ukur.json');
        if (!File::exists($jsonPath)) {
            $this->command->error("File alat_ukur.json tidak ditemukan di {$jsonPath}");
            return;
        }

        $json = File::get($jsonPath);
        $tools = json_decode($json, true);

        if (!is_array($tools)) {
            $this->command->error("Gagal mendecode alat_ukur.json");
            return;
        }

        $count = 0;
        foreach ($tools as $item) {
            AlatUkur::updateOrCreate(
                ['asset_code' => $item['asset_code']],
                [
                    'name'                        => $item['name'],
                    'category_type'               => $item['category_type'] ?? 'KI',
                    'standard_spec'               => $item['standard_spec'] ?? null,
                    'brand_model'                 => $item['brand_model'] ?? null,
                    'serial_number'               => $item['serial_number'] ?? null,
                    'calibrator_ref'              => $item['calibrator_ref'] ?? null,
                    'location'                    => $item['location'] ?? 'QA',
                    'calibration_interval_months' => $item['calibration_interval_months'] ?? 12,
                    'last_calibration_date'       => $item['last_calibration_date'] ?? null,
                    'next_calibration_date'       => $item['next_calibration_date'],
                    'current_status'              => $item['current_status'] ?? 'ACTIVE',
                    'photo_path'                  => $item['photo_path'] ?? null,
                    'notes'                       => $item['notes'] ?? null,
                    'created_by'                  => $defaultUserId,
                ]
            );
            $count++;
        }

        $this->command->info("Berhasil mengimpor {$count} alat ukur ke tabel m_alat_ukur.");

        // 2. Seed Riwayat Transaksi Kalibrasi (1.302 transaksi data real)
        $transPath = database_path('seeders/data/riwayat_kalibrasi.json');
        if (File::exists($transPath)) {
            $transJson = File::get($transPath);
            $transData = json_decode($transJson, true);

            if (is_array($transData)) {
                $toolsByCode = AlatUkur::pluck('id', 'asset_code');
                $transCount = 0;

                foreach ($transData as $t) {
                    $toolId = $toolsByCode[$t['asset_code']] ?? null;
                    if (!$toolId) {
                        continue;
                    }

                    Kalibrasi::updateOrCreate(
                        ['certificate_number' => $t['certificate_number']],
                        [
                            'alat_ukur_id'       => $toolId,
                            'calibration_type'   => $t['calibration_type'] ?? 'INTERNAL',
                            'calibration_date'   => $t['calibration_date'],
                            'next_due_date'      => $t['next_due_date'],
                            'conclusion'         => $t['conclusion'] ?? 'PASS',
                            'technician_id'      => $defaultUserId,
                            'approver_id'        => $defaultUserId,
                            'vendor_name'        => $t['vendor_name'] ?? null,
                            'calibrator_used'    => $t['calibrator_used'] ?? null,
                            'status'             => $t['status'] ?? 'COMPLETED',
                            'approved_at'        => $t['approved_at'],
                            'created_by'         => $defaultUserId,
                        ]
                    );
                    $transCount++;
                }

                $this->command->info("Berhasil mengimpor {$transCount} riwayat kalibrasi ke tabel t_kalibrasi.");
            }
        }
    }
}
