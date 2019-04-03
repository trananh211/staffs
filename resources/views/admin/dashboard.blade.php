@extends('master')
@section('content')
    ADMIN
    <div class="row no-m-t no-m-b">
        <div class="col s12 m12 l4">
            <div class="card stats-card">
                <div class="card-content">
                    <div class="card-options">
                        <ul>
                            <li class="red-text"><span class="badge cyan lighten-1">gross</span></li>
                        </ul>
                    </div>
                    <span class="card-title">Sales</span>
                    <span class="stats-counter">$<span class="counter">48190</span><small>This week</small></span>
                </div>
                <div id="sparkline-bar"></div>
            </div>
        </div>
        <div class="col s12 m12 l4">
            <div class="card stats-card">
                <div class="card-content">
                    <div class="card-options">
                        <ul>
                            <li><a href="javascript:void(0)"><i class="material-icons">more_vert</i></a></li>
                        </ul>
                    </div>
                    <span class="card-title">Page views</span>
                    <span class="stats-counter"><span class="counter">83710</span><small>This month</small></span>
                </div>
                <div id="sparkline-line"></div>
            </div>
        </div>
        <div class="col s12 m12 l4">
            <div class="card stats-card">
                <div class="card-content">
                    <span class="card-title">Reports</span>
                    <span class="stats-counter"><span class="counter">23230</span><small>Last week</small></span>
                    <div class="percent-info green-text">8% <i class="material-icons">trending_up</i></div>
                </div>
                <div class="progress stats-card-progress">
                    <div class="determinate" style="width: 70%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row no-m-t no-m-b">
        <div class="col s12 m12 l12">
            <div class="card invoices-card">
                <div class="card-content">
                    <span class="card-title">List Job 30 day before</span>
                    <table id="list-order" class="display responsive-table datatable-example">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Store</th>
                            <th>Order</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>quantity</th>
                            <th>Price</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Tracking</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Store</th>
                            <th>Order</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>quantity</th>
                            <th>Price</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Tracking</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @if(isset($list_order) && sizeof($list_order) > 0)
                            @foreach($list_order as $key => $order)
                                <tr>
                                    <td>{{ $key+1 }}</td>
                                    <td>{{ $order->name }}</td>
                                    <td>{{ $order->number }}-{{ $order->id }}</td>
                                    <td>{{ $order->product_name }}</td>
                                    <td>{{ $order->status }}</td>
                                    <td>{{ $order->quantity }}</td>
                                    <td>{{ $order->price }}$</td>
                                    <td>{{ $order->payment_method }}</td>
                                    <td>{{ $order->created_at }}</td>
                                    <td>LCN12395068</td>
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
