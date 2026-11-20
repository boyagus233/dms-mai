@extends('layouts/contentNavbarLayout')

@section('title', 'User - Form')

@section('page-script')
{{-- @vite('resources/assets/js/form-basic-inputs.js') --}}
@endsection

@section('content')

<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Add User</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form action="{{ route('users.store') }}" method="post" enctype="multipart/form-data">
          @csrf
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="username">User Name (NIK)*</label>
            <div class="col-sm-6">
              <input type="hidden" id="id" name="id" value="0">
              <input type="text" class="form-control" id="username" name="username" placeholder="User Name" required="required"/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="name">Name*</label>
            <div class="col-sm-6">
              <input type="text" class="form-control" id="name" name="name" placeholder="Name" required="required"/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="email">Email*</label>
            <div class="col-sm-6">
              <input type="email" class="form-control" id="email" name="email" placeholder="Input Email" required="required"/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="password">Password*</label>
            <div class="col-sm-6">
              <input type="password" class="form-control" id="password" name="password" placeholder="············" required="required" />
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Status*</label>
            <div class="col-sm-9">
              <div class="form-check mb-2">
                <input name="status" class="form-check-input" type="radio" value="1" id="statusactive" checked="">
                <label class="form-check-label" for="statusactive"> Active </label>
              </div>
              <div class="form-check">
                <input name="status" class="form-check-input" type="radio" value="0" id="statusinactive">
                <label class="form-check-label" for="statusinactive"> In active </label>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-6">
              <a href="{{ route('users.index') }}" class="btn btn-primary" role="button">Cancel</a>
              <button type="submit" class="btn btn-success">Submit</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection
@push('scripts')
  <script>
    function testJavascript() {
      alert('heloo');
    }
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
@endpush
