@extends('layouts/contentNavbarLayout')

@section('title', 'Document Upload - Form')

@section('styles')
@endsection

@section('content')

<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Edit Document</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form action="{{ route('document-upload.store') }}" method="post" enctype="multipart/form-data">
          @csrf
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="document-level">Document Level*</label>
            <div class="col-sm-6">
              <input type="hidden" id="id" name="id" value="{{ $documentUpload->id }}">
              <input type="hidden" id="document-type" name="document-type" value="{{ $documentUpload->document_type }}">
              <input type="hidden" id="process-code" name="process-code" value="{{ $documentUpload->process_code }}">
              <input type="hidden" id="document-level" name="document-level" value="{{ $documentUpload->document_level }}">
              <input type="hidden" id="departement" name="departement" value="{{ $documentUpload->departement }}">
              <input type="hidden" id="document-type-code" name="document-type-code">
              <input type="hidden" id="document-level-code" name="document-level-code">
              <input type="hidden" id="departement-code" name="departement-code">
              <input type="hidden" id="process-code-code" name="process-code-code">
              <select class="form-select" disabled>
                <option value="">Select Document Level</option>
                @foreach ($documentLevel as $item)
                    <option value="{{ $item->id }}" data-code="{{ $item->code }}"
                      {{ old('document_level', $documentUpload->document_level) === $item->id ? 'selected' : '' }}>
                      {{ $item->code }} - {{ $item->document_level }}
                    </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="process-code">Process Code*</label>
            <div class="col-sm-6">
              <select class="form-select" disabled>
                <option value="">Select Management Process</option>
                @foreach ($managementProcess as $item)
                    <option value="{{ $item->id }}" data-code="{{ $item->code }}"
                      {{ old('management_process', $documentUpload->management_process) === $item->id ? 'selected' : '' }}>
                      {{ $item->code }} - {{ $item->name }}
                    </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="departement">Document Form*</label>
            <div class="col-sm-6">
              <select class="form-select" disabled>
                <option value="">Select Document Form</option>
                @foreach ($documentForm as $item)
                  <option value="{{ $item->id }}" data-name="{{ $item->name }}"
                    {{ old('document_name', $documentUpload->document_name) === $item->name ? 'selected' : '' }}>
                    {{ $item->name }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Document No</label>
            <div class="col-sm-6">
              <input type="text" class="form-control" id="document-no" name="document-no" placeholder="Document No" value="{{ $documentUpload->document_no }}" readonly/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="employee">Assign To*</label>
            <div class="col-sm-6">
              <select name="employee" id="employee" class="form-select" required>
                <option value="">Select Employee</option>
                @foreach ($employee as $key => $item)
                    <option value="{{ $key }}"
                      {{ old('assign_to', $documentUpload->assign_to) === $key ? 'selected' : '' }}>
                      {{ $item }}
                    </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="employee">Assign To (2)*</label>
            <div class="col-sm-6">
              <select name="employee" id="employee" class="form-select" required>
                <option value="">Select Employee</option>
                @foreach ($employee as $key => $item)
                    <option value="{{ $key }}"
                      {{ old('assign_to', $documentUpload->assign_to_2) === $key ? 'selected' : '' }}>
                      {{ $item }}
                    </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="document">Document File</label>
            <div class="col-sm-6">
              <div class="input-group mb-3">
                <input type="hidden" value="{{ $documentUpload->file_name }}" name="document" id="document">
                <input type="file" class="form-control" id="file-upload" name="file-upload" style="display: none;">
                <input type="text" class="form-control" id="file-name" value="{{ $documentUpload->file_name }}" placeholder="No file chosen" readonly>
                <button class="btn btn-outline-secondary" type="button" id="browse-button">Browse</button>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-6">
              <a href="{{ route('document-upload.index') }}" class="btn btn-primary" role="button">Cancel</a>
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
    document.getElementById('document-type').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      const code = option.dataset.code;
      document.getElementById('document-type-code').value = code;
    });

    document.getElementById('document-level').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      const code = option.dataset.code;
      document.getElementById('document-level-code').value = code;
    });

    document.getElementById('process-code').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      const code = option.dataset.code;
      document.getElementById('process-code-code').value = code;
    });

    document.getElementById('departement').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      const code = option.dataset.code;
      document.getElementById('departement-code').value = code;
    });
  </script>
  <script>
    const fileInput = document.getElementById('file-upload');
    const browseButton = document.getElementById('browse-button');
    const fileNameField = document.getElementById('file-name');

    browseButton.addEventListener('click', function () {
      fileInput.click();
    });

    fileInput.addEventListener('change', function () {
      fileNameField.value = this.files.length > 0 ? this.files[0].name : 'No file chosen';
    });
  </script>
@endsection
