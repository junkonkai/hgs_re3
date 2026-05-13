@php
    use Illuminate\Support\Facades\Request;
    use Illuminate\Support\Facades\Route;
@endphp
<!DOCTYPE html>
<html lang="ja" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <title>{{ $controllerName }}.Admin.HGN</title>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <meta content="" name="description">
    <meta content="" name="author">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <!-- ================== BEGIN core-css ================== -->
    <link href="{{ asset('assets/css/vendor.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/transparent/app.min.css') }}" rel="stylesheet">
    <!-- ================== END core-css ================== -->

    <!-- オリジナルCSS -->
    <link href="{{ asset('admin_assets/style.css') }}?{{ time() }}" rel="stylesheet">

    <!-- ================== BEGIN page-css ================== -->
    <link href="{{ asset('assets/plugins/jvectormap-next/jquery-jvectormap.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/bootstrap-datepicker/dist/css/bootstrap-datepicker.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/gritter/css/jquery.gritter.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/jstree/dist/themes/default/style.min.css') }}" rel="stylesheet">
    <!-- ================== END page-css ================== -->
    <script>
        window.START_HGN = false;
    </script>
</head>
<body>
<!-- BEGIN page-cover -->
<div class="app-cover"></div>
<!-- END page-cover -->

<!-- BEGIN #loader -->
<div id="loader" class="app-loader">
    <span class="spinner"></span>
</div>
<!-- END #loader -->

<!-- BEGIN #app -->
<div id="app" class="app app-header-fixed app-sidebar-fixed ">
    <!-- BEGIN #header -->
    <div id="header" class="app-header">
        <!-- BEGIN navbar-header -->
        <div class="navbar-header">
            <a href="{{ route('Admin.Dashboard') }}" class="navbar-brand"><img src="{{ asset('admin_assets/logo.png') }}" class="navbar-logo-hgn"></img> <b class="me-1">H.G.N.</b> Admin</a>
            <button type="button" class="navbar-mobile-toggler" data-toggle="app-sidebar-mobile">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>
        <!-- END navbar-header -->
        <!-- BEGIN header-nav -->
        <div class="navbar-nav">

            <div class="navbar-item navbar-user dropdown">
                <a href="#" class="navbar-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                    {{--<img src="../assets/img/user/user-13.jpg" alt="">--}}
                    <span style="width: 30px;height: 30px;margin: -5px 10px -5px 0;border-radius: 30px;text-align: center;padding-top:5px;">
                        <i class="fa fa-user"></i>
                    </span>
                    <span>
                        <span class="d-none d-md-inline">{{ $adminUser->name }}</span>
                        <b class="caret"></b>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end me-1">
                    {{--
                    <a href="extra_profile.html" class="dropdown-item">Edit Profile</a>
                    <a href="email_inbox.html" class="dropdown-item d-flex align-items-center">
                        Inbox
                        <span class="badge bg-danger rounded-pill ms-auto pb-4px">2</span>
                    </a>
                    <a href="calendar.html" class="dropdown-item">Calendar</a>
                    --}}
                    <a href="{{ route('Root') }}" class="dropdown-item">ルート</a>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('Admin.Logout') }}" class="dropdown-item">Log Out</a>
                </div>
            </div>
        </div>
        <!-- END header-nav -->
    </div>
    <!-- END #header -->

    <!-- BEGIN #sidebar -->
    <div id="sidebar" class="app-sidebar">
        <!-- BEGIN scrollbar -->
        <div class="app-sidebar-content" data-scrollbar="true" data-height="100%">
            <!-- BEGIN menu -->
            <div class="menu">
                <div class="menu-header">Navigation</div>
                <div class="menu-item {{ menu_active("Admin.Dashboard") }}">
                    <a href="{{ route("Admin.Dashboard") }}" class="menu-link">
                        <div class="menu-icon">
                            <i class="fa fa-sitemap"></i>
                        </div>
                        <div class="menu-text">Dashboard</div>
                    </a>
                </div>
                <div class="menu-item has-sub {{ menu_active("Admin.Manage") }}">
                    <a href="javascript:;" class="menu-link">
                        <div class="menu-icon">
                            <i class="far fa-calendar"></i>
                        </div>
                        <div class="menu-text">Manage</div>
                        <div class="menu-caret"></div>
                    </a>
                    <div class="menu-submenu">
                        <div class="menu-item  {{ menu_active("Admin.Manage.Information") }}">
                            <a href="{{ route("Admin.Manage.Information") }}" class="menu-link"><div class="menu-text">Information</div></a>
                        </div>
                        <div class="menu-item  {{ menu_active("Admin.Manage.Contact") }}">
                            <a href="{{ route("Admin.Manage.Contact") }}" class="menu-link"><div class="menu-text">Contact</div></a>
                        </div>
                        <div class="menu-item  {{ menu_active("Admin.Manage.User") }}">
                            <a href="{{ route("Admin.Manage.User") }}" class="menu-link"><div class="menu-text">User</div></a>
                        </div>
                        <div class="menu-item  {{ menu_active("Admin.Manage.FearMeter") }}">
                            <a href="{{ route("Admin.Manage.FearMeter") }}" class="menu-link"><div class="menu-text">Fear Meter</div></a>
                        </div>
                        <div class="menu-item  {{ menu_active("Admin.Manage.Review") }}">
                            <a href="{{ route("Admin.Manage.Review") }}" class="menu-link"><div class="menu-text">Review</div></a>
                        </div>
                        <div class="menu-item  {{ menu_active("Admin.Manage.ShopSoldOut") }}">
                            <a href="{{ route("Admin.Manage.ShopSoldOut") }}" class="menu-link"><div class="menu-text">Shop Sold Out</div></a>
                        </div>
                    </div>
                </div>
                <div class="menu-item has-sub {{ menu_active("Admin.Game") }}">
                    <a href="javascript:;" class="menu-link">
                        <div class="menu-icon">
                            <i class="fas fa-gamepad"></i>
                        </div>
                        <div class="menu-text">Game</div>
                        <div class="menu-caret"></div>
                    </a>
                    <div class="menu-submenu">
                        <div class="menu-item  {{ menu_active("Admin.Game.Maker") }}">
                            <a href="{{ route("Admin.Game.Maker") }}" class="menu-link"><div class="menu-text">Maker</div></a>
                        </div>
                        <div class="menu-item {{ menu_active("Admin.Game.Platform") }}">
                            <a href="{{ route("Admin.Game.Platform") }}" class="menu-link"><div class="menu-text">Platform</div></a>
                        </div>
                        <div class="menu-item {{ menu_active("Admin.Game.Franchise") }}">
                            <a href="{{ route("Admin.Game.Franchise") }}" class="menu-link"><div class="menu-text">Franchise</div></a>
                        </div>
                        <div class="menu-item {{ menu_active("Admin.Game.Series") }}">
                            <a href="{{ route("Admin.Game.Series") }}" class="menu-link"><div class="menu-text">Series</div></a>
                        </div>
                        <div class="menu-item {{ menu_active("Admin.Game.Title") }}">
                            <a href="{{ route("Admin.Game.Title") }}" class="menu-link"><div class="menu-text">Title</div></a>
                        </div>
                        <div class="menu-item {{ menu_active("Admin.Game.PackageGroup") }}">
                            <a href="{{ route("Admin.Game.PackageGroup") }}" class="menu-link"><div class="menu-text">Package Group</div></a>
                        </div>
                        <div class="menu-item {{ menu_active("Admin.Game.Package") }}">
                            <a href="{{ route("Admin.Game.Package") }}" class="menu-link"><div class="menu-text">Package</div></a>
                        </div>
                        <div class="menu-item {{ menu_active("Admin.Game.RelatedProduct") }}">
                            <a href="{{ route("Admin.Game.RelatedProduct") }}" class="menu-link"><div class="menu-text">Related Product</div></a>
                        </div>
                        <div class="menu-item {{ menu_active("Admin.Game.MediaMixGroup") }}">
                            <a href="{{ route("Admin.Game.MediaMixGroup") }}" class="menu-link"><div class="menu-text">Media Mix Group</div></a>
                        </div>
                        <div class="menu-item {{ menu_active("Admin.Game.MediaMix") }}">
                            <a href="{{ route("Admin.Game.MediaMix") }}" class="menu-link"><div class="menu-text">Media Mix</div></a>
                        </div>
                    </div>
                </div>

                <!-- BEGIN minify-button -->
                <div class="menu-item d-flex">
                    <a href="javascript:;" class="app-sidebar-minify-btn ms-auto d-flex align-items-center text-decoration-none" data-toggle="app-sidebar-minify"><i class="fa fa-angle-double-left"></i></a>
                </div>
                <!-- END minify-button -->
            </div>
            <!-- END menu -->
        </div>
        <!-- END scrollbar -->
    </div>
    <div class="app-sidebar-bg"></div>
    <div class="app-sidebar-mobile-backdrop"><a href="#" data-dismiss="app-sidebar-mobile" class="stretched-link"></a></div>
    <!-- END #sidebar -->

    <!-- BEGIN #content -->
    <div id="content" class="app-content">
        @php $pageTitle = ''; @endphp
        @if (Request::route()->getName() !== 'Admin')
        <!-- BEGIN breadcrumb -->
        <ol class="breadcrumb float-xl-end">
            @php $routeNames = []; @endphp
            @foreach (explode('.', Request::route()->getName()) as $route)
                @if($loop->last)
                    <li class="breadcrumb-item active">{{ $route }}</li>
                    @php $pageTitle = $route; @endphp
                @else
                    @php $routeNames[] = $route;$routeName = implode('.', $routeNames); @endphp

                    @isset($overwriteBreadcrumb[$route])
                        <li class="breadcrumb-item"><a href="{{ $overwriteBreadcrumb[$route] }}">{{ $route }}</a></li>
                    @elseif (Route::has($routeName))
                        <li class="breadcrumb-item"><a href="{{ route($routeName) }}">{{ $route }}</a></li>
                    @else
                        <li class="breadcrumb-item">{{ $route }}</li>
                    @endif
                @endif
            @endforeach
        </ol>
        <!-- END breadcrumb -->
        @endif
        <!-- BEGIN page-header -->
        <h1 class="page-header">@hasSection('title') @yield('title') @else {{ $pageTitle }} @endif</h1>
        <!-- END page-header -->

        @yield('content')

    </div>
    <!-- END #content -->

    <!-- BEGIN scroll-top-btn -->
    <a href="javascript:;" class="btn btn-icon btn-circle btn-theme btn-scroll-to-top" data-toggle="scroll-to-top"><i class="fa fa-angle-up"></i></a>
    <!-- END scroll-top-btn -->
</div>
<!-- END #app -->

<!-- ================== BEGIN core-js ================== -->
<script src="{{ asset('assets/js/vendor.min.js') }}"></script>
<script src="{{ asset('assets/js/app.min.js') }}"></script>
<!-- ================== END core-js ================== -->

<!-- ================== BEGIN page-js ================== -->
<script src="{{ asset('assets/plugins/gritter/js/jquery.gritter.js') }}"></script>
<script src="{{ asset('assets/plugins/flot/source/jquery.canvaswrapper.js') }}"></script>
<script src="{{ asset('assets/plugins/flot/source/jquery.colorhelpers.js') }}"></script>
<script src="{{ asset('assets/plugins/jquery-sparkline/jquery.sparkline.min.js') }}"></script>
<script src="{{ asset('assets/plugins/jvectormap-next/jquery-jvectormap.min.js') }}"></script>
<script src="{{ asset('assets/plugins/jvectormap-content/world-mill.js') }}"></script>
<script src="{{ asset('assets/plugins/bootstrap-datepicker/dist/js/bootstrap-datepicker.js') }}"></script>
<script src="{{ asset('assets/plugins/select2/dist/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/plugins/jstree/dist/jstree.min.js') }}"></script>

<script src="{{ asset('admin_assets/common.js') }}"></script>
<script>
    $(()=> {
        $(".default-select2").select2();
        $(".multiple-select2").select2();
    });
</script>
@hasSection('js') @yield('js') @endif

<!-- ================== END page-js ================== -->
</body>
</html>
