@extends('layouts/contentNavbarLayout')

@section('title', 'Request Document Number - Form')

@section('page-script')
@endsection

@section('content')
@php
    use App\Enums\GroupProcess;
@endphp
<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Request Document Number</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form action="{{ route('document-request.store') }}" method="post">
          @csrf
              <input type="hidden" id="id" name="id" value="0">
              <input type="hidden" id="document-type-code" name="document-type-code" value="">
              <input type="hidden" id="document-type-name" name="document-type-name" value="">
              <input type="hidden" id="document-classification-code" name="document-classification-code" value="">
              <input type="hidden" id="document-classification-name" name="document-classification-name" value="">
              <input type="hidden" id="wi-function-code" name="wi-function-code" value="">
              <input type="hidden" id="wi-function-name" name="wi-function-name" value="">
            <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="management-process">Management Process (PROC)*</label>
            <div class="col-sm-6">
              <select name="management-process" id="management-process" class="form-select" required>
                <option value="">Select Management Process</option>
                @foreach ($grouped as $groupKey  => $classifications)
                  @php
                  // convert string back to enum
                  $groupEnum = GroupProcess::tryFrom($groupKey);
                  @endphp
                  <optgroup label="{{ $groupEnum }} - {{ $groupEnum?->label() }}">
                      @foreach ($classifications as $classification)
                          <option value="{{ $classification->id }}" data-code="{{ $classification->code }}" data-name="{{ $classification->name }}">
                            {{ $classification->code }} - {{ $classification->name }}
                          </option>
                      @endforeach
                  </optgroup>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="document-type">Document Type*</label>
            <div class="col-sm-6">
              <select name="document-type" id="document-type" class="form-select" required>
                <option value="">Select Document Type</option>
                  @foreach($documentType as $item)
                    <option value="{{ $item->id }}" data-code="{{ $item->code }}" data-name="{{ $item->document_type }}">
                      {{ $item->document_type }}
                    </option>
                  @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3 d-none" id="funcRow">
            <label class="col-sm-3 col-form-label text-end" >WI Function</label>
            <div class="col-sm-6">
              <select name="wi-function" id="wi-function" class="form-select">
                <option value="">Select Function</option>
                @foreach($documentFunction as $item)
                    <option value="{{ $item->id }}" data-code="{{ $item->code }}" data-name="{{ $item->name }}">
                      {{ $item->code }} - {{ $item->name }}
                    </option>
                  @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3 d-none" id="parentRow">
            <label class="col-sm-3 col-form-label text-end">Choose Parent Number</label>
            <div class="col-sm-6">
              <input type="hidden" id="document-type-parent" name="document-type-parent" value="">
              <input type="hidden" id="document-type-code-parent" name="document-type-code-parent" value="">
              <input type="hidden" id="document-function-code-parent" name="document-function-code-parent" value="">
              <select name="parent-number" id="parent-number" class="form-select">
                <option value="">Select Parent number</option>
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="name">Document Name*</label>
            <div class="col-sm-6">
              <input type="text" class="form-control" id="name" name="name" placeholder="document name"  required="required"/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="notes">Notes</label>
            <div class="col-sm-6">
              <textarea rows="3" class="form-control" id="notes" name="notes" placeholder="Referensi, catatan singkat, link, dsb"></textarea>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Status</label>
            <div class="col-sm-6">
              <input type="text" class="form-control" value="REQUESTED" disabled />
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-6">
              <a href="{{ route('document-request.index') }}" class="btn btn-primary" role="button">Cancel</a>
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
    document.getElementById('management-process').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      const documentClassificationId = option.value;
      const documentClassificationCode = option.dataset.code;
      const documentclassificationname = option.dataset.name;
      const selectedtext = option.text;
      //const documentclassificationname = selectedtext.split("- ")[1];
      //const selectedtext = option.dataset.name;
      document.getElementById('document-classification-code').value = documentClassificationCode;
      document.getElementById('document-classification-name').value = documentclassificationname;
      const optionDocumentType = document.getElementById('document-type');
      const selectedDocumentTypeValue = optionDocumentType.value;
      const documentTypeCode = optionDocumentType.dataset.code;
      if (documentTypeCode==='F') {
        loadParentNumber();
      }
    });

    document.getElementById('wi-function').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      const id = option.value;
      const code = option.dataset.code;
      const name = option.dataset.name;
      //const selectedtext = option.text;
      //const winame = selectedtext.split("- ")[1];
      document.getElementById('wi-function-code').value = code;
      document.getElementById('wi-function-name').value = name;
    });

    document.getElementById('parent-number').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      const parent_no = option.value;
      const id = option.dataset.type;
      const code = option.dataset.typecode;
      const functioncode = option.dataset.functioncode;
      //const selectedtext = option.text;
      //const datatypename = option.dataset.typename;
      document.getElementById('document-type-parent').value = id;
      document.getElementById('document-type-code-parent').value = code;
      document.getElementById('document-function-code-parent').value = functioncode;
    });

    document.getElementById('document-type').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      const id = option.value;
      const code = option.dataset.code;
      const name = option.dataset.name;
      document.getElementById('document-type-code').value = code;
      document.getElementById('document-type-name').value = name;
      const funcRow = document.getElementById('funcRow');
      const parentRow = document.getElementById('parentRow');
      if (code==='P') {
        funcRow.classList.add('d-none');
        parentRow.classList.add('d-none');
      } else if(code==='WI') { funcRow.classList.remove('d-none'); parentRow.classList.add('d-none');}
      else {
        funcRow.classList.add('d-none');
        parentRow.classList.remove('d-none');
        loadParentNumber()
      }
    });

    function loadParentNumber() {
      const parentnumberSel = document.getElementById('parent-number');
      const dataclassification = document.getElementById('management-process').value;

      // Clear current options
      parentnumberSel.innerHTML = '<option value="">-- Loading... --</option>';

      fetch(`/getParentnumber/${dataclassification}`)
        .then(response => response.json())
        .then(data => {
            parentnumberSel.innerHTML = '<option value="">-- Select Parent number --</option>';
            data.forEach(sub => {
                const option = document.createElement('option');
                option.value = sub.document_parent_no;
                option.text = sub.document_parent_no;

                option.setAttribute('data-type', sub.document_type);
                option.setAttribute('data-typecode', sub.document_type_code);
                option.setAttribute('data-functioncode', sub.document_function_code);
                parentnumberSel.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading subcategories:', error);
            parentnumberSel.innerHTML = '<option value="">-- Error loading --</option>';
        });
      }
  </script>
@endsection
