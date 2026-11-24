<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

  <!-- ! Hide app brand if navbar-full -->
  <div class="app-brand demo">
    <a href="{{url('/')}}" class="app-brand-link">
      <span class="app-brand-logo demo me-1">
        @include('_partials.macros')
      </span>
      <span class="app-brand-text demo menu-text fw-semibold ms-2">{{config('variables.appName')}}</span>
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="menu-toggle-icon d-xl-block align-middle"></i>
    </a>
  </div>

  <div class="menu-inner-shadow"></div>

  @php
    $user = Auth::user();
    // Convert to collection before filtering
    if ($user && $user->roles && $user->roles->contains('code', 'ADM')) {
      $filteredMenus = collect($menuData[0]->menu);
    } else {
      $filteredMenus = collect($menuData[0]->menu ?? [])->where('role', 'EMP');
    }

  @endphp

  <ul class="menu-inner py-1">
    {{-- @foreach ($menuData[0]->menu as $menu) --}}
    @foreach ($filteredMenus as $menu)
    {{--@dd($filteredMenus) --}}
      {{-- adding active and open class if child is active --}}

      {{-- menu headers --}}
      @if (isset($menu->menuHeader))
        <li class="menu-header mt-7">
            <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
        </li>
      @else

      {{-- active menu method --}}
      @php
      $activeClass = null;
      $currentRouteName = Route::currentRouteName();
      //dd(($currentRouteName));
      //$routeParameters = Route::current()->parameters();
      $queryParams = request()->query();

      if (!empty($queryParams) && isset($queryParams['viewtype']) && $currentRouteName <> 'document-eksternal-list.index') {
        if (($menu->slug === 'document-master-list' || $menu->slug === 'document-upload') && str_contains($menu->group, $queryParams['viewtype'])) {
          $activeClass = 'active';
        }
      } else {
        if ($currentRouteName === $menu->slug || (is_string($menu->slug) && str_starts_with($currentRouteName, $menu->slug . '.')) || (isset($menu->group) && !empty($menu->group) && str_contains($menu->group, $currentRouteName))) {
          $activeClass = 'active';
        }
        elseif (isset($menu->submenu)) {
          if (gettype($menu->slug) === 'array') {
            foreach($menu->slug as $slug){
              if (str_contains($currentRouteName,$slug) and strpos($currentRouteName,$slug) === 0) {
                $activeClass = 'active open';
              }
            }
          }
          else{
            // if (str_contains($currentRouteName,$menu->slug) and strpos($currentRouteName,$menu->slug) === 0) {
            //   $activeClass = 'active open';
            // }
            if (str_contains($menu->group,$currentRouteName)) {
              $activeClass = 'active open';
            }
          }
        }
      }

      @endphp

      {{-- main menu --}}
      <li class="menu-item {{$activeClass}}">
        <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}" class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
          @isset($menu->icon)
            <i class="{{ $menu->icon }}"></i>
          @endisset
          <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
          @isset($menu->badge)
            @if ($menu->name === 'Required Review Doc')
              <div class="badge bg-danger rounded-pill ms-auto">{{ $totalRequiredReview }}</div>
            @elseif ($menu->name === 'Aknowledge')
              <div class="badge bg-danger rounded-pill ms-auto">{{ $totalAcknowledge }}</div>
            @endif
            {{--<div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge[1] }}</div>--}}
          @endisset
        </a>

        {{-- submenu --}}
        @isset($menu->submenu)
          @include('layouts.sections.menu.submenu',['menu' => $menu->submenu])
        @endisset
      </li>
      @endif
    @endforeach
  </ul>

</aside>
