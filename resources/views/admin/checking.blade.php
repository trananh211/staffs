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
                            <th class="center" data-field="id">Order</th>
                            <th class="center" data-field="name">Item Name</th>
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
                                    <td class="center"> {{ $list->number.'-PID-'.$list->id }}</td>
                                    <td class="center">{{ $list->name }}</td>
                                    <td class="center">
                                        @if(time() - strtotime($list->created_at) > 86400)
                                            <a class="waves-effect waves-red btn-flat m-b-xs">Kiểm_Tra_Nhanh.</a>
                                        @else
                                            <a class="waves-effect waves-green btn-flat m-b-xs">Hôm_Nay</a>
                                        @endif
                                    </td>
                                    <td class="center">
                                        <?php
                                        if ($list->status == 0) {
                                            $class = 'orange';
                                            $status = 'Working';
                                        } else if ($list->status == 1) {
                                            $class = 'blue';
                                            $status = 'Check Again';
                                        } else if ($list->status == 2) {
                                            $class = 'purple';
                                            $status = 'Send_Customer';
                                        } else if ($list->status == 3) {
                                            $class = 'green';
                                            $status = 'Done';
                                        }
                                        ?>
                                        <a class="waves-effect waves-{{$class}} btn-flat">{{ $status }}</a>
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
                                                                        <i class="material-icons left">present_to_all</i>Gửi Khách Hàng
                                                                    </a>
                                                                    <a class="waves-effect waves-light btn red m-b-xs">
                                                                        <i class="material-icons left">thumb_down</i>Làm lại</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col s6">
                                                                <div class="card white">
                                                                    <span class="card-title">Ảnh Gốc</span>
                                                                    <div class="card-content center">
                                                                        @foreach(explode(",",$list->image) as $image)
                                                                            <img class="materialboxed responsive-img initialized"
                                                                                 src="{{ $image }}">
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col s6">
                                                                <div class="card white">
                                                                    <span class="card-title">Ảnh thiết kế</span>
                                                                    <div class="card-content center">
                                                                        <img class="materialboxed responsive-img initialized"
                                                                             src="{{ asset(env('WORKING_DIR').$list->filename) }}" alt="">
                                                                        <div class="divider"></div>
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
                                    Đã hết công việc kiểm tra Design. Vui lòng chuyển sang công việc xem phản hồi khách hàng.
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



