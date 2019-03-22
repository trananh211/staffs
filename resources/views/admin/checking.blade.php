@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Job Tables</div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <table class="responsive-table highlight">
                        <thead>
                        <tr>
                            <th class="center">#</th>
                            <th class="center" data-field="id">Order</th>
                            <th class="center" data-field="name">Item Name</th>
                            <th class="center" data-field="price">Designer</th>
                            <th class="center" data-field="price">Date</th>
                            <th class="center" data-field="price">Status</th>
                            <th class="center" data-field="price">Link</th>
                            <th class="center" data-field="price">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(sizeof($lists) > 0)
                            @foreach($lists as $key => $list)
                                <tr>
                                    <td class="center">{{ $key+1 }}</td>
                                    <td class="center"> {{ $list->number.'-PID-'.$list->id }}</td>
                                    <td class="center">{{ $list->name }}</td>
                                    <td class="center">{{ $list->worker_name }}</td>
                                    <td class="center">
                                        @if(time() - strtotime($list->updated_at) > 86400)
                                            <div class="red darken-1" style="color: rgba(255, 255, 255, 0.901961);">
                                                Nhanh lên
                                            </div>
                                        @else
                                            <div class="green lighten-1">Hôm Nay</div>
                                        @endif
                                    </td>
                                    <td class="center">
                                        <?php
                                        if ($list->status == 0) {
                                            $class = 'amber lighten-3';
                                            $status = 'Working';
                                        } else if ($list->status == 1) {
                                            $class = 'blue lighten-3';
                                            $status = 'Check Again';
                                        } else if ($list->status == 2) {
                                            $class = 'purple lighten-3';
                                            $status = 'Customer';
                                        } else if ($list->status == 3) {
                                            $class = 'green lighten-3';
                                            $status = 'Done';
                                        }
                                        if ($list->redo == 1){
                                            $class = 'red lighten-1';
                                            $status = 'Redo';
                                        }
                                        ?>
                                        <div class="{{ $class }}">{{ $status }}</div>
                                    </td>
                                    <td class="center">
                                        <a class="waves-effect m-b-xs" href="{{ url($list->permalink) }}"
                                           target="_blank">
                                            {{ substr($list->permalink,0,30) }} ...
                                        </a>
                                    </td>
                                    <td class="center">
                                        <a class="waves-effect waves-grey btn white modal-trigger"
                                           href="#modal{{ $key }}">Image</a>
                                        <div id="modal{{ $key }}" class="modal"
                                             style="z-index: 1003; display: none; opacity: 0; transform: scaleX(0.7); top: 250.516304347826px;">
                                            <div class="modal-content">
                                                <div class="card card-transparent">
                                                    <div class="card-content">
                                                        <div class="col s12 m12 l12">
                                                            <div class="card">
                                                                <div class="card-content">
                                                                    <a class="waves-effect waves-light btn green m-b-xs"
                                                                       href="{{ url('send-customer/'.$list->id) }}"
                                                                    >
                                                                        <i class="material-icons left">present_to_all</i>Gửi
                                                                        Khách Hàng
                                                                    </a>
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
                                                                        <img
                                                                            class="materialboxed responsive-img initialized"
                                                                            src="{{ asset(env('WORKING_DIR').$list->filename) }}"
                                                                            alt="">
                                                                    </div>
                                                                </div>
                                                                @if ($list->redo == 1)
                                                                    <div class="card red lighten-1">
                                                                        <div class="card-content">
                                                                            <p class="card-title">Redo</p>
                                                                            {{ $list->reason }}
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
                                <td colspan="6" class="center">
                                    Đã hết công việc kiểm tra Design. Vui lòng chuyển sang công việc xem phản hồi khách
                                    hàng.
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



