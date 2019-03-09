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
                            <th data-field="status">Status</th>
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
                                    <td>
                                        @if($store->status == 1)
                                            <span class="label label-success">Active</span>
                                        @elseif( $store->status == 0)
                                            <span class="label label-warning">Waiting</span>
                                        @else
                                            <span class="label">Not Active</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-mini">Edit</button>
                                        <button class="btn btn-info btn-mini">Delete</button>
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
