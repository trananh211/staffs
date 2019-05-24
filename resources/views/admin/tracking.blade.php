@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Status Supplier</div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <table id="review-job" class="display responsive-table datatable-example">
                        <thead>
                        <tr>
                            <th class="center">#</th>
                            <th class="center">Order</th>
                            <th class="center">Name</th>
                            <th class="center">Design Up</th>
                            <th class="center">Quantity</th>
                            <th class="center">Supplier Up</th>
                            <th class="center">Tracking</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th class="center">#</th>
                            <th class="center">Order</th>
                            <th class="center">Name</th>
                            <th class="center">Design Up</th>
                            <th class="center">Quantity</th>
                            <th class="center">Supplier Up</th>
                            <th class="center">Tracking</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @if(sizeof($lists) > 0)
                            @foreach($lists as $key => $list)
                                <tr>
                                    <td class="center">{{ $key+1 }}</td>
                                    <td class="center"> {{ $list->number }}{{ ($list->working_id != '')? '-'.$list->working_id : '' }}</td>
                                    <td class="center">{{ $list->product_name }}</td>
                                    <td class="center">
                                        {!! compareTime($list->updated_at, date("Y-m-d H:i:s")) !!}
                                    </td>
                                    <td class="center">{{ $list->quantity }}</td>
                                    <td class="center">
                                        {!! ($list->time_upload != null)? compareTime($list->time_upload, date("Y-m-d H:i:s")) : '' !!}
                                    </td>
                                    <td class="center">
                                        {!! showTracking($list->tracking_number, $list->tracking_status) !!}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="center">
                                    Hiện tại chưa có đơn hàng nào cần tracking number.
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
