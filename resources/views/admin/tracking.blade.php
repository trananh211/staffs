@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <div class="row">
                        <div class="col s10">
                            <form class="col s12" action="{{ route('search-tracking') }}" method="get">
                                {{ csrf_field() }}
                                <div class="input-field col s3">
                                    <input placeholder="MLF-6868-USA" name="order_id" type="text" class="validate"
                                        value="{{ (isset($order_id) && $order_id != null)? $order_id : '' }}">
                                    <label for="first_name">Tìm kiếm Order</label>
                                </div>
                                <div class="input-field col s5">
                                    <select name="status">
                                        <option value="5">Choose Status</option>
                                        @foreach( $arr_trackings as $key => $value)
                                            <option value="{{ $key }}" {{ (isset($status) && $status == $key )? 'selected' : '' }}>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                    <label>Trạng thái tracking</label>
                                </div>
                                <div class="col s4">
                                    <button type="submit" class="right waves-effect waves-light btn blue">
                                        <i class="material-icons dp48">search</i> <span>Tìm kiếm</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="col s2">
                            <a href="{{ url('get-file-tracking-now'.$url_download) }}" class="waves-effect waves-light btn green">
                                Download File</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <table id="review-job" class="display responsive-table datatable-example">
                        <thead>
                        <tr>
                            <th class="center">#</th>
                            <th class="center">Order</th>
                            <th class="center">Design Done</th>
                            <th class="center">Time Up Tracking</th>
                            <th class="center">Tracking</th>
                            <th class="center">Carrier</th>
                            <th class="center">Action</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th class="center">#</th>
                            <th class="center">Order</th>
                            <th class="center">Design Done</th>
                            <th class="center">Time Up Tracking</th>
                            <th class="center">Tracking</th>
                            <th class="center">Carrier</th>
                            <th class="center">Action</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @if(sizeof($lists) > 0 && sizeof($lists['data']) > 0)
                            <?php $i= 1; ?>
                            @foreach($lists['data'] as $list)
                                <tr>
                                    <td class="center">{{ $i++ }}</td>
                                    <td class="center"> {{ $list['number'] }}</td>
                                    <td class="center">
                                        {!! compareTime($list['updated_at'], date("Y-m-d H:i:s")) !!}
                                    </td>
                                    <td class="center">
                                        {!! ($list['time_upload'] != null)? compareTime($list['time_upload'], date("Y-m-d H:i:s")) : '' !!}
                                    </td>
                                    <td class="center">
                                        {!! showTracking($list['tracking_number'], $list['tracking_status']) !!}
                                    </td>
                                    <td class="center">{{ $list['shipping_method'] }}</td>
                                    <td class="center">
                                        Edit | Delete
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="center">
                                    Không tồn tại. Mời bạn thử lại
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
