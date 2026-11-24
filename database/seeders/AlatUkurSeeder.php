<?php

namespace Database\Seeders;

use App\Models\AlatUkur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class AlatUkurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
                    'created_by'                  => 1,
                ]
            );
            $count++;
        }

        $this->command->info("Berhasil mengimpor {$count} alat ukur ke tabel m_alat_ukur.");
    }
}
