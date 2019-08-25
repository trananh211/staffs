@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Change Variation Product</div>
        </div>
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <div class="row" id="js-variation-check" data-url="{{ url('js-check-variation-exist') }}">
                        <div class="input-field col s8">
                            <input type="text" class="validate js-variation-name" placeholder="Tên của variation đang thay đổi">
                            <label for="first_name">Variation Name</label>
                            <span id="js-variation-check-result"></span>
                        </div>
                        <div class="col s4">
                            <label>Supliers</label>
                            <select class="js-variation-suplier browser-default">
                                <option value="" disabled selected>Choose your option</option>
                                @foreach($supliers as $suplier)
                                    <option value="{{ $suplier->id }}"> {{ $suplier->name }} </option>
                                @endforeach
                            </select>
                        </div>

                    </div>
                    <div class="row">
                        <div class="input-field col s3">
                            <input type="text" class="validate js-variation-v1" placeholder="men us 15/ eu 7">
                            <label for="first_name">Giá Trị ban đầu</label>
                        </div>
                        <div class="input-field col s3">
                            <input type="text" class="validate js-variation-v2" placeholder="280">
                            <label for="last_name">Giá Trị Trung Gian</label>
                        </div>
                        <div class="input-field col s3">
                            <input type="text" class="validate js-variation-v3" placeholder="men us 14.5/ eu 6.5">
                            <label for="last_name">Giá trị quy đổi</label>
                        </div>
                        <div class="input-field col s3">
                            <input type="text" class="validate js-variation-v-sku" placeholder="Có thể có hoặc để trống">
                            <label for="last_name">Giá trị Sku</label>
                        </div>
                    </div>
                    <div class="row">
                        <a class="js-variation-add waves-effect waves-light btn green m-b-xs">Thêm mới</a>
                        <a class="js-variation-finish waves-effect waves-light btn green m-b-xs">Hoàn tất</a>
                    </div>
                    <div class="row" id="js-variation-url" data-url="{{ url('js-woo-convert-variation') }}">
                        <ul class="collection">

                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
