@extends('layouts/contentNavbarLayout')

@section('title', 'Surat Jalan')

@section('styles')
  @vite('resources/css/datatables.css')
@endsection

@section('content')
<div class="row">
  <div class="card">
    <div class="card-header border-bottom">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <h5 class="mb-0">Surat Jalan</h5>
          <small class="text-muted">Barang keluar (non-komersial) &middot; Online Sign Approval</small>
        </div>
        <a href="{{ route('surat-jalan.create') }}" class="btn btn-primary">
          <i class="ri-add-line me-1"></i> Buat Surat Jalan
        </a>
      </div>

      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
        <ul class="nav nav-pills flex-wrap gap-1" id="scope-tabs">
          <li class="nav-item">
            <button type="button" class="nav-link {{ $scope === 'need_sign' ? 'active' : '' }}" data-scope="need_sign">
              Perlu Tanda Tangan Saya
              @if ($pendingCount > 0)<span class="badge bg-danger rounded-pill ms-1">{{ $pendingCount }}</span>@endif
            </button>
          </li>
          <li class="nav-item"><button type="button" class="nav-link {{ $scope === 'mine' ? 'active' : '' }}" data-scope="mine">Dibuat Saya</button></li>
          <li class="nav-item"><button type="button" class="nav-link {{ $scope === 'involved' ? 'active' : '' }}" data-scope="involved">Saya Sebagai Signer</button></li>
          @if ($isViewerAll)
            <li class="nav-item"><button type="button" class="nav-link {{ $scope === 'all' ? 'active' : '' }}" data-scope="all">Semua</button></li>
          @endif
        </ul>
        <div style="min-width:180px">
          <select id="status" class="form-select form-select-sm">
            <option value="ALL">Semua Status</option>
            <option value="DRAFT">Draft</option>
            <option value="IN_PROGRESS">In Progress</option>
            <option value="COMPLETED">Completed</option>
            <option value="REJECTED">Rejected</option>
          </select>
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table id="surat-jalan-table" class="table table-striped table-bordered datatable-responsive">
        <thead>
          <tr>
            <th>No. Surat Jalan</th>
            <th>Tanggal</th>
            <th>Kepada</th>
            <th>Kategori</th>
            <th>Item</th>
            <th>Pemohon</th>
            <th>Status</th>
            <th>Menunggu</th>
            <th>Dibuat</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
@endsection

@section('scripts')
  @vite('resources/js/datatables.js')

  <script type="module">
    let scope = @json($scope);
    let table = null;

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    function load() {
      if (table) { table.destroy(); }
      table = $('#surat-jalan-table').DataTable({
        ajax: {
          url: @json(route('surat-jalan.data')),
          type: 'GET',
          data: (d) => { d.scope = scope; d.status = $('#status').val(); }
        },
        columns: [
          { data: 'sj_no', render: (d, t, row) => t === 'display'
              ? `<a href="${row.url}" class="fw-semibold">${esc(row.sj_no === '-' ? '(Draft)' : row.sj_no)}</a>` : d },
          { data: 'sj_date' },
          { data: 'kepada', render: (d, t) => t === 'display' ? esc(d) : d },
          { data: 'kategori', render: (d, t) => t === 'display' ? `<small>${esc(d)}</small>` : d },
          { data: 'items_count', className: 'text-center' },
          { data: 'creator', render: (d, t) => t === 'display' ? esc(d) : d },
          { data: 'status_label', render: (d, t, row) => t === 'display'
              ? `<span class="badge ${row.status_class}">${esc(d)}</span>` : d },
          { data: 'waiting_for', render: (d, t) => t === 'display' ? `<small>${esc(d)}</small>` : d },
          { data: 'created_ts', render: (d, t, row) => t === 'display' || t === 'filter' ? row.created_at : d },
        ],
        order: [[8, 'desc']],
        autoWidth: false,
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 100], [10, 25, 100]],
        language: { emptyTable: 'Belum ada Surat Jalan pada daftar ini.' }
      });
    }

    $('#scope-tabs [data-scope]').on('click', function () {
      $('#scope-tabs .nav-link').removeClass('active');
      $(this).addClass('active');
      scope = $(this).data('scope');
      load();
    });
    $('#status').on('change', load);

    load();
  </script>
@endsection
