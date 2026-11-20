@extends('layouts/contentNavbarLayout')

@section('title', 'Document Eksternal Type - Form')

@section('content')
<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">View Document Eksternal Type</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form>
          @csrf
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Code</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentEksternalType->code }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Name</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentEksternalType->name }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-6">
              <a href="{{ route('document-eksternal-type.index') }}" class="btn btn-primary" role="button">Cancel</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


@endsection
