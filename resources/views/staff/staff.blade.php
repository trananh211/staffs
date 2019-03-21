@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Job Tables</div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <a href="{{ url('staff-get-job') }}" class="waves-effect waves-light btn indigo m-b-xs">
                        <i class="material-icons left">play_for_work</i>Nhận việc mới
                    </a>
                    <a onclick="newWindow('{{ url('staff-done-job') }}', 1200, 800)"
                       class="waves-effect waves-light btn green m-b-xs">
                        <i class="material-icons left">present_to_all</i>Trả hàng
                    </a>
                </div>
            </div>
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
                                            <a class="waves-effect waves-red btn-flat m-b-xs">Làm nhanh lên.</a>
                                        @else
                                            <a class="waves-effect waves-green btn-flat m-b-xs">Today</a>
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
                                        <a class="waves-effect waves-light btn m-b-xs"
                                           onclick="newWindow('{{ url('/detail-order/'.$list->id) }}', 1200, 800)">
                                            Xem thông tin</a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="center">
                                    Bạn chưa có bất kỳ công việc nào. Vui lòng nhận công việc ở trên.
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



