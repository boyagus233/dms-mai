<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/vendors.json');
        if (!File::exists($jsonPath)) {
            $this->command->error("File vendors.json not found at: {$jsonPath}");
            return;
        }

        $vendors = json_decode(File::get($jsonPath), true);
        if (empty($vendors)) {
            $this->command->error("vendors.json is empty or invalid JSON.");
            return;
        }

        $now = now();
        $batch = [];
        $total = 0;

        foreach ($vendors as $v) {
            $batch[] = [
                'code'          => $v['code'],
                'name'          => $v['name'],
                'official_name' => $v['official_name'] ?? null,
                'address'       => $v['address'] ?? null,
                'postal_code'   => $v['postal_code'] ?? null,
                'phone'         => $v['phone'] ?? null,
                'fax'           => $v['fax'] ?? null,
                'pic_name'      => $v['pic_name'] ?? null,
                'email'         => $v['email'] ?? null,
                'notes'         => $v['notes'] ?? null,
                'is_supplier'   => $v['is_supplier'] ?? 0,
                'is_customer'   => $v['is_customer'] ?? 0,
                'status'        => 1,
                'created_by'    => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];

            if (count($batch) >= 100) {
                DB::table('m_vendor')->upsert(
                    $batch,
                    ['code'],
                    ['name', 'official_name', 'address', 'postal_code', 'phone', 'fax', 'pic_name', 'email', 'notes', 'is_supplier', 'is_customer', 'status', 'updated_at']
                );
                $total += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('m_vendor')->upsert(
                $batch,
                ['code'],
                ['name', 'official_name', 'address', 'postal_code', 'phone', 'fax', 'pic_name', 'email', 'notes', 'is_supplier', 'is_customer', 'status', 'updated_at']
            );
            $total += count($batch);
        }

        $this->command->info("Successfully seeded {$total} vendors into m_vendor table.");
    }
}
