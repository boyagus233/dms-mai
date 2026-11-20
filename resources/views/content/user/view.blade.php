@extends('layouts/contentNavbarLayout')

@section('title', 'User - Form')

@section('content')
<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">View User</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">User Name (NIK)</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $user->user_name }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Name</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $user->name }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Email</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $user->email }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Password</label>
            <div class="col-sm-6">
              <label class="col-form-label">············</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Status</label>
            <div class="col-sm-9">
              <label class="col-form-label">{{ $user->is_active == 1 ? 'Active' : 'In active' }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Signature</label>
            <div class="col-sm-6">
              <div id="thumbnailDiv">
                @if ($user->signature)
                  <img id="preview" src="{{ asset('storage/signature/' . $user->signature) }}" alt="Image Preview" style="margin: 10px 0px; max-height: 200px;"/>
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
              <a href="{{ route('users.index') }}" class="btn btn-info" role="button">Back</a>
            </div>
          </div>
      </div>
    </div>
  </div>
</div>
@endsection
