@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Danh sách đơn hàng chưa có thiết kế</div>
            <span class="right">
                <a href="{{ url('get-design-new') }}" class="waves-effect waves-light btn">Nhận thiết kế</a>
            </span>
        </div>
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                </div>
            </div>
        </div>
    </div>
@endsection
