@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Job Tables</div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <table id="review-job" class="display responsive-table datatable-example">
                        <thead>
                        <tr>
                            <th class="center">#</th>
                            <th class="center">Order</th>
                            <th class="center">Item Name</th>
                            <th class="center">Date</th>
                            <th class="center">Status</th>
                            <th class="center">Designer</th>
                            <th class="center">QC</th>
                            <th class="center">Action</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th class="center">#</th>
                            <th class="center">Order</th>
                            <th class="center">Item Name</th>
                            <th class="center">Date</th>
                            <th class="center">Status</th>
                            <th class="center">Designer</th>
                            <th class="center">QC</th>
                            <th class="center">Action</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @if(sizeof($lists) > 0)
                            @foreach($lists as $key => $list)
                                <tr>
                                    <td class="center">{{ $key+1 }}</td>
                                    <td class="center"> {{ $list->number.'-PID-'.$list->id }}</td>
                                    <td class="center">{{ $list->name }}</td>
                                    <td class="center">{!! compareTime($list->updated_at, date("Y-m-d H:i:s")) !!}</td>
                                    <td class="center">{!! statusJob($list->status, $list->redo, $list->reason) !!}</td>
                                    <td class="center">{{ $list->worker_name }}</td>
                                    <td class="center">{{ $list->qc_name }}</td>
                                    <td class="center">
                                        <a working_id="{{ $list->id }}" order_id="{{ $list->woo_order_id }}"
                                            class="waves-effect waves-light btn green m-b-xs js-done-job">
                                            Supplier
                                        </a>
                                        <a class="waves-effect waves-grey btn white modal-trigger m-b-xs"
                                           href="#modal{{ $key }}">Image</a>
                                        <div id="modal{{ $key }}" class="modal"
                                             style="z-index: 1003; display: none; opacity: 0; transform: scaleX(0.7); top: 250.516304347826px;">
                                            <div class="modal-content">
                                                <div class="card card-transparent">
                                                    <div class="card-content">
                                                        <div class="col s12 m12 l12">
                                                            <div class="card">
                                                                <div class="card-content">
                                                                    <a class="waves-effect waves-light btn red m-b-xs js-btn-redo" order_id="{{ $list->id }}">
                                                                        <i class="material-icons left">thumb_down</i>Làm lại</a>
                                                                    <div class="row js-redo-form-{{ $list->id }}" style="display: none;">
                                                                        <form action="{{ url('redo-designer') }}" method="post" class="col s12">
                                                                            {{ csrf_field() }}
                                                                            <div class="row">
                                                                                <div class="input-field col s10">
                                                                                    <i class="material-icons prefix">mode_edit</i>
                                                                                    <input type="text" style="display: none;" name="order_id" value="{{ $list->id }}"/>
                                                                                    <textarea id="icon_prefix2" name="reason"
                                                                                              class="materialize-textarea"></textarea>
                                                                                    <label for="icon_prefix2">Lý do</label>
                                                                                </div>
                                                                                <div class="col s2">
                                                                                    <button type="submit" class="btn-floating btn-large waves-effect waves-light red"
                                                                                    ><i class="material-icons">send</i></button>
                                                                                </div>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col s6">
                                                                <div class="card white">
                                                                    <span class="card-title">Ảnh Gốc</span>
                                                                    <div class="card-content center">
                                                                        @foreach(explode(",",$list->image) as $image)
                                                                            <img
                                                                                class="materialboxed responsive-img initialized"
                                                                                src="{{ $image }}">
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col s6">
                                                                <div class="card white">
                                                                    <span class="card-title">Ảnh thiết kế</span>
                                                                    <div class="card-content center">
                                                                        @if(array_key_exists($list->id, $images))
                                                                            @foreach($images[$list->id] as $img)
                                                                                <img
                                                                                    class="materialboxed responsive-img initialized"
                                                                                    src="{{ asset($img) }}"
                                                                                    alt="">
                                                                                <div>{{ pathinfo($img)['basename'] }}</div>
                                                                            @endforeach
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                @if ($list->redo == 1)
                                                                    <div class="card red lighten-1">
                                                                        <div class="card-content">
                                                                            <p class="card-title">Redo</p>
                                                                            {!! html_entity_decode($list->reason) !!}
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                                <div class="card">
                                                                    <div class="card-content">
                                                                        <ul class="collection">
                                                                            @foreach(explode("-;-;-",$list->detail) as $detail)
                                                                                <li class="collection-item">{{ $detail }}</li>
                                                                            @endforeach
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
