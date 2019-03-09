<!DOCTYPE html>
<html lang="en">
@include('layouts.head')
<body>
<div class="loader-bg"></div>
<div class="loader">
    <div class="preloader-wrapper big active">
        <div class="spinner-layer spinner-blue">
            <div class="circle-clipper left">
                <div class="circle"></div>
            </div>
            <div class="gap-patch">
                <div class="circle"></div>
            </div>
            <div class="circle-clipper right">
                <div class="circle"></div>
            </div>
        </div>
        <div class="spinner-layer spinner-teal lighten-1">
            <div class="circle-clipper left">
                <div class="circle"></div>
            </div>
            <div class="gap-patch">
                <div class="circle"></div>
            </div>
            <div class="circle-clipper right">
                <div class="circle"></div>
            </div>
        </div>
        <div class="spinner-layer spinner-yellow">
            <div class="circle-clipper left">
                <div class="circle"></div>
            </div>
            <div class="gap-patch">
                <div class="circle"></div>
            </div>
            <div class="circle-clipper right">
                <div class="circle"></div>
            </div>
        </div>
        <div class="spinner-layer spinner-green">
            <div class="circle-clipper left">
                <div class="circle"></div>
            </div>
            <div class="gap-patch">
                <div class="circle"></div>
            </div>
            <div class="circle-clipper right">
                <div class="circle"></div>
            </div>
        </div>
    </div>
</div>
<div class="mn-content fixed-sidebar">
    @include('layouts.header')
    @include('layouts.sidebar')
    <main class="mn-inner inner-active-sidebar">
        <div class="middle-content">
            <div class="row">
                @if (Session::has('error'))
                    <div class="col s12 m12 l12">
                        <div class="card">
                            <div class="card-content">
                                <div class="widget-content alert-content">
                                    <div class="alert alert-success alert-block">
                                        <a class="close" onclick="closeAler()" data-dismiss="alert" href="#">×</a>
                                        <h4 class="alert-heading">Success!!</h4>
                                        {{ Session('error') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if (Session::has('success'))
                    <div class="col s12 m12 l12">
                        <div class="card">
                            <div class="card-content">
                                <div class="widget-content alert-content">
                                    <div class="alert alert-success alert-block">
                                        <a class="close" onclick="closeAler()" data-dismiss="alert" href="#">×</a>
                                        <h4 class="alert-heading">Success!!</h4>
                                        {{ Session('success') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            @yield('content')
        </div>
        @include('layouts.inner-sidebar')
    </main>
    @include('layouts.footer')
</div>
<div class="left-sidebar-hover"></div>
@include('layouts.footer_js')
</body>
</html>
