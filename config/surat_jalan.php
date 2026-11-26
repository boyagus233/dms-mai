<?php

return [

    /*
    | Identitas perusahaan pada kop PDF Surat Jalan.
    | Default mengikuti kop form fisik "Form Surat Jalan Keluar" (bisa di-override lewat .env).
    */
    'company' => [
        'tagline' => env('SJ_COMPANY_TAGLINE', '"To the infinite development and harmony of the technology and human being"'),
        'name'    => env('SJ_COMPANY_NAME', 'PT. METALART ASTRA INDONESIA'),
        'address' => env('SJ_COMPANY_ADDRESS', "Kawasan Industri KIIC, Jl. Harapan III Lot-JJ2A, Desa Sirnabaya,\nKecamatan Teluk Jambe Timur, Karawang 41361 Jawa Barat"),
        'phone'   => env('SJ_COMPANY_PHONE', '(021) 2936 9960, (0267) 7863 9862, Fax : (021) 2936 9965'),
        // path relatif dari folder public/
        'logo'    => env('SJ_COMPANY_LOGO', 'assets/img/surat-jalan/logo-metalart.png'),
    ],

    'default_location' => 'Karawang',

    // Format nomor: {No Urut}/{KODE_DEPT}/{KODE_PERUSAHAAN}/{Bulan Romawi}/{Tahun}
    // Contoh: 001/QA/MAI/XI/2026 — no urut per departemen, mulai lagi dari 001 setiap bulan baru.
    'company_code'  => env('SJ_COMPANY_CODE', 'MAI'),
    'number_digits' => 3,

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

    // Urutan kotak dari kiri ke kanan pada lembar Surat Jalan (berisi nomor step),
    // mengikuti template fisik: Pemeriksa | Mengetahui | Diserahkan | Diterima.
    'slot_layout' => [3, 2, 1, 4],

    // Keterangan distribusi lembar (dicetak di bawah kotak tanda tangan).
    'lembar' => [
        'Lembar 1' => 'Pemilik Barang',
        'Lembar 2' => 'Penerima',
        'Lembar 3' => 'Security',
    ],

    'satuan' => ['PCS', 'SET', 'UNIT', 'KG', 'BOX', 'LOT', 'ROLL', 'LEMBAR', 'METER', 'LITER', 'DUS', 'PALLET'],
];
