@extends('layouts/contentNavbarLayout')

@section('title', 'Process Mapping - Form')

@section('page-script')
@endsection

@section('content')

<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Add Process Mapping</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form action="{{ route('process-mapping.store') }}" method="post">
          @csrf
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="process-type">Process Type*</label>
            <div class="col-sm-6">
              <select name="process-type" id="process-type" class="form-select" required>
                <option value="">Select Process Type</option>
                @foreach($processTypes as $processType)
                  <option value="{{ $processType->value }}" {{ old('process-type') === $processType->value ? 'selected' : '' }}>
                      {{ $processType->label() }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="code">Code*</label>
            <div class="col-sm-6">
              <input type="hidden" id="id" name="id" value="0">
              <input type="text" class="form-control" id="code" name="code" placeholder="Code"  required="required"/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="process-mapping-name">Process Mapping Name*</label>
            <div class="col-sm-6">
              <input type="text" class="form-control" id="process-mapping-name" name="process-mapping-name" placeholder="Process Mapping Name" required="required"/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="status">Status*</label>
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
              <a href="{{ route('process-mapping.index') }}" class="btn btn-primary" role="button">Cancel</a>
              <button type="submit" class="btn btn-success">Submit</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


@endsection
