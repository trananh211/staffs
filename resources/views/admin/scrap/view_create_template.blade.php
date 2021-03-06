@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Connect Template</div>
            <div class="right"><a href="{{ url('scrap-custom-create-template') }}">Tạo custom template</a></div>
        </div>
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <div class="row">
                        <form class="p-v-xs" action="{{url('scrap-save-template')}}" method="post" id="js_create_template" enctype="multipart/form-data">
                            {{ csrf_field() }}
                            <div class="file-field input-field">
                                <div class="row">
                                    <div class="input-field col s4">
                                        <select name="platform_id" required>
                                            <option value="" disabled selected>Choose flatform</option>
                                            @foreach($lst_auto_webs as $key_auto => $web_auto)
                                                <option value="{{ $key_auto }}">{{ $key_auto }} - {{ $web_auto }}</option>
                                            @endforeach
                                        </select>
                                        <label>Type Flatform</label>
                                    </div>
                                    <div class="input-field col s4">
                                        <select name="website_id">
                                            <option value="" disabled selected>Choose your option</option>
                                            @foreach($lst_web as $key => $web)
                                                <option value="{{ $key }}">{{ $key }} - {{ $web }}</option>
                                            @endforeach
                                        </select>
                                        <label>Web Select
                                            <small class="blue-text text-darken-1">Nếu thuộc platform thì không cần chọn trường này</small>
                                        </label>
                                    </div>
                                    <div class="input-field col s4">
                                        <input name="web_link" placeholder="Nhập url của website cần crawler vào đây" type="text" class="validate" required>
                                        <label class="active">
                                            Url website Flatform
                                            <small class="blue-text text-darken-1">Nếu thuộc platform bắt buộc phải chọn trường này</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-field col s3">
                                        <select id="woo-tem-choose-store" name="store_id">
                                            <option value="" disabled selected>Choose your option</option>
                                            @foreach($stores as $store)
                                                <option value="{{ $store->id }}" con_key="{{ $store->consumer_key }}"
                                                        con_sec="{{ $store->consumer_secret }}" url="{{ $store->url }}"
                                                >
                                                    {{ $store->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label>Store Select</label>
                                    </div>
                                    <div class="input-field col s3">
                                        <input name="id_product" placeholder="Nhập mã ID của product mẫu ở đây" type="text" class="validate"
                                               required>
                                        <label class="active">Id Product Template</label>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="input-field col s2">
                                        <input name="first_title" type="text" class="validate" placeholder="CanvasABC">
                                        <label class="active">
                                            Fixed ký tự đầu<br>
                                            <small class="blue-text text-darken-1">Chọn khi không phải name, age</small>
                                        </label>
                                    </div>
                                    <div class="input-field col s4">
                                        <input value="" name="auto_sku" type="text" class="validate" placeholder="Điền mã sku của sản phẩm">
                                        <label for="active">Auto SKU
                                            <small class="blue-text text-darken-1">Nếu sản phẩm không có SKU cố định. Hãy chọn trường này để hệ thống gen tự động mã SKU</small>
                                        </label>
                                    </div>
                                    <div class="input-field col s2">
                                        <select name="template_tool_status">
                                            <option value="" disabled selected>Choose your option</option>
                                            @foreach ($template_tool_status as $tool_status => $value_status)
                                                <option value="{{ $tool_status }}">{{ $value_status }}</option>
                                            @endforeach
                                        </select>
                                        <label>Title Status Select</label>
                                    </div>
                                    <div class="input-field col s4">
                                        <input name="text_exclude" type="text" class="validate"
                                               placeholder="H8L1AF01 hoặc BL750">
                                        <label class="active">
                                            Exclude Text<br>
                                            <small class="blue-text text-darken-1">Nếu tiêu đề sản phẩm có chứa ký tự số bắt buộc phải chọn trường này</small>
                                        </label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="input-field col s4">
                                        <input name="image_choose" type="text" class="validate" placeholder="1,2,3,4,5">
                                        <label class="active">
                                            Image Choose<br>
                                            <small class="blue-text text-darken-1">Chọn số thứ tự ảnh muốn thêm vào store. Phân cách bởi dấu , </small>
                                        </label>
                                    </div>

                                    <div class="input-field col s2">
                                        <label class="active">
                                            Từ khóa thêm tự động<span class="red-text text-darken-1">(*)</span><br>
                                            <small class="blue-text text-darken-1">Thêm từ khóa vào tiêu đề sản phẩm</small>
                                        </label>
                                        <div class="switch m-b-md">
                                            <label>
                                                Off
                                                <input type="checkbox" name="keyword_import">
                                                <span class="lever"></span>
                                                On
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-field col s12">
                                        <input name="exclude_image" type="text" class="validate" placeholder="Thêm link image exclude">
                                        <label class="active">
                                            Điền link ảnh không muốn thêm vào website. Esty dùng thuộc tính : data-src-zoom-image <br>
                                            <small class="blue-text text-darken-1">Dán link ảnh phân cách với nhau bằng dấu ,</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-field col s4">
                                        <input name="url" type="text" class="validate js_url" required>
                                        <label class="active">Url</label>
                                    </div>
                                    <div class="input-field col s4">
                                        <input name="consumer_key" type="text" class="validate js_con_key" required>
                                        <label class="active">consumer_key</label>
                                    </div>
                                    <div class="input-field col s4">
                                        <input name="consumer_secret" type="text" class="validate js_con_sec" required>
                                        <label class="active">consumer_secret</label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col s12">
                                        <button type="submit" class="right waves-effect waves-light btn blue">
                                            Tìm Kiếm
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
