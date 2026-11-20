@extends('layouts/contentNavbarLayout')

@section('title', 'Employee - Form')

@section('content')
<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">View Employee</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form>
          @csrf
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">NIK</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $employee->nik }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">NAME</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $employee->name }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Phone Number</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $employee->phone_number }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Email</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $employee->email }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Departement</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $employee->departements->name }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Departements tag</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ implode(', ', $departmentNames) }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Position</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $employee->positions->name }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Status</label>
            <div class="col-sm-9">
              <label class="col-form-label">{{ $employee->status == 1 ? 'Active' : 'In active' }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Signature</label>
            <div class="col-sm-6">
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
              <a href="{{ route('employee.index') }}" class="btn btn-secondary" role="button">Back</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


@endsection
