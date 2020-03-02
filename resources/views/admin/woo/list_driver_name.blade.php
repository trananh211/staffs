@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">List Folder Product</div>
        </div>
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Input fields</span><br>
                    <div class="row">
                        <form action="{{url('woo-save-create-template')}}" method="post" class="col s12">
                            {{ csrf_field() }}
                            <div class="row">

                                <div class="input-field col s2">
                                    <input type="text" value="{{ $rq['template_id'] }}" name="template_id" class="validate" required>
                                    <label for="email">Template ID</label>
                                </div>
                                <div class="input-field col s8">
                                    <input value="{{ $rq['name'] }}" name="name" type="text" class="validate">
                                    <label for="active">Name Product Template</label>
                                </div>
                                <div class="input-field col s2">
                                    <input type="text" value="{{ $rq['store_id'] }}" name="store_id"  class="validate" required>
                                    <label for="email">Store ID</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field col s3">
                                    <input type="text" value="{{ $rq['category_id'] }}" name="category_id" class="validate" required>
                                    <label for="email">Category Id</label>
                                </div>
                                <div class="input-field col s3">
                                    <input type="text" value="{{ $rq['category_name'] }}" name="category_name" class="validate" required>
                                    <label for="email">Category Name</label>
                                </div>
                                <div class="input-field col s3">
                                    <input value="{{ $rq['woo_category_id'] }}" name="woo_category_id" type="text" class="validate">
                                    <label for="active">Woo Category Id</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field col s4">
                                    <input type="text" value="{{ $rq['name_driver'] }}" name="name_driver" placeholder="Tên của thư mục Google Driver" class="validate" required>
                                    <label for="email">Tên thư mục</label>
                                </div>
                                <div class="input-field col s8">
                                    <input type="text" value="{{ $rq['path_driver'] }}" name="path_driver" placeholder="Đường dẫn đến thư mục Google Driver" class="validate" required>
                                    <label for="email">Đường dẫn thư mục</label>
                                </div>
                            </div>
                            <div class="col s12">
                                <button type="submit" class="right waves-effect waves-light btn blue">
                                    Kết thúc tạo sản phẩm
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="row">
                        <ul class="collection with-header">
                            <li class="collection-header"><h4>List Folder : <b>{{ $rq['name_driver'] }}</b></h4></li>
                            <?php $i=1 ?>
                            @foreach($lists as $key => $list)
                                <li class="collection-item">
                                    <div>{{ $i++ .'. '. $list['name'] }}<a href="#!" class="secondary-content"><i class="material-icons">description</i></a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
