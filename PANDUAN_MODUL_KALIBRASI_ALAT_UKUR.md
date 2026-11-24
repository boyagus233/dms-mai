# Panduan dan Spesifikasi Modul Kalibrasi Alat Ukur
**Sistem E-Signature & Quality Management System (QMS)**

Dokumen ini merangkum seluruh konsep, alur kerja, arsitektur database, dan panduan teknis implementasi untuk modul **Kalibrasi Alat Ukur (Internal & Eksternal)**.

---

## 1. Latar Belakang & Ringkasan Konsep

Di lingkungan manufaktur/pabrik, perusahaan memiliki ratusan hingga ribuan alat ukur presisi (*Digital Caliper, Micrometer, Dial Indicator, Analytical Balance, Multimeter*, dll.) sebagaimana tercatat pada master data inventaris `Alat Ukur.xlsx`.

Setiap alat ukur memiliki masa berlaku kalibrasi wajib (umumnya **1 tahun sekali** / 12 bulan) guna menjamin keabsahan pengukuran dan memenuhi kepatuhan standar ISO 9001 / IATF 16949.

### Masalah yang Diselesaikan:
1. **Pemantauan Jatuh Tempo Otomatis**: Karyawan tidak perlu lagi memeriksa tanggal kalibrasi secara manual. Saat membuka menu, sistem otomatis menyortir alat ukur dari yang paling mendekati jatuh tempo kalibrasi (*due date* terdekat).
2. **Digitalisasi Sertifikat Hybrid**: Menggabungkan data form sertifikat baku perusahaan dengan file PDF lampiran data teknis pengukuran manual.
3. **Approval Cepat & Efisien**: 
   - Untuk **Kalibrasi Internal**: Auto-routing ke atasan tanpa perlu memilih atasan manual + 1-Click Approve dengan cap tanda tangan digital otomatis pada template.
   - Untuk **Kalibrasi Eksternal**: Bypass approval (auto-close) saat alat fisik kembali dari vendor bersertifikat.

---

## 2. Perbandingan Alur: Kalibrasi Internal vs Kalibrasi Eksternal

| Parameter | 🛠️ Kalibrasi Internal (KI) | 🚚 Kalibrasi Eksternal (KE) |
| :--- | :--- | :--- |
| **Definisi & Pelaksana** | Dikerjakan sendiri oleh teknisi QA/Metrologi di lab internal perusahaan | Dikerjakan oleh laboratorium kalibrasi pihak ketiga (vendor akreditasi KAN/ISO 17025) |
| **Lokasi Fisik Alat** | Alat tetap berada di dalam pabrik / lab QA | Alat keluar pabrik dibawa vendor (`OUT_OF_PREMISE` / Sedang Kalibrasi) |
| **Bentuk Dokumen** | **Hybrid PDF**: Halaman 1 = Template Sertifikat Resmi + Halaman 2 dst. = Lembar Data Ukur Manual Teknisi | **PDF Sertifikat Vendor**: File PDF resmi yang diterbitkan dan dicap oleh vendor |
| **Alur Persetujuan** | **Wajib Approval Atasan**: Auto-routing ke Manager QA | **Bypass Approval (Auto-Close)**: Langsung selesai saat sertifikat diunggah |
| **Next Due Date** | Otomatis +1 tahun (12 bulan) dari tanggal kalibrasi internal | Otomatis +1 tahun (12 bulan) dari tanggal sertifikat vendor |

---

## 3. Detail Alur Pengguna (Step-by-Step User Flow)

```mermaid
flowchart TD
    Start([Karyawan Buka Menu Kalibrasi]) --> Tab[Dashboard: Perlu Kalibrasi\nSort by Due Date Terdekat]
    
    Tab --> Choice{Pilih Tipe Kalibrasi}
    
    %% Alur Internal
    Choice -- "Klik 'Kalibrasi Internal'" --> KI1[Form Auto-Fill: Data Alat Terisi]
    KI1 --> KI2[Input Tgl Kalibrasi, Kesimpulan, Upload PDF Lembar Ukur]
    KI2 --> KI3[Submit: Auto-Routing ke Atasan via Database]
    KI3 --> KI4[Atasan Buka Antrean: Live Preview Hal 1 + Hal 2]
    KI4 --> KI5{Review Atasan}
    KI5 -- Reject --> KIR[Ditolak: Catat Alasan Penolakan]
    KI5 -- Approve --> KIA[1-Click Approve: Auto-Stamp TTD PNG]
    KIA --> KIFinal[Sistem Merge & Flatten PDF Final]
    KIFinal --> KIDone[Status: Selesai | Next Due Date: +1 Tahun]

    %% Alur Eksternal
    Choice -- "Klik 'Kalibrasi Eksternal'" --> KE1[Status Alat: Sedang Kalibrasi / OUT_OF_PREMISE]
    KE1 --> KE2[Alat Dibawa Vendor | Masuk Tab Pending Return]
    KE2 --> KE3[Beberapa Hari Kemudian: Alat Kembali]
    KE3 --> KE4[Klik 'Barang Kembali' di Tab Pending Return]
    KE4 --> KE5[Input Nama Vendor, Tgl Sertifikat, Upload PDF Vendor]
    KE5 --> KEDone[Bypass Approval: Auto-Close & Arsipkan Dokumen]
    KEDone --> KEFin[Status: Selesai | Next Due Date: +1 Tahun]
```

### A. Alur Kalibrasi Internal (KI)
1. **Pilih Alat di Menu "Perlu Kalibrasi":**
   - Karyawan memilih alat yang masuk jatuh tempo, lalu klik tombol **`Kalibrasi Internal`**.
2. **Form Auto-Fill Sederhana:**
   - Semua data alat (Kode Aset, Nama Alat, Merk/Model, No. Seri, Rentang Ukur, Lokasi) otomatis ditarik dari database.
   - Karyawan hanya menginput:
     - **Tanggal Kalibrasi**: (sistem otomatis menghitung *Next Calibration Date = +1 tahun*).
     - **Kesimpulan**: *Laik Pakai (Pass)*, *Laik dengan Catatan*, atau *Tidak Laik (Fail)*.
     - **Upload PDF Lembar Ukur**: File PDF berisi tabel pengukuran, data komparasi standar, atau grafik.
3. **Auto-Routing (Tanpa Pilih Atasan):**
   - Karyawan menekan **`Submit`**.
   - Sistem otomatis membaca data organisasi karyawan dan langsung mengirim berkas ke antrean Manager/Atasan tanpa user perlu memilih nama secara manual.
4. **One-Click Approval & Fixed Stamping:**
   - Atasan membuka antrean persetujuan dan melihat preview gabungan:
     - **Halaman 1**: Template Draf Sertifikat Resmi Perusahaan.
     - **Halaman 2 dst.**: File PDF lembar data teknis teknisi.
   - Atasan klik tombol **`Approve`**.
   - Sistem mengambil aset tanda tangan PNG atasan dan menempelkannya tepat pada koordinat baku template.
   - Dokumen di-flatten menjadi **1 file PDF final** dan tersimpan permanen di arsip.

---

### B. Alur Kalibrasi Eksternal (KE)
1. **Tandai Keluar (Out of Premise):**
   - Karyawan memilih alat di menu **Perlu Kalibrasi**, lalu klik **`Kalibrasi Eksternal`**.
   - Status alat di sistem langsung berubah menjadi **`Sedang Kalibrasi (OUT_OF_PREMISE)`**. Alat fisik diserahkan ke driver vendor.
2. **Tracking di Tab "Pending Return":**
   - Seluruh alat yang sedang di luar terpantau di tab **Pending Return**.
3. **Penerimaan Barang & Upload Sertifikat:**
   - Ketika alat fisik diantar kembali oleh vendor:
   - Karyawan membuka tab **Pending Return**, lalu klik tombol **`Barang Kembali`**.
   - Karyawan hanya menginput:
     - **Nama Vendor**: Terintegrasi dengan dropdown **Master Vendor**.
     - **Tanggal Kalibrasi**: Sesuai tanggal sertifikat vendor (sistem otomatis set *Next Calibration = +1 tahun*).
     - **Upload PDF Sertifikat Vendor**: File PDF scan resmi dari vendor.
4. **Bypass Approval (Auto-Close):**
   - Dokumen langsung berstatus **Completed / Closed** tanpa perlu dikirim ke atasan lagi.
   - Status alat kembali menjadi **`ACTIVE`** dan sertifikat vendor tersimpan sebagai riwayat sah.

---

## 4. Struktur Antarmuka Pengguna (UI Tabs)

Modul ini ditempatkan pada sidebar utama portal:

```
├── Kalibrasi Alat Ukur
│   ├── Tab 1: Perlu Kalibrasi      (Dashboard Utama - Sort Next Due Terdekat)
│   ├── Tab 2: Pending Return       (Tracking Alat di Vendor Eksternal)
│   ├── Tab 3: Antrean Approval     (Khusus Akun Manager / Atasan)
│   ├── Tab 4: Arsip Sah            (Database Riwayat Semua Sertifikat)
│   └── Tab 5: Master Alat Ukur     (Master Data 1.000 Alat Ukur dari Excel)
```

### Rincian Tampilan Tab:
1. **Tab 1: Perlu Kalibrasi**:
   - Menampilkan tabel alat ukur terurut otomatis dari yang paling mendesak (`next_calibration_date` ASC).
   - Indikator Warna Status Jatuh Tempo:
     - 🔴 **Lewat Jatuh Tempo** (Overdue / Merah)
     - 🟡 **Mendekati Expired** ($\le 30$ hari / Kuning)
     - 🟢 **Jadwal Normal** (Hijau)
   - Tombol Aksi di setiap baris:
     - Tombol Biru: `[ Kalibrasi Internal ]`
     - Tombol Oranye: `[ Kalibrasi Eksternal ]`
2. **Tab 2: Pending Return**:
   - Menampilkan alat yang statusnya `OUT_OF_PREMISE`.
   - Tombol Aksi: `[ Barang Kembali / Upload Sertifikat ]`.
3. **Tab 3: Antrean Approval**:
   - Menampilkan dokumen yang statusnya `PENDING_APPROVAL`.
   - Menampilkan viewer dokumen gabungan dan tombol 1-Click `Approve` / `Reject`.
4. **Tab 4: Arsip Sah**:
   - Seluruh sertifikat kalibrasi yang sudah selesai beserta link download PDF utuh dan tombol *Cetak Audit History*.

---

## 5. Rancangan Skema Database (Database Schema)

### A. Tabel Master Alat Ukur (`m_alat_ukur`)
```sql
CREATE TABLE m_alat_ukur (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_code                  VARCHAR(50)  NOT NULL UNIQUE COMMENT 'No. Inventaris / Kode Alat',
    name                        VARCHAR(150) NOT NULL COMMENT 'Nama Alat Ukur',
    category_type               ENUM('KI', 'KE') NOT NULL DEFAULT 'KI' COMMENT 'Tipe Default: Kalibrasi Internal / Eksternal',
    standard_spec               VARCHAR(100) NULL COMMENT 'Spesifikasi Standar (misal: 300/0.01)',
    brand_model                 VARCHAR(120) NULL COMMENT 'Merk / Model',
    serial_number               VARCHAR(100) NULL COMMENT 'Nomor Seri Alat',
    calibrator_ref              VARCHAR(150) NULL COMMENT 'Standar Kalibrator (misal: Caliper Checker, Block Gauge)',
    location                    VARCHAR(100) NULL COMMENT 'Area / Lab / Line (misal: QA, Machining)',
    calibration_interval_months TINYINT UNSIGNED NOT NULL DEFAULT 12 COMMENT 'Interval Kalibrasi (Default 12 bulan)',
    last_calibration_date       DATE         NULL COMMENT 'Tanggal Kalibrasi Terakhir',
    next_calibration_date       DATE         NOT NULL COMMENT 'Tanggal Jatuh Tempo Kalibrasi Berikutnya',
    current_status              ENUM(
                                    'ACTIVE',
                                    'PENDING_APPROVAL',
                                    'OUT_OF_PREMISE',
                                    'BROKEN',
                                    'RETIRED'
                                ) NOT NULL DEFAULT 'ACTIVE',
    photo_path                  VARCHAR(255) NULL,
    notes                       TEXT         NULL,
    created_at                  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### B. Tabel Transaksi Kalibrasi (`t_kalibrasi`)
```sql
CREATE TABLE t_kalibrasi (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    certificate_number          VARCHAR(100) NOT NULL UNIQUE COMMENT 'CAL/INT/YYYY/MM/XXXX atau No. Sertifikat Vendor',
    alat_ukur_id                BIGINT UNSIGNED NOT NULL,
    calibration_type            ENUM('INTERNAL', 'EKSTERNAL') NOT NULL,
    calibration_date            DATE NOT NULL,
    next_due_date               DATE NOT NULL,
    conclusion                  ENUM('PASS', 'PASS_WITH_NOTE', 'FAIL') NOT NULL DEFAULT 'PASS',
    technician_id               BIGINT UNSIGNED NOT NULL COMMENT 'Karyawan yang mengajukan / menginput',
    approver_id                 BIGINT UNSIGNED NULL COMMENT 'Manager/Atasan yang menyetujui (Khusus Internal)',
    vendor_id                   BIGINT UNSIGNED NULL COMMENT 'ID Vendor dari m_vendor (Khusus Eksternal)',
    vendor_name                 VARCHAR(200) NULL,
    raw_attachment_pdf          VARCHAR(255) NULL COMMENT 'PDF Lembar Ukur Teknisi atau PDF Asli Vendor',
    final_merged_pdf            VARCHAR(255) NULL COMMENT 'PDF Utuh (Halaman 1 Sertifikat + Halaman Lampiran)',
    status                      ENUM('DRAFT', 'PENDING_APPROVAL', 'COMPLETED', 'REJECTED') NOT NULL DEFAULT 'DRAFT',
    rejection_note              TEXT NULL,
    approved_at                 TIMESTAMP NULL,
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (alat_ukur_id) REFERENCES m_alat_ukur(id) ON DELETE CASCADE
);
```

---

## 6. Spesifikasi Teknis Pembuatan PDF & Auto-Stamp

1. **Template Halaman 1 (Sertifikat Standar):**
   - Dibuat dari view Blade (`resources/views/content/kalibrasi/pdf/certificate.blade.php`).
   - Dikonversi ke file PDF sementara menggunakan DomPDF.
   - Posisi kotak tanda tangan teknisi dan atasan memiliki koordinat fixed yang presisi.
2. **Penggabungan PDF (PDF Merging):**
   - Menggunakan library **FPDI** (`setasign/fpdi`).
   - FPDI membaca Halaman 1 dari template sertifikat, lalu membaca file PDF lampiran teknisi dan memasukkannya sebagai Halaman 2, 3, dst.
3. **Auto-Load Image Stamp:**
   - Gambar tanda tangan PNG atasan diambil dari folder `storage/app/public/signature/...`.
   - Tanda tangan otomatis ditempelkan ke Halaman 1 saat tombol *Approve* ditekan.
4. **Audit History Log:**
   - Semua tahapan (pengajuan, tanggal, IP, viewed, signed, approved) otomatis tercatat pada tabel `document_audit_logs`.

---

## 7. Langkah Implementasi Selanjutnya

- [ ] **Langkah 1**: Buat migration dan model `AlatUkur` & `Kalibrasi`.
- [ ] **Langkah 2**: Import 1.000 data dari `Alat Ukur.xlsx` menggunakan Seeder.
- [ ] **Langkah 3**: Buat Service PDF Merge (FPDI) dan template sertifikat Blade.
- [ ] **Langkah 4**: Buat Controller, Routes, dan View Blade (Tab Perlu Kalibrasi, Pending Return, Approval, dan Arsip).
- [ ] **Langkah 5**: Integrasikan Auto-Routing atasan berdasarkan departemen/organisasi.
- [ ] **Langkah 6**: Pengujian menyeluruh dan deployment ke Docker VM.
