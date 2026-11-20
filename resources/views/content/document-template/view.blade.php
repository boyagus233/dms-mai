@extends('layouts/contentNavbarLayout')

@section('title', 'Document Template - Form')

@section('content')
<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">View Document Template</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form>
          @csrf
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Document Name</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentTemplate->document_name }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Description</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentTemplate->description }}</label>
            </div>
          </div>
          <div class="row mb-0">
            <label class="col-sm-3 col-form-label text-end">File name</label>
            <div class="col-sm-8">
              <label class="col-form-label">{{ $documentTemplate->file_name }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end"></label>
            <div class="col-sm-6">
              @if (!empty($documentTemplate->file_name))
              @php
                  $fileUrl = asset('storage/document-template/' . $documentTemplate->file_name);
                  $extension = strtolower(pathinfo($documentTemplate->file_name, PATHINFO_EXTENSION));
              @endphp

              {{-- Image preview --}}
              @if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                  <img id="previewImage"
                      src="{{ $fileUrl }}"
                      style="max-width: 300px; display:block;">
              @endif

              {{-- PDF preview --}}
              @if ($extension === 'pdf')
                  <iframe id="previewPdf"
                          src="{{ $fileUrl }}#toolbar=0&navpanes=0&scrollbar=0"
                          style="height:500px; width:100%; display:block;"></iframe>
              @endif

              @else
                  {{-- No file = nothing to show --}}
              @endif
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-6">
              <a href="{{ route('document-template.index') }}" class="btn btn-primary" role="button">Cancel</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


@endsection
