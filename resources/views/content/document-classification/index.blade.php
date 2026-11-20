@extends('layouts/contentNavbarLayout')

@section('title', 'Management Process - Form')

@section('styles')
  @vite('resources/css/datatables.css')
@endsection

@section('content')

<div class="row">
  <div class="card">
    <h5 class="card-header mb-1">
      <div class="col-sm-12">Document Process List</div>
      <div class="d-flex justify-content-end">
        <a href="{{ route('document-classification.create') }}" class="btn btn-primary">Add Document Process</a>
      </div>
    </h5>
    <div class="table-responsive">
      <table id="datatable-responsive" class="table table-striped table-bordered datatable-responsive">
        <thead>
          <tr>
            <th width="5%">action</th>
            <th>Group</th>
            <th>Code</th>
            <th>Name</th>
            <th>Status</th>
            <th>Sorting</th>{{-- index 5 (will be hidden) --}}
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @if(!empty($documentClassifications))
            @foreach ( $documentClassifications as $documentClassification )
            <tr>
                <td>
                  <a href="{{ route('document-classification.edit', $documentClassification->id) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Edit"><i class="ri-pencil-line me-1"></i></a>
                  <a href="{{ route('document-classification.destroy', $documentClassification->id) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Delete" data-confirm-delete="true"><i class="ri-delete-bin-6-line me-1" style="color: red;"></i></a>
                </td>
                <td>
                  {{ $documentClassification->group_process }}
                </td>
                <td>
                  <a href="{{ route('document-classification.show', $documentClassification->id) }}" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="tooltip" title="View">{{ $documentClassification->code }}</a>
                </td>
                <td>{{ $documentClassification->name }}</td>
                <td>{{ $documentClassification->status ? 'Active' : 'In active' }}</td>
                <td>{{ $documentClassification->sort }}</td> {{-- This will be hidden --}}
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
