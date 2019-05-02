@extends('master')
@section('content')
    <div class="row no-m-t no-m-b">
        <div class="col s12">
            <div class="page-title">Job Tables</div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <a class="waves-effect waves-light btn indigo m-b-xs modal-trigger" href="#modala1">
                        <i class="material-icons left">play_for_work</i>Cập nhật Order
                    </a>
                    <div id="modala1" class="modal" style="z-index: 1003; display: none; opacity: 0; transform: scaleX(0.7); top: 250.516304347826px;">
                        <div class="modal-content">
                            <h4>Cập nhật order</h4>
                            <form class="col s12" method="POST" action="{{ url('update-order') }}">
                                {{ csrf_field() }}
                                <div class="row">
                                    <div class="input-field col s12">
                                        <select name="id_store" required>
                                            <option value="" disabled selected>Choose Store</option>
                                            @foreach ($list_stores as $lst_store)
                                                <option value="{{ $lst_store->id }}">{{ $lst_store->name }}</option>
                                            @endforeach
                                        </select>
                                        <label>Store Select</label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-field col s12">
                                        <i class="material-icons prefix">mode_edit</i>
                                        <textarea id="icon_prefix2" required name="order_id" placeholder="Phân cách bằng dấu ," class="materialize-textarea"></textarea>
                                        <label for="icon_prefix2">Order ID</label>
                                    </div>
                                </div>
                                <div class="row">
                                    <button type="submit" class="waves-effect waves-light btn indigo m-b-xs">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
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
                                    <td>{!! statusJob($order->status, 0, '') !!}</td>
                                    <td>{{ $order->quantity }}</td>
                                    <td>{!! statusPayment($order->order_status, $order->payment_method) !!}</td>
                                    <td>{!! compareTime($order->created_at, date("Y-m-d H:i:s")) !!}</td>
                                    <td></td>
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
