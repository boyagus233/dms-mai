@extends('layouts/contentNavbarLayout')

@section('title', 'Document Process - Form')

@section('page-script')
@vite('resources/assets/js/form-basic-inputs.js')
@endsection

@section('content')

<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Edit Document Process</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form action="{{ route('document-classification.store') }}" method="post">
          @csrf
          <input type="hidden" id="id", name="id" value="{{ $documentClassification->id }}">
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="group">Group*</label>
            <div class="col-sm-6">
              <select name="group" id="group" class="form-select" required>
                <option value="">Select Group Process</option>
                @foreach (\App\Enums\GroupProcess::cases() as $group)
                    <option value="{{ $group->value }}" {{ old('group_process', $documentClassification->group_process?->value) == $group->value ? 'selected' : '' }}>
                        {{ $group->name }} - {{ $group->label() }}
                    </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="code">Code*</label>
            <div class="col-sm-6">
              <input type="text" class="form-control" id="code" name="code" value="{{ $documentClassification->code }}" placeholder="Code" required="required"/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="name">Name*</label>
            <div class="col-sm-6">
              <input type="text" class="form-control" id="name" name="name" value="{{ $documentClassification->name }}" placeholder="Name" required="required"/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="status">Status*</label>
            <div class="col-sm-9">
              <div class="form-check mb-2">
                <input name="status" class="form-check-input" type="radio" value="1" {{ $documentClassification->status == 1 ? 'checked' : '' }} id="statusactive">
                <label class="form-check-label" for="statusactive"> Active </label>
              </div>
              <div class="form-check">
                <input name="status" class="form-check-input" type="radio" value="0" {{ $documentClassification->status == 0 ? 'checked' : '' }} id="statusinactive">
                <label class="form-check-label" for="statusinactive"> In active </label>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-6">
              <a href="{{ route('document-classification.index') }}" class="btn btn-primary" role="button">Cancel</a>
              <button type="submit" class="btn btn-success">Submit</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


@endsection
