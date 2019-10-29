@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Connect Template</div>
        </div>
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <div class="row">
                        <form class="p-v-xs" action="{{url('scrap-save-template')}}" method="post" id="js_create_template" enctype="multipart/form-data">
                            {{ csrf_field() }}
                            <div class="file-field input-field">
                                <div class="input-field col s4">
                                    <select name="website_id">
                                        <option value="" disabled selected>Choose your option</option>
                                        @foreach($lst_web as $key => $web)
                                            <option value="{{ $key }}">{{ $web }}</option>
                                        @endforeach
                                    </select>
                                    <label>Web Select</label>
                                </div>
                                <div class="input-field col s4">
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
                                    <input name="id_product" placeholder="Nhập mã ID của product mẫu ở đây" type="text" class="validate"
                                           required>
                                    <label class="active">Id Product Template</label>
                                </div>
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
