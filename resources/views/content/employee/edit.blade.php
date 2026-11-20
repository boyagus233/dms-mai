@extends('layouts/contentNavbarLayout')

@section('title', 'Employee - Form')

@section('styles')
  <!-- Select2 -->
  <link href="/vendor/select2/dist/css/select2.min.css?v=20240303114000" rel="stylesheet">
@endsection
@section('content')

<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Edit Employee</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form action="{{ route('employee.store') }}" method="post" enctype="multipart/form-data">
          @csrf
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="nik">NIK*</label>
            <div class="col-sm-6">
              <input type="hidden" id="id", name="id" value="{{ $employee->id }}">
              <input type="text" class="form-control" id="nik" name="nik" placeholder="NIK" value="{{ $employee->nik }}" readonly/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="employee-name">Name*</label>
            <div class="col-sm-6">
              <input type="text" class="form-control" id="employee-name" name="employee-name" value="{{ $employee->name }}" placeholder="Employee Name" required/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="phonenumber">Phone Number*</label>
            <div class="col-sm-6">
              <input type="number" class="form-control" id="phonenumber" name="phonenumber" value="{{ $employee->phone_number }}" placeholder="Phonenumber" required/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="email">Email*</label>
            <div class="col-sm-6">
              <input type="email" class="form-control" id="email" name="email" value="{{ $employee->email }}" placeholder="Input Email" required="required"/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="departement">Departement*</label>
            <div class="col-sm-6">
              <select name="departement" id="departement" class="form-select" required>
                <option value="">Select Departement</option>
                  @foreach($departement as $item)
                    <option value="{{ $item->id }}"
                        {{ old('departement', $employee->departement) === $item->id ? 'selected' : '' }}>
                        {{ $item->name }}
                    </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="departementstag">Departements tag</label>
            <div class="col-sm-6">
              <select name="departementstag[]" multiple id="departementstag" class="form-select">
                <option value="">Select Departement</option>
                  @foreach ($departement as $item)
                    <option value="{{ $item->id }}" data-code="{{ $item->code }}"
                      {{ in_array($item->id, old('departements', $selectedDepartments ?? [])) ? 'selected' : '' }}>
                      {{ $item->code }} - {{ $item->name }}
                    </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="position">Position*</label>
            <div class="col-sm-6">
              <select name="position" id="position" class="form-select" required>
                <option value="">Select Position</option>
                @foreach ($position as $key => $item)
                  <option value="{{ $key }}"
                      {{ old('position', $employee->position) === $key ? 'selected' : '' }}>
                      {{ $item }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Status*</label>
            <div class="col-sm-9">
              <div class="form-check mb-2">
                <input name="status" class="form-check-input" type="radio" value="1" {{ $employee->status == 1 ? 'checked' : '' }} id="statusactive">
                <label class="form-check-label" for="statusactive"> Active </label>
              </div>
              <div class="form-check">
                <input name="status" class="form-check-input" type="radio" value="0" {{ $employee->status == 0 ? 'checked' : '' }} id="statusinactive">
                <label class="form-check-label" for="statusinactive"> In active </label>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="image">Signature</label>
            <div class="col-sm-6">
              <input type="hidden" value="{{ $employee->signature }}">
              <input type="file" name="image" id="image" accept="image/png, image/jpeg" onchange="validateImage(this)"/>
              <div id="thumbnailDiv">
                @if ($employee->signature)
                  <img id="preview" src="{{ asset('storage/signature/' . $employee->signature) }}" alt="Image Preview" style="margin: 10px 0px; max-height: 200px;"/>
                @else
                  <img id="preview" src="#" alt="Image Preview" style="margin: 10px 0px;max-height: 200px;"/>
                @endif

              </div>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-6">
              <a href="{{ route('employee.index') }}" class="btn btn-primary" role="button">Cancel</a>
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
<script src="/vendor/select2/dist/js/select2.full.min.js?v=20240303114000"></script>
<script>
  $("#departementstag").select2({
      placeholder: " Select departement tags",
      allowClear: true,
  });
</script>
<script>
    function validateImage(input) {
        const file = input.files[0];
        const maxSizeMB = 2;
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

        if (!file) return;

        if (file.size > maxSizeMB * 1024 * 1024) {
            alert("File must be less than " + maxSizeMB + "MB.");
            input.value = "";
            document.getElementById('preview').style.display = 'none';
            return;
        }

        if (!allowedTypes.includes(file.type)) {
            alert("Only JPEG, PNG or JPG files are allowed.");
            input.value = "";
            document.getElementById('preview').style.display = 'none';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            const img = new Image();
            img.src = e.target.result;
            img.onload = function () {
                const maxWidth = 2000;
                const maxHeight = 2000;

                if (img.width > maxWidth || img.height > maxHeight) {
                    alert(`Image dimensions must be less than ${maxWidth}x${maxHeight}px.`);
                    input.value = "";
                    document.getElementById('preview').style.display = 'none';
                    return;
                }
                const preview = document.getElementById('preview');
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            img.src = e.target.result; // This must come *after* setting onload
        };
        reader.readAsDataURL(file);
    }
  </script>
@endsection
