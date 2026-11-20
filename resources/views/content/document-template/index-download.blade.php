@extends('layouts/contentNavbarLayout')

@section('title', 'Document Template - Form')

@section('styles')
  @vite('resources/css/datatables.css')
@endsection

@section('content')

<div class="row">
  <div class="card">
    <h5 class="card-header mb-1">
      <div class="col-sm-12">Document Template List</div>
    </h5>
    <div class="table-responsive">
      <table id="datatable-responsive" class="table table-striped table-bordered datatable-responsive">
        <thead>
          <tr>
            <th>Document Name</th>
            <th>Description</th>
            <th>File Name</th>
            <th>Download</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @if(!empty($documentTemplates))
            @foreach ( $documentTemplates as $documentTemplate )
            <tr>
                <td>
                  <a href="#" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="tooltip" title="">{{ $documentTemplate->document_name }}</a>
                </td>
                <td>{{ $documentTemplate->description }}</td>
                <td>{{ $documentTemplate->file_name }}</td>
                <td>
                  @if (!empty($documentTemplate->file_name))
                    <a href="{{ route('document-template.download', $documentTemplate->id) }}"
                      class="btn btn-sm btn-primary"
                      title="Download">
                        <i class="tf-icons ri-download-2-fill"></i>
                    </a>
                  @else
                      <span class="text-muted">No File</span>
                  @endif
                </td>
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
