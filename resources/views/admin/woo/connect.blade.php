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
                            {{ csrf_field() }}
                            <div class="row">
                                <div class="input-field col s6">
                                    <input placeholder="Name Store" name="name" type="text" class="validate">
                                    <label>Name</label>
                                </div>
                                <div class="input-field col s4">
                                    <input name="url" type="text" class="validate">
                                    <label >Url</label>
                                </div>
                                <div class="input-field col s2">
                                    <input name="sku" type="text" class="validate">
                                    <label >SKU</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field col s6">
                                    <input name="consumer_key" type="text" class="validate">
                                    <label>Consumer_key</label>
                                </div>
                                <div class="input-field col s6">
                                    <input name="consumer_secret" type="text" class="validate">
                                    <label >Consumer_secret</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field col s6">
                                    <input placeholder="Email" name="email" type="email" class="validate">
                                    <label>Email</label>
                                </div>
                                <div class="input-field col s6">
                                    <input name="password" type="text" class="validate">
                                    <label >Password</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field col s4">
                                    <input placeholder="Host" name="host" type="text" class="validate">
                                    <label>Host</label>
                                </div>
                                <div class="input-field col s4">
                                    <input name="port" type="text" class="validate">
                                    <label >Port</label>
                                </div>
                                <div class="input-field col s4">
                                    <input name="security" type="text" class="validate">
                                    <label >Security</label>
                                </div>
                            </div>
                            <div class="row">
                                <button class="waves-effect waves-light btn" type="submit">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
