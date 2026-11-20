@extends('layouts/contentNavbarLayout')

@section('title', 'User - Form')

@section('styles')
  @vite('resources/css/datatables.css')
@endsection

@section('content')
<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Edit User</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form action="{{ route('users.store') }}" method="post" enctype="multipart/form-data">
          @csrf
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="username">User Name (NIK)*</label>
            <div class="col-sm-6">
              <input type="hidden" id="id", name="id" value="{{ $user->id }}">
              <input type="text" class="form-control" id="username" name="username" value="{{ $user->user_name }}" placeholder="User Name" required="required" readonly/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="name">Name*</label>
            <div class="col-sm-6">
              <input type="text" class="form-control" id="name" name="name" value="{{ $user->name }}" placeholder="Name" required="required"/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="email">Email*</label>
            <div class="col-sm-6">
              <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}" placeholder="Input Email" required="required"/>
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
                <input name="status" class="form-check-input" type="radio" value="1" {{ $user->is_active == 1 ? 'checked' : '' }} id="statusactive">
                <label class="form-check-label" for="statusactive"> Active </label>
              </div>
              <div class="form-check">
                <input name="status" class="form-check-input" type="radio" value="0" {{ $user->is_active == 0 ? 'checked' : '' }} id="statusinactive">
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

<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">

      <div class="card-body">
        <div class="card">
          <h5 class="card-header mb-1">
            <div class="col-sm-12">User Role</div>
            <div class="d-flex justify-content-end">
              <a href="#" class="btn btn-primary float-right" role="button" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fa fa-plus-circle"></i> <span>Add</span></label></a>
            </div>
          </h5>
          <div class="table-responsive">
            <table id="datatable-responsive" class="table table-striped table-bordered datatable-responsive">
              <thead>
                <tr>
                  <th width="5%">Action</th>
                  <th>Code</th>
                  <th>Name</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @if(!empty($user->roles))
                    @foreach ( $user->roles as $role )
                    <tr>
                        <td>
                          <a class="btn-sm action-btn" href="#" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" data-userid="{{ $user->id }}" data-roleid="{{ $role->id }}" data-bs-placement="top" data-bs-toggle="tooltip" title="Delete"><i class="ri-delete-bin-6-line me-1" style="color: red;"></i></a>
                        </td>
                        <td>{{ $role->code }}</td>
                        <td>{{ $role->name }}</td>
                        <td>{{ $role->status ? 'Active' : 'In active' }}</td>
                    </tr>
                    @endforeach
                @endif
              </tbody>
            </table>

            <!-- add modal -->
            <div id="addModal" class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Add Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form action="{{ route('users.addRole', $user->id) }}" method="post" id="addForm" data-parsley-validate="" class="form-horizontal form-label-left" novalidate="">
                      @csrf
                      <div class="row mb-3">
                          <input id="userId" name="userId" type="hidden" value="{{ $user->id }}">
                          <label class="col-form-label col-md-2 col-sm-2 label-align"> User Role
                          </label>
                          <div class="col-md-6 col-sm-6">
                          <select id="roleId" name="roleId" class="form-select">
                              @foreach ($roles as $role)
                                  <option value="{{ $role->id }}" data-role-code="{{ $role->code }}">
                                      {{ $role->code }}
                                  </option>
                              @endforeach
                          </select>
                          </div>
                      </div>
                      <div class="row mb-3">
                        <div class="col-sm-2">
                        </div>
                        <div class="col-md-6 col-sm-6">
                          <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-sm btn-primary">Add</a>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
            <!-- /add modal -->

            <!-- delete modal -->
            <div id="deleteConfirmModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
              <div class="modal-dialog modal-sm">
                <div class="modal-content">
                  <div class="modal-header">
                      <h4 class="modal-title" id="myModalLabel2"><i class="ri-close-circle-fill me-2" style="color: red;"></i> <span>Delete</span></h4>
                      <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">X</span>
                      </button>
                  </div>
                  <div class="modal-body">
                      <p>Are you sure want to delete this item?</p>
                  </div>
                  <div class="modal-footer">
                      <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                      <a href="#" type="button" class="btn btn-sm btn-danger">Delete</a>
                  </div>
                </div>
              </div>
            </div>
            <!-- /delete modal -->

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
@section('scripts')
  @vite('resources/js/datatables.js')
  <!-- delete modal -->
  <script type="module">
    $('#deleteConfirmModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var userid = button.data('userid');
      var roleid = button.data('roleid');

      var modal = $(this)
      modal.find('.modal-footer a').attr('href', '/users/' + userid + '/delete-role/' + roleid);
    })
  </script>
  <!-- /delete modal -->
@endsection
