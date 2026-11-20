@extends('layouts/contentNavbarLayout')

@section('title', 'Document Template - Form')

@section('page-script')
@endsection

@section('content')

<div class="row">
  @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Edit Document Template</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form action="{{ route('document-template.update', $documentTemplate) }}" method="post" enctype="multipart/form-data">
          @csrf
          @method('PUT')
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="document_name">Document Name*</label>
            <div class="col-sm-6">
              <input type="hidden" id="id", name="id" value="{{ $documentTemplate->id }}">
              <input type="text" class="form-control" id="document_name" name="document_name" value="{{ $documentTemplate->document_name }}" placeholder="Document Name" required="required"/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="description">Description</label>
            <div class="col-sm-6">
              <textarea rows="3" class="form-control" id="description" name="description" placeholder="Document Description">{{ $documentTemplate->description }}</textarea>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end">Document File</label>
            <div class="col-sm-6">
              <div class="input-group mb-1">
                <input type="file" class="form-control" id="file" name="file" accept=".pdf" style="display: none;">
                <input type="text" class="form-control" id="file-name" placeholder="No file chosen" readonly>
                <button class="btn btn-sm btn-outline-secondary" type="button" id="browse-button">Browse</button>
              </div>
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
                  <img id="previewImage" src="" style="display:none;">
                  <iframe id="previewPdf" style="display:none; height:500px; width: 100%;"></iframe>
              @endif
            </div>
          </div>

          <div class="row mb-1">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-6">
              <a href="{{ route('document-template.index') }}" class="btn btn-primary" role="button">Cancel</a>
              <button type="submit" class="btn btn-success">Submit</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


@endsection

@section('scripts')

<script>
  const fileInput = document.getElementById('file');
  const browseButton = document.getElementById('browse-button');
  const fileNameField = document.getElementById('file-name');

  browseButton.addEventListener('click', function () {
    fileInput.click();
  });

  fileInput.addEventListener('change', function () {
    fileNameField.value = this.files.length > 0 ? this.files[0].name : 'No file chosen';
  });

  // Upload PDF
  document.getElementById('file').addEventListener('change', function(event) {
    let file = event.target.files[0];
    if (!file) return;

    let reader = new FileReader();

    reader.onload = function(e) {
        let fileURL = e.target.result;

        // IMAGE preview
        if (file.type.includes('image')) {
            document.getElementById('previewImage').src = fileURL;
            document.getElementById('previewImage').style.display = 'block';
            document.getElementById('previewPdf').style.display = 'none';
        }

        // PDF preview
        else if (file.type === "application/pdf") {
            document.getElementById('previewPdf').src = fileURL + "#toolbar=0&navpanes=0&scrollbar=0";
            document.getElementById('previewPdf').style.display = 'block';
            document.getElementById('previewImage').style.display = 'none';
        }
    };

    reader.readAsDataURL(file);
  });
</script>
@endsection
