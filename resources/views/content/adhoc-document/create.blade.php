@extends('layouts/contentNavbarLayout')

@section('title', 'Upload & Request Signature - All Document Approval')

@section('styles')
<style>
    .workflow-card {
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        border: 2px solid #e0e2e8;
    }
    .workflow-card:hover {
        border-color: #666cff;
    }
    .workflow-card.active {
        border-color: #666cff;
        background-color: rgba(102, 108, 255, 0.04);
    }
    .dropzone-box {
        border: 2px dashed #b4b7c5;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        background: #fcfcfd;
        cursor: pointer;
        transition: border 0.3s ease;
    }
    .dropzone-box:hover {
        border-color: #666cff;
        background: #f7f7fc;
    }
    .signer-row {
        background: #fbfbfd;
        border: 1px solid #e7e8ee;
        border-radius: 6px;
        padding: 12px 15px;
        margin-bottom: 10px;
        transition: all 0.2s;
    }
    .signer-row:hover {
        border-color: #c4c7d6;
    }
    .signer-handle {
        cursor: grab;
        color: #999;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-3">
                <li class="breadcrumb-item"><a href="{{ route('adhoc-document.index') }}">All Document Approval</a></li>
                <li class="breadcrumb-item active">Upload &amp; Request Signature</li>
            </ol>
        </nav>

        <form action="{{ route('adhoc-document.store') }}" method="POST" enctype="multipart/form-data" id="form-upload-adhoc">
            @csrf

            <!-- Card 1: Informasi Dokumen & Upload -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header border-bottom py-3">
                    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="ri-file-upload-line text-primary fs-4"></i>
                        <span>Langkah 1: Upload File &amp; Informasi Dokumen</span>
                    </h5>
                </div>
                <div class="card-body pt-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold" for="title">Judul Dokumen <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" placeholder="Contoh: Perjanjian Kerja Sama Vendor PT ABC" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold" for="description">Deskripsi / Catatan Tambahan (Opsional)</label>
                            <textarea class="form-control" id="description" name="description" rows="2" placeholder="Tuliskan catatan khusus atau instruksi bagi penanda tangan...">{{ old('description') }}</textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Pilih File PDF Dokumen <span class="text-danger">*</span></label>
                            <div class="dropzone-box" onclick="document.getElementById('document_file').click()">
                                <i class="ri-file-pdf-2-line fs-1 text-primary d-block mb-2"></i>
                                <h6 class="mb-1" id="file-label-text">Klik untuk memilih file PDF atau drag &amp; drop ke sini</h6>
                                <p class="text-muted small mb-0">Hanya format PDF asli (Maksimal 25 MB). Tanpa template khusus.</p>
                            </div>
                            <input type="file" class="d-none @error('document_file') is-invalid @enderror" id="document_file" name="document_file" accept=".pdf,application/pdf" required onchange="handleFileSelected(this)">
                            @error('document_file')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Pengaturan Mode Urutan Signer -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header border-bottom py-3">
                    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="ri-git-merge-line text-primary fs-4"></i>
                        <span>Langkah 2: Pilih Mode Urutan Tanda Tangan</span>
                    </h5>
                </div>
                <div class="card-body pt-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card workflow-card p-3 active" id="card-seq" onclick="selectWorkflow('SEQUENTIAL')">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="radio" name="workflow_type" id="flow-seq" value="SEQUENTIAL" checked>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-bold text-primary"><i class="ri-arrow-right-line me-1"></i> Sequential (Berurutan)</h6>
                                        <p class="text-muted small mb-0">Signer 1 &rarr; Signer 2 &rarr; Signer 3. Email dikirim bertahap sesuai giliran setelah signer sebelumnya menyetujui.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card workflow-card p-3" id="card-par" onclick="selectWorkflow('PARALLEL')">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="radio" name="workflow_type" id="flow-par" value="PARALLEL">
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-bold text-warning"><i class="ri-shuffle-line me-1"></i> Parallel (Bersamaan)</h6>
                                        <p class="text-muted small mb-0">Semua signer menerima email notifikasi secara serentak dan dapat menandatangani kapan saja tanpa saling menunggu.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Daftar Signer Internal Dinamis -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="ri-user-received-2-line text-primary fs-4"></i>
                        <span>Langkah 3: Tentukan Daftar Penanda Tangan (Signers)</span>
                    </h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSignerRow()">
                        <i class="ri-user-add-line me-1"></i> + Tambah Signer
                    </button>
                </div>
                <div class="card-body pt-4">
                    <p class="text-muted small mb-3">Tentukan daftar user internal yang wajib menandatangani dokumen ini. Anda bisa menambahkan sebanyak mungkin signer yang dibutuhkan.</p>

                    <div id="signers-container">
                        <!-- Default 1 Signer row -->
                    </div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addSignerRow()">
                            <i class="ri-add-line me-1"></i> Tambah Penanda Tangan Lainnya
                        </button>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="d-flex justify-content-between align-items-center mb-5">
                <a href="{{ route('adhoc-document.index') }}" class="btn btn-outline-secondary">
                    <i class="ri-arrow-left-line me-1"></i> Batal
                </a>
                <button type="submit" class="btn btn-primary btn-lg d-flex align-items-center gap-2" id="btn-submit">
                    <span>Lanjut ke Penempatan Tanda Tangan (Visual Tagging)</span>
                    <i class="ri-arrow-right-line"></i>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('page-script')
<script>
    const internalUsers = @json($users);
    let signerRowCount = 0;

    function selectWorkflow(type) {
        document.getElementById('flow-seq').checked = (type === 'SEQUENTIAL');
        document.getElementById('flow-par').checked = (type === 'PARALLEL');
        
        document.getElementById('card-seq').classList.toggle('active', type === 'SEQUENTIAL');
        document.getElementById('card-par').classList.toggle('active', type === 'PARALLEL');
        
        updateSignerOrderBadges();
    }

    function handleFileSelected(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
            document.getElementById('file-label-text').innerHTML = `
                <strong class="text-success"><i class="ri-checkbox-circle-fill me-1"></i> ${file.name}</strong> (${sizeMb} MB)
            `;
        }
    }

    function addSignerRow(defaultUserId = '', defaultRole = '') {
        signerRowCount++;
        const container = document.getElementById('signers-container');
        
        const row = document.createElement('div');
        row.className = 'signer-row d-flex align-items-center gap-3 flex-wrap flex-md-nowrap';
        row.id = `signer-row-${signerRowCount}`;
        
        let userOptions = '<option value="">-- Pilih User / Karyawan Internal --</option>';
        internalUsers.forEach(u => {
            const emp = u.employee;
            const dept = emp && emp.departements ? ` [${emp.departements.name}]` : '';
            const pos = emp && emp.positions ? ` - ${emp.positions.name}` : '';
            const selected = (defaultUserId == u.id) ? 'selected' : '';
            userOptions += `<option value="${u.id}" ${selected}>${u.name} (${u.user_name})${pos}${dept}</option>`;
        });

        row.innerHTML = `
            <div class="d-flex align-items-center gap-2" style="min-width: 100px;">
                <span class="signer-handle fs-5"><i class="ri-drag-move-2-line"></i></span>
                <span class="badge bg-primary signer-order-badge">#${signerRowCount}</span>
            </div>
            
            <div class="flex-grow-1" style="min-width: 250px;">
                <label class="form-label small mb-1">User Akun Internal <span class="text-danger">*</span></label>
                <select name="signers[${signerRowCount}][user_id]" class="form-select signer-select" required onchange="handleSignerUserChange(this, ${signerRowCount})">
                    ${userOptions}
                </select>
            </div>

            <div style="min-width: 200px;">
                <label class="form-label small mb-1">Peran / Posisi di Dokumen</label>
                <input type="text" name="signers[${signerRowCount}][role_name]" id="role-input-${signerRowCount}" class="form-control" value="${defaultRole}" placeholder="Contoh: Approver 1 / Manager">
            </div>

            <div class="pt-3">
                <button type="button" class="btn btn-icon btn-outline-danger" onclick="removeSignerRow(${signerRowCount})" title="Hapus Signer">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        `;

        container.appendChild(row);
        updateSignerOrderBadges();
    }

    function removeSignerRow(id) {
        const rows = document.querySelectorAll('.signer-row');
        if (rows.length <= 1) {
            alert('Minimal harus ada 1 orang penanda tangan.');
            return;
        }
        const row = document.getElementById(`signer-row-${id}`);
        if (row) {
            row.remove();
            updateSignerOrderBadges();
        }
    }

    function updateSignerOrderBadges() {
        const badges = document.querySelectorAll('.signer-order-badge');
        const isSequential = document.getElementById('flow-seq').checked;
        badges.forEach((b, idx) => {
            b.innerText = isSequential ? `#${idx + 1}` : 'Signer';
        });
    }

    function handleSignerUserChange(select, rowId) {
        const userId = select.value;
        const user = internalUsers.find(u => u.id == userId);
        const roleInput = document.getElementById(`role-input-${rowId}`);
        if (user && roleInput && !roleInput.value) {
            if (user.employee && user.employee.positions) {
                roleInput.value = user.employee.positions.name;
            } else {
                roleInput.value = 'Signer ' + rowId;
            }
        }
    }

    // Initialize with 2 default signer rows
    document.addEventListener('DOMContentLoaded', function() {
        addSignerRow();
        addSignerRow();
    });
</script>
@endsection
