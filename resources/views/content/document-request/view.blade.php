@extends('layouts/contentNavbarLayout')

@section('title', 'Document Request - Form')

@section('content')
<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">View Document Request</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form>
          @csrf
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Management Process</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentRequest->document_classification_code }} - {{ $documentRequest->documentClassification->name }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Document Type</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentRequest->document_type_code }} - {{ $documentRequest->documentType->document_type }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">WI Function</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentRequest->name }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Parent Number</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentRequest->document_parent_no }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Document No</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentRequest->document_no }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Document Name</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentRequest->document_name }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Document Status</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentRequest->document_status }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-6">
              <a href="{{ route('document-request.index') }}" class="btn btn-secondary" role="button">Back</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


@endsection
