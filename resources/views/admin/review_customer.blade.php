@extends('master')
@section('content')
<div class="row">
    <div class="col s12 js-redo-new-url" url="{{ url('ajax-redo-new-sku') }}">
        <div class="page-title">Review Customer about Job</div>
    </div>
<div class="col s12 m12 l12">
<div class="card">
<div class="card-content">
    <table id="review-job" class="display responsive-table datatable-example">
        <thead>
        <tr>
            <th class="center">#</th>
            <th class="center">Job</th>
            <th class="center">Time</th>
            <th class="center">Status</th>
            <th class="center">Designer</th>
            <th class="center">QC</th>
            <th class="center">Action</th>
            <th class="center">FulFill</th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <th class="center">#</th>
            <th class="center">Job</th>
            <th class="center">Time</th>
            <th class="center">Status</th>
            <th class="center">Designer</th>
            <th class="center">QC</th>
            <th class="center">Action</th>
            <th class="center">FulFill</th>
        </tr>
        </tfoot>
        <tbody>
        @if(sizeof($lists) > 0)
            @foreach($lists as $key => $list)
                <tr>
                    <td class="center">{{ $key+1 }}</td>
                    <td class="center"> {{ $list['info']['sku'].'-PID-'.$list['info']['id'] }}</td>
                    <td class="center">{!! compareTime($list['info']['updated_at'], date("Y-m-d H:i:s")) !!}</td>
                    <td class="center">{!! statusJob($list['info']['status'], $list['info']['redo'], $list['info']['reason']) !!}</td>
                    <td class="center">{{ $list['info']['worker_name'] }}</td>
                    <td class="center">{{ $list['info']['qc_name'] }}</td>
<td class="center">
    <a class="waves-effect waves-grey btn white modal-trigger m-b-xs amber lighten-3"
       href="#modal{{ $list['info']['id'] }}">Check</a>
    <div id="modal{{ $list['info']['id'] }}" class="modal"
         style="z-index: 1003; display: none; opacity: 0; transform: scaleX(0.7); top: 250.516304347826px;">
        <div class="col s12">
            <div class="page-title">
                Đang Check Job: {{ $list['info']['sku'].'-PID-'.$list['info']['id'] }}
            </div>
        </div>
        <div class="modal-content">
            <div class="col s12 m12 l12">
                <div class="card">
                    <div class="card-content">

                        {{-- Đây là table toàn bộ order của khách trong Job này--}}
                        <div class="row">
                            <table class="striped">
                                <thead>
                                <tr>
                                    <th data-field="id">Order</th>
                                    <th data-field="detail">Detail</th>
                                    <th data-field="sku">SKU</th>
                                    <th data-field="name">Name</th>
                                    <th data-field="email">Email</th>
                                    <th data-field="action">Action</th>
                                </tr>
                                </thead>
                                <tbody style="font-size: 13px;">
                                @foreach($list['orders'] as $order)
                                <tr class="js-remove-tr-{{ $order['id'] }}">
                                    <td>{{ $order['number'] }}</td>
                                    <td>
                                        <?php
                                            $tmp_detail = explode('-;-;-',$order['variation_full_detail']);
                                            if ($tmp_detail > 0) {
                                               foreach ($tmp_detail as $key => $detail) {
                                                   if ($detail != ''){
                                                       echo ++$key.' : '.$detail."<br>";
                                                   }
                                               }
                                            } else {
                                                echo $order['variation_full_detail'];
                                            }
                                            ?>
                                    </td>
                                    <td>{{ $order['sku'] }}</td>
                                    <td>{{ $order['fullname'] }}</td>
                                    <td>{{ $order['email'] }}</td>
                                    <td>
                                        <a class="js-show-redo-sku waves-effect waves-light btn red m-b-xs"
                                           order_id="{{ $order['id'] }}"
                                           title="Yêu cầu Design làm lại thành một mẫu khác hoàn toàn với SKU mới"
                                        >Redo SKU</a>
                                    </td>
                                </tr>
                                {{-- Show redo sku --}}
                                <tr class="js-show-redo-sku-{{ $order['id'] }} js-remove-tr-{{ $order['id'] }}" style="display: none;">
                                    <td colspan="6" class="grey lighten-1">
                                        <div class="row">
                                            <div class="col s12 m4 l4">
                                                <div class="card white">
                                                    <div class="card-content center">
                                                        <div class="row">
                                                            <div class="card-content center">
                                                                <input required placeholder="NameMLF1234" type="text" class="validate js-redo-new-sku-{{ $order['id'] }}">
                                                                <label>New Sku</label>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <label>Designer</label>
                                                            <select class="browser-default js-redo-new-designer-{{ $order['id'] }}">
                                                                <option value="" disabled selected>Choose designer</option>
                                                                @foreach($workers as $worker)
                                                                    <option {{ ($list['info']['worker_id'] == $worker->id) ? 'selected' : '' }} value="{{$worker->id}}">
                                                                        {{ $worker->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col s12 m4 l8">
                                                <div class="card white">
                                                    <div class="card-content center">
                                                        <div class="row">
                                                            <div class="input-field col s12">
                                                                <textarea id="textarea1" class="materialize-textarea js-redo-new-reason-{{ $order['id'] }}" length="255"></textarea>
                                                                <label for="textarea1">Lý do redo</label>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <span class="waves-effect waves-light btn red lighten-2 js-redo-new-submit"
                                                                  order-id="{{ $order['id'] }}" working-id="{{ $list['info']['id'] }}"
                                                                  design-id="{{ $list['info']['design_id'] }}" variation="{{ $list['info']['variation'] }}"
                                                                      >
                                                                Submit
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                {{-- End Show redo SKU--}}
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{-- End Table toàn bộ order của khách trong Job này--}}

                        {{-- Redo toan bộ job--}}
                        @if ($list['info']['status'] == env('STATUS_WORKING_CUSTOMER'))
                        <div class="row">
                            <a title="Yêu cầu Designer làm lại design đã thiết kế. Cần đưa thêm lý do để Designer làm lại."
                                class="waves-effect waves-light btn red m-b-xs js-btn-redo" order_id="{{ $list['info']['id'] }}">
                                <i class="material-icons left">thumb_down</i>Làm Lại Cả Job</a>
                            <div class="js-redo-form-{{ $list['info']['id'] }}" style="display: none;">
                                <form action="{{ url('redo-designer') }}" method="post" class="col s12">
                                    {{ csrf_field() }}
                                    <div class="row">
                                        <div class="input-field col s10">
                                            <i class="material-icons prefix">mode_edit</i>
                                            <input type="text" style="display: none;" name="order_id" value="{{ $list['info']['id'] }}"/>
                                            <textarea id="icon_prefix2" name="reason" class="materialize-textarea"></textarea>
                                            <label for="icon_prefix2">Lý do</label>
                                        </div>
                                        <div class="col s2">
                                            <button type="submit" class="btn-floating btn-large waves-effect waves-light red">
                                                <i class="material-icons">send</i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endif
                        {{-- End Redo toàn bộ job--}}

                        {{-- Hiển thị ảnh, thông tin redo và các thông tin khác--}}
                        <div class="row">
                            <div class="col s6">
                                <div class="card white">
                                    <span class="card-title">Ảnh Gốc</span>
                                    <div class="card-content center">
                                        @foreach(explode(",",$list['info']['image']) as $key => $image)
                                            @if ($key < 2)
                                                {!! thumb_c($image, env('THUMB'), basename($image)) !!}
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col s6">
                                <div class="card white">
                                    <span class="card-title">Ảnh thiết kế</span>
                                    {{-- Show ly do redo --}}
                                    <div class="row">
                                        @if ($list['info']['redo'] == 1)
                                            <div class="card red lighten-1">
                                                <div class="card-content">
                                                    <p class="card-title">Redo</p>
                                                    {!! html_entity_decode($list['info']['reason']) !!}
                                                    </p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    {{-- End Show ly do redo --}}
                                    {{-- Anh thiết kế của designer --}}
                                    <div class="row">
                                        <div class="card-content center">
                                            @if(array_key_exists($list['info']['id'], $images))
                                                @foreach($images[$list['info']['id']] as $img)
                                                    {!! ($img['thumb'] != '') ? thumb_c($img['thumb'], env('THUMB'), '') : '' !!}
                                                    <div>{{ $img['name'] }}</div>
                                                @endforeach
                                                <?php unset($images[$list['info']['id']]); ?>
                                            @endif
                                        </div>
                                    </div>
                                    {{-- End Anh thiết kế của designer --}}

                                </div>
                            </div>
                        </div>
                        {{-- End Hiển thị ảnh, thông tin redo và các thông tin khác--}}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if ($list['info']['status'] == env('STATUS_WORKING_CUSTOMER'))
        <a working_id="{{ $list['info']['id'] }}" design_id="{{ $list['info']['design_id'] }}"
           data-url="{{ url('ajax-re-send-email') }}"
           onclick="return confirm('Bạn có chắc chắn muốn gửi lại email cho khách hàng này?');"
           class="waves-effect waves-light btn blue m-b-xs js-re-send-email">
            Gửi lại email
        </a>
    @endif

</td>
                    <td>
                        @if ($list['info']['status'] == env('STATUS_WORKING_CUSTOMER'))
                            <a working_id="{{ $list['info']['id'] }}" design_id="{{ $list['info']['design_id'] }}"
                               class="waves-effect waves-light btn green m-b-xs js-done-job">
                                Supplier
                            </a>
                        @endif

                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="8" class="center">
                    Đã hết phản hồi khách hàng. Liên hệ với quản lý để làm công việc tiếp theo.
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
