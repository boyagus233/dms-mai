@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
$containerNav = $containerNav ?? 'container-fluid';
$navbarDetached = ($navbarDetached ?? '');

@endphp

<!-- Navbar -->
@if(isset($navbarDetached) && $navbarDetached == 'navbar-detached')
<nav class="layout-navbar {{$containerNav}} navbar navbar-expand-xl {{$navbarDetached}} align-items-center bg-navbar-theme" id="layout-navbar">
  @endif
  @if(isset($navbarDetached) && $navbarDetached == '')
  <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
    <div class="{{$containerNav}}">
      @endif

      <!--  Brand demo (display only for navbar-full and hide on below xl) -->
      @if(isset($navbarFull))
      <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-6">
        <a href="{{url('/')}}" class="app-brand-link gap-2">
          <span class="app-brand-logo demo">@include('_partials.macros')</span>
          <span class="app-brand-text demo menu-text fw-semibold ms-1">{{config('variables.appName')}}</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
          <i class="ri-close-fill align-middle"></i>
        </a>
      </div>
      @endif

      <!-- ! Not required for layout-without-menu -->
      @if(!isset($navbarHideToggle))
      <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ?' d-xl-none ' : '' }}">
        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
          <i class="ri-menu-fill ri-24px"></i>
        </a>
      </div>
      @endif

      <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <!-- Search -->
        <div class="navbar-nav align-items-center">
          <div class="nav-item d-flex align-items-center position-relative">
            <i class="ri-search-line ri-22px me-1_5"></i>
            <input type="text" id="searchInput" class="form-control border-0 shadow-none ps-1 ps-sm-2 ms-50" placeholder="Search..." aria-label="Search..." autocomplete="off">
            <ul id="suggestionsBox" class="suggestions"></ul>
          </div>
        </div>
        <!-- /Search -->
        <ul class="navbar-nav flex-row align-items-center ms-auto">

          <!-- Notification -->
          <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-1">
            <a class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-secondary rounded-pill"
              href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
              <span class="position-relative">
                <i class="icon-base ri ri-notification-2-line icon-22px" style="color: #8c57ff"></i>
                <span class="badge rounded-pill bg-danger badge-dot badge-notifications border"></span>
              </span>
              <span id="countdocument" class="flex-shrink-0 badge rounded-pill bg-danger">{{ $totalRequiredReview + $totalAcknowledge }}</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end p-0" style="width: 300px;">
              <li class="dropdown-menu-header border-bottom">
                <div class="dropdown-header d-flex align-items-center py-3">
                  <h6 class="mb-0 me-auto">Notification</h6>
                </div>
              </li>
              {{-- route('document-upload.edit', [$lst->id, 'action' => 'check', 'viewtype' => 'required']) --}}
              <li class="dropdown-notifications-list scrollable-container">
                <ul class="list-group list-group-flush">
                  @foreach($listOutstanding as $lst)
                  @php
                      $url = $lst->outstanding_type == 'review'
                          ? #
                          : route('document-acknowledge.show', $lst->id);
                  @endphp
                    <li class="list-group-item list-group-item-action dropdown-notifications-item">
                        <a href="{{ $url }}" class="menu-link d-flex align-items-center text-decoration-none">
                            <i class="menu-icon tf-icons ri-file-copy-line"></i>
                            <div class="flex-grow-1">
                                <h6 class="small mb-50">{{ $lst->document_no }}</h6>
                                <small class="mb-1 d-block text-body">{{ $lst->document_name }}</small>
                            </div>
                        </a>
                    </li>
                  @endforeach
                </ul>
              </li>
            </ul>
          </li>
          <!--Notification -->

          <!-- User -->
          <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
              <div class="avatar avatar-online">
                <img src="{{ asset('assets/img/avatars/1.png') }}" alt class="w-px-40 h-auto rounded-circle">
              </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end mt-3 py-2">
            <li>
                <a class="dropdown-item" href="javascript:void(0);">
                  <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-2">
                      <div class="avatar avatar-online">
                        <img src="{{ asset('assets/img/avatars/1.png') }}" alt class="w-px-40 h-auto rounded-circle">
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      @auth
                        <span>
                            <h6 class="mb-0 small">{{ Auth::user()->name }}</h6>
                            <small class="text-muted">{{ Auth::user()->name }}</small>
                        </span>
                      @endauth
                      @guest
                        <h6 class="mb-0 small">Guest</h6>
                        <small class="text-muted">Guest</small>
                      @endguest
                    </div>
                  </div>
                </a>
              </li>
              <li>
                <div class="dropdown-divider"></div>
              </li>
              {{-- <li>
                <a class="dropdown-item" href="javascript:void(0);">
                  <i class="ri-user-3-line ri-22px me-2"></i>
                  <span class="align-middle">My Profile</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="javascript:void(0);">
                  <i class='ri-settings-4-line ri-22px me-2'></i>
                  <span class="align-middle">Settings</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="javascript:void(0);">
                  <span class="d-flex align-items-center align-middle">
                    <i class="flex-shrink-0 ri-file-text-line ri-22px me-3"></i>
                    <span class="flex-grow-1 align-middle">Billing</span>
                    <span class="flex-shrink-0 badge badge-center rounded-pill bg-danger h-px-20 d-flex align-items-center justify-content-center">4</span>
                  </span>
                </a>
              </li> --}}
              <li>
                <div class="dropdown-divider"></div>
              </li>
              <li>
                <div class="d-grid px-4 pt-2 pb-1">
                  <a class="btn btn-danger d-flex" href="{{ route('logout') }}">
                    <small class="align-middle">Logout</small>
                    <i class="ri-logout-box-r-line ms-2 ri-16px"></i>
                  </a>
                </div>
              </li>
            </ul>
          </li>
          <!--/ User -->
        </ul>
      </div>

      @if(!isset($navbarDetached))
    </div>
    @endif
  </nav>
  <!-- / Navbar -->
