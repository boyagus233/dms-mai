<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    // User::factory(10)->create();

      User::factory()->create([
          'name' => 'Admin',
          'user_name' => 'admin',
          'email' => 'admin@example.com',
          'password' => '$2a$10$UdKpDEcQ589d9C6Q.UMhaOZ2Q3tGOS1d65ao3mQ1ap1qsJLY6TXPO'
      ]);
  }
}
