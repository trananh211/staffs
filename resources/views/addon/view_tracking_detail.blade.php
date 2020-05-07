@extends('popup')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Fulfill Orders</div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <table id="review-job" class="display responsive-table datatable-example">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Order Id</th>
                            <th>Tracking</th>
                            <th>Shipping Method</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Order Id</th>
                            <th>Tracking</th>
                            <th>Shipping Method</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        <?php $i=1; ?>
                        @foreach ($lists as $key => $list)
                            <tr>
                                <td>{{ $i++ }}</td>
                                <td>{{ $list->order_id }}</td>
                                <td>{!! showTracking($list->tracking_number, $list->status) !!}</td>
                                <th>{{ $list->shipping_method }}</th>
                                <td>{!! ($list->time_upload != '')? compareTime($list->time_upload, date("Y-m-d H:i:s")) : '' !!}</td>
                                <td>
                                    <a class="waves-effect waves-light btn modal-trigger" href="#modal{{ $list->tracking_id }}">Edit</a>

                                    <!-- Modal Structure -->
                                    <div id="modal{{ $list->tracking_id }}" class="modal">
                                        <div class="page-title">Đang thay đổi thông tin order: {{ $list->order_id }}</div>
                                        <div class="modal-content">
                                            <form class="col s12" action="{{url('edit-tracking-number')}}" method="post">
                                                {{ csrf_field() }}
                                                <div class="row hidden">
                                                    <div class="input-field col s12">
                                                        <input class="validate" name="tracking_id" value="{{ $list->tracking_id }}" required>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="input-field col s6">
                                                        <input placeholder="LZ009298136CN" name="tracking_number" type="text"
                                                               value="{{ ($list->tracking_number != '')? $list->tracking_number : '' }}" class="validate" required>
                                                        <label for="first_name">Tracking Number</label>
                                                    </div>
                                                    <div class="input-field col s6">
                                                        <input placeholder="DHL eCommerces, DHL, USPS" name="shipping_method" type="text"
                                                               value="{{ ($list->shipping_method != '')? $list->shipping_method : '' }}" class="validate" required>
                                                        <label for="first_name">Shipping Method</label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="input-field col s12">
                                                        <textarea id="textarea1" name="note" class="materialize-textarea" length="520">
                                                            {{ ($list->note != '')? html_entity_decode($list->note) : '' }}
                                                        </textarea>
                                                        <label for="textarea1" class="">Ghi chú - <small>Đơn hàng này đang ở trạng thái như thế nào ...</small></label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col s12">
                                                        <button type="submit"
                                                                class="right waves-effect waves-light btn blue">
                                                            Cập nhật
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
