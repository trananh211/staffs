@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Create Template</div>
        </div>
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Input fields</span><br>
                    <div class="row">
                        <form action="{{url('woo-check-driver-product')}}" method="post" class="col s12">
                            {{ csrf_field() }}
                            <div class="row">
                                <div class="input-field col s2">
                                    <input type="text" value="{{ $rq['id_product'] }}" name="template_id" class="validate" required>
                                    <label for="email">Template ID</label>
                                </div>
                                <div class="input-field col s8">
                                    <input value="{{ $template_data['name'] }}" name="name" type="text" class="validate" required>
                                    <label for="active">Name Product Template</label>
                                </div>
                                <div class="input-field col s2">
                                    <input type="text" value="{{ $rq['id_store'] }}" name="store_id"  class="validate" required>
                                    <label for="email">Store ID</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field col s3">
                                    <input type="text" value="{{ $category_data['category_id'] }}" name="category_id" class="validate" required>
                                    <label for="email">Category Id</label>
                                </div>
                                <div class="input-field col s3">
                                    <input type="text" value="{{ $category_data['category_name'] }}" name="category_name" class="validate" required>
                                    <label for="email">Category Name</label>
                                </div>
                                <div class="input-field col s3">
                                    <input value="{{ $category_data['woo_category_id'] }}" name="woo_category_id" type="text" class="validate">
                                    <label for="active">Woo Category Id</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field col s4">
                                    <input type="text" name="name_driver" placeholder="Tên của thư mục Google Driver" class="validate" required>
                                    <label for="email">Tên thư mục</label>
                                </div>
                                <div class="input-field col s8">
                                    <input type="text" name="path_driver" placeholder="Đường dẫn đến thư mục Google Driver" class="validate" required>
                                    <label for="email">Đường dẫn thư mục</label>
                                </div>
                            </div>
                            <div class="col s12">
                                <button type="submit" class="right waves-effect waves-light btn blue">
                                    Kiểm tra
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
