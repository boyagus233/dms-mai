@extends('layouts/contentNavbarLayout')

@section('title', 'Document Upload - Form')

@section('styles')
@endsection

@section('content')

<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Add Document</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form action="{{ route('document-upload.store') }}" method="post" enctype="multipart/form-data">
          @csrf
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="document-level">Document Level*</label>
            <div class="col-sm-6">
              <input type="hidden" id="id" name="id" value="0">
              <input type="hidden" id="document-level-code" name="document-level-code">
              <input type="hidden" name="document" id="document">
              <input type="hidden" name="old_file">
              <input type="hidden" id="stampX" name="stampX">
              <input type="hidden" id="stampY" name="stampY">
              <input type="hidden" id="canvasHeight" name="canvasHeight">
              <input type="hidden" id="canvasWidth" name="canvasWidth">
              <input type="hidden" id="pageNumber" name="pageNumber" value="">
              <select name="document-level" id="document-level" class="form-select" required>
                <option value="">Select Document Level</option>
                @foreach($documentLevel as $item)
                  <option value="{{ $item->id }}" data-code="{{ $item->code }}">
                      {{ $item->code }} - {{ $item->document_level }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="document-level">Management Process*</label>
            <div class="col-sm-6">
              <input type="hidden" id="management-process-code" name="management-process-code">
              <select name="management-process" id="management-process" class="form-select" required>
                <option value="">Select Management Process</option>
                @foreach($managementProcess as $item)
                  <option value="{{ $item->id }}" data-code="{{ $item->code }}">
                      {{ $item->code }} - {{ $item->name }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Document Form*</label>
            <div class="col-sm-6">
              <input type="hidden" id="document-form-name" name="document-form-name">
              <select name="document-form" id="document-form" class="form-select">
                <option value="">Select Document Form</option>
                @foreach($documentForm as $item)
                  <option value="{{ $item->id }}" data-name="{{ $item->name }}">
                      {{ $item->name }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Document No</label>
            <div class="col-sm-6">
              <input type="text" class="form-control" id="document-no" name="document-no" placeholder="Document No" readonly/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="employee">Assign To (1)*</label>
            <div class="col-sm-6">
              <select name="employee" id="employee" class="form-select" required>
                <option value="">Select Employee</option>
                @foreach ($employee as $key => $item)
                    <option value="{{ $key }}">{{ $item }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="employee">Assign To (2)*</label>
            <div class="col-sm-6">
              <select name="employee2" id="employee2" class="form-select" required>
                <option value="">Select Employee</option>
                @foreach ($employee as $key => $item)
                    <option value="{{ $key }}">{{ $item }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Document File</label>
            <div class="col-sm-6">
              <div class="input-group mb-3">
                <input type="file" class="form-control" id="pdf-upload" name="pdf-upload" style="display: none;">
                <input type="text" class="form-control" id="file-name" placeholder="No file chosen" readonly>
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
    document.getElementById('document-level').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      // console.log('ID:', option.value);
      // console.log('Code:', option.innerHTML);
      const code = option.dataset.code;
      document.getElementById('document-level-code').value = code;
    });

    document.getElementById('management-process').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      const code = option.dataset.code;
      document.getElementById('management-process-code').value = code;
    });

    document.getElementById('document-form').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      const name = option.dataset.name;
      document.getElementById('document-form-name').value = name;
    });
  </script>
  <script>
    const fileInput = document.getElementById('pdf-upload');
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
