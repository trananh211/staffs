@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Create Template</div>
        </div>
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <div class="row">
                        <form class="p-v-xs" action="{{url('woo-check-template')}}" method="post" id="js_create_template" enctype="multipart/form-data">
                            {{ csrf_field() }}
                            <div class="file-field input-field">
                                <div class="row">
                                    <div class="input-field col s3">
                                        <select id="woo-tem-choose-store" name="id_store">
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
                                    <div class="input-field col s4">
                                        <input name="id_product" placeholder="Nhập mã ID của product mẫu ở đây"
                                               type="text" class="validate"
                                               required>
                                        <label class="active">Id Product Template</label>
                                    </div>

                                    <div class="input-field col s5">
                                        <input value="" name="auto_sku" type="text" class="validate" placeholder="Điền mã sku của sản phẩm">
                                        <label for="active">Auto SKU
                                            <small class="blue-text text-darken-1">Nếu sản phẩm không có SKU cố định. Hãy chọn trường này để hệ thống gen tự động mã SKU</small>
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
                                <div class="col s12">
                                    <button type="submit" class="right waves-effect waves-light btn blue">
                                        Tìm Kiếm
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
