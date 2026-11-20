@extends('layouts/contentNavbarLayout')

@section('title', 'Document Eksternal Type - Form')

@section('page-script')
@endsection

@section('content')

<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Add Document Eksternal Type</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form action="{{ route('document-eksternal-type.store') }}" method="post">
          @csrf
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="code">Code*</label>
            <div class="col-sm-6">
              <input type="hidden" id="id" name="id" value="0">
              <input type="text" class="form-control" id="code" name="code" placeholder="Code"  required="required"/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="name">Name*</label>
            <div class="col-sm-6">
              <input type="text" class="form-control" id="name" name="name" placeholder="Document Type" required="required"/>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-6">
              <a href="{{ route('document-eksternal-type.index') }}" class="btn btn-primary" role="button">Cancel</a>
              <button type="submit" class="btn btn-success">Submit</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

