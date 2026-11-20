@extends('layouts/contentNavbarLayout')

@section('title', 'All Document Approval - Custom E-Sign')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><span class="text-muted fw-light">E-Signature /</span> All Document Approval</h4>
                <p class="text-muted mb-0">Kelola dan mintakan tanda tangan dokumen PDF apa saja secara dinamis.</p>
            </div>
            <div>
                <a href="{{ route('adhoc-document.create') }}" class="btn btn-primary d-flex align-items-center gap-1">
                    <i class="ri-add-line fs-5"></i>
                    <span>Upload &amp; Request Signature</span>
                </a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header p-0">
                <ul class="nav nav-tabs card-header-tabs m-0" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'my_requests' ? 'active' : '' }}" href="{{ route('adhoc-document.index', ['tab' => 'my_requests']) }}">
                            <i class="ri-file-user-line me-1"></i> Permohonan Saya
                            <span class="badge bg-label-primary ms-1">{{ $myRequestsCount }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'need_sign' ? 'active' : '' }}" href="{{ route('adhoc-document.index', ['tab' => 'need_sign']) }}">
                            <i class="ri-quill-pen-line me-1"></i> Perlu Tanda Tangan Saya
                            @if($needSignCount > 0)
                                <span class="badge bg-danger ms-1">{{ $needSignCount }}</span>
                            @else
                                <span class="badge bg-label-secondary ms-1">0</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'all' ? 'active' : '' }}" href="{{ route('adhoc-document.index', ['tab' => 'all']) }}">
                            <i class="ri-list-check me-1"></i> Semua Dokumen
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body pt-3">
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Aksi</th>
                                <th>Judul Dokumen</th>
                                <th>Pengunggah</th>
                                <th>Alur</th>
                                <th>Progress Signer</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @forelse($documents as $doc)
                            @php
                                $totalSigners = $doc->signers->count();
                                $signedCount = $doc->signers->where('status', 'SIGNED')->count();
                                $isPendingForMe = $doc->signers->where('user_id', Auth::id())->where('status', 'PENDING')->isNotEmpty();
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex gap-1 align-items-center">
                                        <a href="{{ route('adhoc-document.show', ['uuid' => $doc->uuid]) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Lihat Detail & Dokumen">
                                            <i class="ri-eye-line"></i>
                                        </a>

                                        @if($doc->status === 'DRAFT' && ($doc->created_by === Auth::id() || (Auth::check() && Auth::user()->hasRole('ADM'))))
                                        <a href="{{ route('adhoc-document.tagging', ['uuid' => $doc->uuid]) }}" class="btn btn-sm btn-primary d-flex align-items-center gap-1" title="Atur Posisi Tanda Tangan">
                                            <i class="ri-drag-drop-line"></i>
                                            <span>Atur Tag</span>
                                        </a>
                                        @endif

                                        @if($isPendingForMe && $doc->status === 'IN_PROGRESS')
                                        <a href="{{ route('adhoc-document.review', ['uuid' => $doc->uuid]) }}" class="btn btn-sm btn-danger d-flex align-items-center gap-1" title="Tanda Tangani Sekarang">
                                            <i class="ri-quill-pen-line"></i>
                                            <span>Sign</span>
                                        </a>
                                        @endif

                                        @if($doc->status === 'COMPLETED' || $doc->signed_file_path)
                                        <a href="{{ route('adhoc-document.download-signed', ['uuid' => $doc->uuid]) }}" class="btn btn-sm btn-icon btn-outline-success" title="Download Signed PDF">
                                            <i class="ri-download-2-line"></i>
                                        </a>
                                        @endif

                                        @if($doc->status !== 'DRAFT')
                                        <a href="{{ route('adhoc-document.download-audit-history', ['uuid' => $doc->uuid]) }}" target="_blank" class="btn btn-sm btn-icon btn-outline-info" title="Download Certificate of Completion">
                                            <i class="ri-shield-check-line"></i>
                                        </a>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $doc->title }}</strong><br>
                                    <small class="text-muted"><i class="ri-file-pdf-line text-danger"></i> {{ $doc->original_file_name }}</small>
                                </td>
                                <td>
                                    <span>{{ $doc->creator->name ?? 'Internal User' }}</span>
                                </td>
                                <td>
                                    @if($doc->workflow_type === 'SEQUENTIAL')
                                        <span class="badge bg-label-info"><i class="ri-arrow-right-line me-1"></i> Sequential</span>
                                    @else
                                        <span class="badge bg-label-warning"><i class="ri-shuffle-line me-1"></i> Parallel</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px; min-width: 80px;">
                                            @php
                                                $pct = $totalSigners > 0 ? round(($signedCount / $totalSigners) * 100) : 0;
                                            @endphp
                                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small class="fw-semibold">{{ $signedCount }}/{{ $totalSigners }}</small>
                                    </div>
                                </td>
                                <td>
                                    @if($doc->status === 'COMPLETED')
                                        <span class="badge bg-success"><i class="ri-checkbox-circle-line me-1"></i> Completed</span>
                                    @elseif($doc->status === 'IN_PROGRESS')
                                        <span class="badge bg-primary"><i class="ri-time-line me-1"></i> In Progress</span>
                                    @elseif($doc->status === 'REJECTED')
                                        <span class="badge bg-danger"><i class="ri-close-circle-line me-1"></i> Rejected</span>
                                    @else
                                        <span class="badge bg-secondary">Draft</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $doc->created_at->format('d M Y H:i') }}</small>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="ri-folder-open-line fs-1 text-muted d-block mb-2"></i>
                                    <p class="text-muted mb-0">Belum ada dokumen pada kategori ini.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $documents->appends(['tab' => $tab])->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
