<!DOCTYPE html>

<html class="light-style layout-menu-fixed" data-theme="theme-default" data-assets-path="{{ asset('/assets') . '/' }}"
    data-base-url="{{ url('/') }}" data-framework="laravel" data-template="vertical-menu-laravel-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>@yield(section: 'title') | {{ config('variables.appName') }} </title>
    <meta name="description"
        content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
    <meta name="keywords"
        content="{{ config('variables.templateKeyword') ? config('variables.templateKeyword') : '' }}">
    <!-- laravel CRUD token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Canonical SEO -->
    <link rel="canonical" href="{{ config('variables.productPage') ? config('variables.productPage') : '' }}">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon_bak.ico') }}" />


    <!-- Include Styles -->
    @include('layouts/sections/styles')

    @yield(section: 'styles')
    <!-- Include Scripts for customizer, helper, analytics, config -->
    @include('layouts/sections/scriptsIncludes')
    <style>
        html {
            //transform: scale(0.75);
            //transform-origin: top left;
            //width: 133.33%; /* 1 / 0.75 */
        }

        .suggestions {
            position: absolute;
            top: 40px;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            list-style: none;
            padding: 0;
            margin: 0;
            display: none;
            z-index: 1000;
        }

        .suggestions li {
            padding: 10px;
            cursor: pointer;
        }

        .suggestions li:hover {
            background-color: #eee;
        }
    </style>
</head>

<body>

    <!-- Layout Content -->
    @yield(section: 'layoutContent')
    <!--/ Layout Content -->


    <!-- Include Scripts -->
    @include('layouts/sections/scripts')


    <!-- call sweet alert -->
    @include('sweetalert::alert')

    <!-- for java script action -->
    @yield(section: 'scripts')
    <script>
        const input = document.getElementById("searchInput");
        const suggestionBox = document.getElementById("suggestionsBox");
        input.addEventListener("input", async function() {
            const query = this.value.trim();

            if (!query || query.length < 3) {
                suggestionBox.style.display = "none";
                return;
            }

            const res = await fetch(`/search?q=${query}`);
            const data = await res.json();

            suggestionBox.innerHTML = data
                .map(item => `
            <li class="list-group-item list-group-item-action dropdown-notifications-item">
                <a href="${item.url}" class="menu-link d-flex align-items-center text-decoration-none">
                    <i class="menu-icon tf-icons ri-file-copy-line"></i>
                    <div class="flex-grow-1">
                        <h6 class="small mb-50">${item.document_no}</h6>
                        <small class="mb-1 d-block text-body">${item.document_name}</small>
                    </div>
                </a>
            </li>
        `)
                .join("");

            suggestionBox.style.display = data.length ? "block" : "none";
        });
    </script>
</body>

</html>
