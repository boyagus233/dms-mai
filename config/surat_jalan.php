<?php

return [

    /*
    | Identitas perusahaan pada kop PDF Surat Jalan.
    | Lengkapi alamat sesuai kop form fisik (bisa juga lewat .env).
    */
    'company' => [
        'name'    => env('SJ_COMPANY_NAME', 'PT. METALART ASTRA INDONESIA'),
        'address' => env('SJ_COMPANY_ADDRESS', 'Karawang, Jawa Barat - Indonesia'),
        'phone'   => env('SJ_COMPANY_PHONE', ''),
        // path relatif dari folder public/
        'logo'    => env('SJ_COMPANY_LOGO', 'assets/img/surat-jalan/logo-metalart.png'),
    ],

    'default_location' => 'Karawang',

    // Prefix nomor: SJ/{KODE_DEPT}/{YYYY}/{MM}/{XXXX} — urut ulang tiap departemen per bulan.
    'number_prefix' => 'SJ',
    'number_digits' => 4,

    // Kode m_departement yang dipakai untuk menyaring pilihan signer slot Pemeriksa (HRD/GA).
    'pemeriksa_departement_code' => env('SJ_PEMERIKSA_DEPT', 'HR'),

    // Role (m_role.code) yang boleh melihat SEMUA Surat Jalan (selain pembuat & signer).
    'viewer_roles' => ['ADM'],

    'kepada_tipe' => [
        'SUPPLIER'      => 'Supplier',
        'CUSTOMER'      => 'Customer',
        'VENDOR_REPAIR' => 'Vendor Repair',
        'LAINNYA'       => 'Lainnya',
    ],

    // Kategori / tujuan pengeluaran barang (sesuai checklist form fisik).
    'kategori' => [
        'DIBUANG'         => 'Dibuang (Scrap / Disposal)',
        'TUKAR_GULING'    => 'Tukar Guling',
        'DIPERBAIKI'      => 'Diperbaiki / Direpair',
        'SAMPLE_SUPPLIER' => 'Sample ke Supplier',
        'SAMPLE_CUSTOMER' => 'Sample ke Customer',
        'LAIN_LAIN'       => 'Lain-lain',
    ],

    // 4 slot tanda tangan tetap (urutan = urutan approval sequential).
    'slots' => [
        1 => ['code' => 'DISERAHKAN', 'label' => 'Diserahkan', 'role' => 'Pemohon / PIC'],
        2 => ['code' => 'MENGETAHUI', 'label' => 'Mengetahui', 'role' => 'Dept. Head'],
        3 => ['code' => 'PEMERIKSA',  'label' => 'Pemeriksa',  'role' => 'HRD / GA'],
        4 => ['code' => 'DITERIMA',   'label' => 'Diterima',   'role' => 'Penerima / Security'],
    ],

    'satuan' => ['PCS', 'SET', 'UNIT', 'KG', 'BOX', 'LOT', 'ROLL', 'LEMBAR', 'METER', 'LITER', 'DUS', 'PALLET'],
];
