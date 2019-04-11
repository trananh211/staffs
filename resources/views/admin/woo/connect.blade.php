@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Form Connect Woocommerce Store</div>
        </div>
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Input fields</span><br>
                    <div class="row">
                        <form class="col s12" action="{{url('woo_connect')}}" method="post">
                            {{ csrf_field()}}
                            <div class="row">
                                <div class="input-field col s6">
                                    <input placeholder="Placeholder" id="store_name" name="store_name" type="text" class="validate">
                                    <label for="store_name" class="active">Store Name</label>
                                </div>
                                <div class="input-field col s6">
                                    <input id="store_url" name="store_url" type="text" class="validate">
                                    <label for="store_url" class="">Store Url</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field col s10">
                                    <input id="email" type="email" name="email" class="validate">
                                    <label for="email" class="">Email</label>
                                </div>
                                <div class="input-field col s2">
                                    <input id="sku" type="text" name="sku" class="validate">
                                    <label for="sku" class="">Sku</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field col s12">
                                    <input id="consumer_key" name="consumer_key" type="text" class="validate">
                                    <label for="consumer_key" class="">Consumer key</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field col s12">
                                    <input id="consumer_secret" name="consumer_secret"  type="text" class="validate">
                                    <label for="consumer_secret" class="">Consumer secret</label>
                                </div>
                            </div>
                            <div class="row">
                                <button type="submit" class="waves-effect waves-light btn">Save</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
