@extends('master')
@section('content')
    <div class="row no-m-t no-m-b">
        <div class="col s12">
            <div class="page-title">Job Tables</div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <a class="waves-effect waves-light btn purple js-skip-product" data-url="{{ url('ajax-skip-product') }}">
                        Bỏ Qua
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="row no-m-t no-m-b">
        <div class="col s12 m12 l12">
            <div class="card invoices-card">
                <div class="card-content">
                    <table id="list-order" class="display responsive-table datatable-example">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Supplier</th>
                            <th>Store</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Supplier</th>
                            <th>Store</th>
                            <th></th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @if(isset($list_products) && sizeof($list_products) > 0)
                            @foreach($list_products as $key => $product)
                                <tr>
                                    <td>{{ $key+1 }}</td>
                                    <td>{!! thumb( explode(',',$product->image)[0], 50, $product->name) !!}</td>
                                    <td><a href="{{ $product->permalink }}" target="_blank">{{ $product->name }}</a>
                                    </td>
                                    <td>{!! statusType($product->type) !!}</td>
                                    <td>Chris</td>
                                    <td>{{ $product->store_name }}</td>
                                    <td>
                                        <p class="p-v-xs js-data" data-product-id="{{ $product->product_id }}">
                                            <input type="checkbox" id="test{{$key+1}}" class="js-checkbox-one"/>
                                            <label for="test{{$key+1}}"></label>
                                        </p>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="9" class="center">
                                    30 ngày vừa rồi bạn chưa bán được cái đéo gì cả. Xem lại bản thân mình đi.
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
