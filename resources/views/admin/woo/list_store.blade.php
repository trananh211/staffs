@extends('master')
@section('content')
<div class="row">
    <div class="col s12">
        <div class="page-title">List Store Woocommerce</div>
    </div>
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <table class="striped">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th data-field="id">Name</th>
                        <th data-field="name">Url</th>
                        <th data-field="email">Email</th>
                        <th data-field="email">Pass</th>
                        <th data-field="email">Host</th>
                        <th data-field="email">Port</th>
                        <th data-field="email">Security</th>
                        <th data-field="status">SKU</th>
                        <th data-field="Action">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if($stores && !empty($stores))
                        @foreach($stores as $key => $store)
                        <tr>
                            <?php $check = 0; ?>
                            <td>{{ ++$key }}</td>
                            <td>{{ $store->name }}</td>
                            <td>{{ $store->url }}</td>
                            <td>{{ $store->email }}</td>
                            <td>{{ $store->password }}</td>
                            <td>{{ $store->host }}</td>
                            <td>{{ $store->port }}</td>
                            <td>{{ $store->security }}</td>
                            <td>{{ $store->sku }}</td>
                            <td>
                            <a class="waves-effect waves-light btn modal-trigger" href="#modal{{$key}}">Edit</a>
                            <!-- Modal Structure -->
                            <div id="modal{{ $key }}" class="modal">
                                <div class="modal-content">
                                    <h4>Edit {{ $store->name }}</h4>
                                    <form class="col s12" action="{{url('woo_connect')}}" method="post">
                                        {{ csrf_field()}}
                                        <div class="row" style="display: none;">
                                            <input name="id_store" type="text" value="{{$store->id}}" class="validate">
                                            <label>Id</label>
                                        </div>
                                        <div class="row">

                                            <div class="input-field col s6">
                                                <input placeholder="Name Store" name="name" type="text" value="{{$store->name}}" class="validate">
                                                <label>Name</label>
                                            </div>
                                            <div class="input-field col s4">
                                                <input name="url" value="{{$store->url}}" type="text" class="validate">
                                                <label >Url</label>
                                            </div>
                                            <div class="input-field col s2">
                                                <input name="sku" value="{{$store->sku}}" type="text" class="validate">
                                                <label >SKU</label>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="input-field col s6">
                                                <input name="consumer_key" type="text" value="{{$store->consumer_key}}" class="validate">
                                                <label>Consumer_key</label>
                                            </div>
                                            <div class="input-field col s6">
                                                <input name="consumer_secret" value="{{$store->consumer_secret}}" type="text" class="validate">
                                                <label >Consumer_secret</label>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="input-field col s6">
                                                <input placeholder="Email" name="email" type="email" value="{{$store->email}}" class="validate">
                                                <label>Email</label>
                                            </div>
                                            <div class="input-field col s6">
                                                <input name="password" value="{{$store->password}}" type="text" class="validate">
                                                <label >Password</label>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="input-field col s4">
                                                <input placeholder="Host" name="host" type="text" value="{{$store->host}}" class="validate">
                                                <label>Host</label>
                                            </div>
                                            <div class="input-field col s4">
                                                <input name="port" value="{{$store->port}}" type="text" class="validate">
                                                <label >Port</label>
                                            </div>
                                            <div class="input-field col s4">
                                                <input name="security" value="{{$store->security}}" type="text" class="validate">
                                                <label >Security</label>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <button class="waves-effect waves-light btn" type="submit">Submit</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6">Empty store</td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
