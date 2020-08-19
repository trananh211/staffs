@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <div class="row">
                        <div class="col s8">
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
                        <div class="col s2">
                            <!-- Modal Trigger -->
                            <a class="waves-effect waves-light btn modal-trigger blue" href="#modal-uptracking">Up Tracking</a>
                            <!-- Modal Structure -->
                            <div id="modal-uptracking" class="modal">
                                <div class="modal-content">
                                    <ul id="js-noti" class="text-darken-2"></ul>
                                    <form class="col s12" id="form-up-tracking" method="post" data-url="{{ url('action-up-tracking') }}">
                                        {{ csrf_field() }}
                                        <div class="row">
                                            <div class="input-field col s6">
                                                <input type="file" multiple name="files[]" required style="border:1px solid #ccc; padding: 60px;"/>
                                            </div>
                                            <div class="input-field col s6">

                                                <div class="row">
                                                    <!-- Switch -->
                                                    <div class="switch m-b-md">
                                                        <label>
                                                            Status Shipping Default
                                                            <input type="checkbox" name="fixed_shipping">
                                                            <span class="lever"></span>
                                                            Fixed Status Shipping
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="row">

                                                    <select name="type_upload">
                                                        <option value="1">Thay thế tracking cũ (1 order 1 item)</option>
                                                        <option value="2">Thêm tracking mới ( 1 order nhiều item)</option>
                                                    </select>
                                                    <label>Dạng upload</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col s12">
                                                <button type="submit" class="right waves-effect waves-light btn blue">Update</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <div class="row">
                        <div aria-label="Page navigation">
                            <ul class="pagination">
                                <li class="page-item">
                                    <a class="page-link"
                                       href="{{ ($lists['prev_page_url'] != '')? $lists['prev_page_url'].$lists['param_url']: '#'  }}">Previous</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link active" href="#">{{ $lists['current_page'] }}</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link"
                                       href="{{ ($lists['next_page_url'] != '')? $lists['next_page_url'].$lists['param_url']: '#'  }}">Next</a>
                                </li>
                                <a class="page-link right"> Page {{ $lists['current_page'] }} / {{ $lists['last_page'] }}</a>

                            </ul>
                        </div>
                    </div>
                    <table class="display responsive-table datatable-example">
                        <thead>
                        <tr>
                            <th class="center">#</th>
                            <th class="center">Order</th>
                            <th class="center">Product Name</th>
                            <th class="center">Design Done</th>
                            <th class="center">Time Up Tracking</th>
                            <th class="center">Tracking</th>
                            <th class="center">Carrier</th>
                            <th class="center">Note</th>
                            <th class="center">Action</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th class="center">#</th>
                            <th class="center">Order</th>
                            <th class="center">Product Name</th>
                            <th class="center">Design Done</th>
                            <th class="center">Time Up Tracking</th>
                            <th class="center">Tracking</th>
                            <th class="center">Carrier</th>
                            <th class="center">Note</th>
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
                                    <td class="center"> {{ $list['product_name'] }}</td>
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
                                        @if(isset($list['tracking_id']) && $list['tracking_id'] != '' && $list['note'] != '')
                                            <a class="tooltipped" data-position="top" data-delay="50" data-tooltip="{{ html_entity_decode($list['note']) }}">Ghi chú: ...</a>
                                        @endif
                                    </td>
                                    <td class="center">
                                        @if(isset($list['tracking_id']) && $list['tracking_id'] != '')
                                            <!-- Modal Trigger -->
                                            <a class="waves-effect waves-light btn blue modal-trigger" href="#modal{{ $list['tracking_id'] }}">Edit</a>

                                            <!-- Modal Structure -->
                                                <div id="modal{{ $list['tracking_id'] }}" class="modal">
                                                    <div class="page-title">Đang thay đổi thông tin order: {{ $list['number'] }}</div>
                                                    <div class="modal-content">
                                                        <form class="col s12" action="{{url('edit-tracking-number')}}" method="post">
                                                            {{ csrf_field() }}
                                                            <div class="row hidden">
                                                                <div class="input-field col s12">
                                                                    <input class="validate" name="tracking_id" value="{{ $list['tracking_id'] }}" required>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="input-field col s6">
                                                                    <input placeholder="LZ009298136CN" name="tracking_number" type="text"
                                                                           value="{{ ($list['tracking_number'] != '')? $list['tracking_number'] : '' }}" class="validate" required>
                                                                    <label for="first_name">Tracking Number</label>
                                                                </div>
                                                                <div class="input-field col s6">
                                                                    <input placeholder="DHL eCommerces, DHL, USPS" name="shipping_method" type="text"
                                                                           value="{{ ($list['shipping_method'] != '')? $list['shipping_method'] : '' }}" class="validate" required>
                                                                    <label for="first_name">Shipping Method</label>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="input-field col s12">
                                                                    <textarea id="textarea1" name="note" class="materialize-textarea" length="520">
                                                                        {{ ($list['note'] != '')? html_entity_decode($list['note']) : '' }}
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
                                        @endif
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
