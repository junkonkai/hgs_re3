<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8" />
    <title>Login.Admin.HGN</title>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <meta content name="description" />
    <meta content name="author" />

    <link href="{{ asset('assets/css/vendor.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/transparent/app.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('admin_assets/style.css') }}" rel="stylesheet" />

</head>
<body class="pace-top">

<div class="app-cover"></div>


<div id="loader" class="app-loader">
    <span class="spinner"></span>
</div>


<div id="app" class="app">

    <div class="login login-v1">

        <div class="login-container">
            <div class="login-header">
                <div class="brand">
                    <div class="d-flex align-items-center">
                        <img src="{{ asset('admin_assets/logo.png') }}" class="navbar-logo-hgn" alt="logo">
                        <b class="me-1">H.G.N.</b> Admin
                    </div>
                    <small>ホラーゲームネットワーク管理</small>
                </div>
                <div class="icon">
                    <i class="fa fa-lock"></i>
                </div>
            </div>

            <div class="login-body">
                <div class="login-content fs-13px">
                    <form action="{{ route('Admin.Auth') }}" method="POST">
                        @csrf
                        <div class="form-floating mb-20px">
                            <input type="text" name="email" class="form-control fs-13px h-45px" id="emailAddress" placeholder="Email">
                            <label for="emailAddress" class="d-flex align-items-center">Email Address</label>
                        </div>
                        <div class="form-floating mb-20px">
                            <input type="password" name="password" class="form-control fs-13px h-45px" id="password" placeholder="Password">
                            <label for="password" class="d-flex align-items-center">Password</label>
                        </div>
                        <div class="form-check mb-20px">
                            <input class="form-check-input" type="checkbox" name="remember_me" value="1" id="rememberMe" checked>
                            <label class="form-check-label" for="rememberMe">
                                ログイン状態を数日維持する
                            </label>
                        </div>
                        <div class="login-buttons">
                            <button type="submit" class="btn btn-theme h-45px d-block w-100 btn-lg">ログイン</button>
                        </div>
                    </form>

                    @error('login')
                        <div class="alert alert-danger alert-dismissible fade show mt-4">
                            <strong>Error!</strong>
                            {{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror


                    @if (app()->environment('local'))
                        <div class="alert alert-info alert-dismissible fade show mt-4">
                            <strong>Debug Info:</strong>
                            <br>
                            <strong>Encrypted Password:</strong> {{ \Illuminate\Support\Facades\Hash::make('huckle') }}

                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('assets/js/vendor.min.js') }}"></script>
<script src="{{ asset('assets/js/app.min.js') }}"></script>

</body>
</html>
