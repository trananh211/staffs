@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Suppliers</span><br>
                    <div class="row">
                        <form class="col s12" action="{{url('woo-add-new-supplier')}}" method="post">
                            {{ csrf_field() }}
                            <div class="row">
                                <div class="input-field col s6">
                                    <input placeholder="Ví dụ Junchen China" name="name" type="text" class="validate" required>
                                    <label for="first_name">Supplier Name</label>
                                </div>
                                <div class="input-field col s6">
                                    <select name="status" required>
                                        <option value="" disabled selected>Choose your option</option>
                                        <option value="1">Rate 1</option>
                                        <option value="2">Rate 2</option>
                                        <option value="3">Rate 3</option>
                                        <option value="4">Rate 4</option>
                                        <option value="5">Rate 5</option>
                                    </select>
                                    <label>Rate Select</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field col s12">
                                    <textarea id="textarea1" name="note" class="materialize-textarea" length="520"></textarea>
                                    <label for="textarea1" class="">Ghi chú - <small>Aliexpress link, skype, wechat, có sản phẩm gì ...</small></label>
                                    <span class="character-counter" style="float: right; font-size: 12px; height: 1px;"></span></div>
                            </div>
                            <div class="col s12">
                                <button type="submit" class="right waves-effect waves-light btn blue">
                                    Tạo mới
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
