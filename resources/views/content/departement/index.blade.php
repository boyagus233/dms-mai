@extends('layouts/contentNavbarLayout')

@section('title', 'Departement - Form')

@section('styles')
  @vite('resources/css/datatables.css')
@endsection

@section('content')

<div class="row">
  <div class="card">
    <h5 class="card-header mb-1">
      <div class="col-sm-12">Departement List</div>
      <div class="d-flex justify-content-end">
        <a href="{{ route('departement.create') }}" class="btn btn-primary">Add Departement</a>
      </div>
    </h5>
    <div class="table-responsive">
      <table id="datatable-responsive" class="table table-striped table-bordered datatable-responsive">
        <thead>
          <tr>
            <th width="5%">action</th>
            <th>Code</th>
            <th>Name</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @if(!empty($departements))
            @foreach ( $departements as $departement )
            <tr>
                <td>
                  <a href="{{ route('departement.edit', $departement->id) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Edit"><i class="ri-pencil-line me-1"></i></a>
                  <a href="{{ route('departement.destroy', $departement->id) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Delete" data-confirm-delete="true"><i class="ri-delete-bin-6-line me-1" style="color: red;"></i></a>
                <td>
                  <a href="{{ route('departement.show', $departement->id) }}" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="tooltip" title="View">{{ $departement->code }}</a>
                </td>
                <td>{{ $departement->name }}</td>
                <td>{{ $departement->status ? 'Active' : 'In active' }}</td>
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
  @vite('resources/js/datatables.js')
@endsection
