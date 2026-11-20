@extends('layouts/contentNavbarLayout')

@section('title', 'Document Upload - Form')

@section('styles')
  <!-- Select2 -->
  <link href="/vendor/select2/dist/css/select2.min.css?v=20240303114000" rel="stylesheet">

@endsection

@section('content')
@php
    use App\Enums\GroupProcess;
@endphp
<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Add Document</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form action="{{ route('document-upload.store') }}" method="post" enctype="multipart/form-data">
          @csrf
              <input type="hidden" id="id" name="id" value="0">
              <input type="hidden" id="viewtype" name="viewtype" value="create">
              <input type="hidden" name="departement" id="departement" value="{{ $authordepartement }}">
              <input type="hidden" name="departement-name" id="departement-name">
              <input type="hidden" name="doc-type-code" id="doc-type-code">
              <input type="hidden" name="doc-classification-code" id="doc-classification-code">
              <input type="hidden" name="doc-function-code" id="doc-function-code">
              <input type="hidden" name="doc-form-code" id="doc-form-code">
              <input type="hidden" name="document-number" id="document-number">
              <input type="hidden" name="old_file">
              <input type="hidden" name="reason">
              <input type="hidden" id="checker-name" name="checker-name">
              <input type="hidden" id="approver-name" name="approver-name">
              <input type="hidden" id="stampX" name="stampX">
              <input type="hidden" id="stampY" name="stampY">
              <input type="hidden" id="canvasHeight" name="canvasHeight">
              <input type="hidden" id="canvasWidth" name="canvasWidth">
              <input type="hidden" id="stampWidth" name="stampWidth">
              <input type="hidden" id="stampHeight" name="stampHeight">

          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end" for="document-no">Document No*</label>
            <div class="col-sm-6">
              <select name="document-no" id="document-no" class="form-select form-select-sm" required>
                <option value="">Select Document No</option>
                @foreach($documentNumbers as $item)
                  <option value="{{ $item->id }}" data-docno="{{ $item->document_no }}" data-docname="{{ $item->document_name }}" data-doctypecode="{{ $item->document_type_code }}" data-docclasscode="{{ $item->document_classification_code }}" data-docfunccode="{{ $item->document_function_code }}" data-docform="{{ $item->document_form }}">
                      {{ $item->document_no }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end">Document Name</label>
            <div class="col-sm-6">
              <input type="text" class="form-control form-control-sm" id="document-name" name="document-name" placeholder="Document Name" readonly/>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end">Departement*</label>
            <div class="col-sm-6">
              <select id="departement-creator" class="form-select form-select-sm" disabled>
                <option value="">Select Departement</option>
                @foreach ($departement as $item)
                    <option value="{{ $item->id }}" data-code="{{ $item->code }}"
                      {{ old('departement', $authordepartement) === $item->id ? 'selected' : '' }}>
                      {{ $item->name }}
                    </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end" for="departementstag">Departements tag*</label>
            <div class="col-sm-6">
              <select id="departementstag" name="departementstag[]" class="form-select form-select-sm" multiple required>
                <option value="">Select Departement</option>
                @foreach($departement as $item)
                  <option value="{{ $item->id }}" data-code="{{ $item->code }}"
                      {{ in_array($item->id, old('departement', [])) ? 'selected' : '' }}>
                      {{ $item->code }} - {{ $item->name }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end" for="checker">Checker*</label>
            <div class="col-sm-6">
              <select name="checker" id="checker" class="form-select form-select-sm" required>
                <option value="">Select Employee</option>
                @foreach ($employees as $departmentCode => $group)
                  <optgroup label="{{ $departmentCode }}">
                      @foreach ($group as $employee)
                          <option value="{{ $employee->id }}">{{ $employee->employee_name }}</option>
                      @endforeach
                  </optgroup>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end" for="approver">Approver*</label>
            <div class="col-sm-6">
              <select name="approver" id="approver" class="form-select form-select-sm" required>
                <option value="">Select Employee</option>
                @foreach ($employees as $departmentCode => $group)
                  <optgroup label="{{ $departmentCode }}">
                      @foreach ($group as $employee)
                          <option value="{{ $employee->id }}">{{ $employee->employee_name }}</option>
                      @endforeach
                  </optgroup>
                @endforeach
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
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end">Attachment File</label>
            <div class="col-sm-6">
              <div class="input-group mb-1">
                <input type="file" class="form-control" id="file-attachment" name="file-attachment" style="display: none;">
                <input type="text" class="form-control" id="file-attachment-name" placeholder="No file chosen" readonly>
                <button class="btn btn-sm btn-outline-secondary" type="button" id="browse-button-attachment">Browse</button>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-6">
              <a href="{{ route('document-upload.index', ['viewtype' => $viewtype]) }}" class="btn btn-primary" role="button">Cancel</a>
              <button type="submit" name="action" value="newupload-create" id="newBtn" class="btn btn-success">Submit</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')

<!-- Select2 -->
<script type="module" src="/vendor/select2/dist/js/select2.full.min.js?v=20240303114000"></script>
<script type="module">
  $("#departementstag").select2({
      placeholder: " Select departement tags",
      allowClear: true,
  });
</script>

<script>
  // Upload PDF
  document.getElementById('pdf-upload').addEventListener('change', function(event) {
    let file = event.target.files[0];
    if (!file) {
        console.error("No file selected");
        return;
    }

    if (file.type !== "application/pdf") {
        console.error("Selected file is not a PDF");
        //return;
    }

    let reader = new FileReader();

    reader.onload = function(e) {
        //let fileURL = e.target.result;
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
    };

    reader.readAsDataURL(file);
  });
</script>
  <script>
    document.getElementById('document-no').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      // console.log('ID:', option.value);
      // console.log('Code:', option.innerHTML);
      const documentno = option.dataset.docno;
      const documentname = option.dataset.docname;
      const documenttype = option.dataset.doctypecode;
      const documentclass = option.dataset.docclasscode;
      const documentfunction = option.dataset.docfunccode;
      const documentform = option.dataset.docform;
      document.getElementById('document-name').value = documentname;
      document.getElementById('document-number').value = documentno;
      document.getElementById('doc-type-code').value = documenttype;
      document.getElementById('doc-classification-code').value = documentclass;
      document.getElementById('doc-function-code').value = documentfunction;
      document.getElementById('doc-form-code').value = documentform;
    });

    document.getElementById('checker').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      const empname = option.innerHTML;
      document.getElementById('checker-name').value = empname;
    });

    document.getElementById('approver').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      const empname = option.innerHTML;
      document.getElementById('approver-name').value = empname;
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
  <script>
    const fileInputAttc = document.getElementById('file-attachment');
    const browseButtonAttc = document.getElementById('browse-button-attachment');
    const fileNameFieldAttc = document.getElementById('file-attachment-name');

    browseButtonAttc.addEventListener('click', function () {
      fileInputAttc.click();
    });

    fileInputAttc.addEventListener('change', function () {
      fileNameFieldAttc.value = this.files.length > 0 ? this.files[0].name : 'No file chosen';
    });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form');
        const select = document.getElementById('departement-creator');
        const hiddenInput = document.getElementById('departement-name');

        // Set initial value
        hiddenInput.value = select.options[select.selectedIndex].text;;

        // Optional: update hidden input on change, in case you toggle disabled dynamically
        select.addEventListener('change', function () {
            hiddenInput.value = select.value;
        });

        // OR: update just before form submits
        //form.addEventListener('submit', function () {
        //    hiddenInput.value = select.value;
        //});
    });
  </script>
@endsection
