@extends('layouts/contentNavbarLayout')

@section('title', 'Document Type - Form')

@section('styles')
  @vite('resources/css/datatables.css')
@endsection

@section('content')

<div class="row">
  <div class="card">
    <h5 class="card-header mb-1">
      <div class="col-sm-12">Document Form List</div>
      <div class="d-flex justify-content-end">
        <a href="{{ route('document-form.create') }}" class="btn btn-primary">Add Document Form</a>
      </div>
    </h5>
    <div class="table-responsive">
      <table id="datatable-responsive" class="table table-striped table-bordered datatable-responsive">
        <thead>
          <tr>
            <th width="5%">action</th>
            <th>Document Name</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @if(!empty($documentForms))
            @foreach ( $documentForms as $documentForm )
            <tr>
                <td>
                  <a href="{{ route('document-type.edit', $documentForm->id) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Edit"><i class="ri-pencil-line me-1"></i></a>
                  <a href="{{ route('document-type.destroy', $documentForm->id) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Delete" data-confirm-delete="true"><i class="ri-delete-bin-6-line me-1" style="color: red;"></i></a>
                <td>
                  <a href="{{ route('document-type.show', $documentForm->id) }}" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="tooltip" title="View">{{ $documentForm->name }}</a>
                </td>
                <td>{{ $documentForm->status ? 'Active' : 'In active' }}</td>
            </tr>
            @endforeach
          @endif
        </tbody>
      </table>

      <!-- /delete modal -->
        <div id="deleteConfirmModal" class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel2"><i class="fa fa-times-circle fa-lg red"></i> <span>Delete</span></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span>
                </button>
                </div>
                <div class="modal-body">
                <p>Are you sure you want to delete this item?</p>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-dismiss="modal">Cancel</button>
                <a href="#" type="button" class="btn btn-sm btn-danger">Delete</a>
                </div>
            </div>
            </div>
        </div>
        <!-- /delete modal -->

    </div>
  </div>
</div>

@endsection

@section('scripts')
  @vite('resources/js/datatables.js')
@endsection
