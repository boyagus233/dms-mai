@extends('layouts/contentNavbarLayout')

@section('title', 'Process Mapping - Form')

@section('styles')
  <!-- Datatables -->
  <link href="https://cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css" rel="stylesheet">
  <link href="/vendor/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
  <link href="/vendor/datatables.net-responsive-bs/css/responsive.bootstrap.min.css" rel="stylesheet">
  <link href="/vendor/datatables.net-scroller-bs/css/scroller.bootstrap.min.css" rel="stylesheet">
@endsection

@section('content')

<div class="row">
  <div class="card">
    <h5 class="card-header mb-1">
      <div class="col-sm-12">Process Mapping List</div>
      <div class="d-flex justify-content-end">
        <a href="{{ route('process-mapping.create') }}" class="btn btn-primary">Add Process Mapping</a>
      </div>
    </h5>
    <div class="table-responsive">
      <table id="datatable-responsive" class="table table-striped table-bordered datatable-responsive">
        <thead>
          <tr>
            <th width="5%">action</th>
            <th>Process Type</th>
            <th>Code</th>
            <th>Name</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @if(!empty($processMappings))
            @foreach ( $processMappings as $processMapping )
            <tr>
                <td>
                  <a href="{{ route('process-mapping.edit', $processMapping->id) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Edit"><i class="ri-pencil-line me-1"></i></a>
                  <a href="{{ route('process-mapping.destroy', $processMapping->id) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Delete" data-confirm-delete="true"><i class="ri-delete-bin-6-line me-1" style="color: red;"></i></a>
                <td>{{ $processMapping->process_type?->label() ?? '—' }}</td>
                <td>
                  <a href="{{ route('process-mapping.show', $processMapping->id) }}" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="tooltip" title="View">{{ $processMapping->code }}</a>
                </td>
                <td>{{ $processMapping->name }}</td>
                <td>{{ $processMapping->status ? 'Active' : 'In active' }}</td>
            </tr>
            @endforeach
          @endif
        </tbody>
      </table>

    </div>
  </div>
</div>

@endsection

@section('scripts')
  <!-- Datatables -->
  <script src="/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
  <script src="/vendor/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
  <script src="/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
  <script src="/vendor/datatables.net-responsive-bs/js/responsive.bootstrap.js"></script>
  <script src="/vendor/datatables.net-scroller/js/dataTables.scroller.min.js"></script>

  <!-- data tables -->
  <script>
    var table = $('#datatable-responsive').DataTable({
      responsive: true,
      paging: true,
      searching: true,
      ordering: true
    });

  </script>
@endsection
