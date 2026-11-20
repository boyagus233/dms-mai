@extends('layouts/contentNavbarLayout')

@section('title', 'Document Eksternal - Form')

@section('styles')
@endsection

@section('content')
@php
    use App\Enums\GroupProcess;
@endphp
<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Add Eksternal Document</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form action="{{ route('document-eksternal.store') }}" method="post" enctype="multipart/form-data">
          @csrf
          <input type="hidden" id="id" name="id" value="0">
          <input type="hidden" id="document_eksternal_type" name="document_eksternal_type" value="">
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end" for="eksternal_type">Eksternal Type</label>
            <div class="col-sm-6">
              <select name="eksternal_type" id="eksternal_type" class="form-select form-select-sm" required>
                <option value="">Select Document Eksternal Type</option>
                  @foreach($documentEksternalType as $item)
                    <option value="{{ $item->id }}" data-code="{{ $item->code }}" data-name="{{ $item->name }}">
                      {{ $item->name }}
                    </option>
                  @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end" for="document_no">Document No</label>
            <div class="col-sm-6">
              <input type="text" class="form-control form-control-sm" id="document_no" name="document_no" placeholder="Document No" required/>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end" for="document_name">Document Name</label>
            <div class="col-sm-6">
              <input type="text" class="form-control form-control-sm" id="document_name" name="document_name" placeholder="Document Name" required/>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end">Revisi</label>
            <div class="col-sm-6">
              <input type="text" class="form-control form-control-sm" id="revisi" name="revisi" placeholder="Revisi"/>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end" for="status">Status</label>
            <div class="col-sm-6">
              <select name="status" id="status" class="form-select form-select-sm" required>
                <option value="ACTIVE">Active</option>
                <option value="OBSOLETE">Obsolete</option>
              </select>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end">Document File</label>
            <div class="col-sm-6">
              <div class="input-group mb-1">
                <input type="file" class="form-control" id="pdf-upload" name="pdf-upload" accept=".pdf" style="display: none;" required>
                <input type="text" class="form-control" id="file-name" placeholder="No file chosen" readonly>
                <button class="btn btn-sm btn-outline-secondary" type="button" id="browse-button">Browse</button>
              </div>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end"></label>
            <div class="col-sm-6">
                <img id="previewImage" src="" style="display:none;">
                <iframe id="previewPdf" style="display:none; height:500px; width: 100%;"></iframe>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-6">
              <a href="{{ route('document-eksternal.index') }}" class="btn btn-primary" role="button">Cancel</a>
              <button type="submit" name="action" value="create" id="newBtn" class="btn btn-success">Submit</button>
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
  // Upload PDF
    document.getElementById('pdf-upload').addEventListener('change', function(event) {
      let file = event.target.files[0];
      const fileNameField = document.getElementById('file-name');

      if (!file) {
          fileNameField.value = 'No file chosen';
          return;
      }

      // Auto-fill filename input text
      fileNameField.value = file.name;

      // Use object URL which is much safer and faster than FileReader (base64)
      let fileURL = URL.createObjectURL(file);

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
    });

    const browseButton = document.getElementById('browse-button');
    browseButton.addEventListener('click', function () {
      document.getElementById('pdf-upload').click();
    });

    document.getElementById('eksternal_type').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      const code = option.dataset.code;
      document.getElementById('document_eksternal_type').value = code;
    });
  </script>
@endsection
