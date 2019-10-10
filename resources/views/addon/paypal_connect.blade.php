@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Add Tracking To Paypal</div>
        </div>
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <div class="row">
                        <form class="col s12" action="{{url('paypal-create')}}" method="post">
                            {{ csrf_field() }}
                            <div class="row">
                                <div class="input-field col s5">
                                    <input placeholder="Paypal email" name="email" type="email" class="validate"
                                           required>
                                    <label for="first_name">Email</label>
                                </div>
                                <div class="input-field col s5">
                                    <select name="store_id">
                                        <option disabled>Choose your option</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                                        @endforeach
                                    </select>
                                    <label>Store Select</label>
                                </div>
                                <div class="col s2">
                                    <label>Used</label>
                                    <div class="switch m-b-md">
                                        <label>
                                            Off
                                            <input type="checkbox" name="active">
                                            <span class="lever"></span>
                                            On
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field col s6">
                                    <input required placeholder="Client Id" type="text" name='client_id'
                                           class="validate">
                                    <label for="client_id">Client Id</label>
                                </div>
                                <div class="input-field col s6">
                                    <input required placeholder="Client Secret" type="text" name='client_secret'
                                           class="validate">
                                    <label for="client_secret">Client Secret</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field col s12">
                                    <textarea class="materialize-textarea" name="note"></textarea>
                                    <label for="textarea1">Note</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col s12">
                                    <button type="submit" class="right waves-effect waves-light btn blue">
                                        Create New
                                    </button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>

        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <table class="striped">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th data-field="id">Account</th>
                            <th data-field="name">Store Name</th>
                            <th data-field="price">Status</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $i = 1; ?>
                        @if (sizeof($paypals) > 0)
                            @foreach($paypals as $paypal)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td>{{ $paypal->paypal_email}}</td>
                                    <td>{{ $paypal->store_name }}</td>
                                    <td>{{ $paypal->status }}</td>
                                    <td>Edit | Deleted</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td class="center" colspan="5">
                                    Chưa có tài khoản Paypal nào được kết nối.
                                </td>
                            </tr>
                        @endif

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
