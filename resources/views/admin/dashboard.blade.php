@extends('master')
@section('content')
    <div class="col s12 m12 l12">
        <div class="card invoices-card">
            <div class="card-content">
                <div class="row">
                    <div class="col s6 grid-example">
                        <h5>Bảng thống kê</h5>
                    </div>
                    {{-- Form Choose Date--}}
                    <div class="col s6 grid-example">
                        <div class="card white">
                            <form class="col s12" action="{{url('dashboard-date')}}" method="post">
                                {{ csrf_field() }}
                                <div class="input-field col s5">
                                    <input id="date_from" type="date" name="date_from" required
                                           value="{{ (\Session::has('date'))? \Session::get('date.date_from') : '' }}">
                                    <label for="date_from" class="active">Date From</label>
                                </div>
                                <div class="input-field col s5">
                                    <input id="date_to" type="date" name="date_to" required
                                           value="{{ (\Session::has('date'))? \Session::get('date.date_to') : '' }}">
                                    <label for="date_to" class="active">Date To</label>
                                </div>
                                <div class="input-field col s2">
                                    <button type="submit" class="waves-effect waves-light btn">Ok</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    {{-- End Form Choose Date--}}
                    <div class="col s12 grid-example">
                        <div class="card white">
                            <div class="card-content center">
                                <span class="card-title">Top Stores</span>
                                <table class="bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th data-field="id">Store</th>
                                            <th data-field="order">Order</th>
                                            <th data-field="name">Item</th>
                                            <th data-field="price">Cross</th>
                                            <th data-field="price">Ship</th>
                                            <th data-field="price">Base</th>
                                            <th data-field="price">Net</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                        $i = 1;
                                        $order = 0;
                                        $item = 0;
                                        $cross = 0;
                                        $ship = 0;
                                        $base_cost = 0;
                                        $net = 0;
                                        ?>
                                    @foreach ($stores as $store)
                                        <tr>
                                            <td>{{ $i++ }}</td>
                                            <td>{{ $store['store_name'] }}</td>
                                            <td>{{ sizeof($store['order']) }}</td>
                                            <td>{{ $store['item'] }}</td>
                                            <td>{!! showCurrency($store['cross']) !!}</td>
                                            <td>{!! showCurrency($store['ship']) !!}</td>
                                            <td>{!! showCurrency($store['base_cost']) !!}</td>
                                            <td>{!! showCurrency($store['net']) !!}</td>
                                        </tr>
                                        <?php
                                            $item += $store['item'];
                                            $order+= sizeof($store['order']);
                                            $cross += $store['cross'];
                                            $ship += $store['ship'];
                                            $base_cost += $store['base_cost'];
                                            $net += $store['net'];
                                        ?>
                                    @endforeach
                                    <tr>
                                        <td></td>
                                        <td>Tổng kết: </td>
                                        <td>{{ $order }}</td>
                                        <td>{{ $item }}</td>
                                        <td>{!! showCurrency($cross) !!}</td>
                                        <td>{!! showCurrency($ship) !!}</td>
                                        <td>{!! showCurrency($base_cost) !!}</td>
                                        <td>{!! showCurrency($net) !!}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col s4 grid-example">
                        <div class="card white">
                            <div class="card-content center">
                                <span class="card-title">Top 10 Design</span>
                                <table class="bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th data-field="id">Design</th>
                                            <th data-field="price">Item</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php $i= 1; ?>
                                    @foreach ($designs as $design)
                                        <tr>
                                            <td>{{ $i++ }}</td>
                                            <td>{{ $design['sku'] }}</td>
                                            <td>{{ $design['item'] }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col s4 grid-example">
                        <div class="card white">
                            <div class="card-content center">
                                <span class="card-title">Top 10 Country</span>
                                <table class="bordered">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th data-field="id">Country</th>
                                        <th data-field="price">Item</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php $i= 1; ?>
                                    @foreach ($countries as $country)
                                        <tr>
                                            <td>{{ $i++ }}</td>
                                            <td>{{ $country['country'] }}</td>
                                            <td>{{ $country['item'] }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col s4 grid-example">
                        <div class="card white">
                            <div class="card-content center">
                                <span class="card-title">Top 10 State</span>
                                <table class="bordered">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th data-field="state">State</th>
                                        <th data-field="price">Item</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php $i= 1; ?>
                                    @foreach ($states as $state)
                                        <tr>
                                            <td>{{ $i++ }}</td>
                                            <td>{{ $state['state'].', '.$state['country'] }}</td>
                                            <td>{{ $state['item'] }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col s6 grid-example">
                        <div class="card white">
                            <div class="card-content center">
                                <span class="card-title">Top Categories</span>
                                <table class="bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th data-field="id">Category</th>
                                            <th data-field="name">Item</th>
                                            <th data-field="price">Net</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php $i= 1; ?>
                                    @foreach ($categories as $category)
                                        <tr>
                                            <td>{{ $i++ }}</td>
                                            <td>{{ $category['category_name'] }}</td>
                                            <td>{{ $category['item'] }}</td>
                                            <td>{!! showCurrency($category['net']) !!}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col s6 grid-example">
                        <div class="card white">
                            <div class="card-content center">
                                <span class="card-title">Top Products</span>
                                <table class="bordered">
                                    <thead>
                                        <tr>
                                            <th data-field="id">Name</th>
                                            <th data-field="name">Name</th>
                                            <th data-field="item">Item</th>
                                            <th data-field="net">Net</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php $i= 1; ?>
                                    @foreach ($products as $product)
                                        <tr>
                                            <td>{{ $i++ }}</td>
                                            <td>{{ $product['product_name'] }}</td>
                                            <td>{{ $product['item'] }}</td>
                                            <td>{!! showCurrency($product['net']) !!}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
