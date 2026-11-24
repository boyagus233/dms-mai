@extends('layouts/contentNavbarLayout')

@section('title', 'Master Vendor - List')

@section('styles')
  @vite('resources/css/datatables.css')
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="card-title mb-0 fw-bold"><i class="ri-store-2-line text-primary me-1"></i> Master Data Vendor</h5>
          <small class="text-muted">Kelola data vendor, supplier, dan customer untuk Surat Jalan</small>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="{{ route('vendor.export') }}" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1 shadow-sm">
            <i class="ri-file-excel-2-line"></i>
            <span>Export CSV</span>
          </a>
          <a href="{{ route('vendor.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
            <i class="ri-add-line"></i>
            <span>Tambah Vendor</span>
          </a>
        </div>
      </div>
      
      <div class="card-body pt-3">
        <div class="table-responsive">
          <table id="vendor-datatable" class="table table-striped table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th width="8%" class="text-center">Action</th>
                <th width="12%">Kode</th>
                <th>Nama Vendor</th>
                <th>Telepon / PIC</th>
                <th>Tipe</th>
                <th width="8%" class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach($vendors as $vendor)
              <tr>
                <td class="text-center">
                  <div class="d-flex justify-content-center gap-1">
                    <a href="{{ route('vendor.show', $vendor->id) }}" class="btn btn-xs btn-icon btn-outline-info" title="Lihat Detail">
                      <i class="ri-eye-line"></i>
                    </a>
                    <a href="{{ route('vendor.edit', $vendor->id) }}" class="btn btn-xs btn-icon btn-outline-primary" title="Edit Vendor">
                      <i class="ri-pencil-line"></i>
                    </a>
                    <a href="{{ route('vendor.destroy', $vendor->id) }}" class="btn btn-xs btn-icon btn-outline-danger" title="Hapus Vendor" data-confirm-delete="true">
                      <i class="ri-delete-bin-6-line"></i>
                    </a>
                  </div>
                </td>
                <td>
                  <a href="{{ route('vendor.show', $vendor->id) }}" class="fw-bold text-primary">
                    {{ $vendor->code }}
                  </a>
                </td>
                <td>
                  <strong>{{ $vendor->name }}</strong>
                  @if($vendor->official_name && $vendor->official_name !== $vendor->name)
                    <br><small class="text-muted">{{ $vendor->official_name }}</small>
                  @endif
                  @if($vendor->address)
                    <br><small class="text-muted text-truncate d-inline-block" style="max-width: 320px;" title="{{ $vendor->address }}">
                      <i class="ri-map-pin-line text-secondary"></i> {{ $vendor->address }}
                    </small>
                  @endif
                </td>
                <td>
                  @if($vendor->pic_name)
                    <div><i class="ri-user-line text-muted"></i> {{ $vendor->pic_name }}</div>
                  @endif
                  @if($vendor->phone)
                    <div><i class="ri-phone-line text-muted"></i> {{ $vendor->phone }}</div>
                  @endif
                  @if(!$vendor->pic_name && !$vendor->phone)
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>
                  @if($vendor->is_supplier)
                    <span class="badge bg-label-info me-1">Supplier</span>
                  @endif
                  @if($vendor->is_customer)
                    <span class="badge bg-label-warning me-1">Customer</span>
                  @endif
                  @if(!$vendor->is_supplier && !$vendor->is_customer)
                    <span class="badge bg-label-secondary">Vendor</span>
                  @endif
                </td>
                <td class="text-center">
                  @if($vendor->status == 1)
                    <span class="badge bg-success">Active</span>
                  @else
                    <span class="badge bg-secondary">Inactive</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
  @vite('resources/js/datatables.js')
  <script type="module">
    (function($) {
      $(document).ready(function () {
        $('#vendor-datatable').DataTable({
          responsive: true,
          pageLength: 25,
          order: [[1, 'asc']],
          language: {
            search: "Cari Vendor:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ vendor",
            paginate: {
              previous: "<i class='ri-arrow-left-s-line'></i>",
              next: "<i class='ri-arrow-right-s-line'></i>"
            }
          }
        });
      });
    })(window.jQuery);
  </script>
@endsection
