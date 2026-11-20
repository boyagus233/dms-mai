@extends('layouts/blankLayout')

@section('title', 'Login - Pages')

@section('page-style')
@vite([
'resources/assets/vendor/scss/pages/page-auth.scss'
])
@endsection

@section('content')
<div class="position-relative">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6 mx-4">

      <!-- Login -->
      <div class="card p-7">
        <!-- Logo -->
        <div class="app-brand justify-content-center mt-5">
          <a href="{{url('/')}}" class="app-brand-link gap-3">
            <span class="app-brand-logo demo"></span>
            <span class="app-brand-text demo text-heading fw-semibold">{{config('variables.appName')}}</span>
          </a>
        </div>
        <!-- /Logo -->

        <div class="card-body mt-1">
          <h4 class="mb-1"><br /></h4>
          <p class="mb-5"><br /></p>
          @if ($errors->any())
          <div class="alert alert-warning">
            <span>Username / password combination is not valid.</span>
          </div>
          @endif

          <form id="formAuthentication" class="mb-5" action="{{ route('login.perform') }}" method="POST">
            @csrf
            <div class="mb-5">
              <label for="username" class="form-label">NPK</label>
              <input type="text" class="form-control" id="username" name="username" required placeholder="Enter your username">
            </div>
            <div class="mb-5">
              <label for="password" class="form-label">Password</label>
              <div class="form-password-toggle">
                <div class="input-group input-group-merge">
                  <div class="form-floating form-floating-outline">
                    <input type="password" id="password" class="form-control" name="password" required placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" />

                  </div>
                  <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line ri-20px"></i></span>
                </div>
              </div>
            </div>
            <div class="mb-5 pb-2 d-flex justify-content-between pt-2 align-items-center">
              <div class="form-check mb-0">

              </div>
              <a href="{{ route('forgot.password') }}" class="float-end mb-1">
                <span>Forgot Password?</span>
              </a>
            </div>
            <div class="mb-5">
              <button class="btn btn-primary d-grid w-100" type="submit">login</button>
            </div>
          </form>

          <p class="text-center mb-5">
            <br />
            <span>©2025 All Rights Reserved. Privacy and Terms</span>
          </p>
        </div>
      </div>
      <!-- /Login -->
      <img src="{{asset('assets/img/illustrations/tree-3.png')}}" alt="auth-tree" class="authentication-image-object-left d-none d-lg-block">
      <img src="{{asset('assets/img/illustrations/auth-basic-mask-light.png')}}" class="authentication-image d-none d-lg-block" height="172" alt="triangle-bg">
      <img src="{{asset('assets/img/illustrations/tree.png')}}" alt="auth-tree" class="authentication-image-object-right d-none d-lg-block">
    </div>
  </div>
</div>
@endsection