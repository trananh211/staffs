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
                    <a onclick="newWindow('{{ url('staff-done-job/'.env('UP_ORDER')) }}', 1200, 800)"
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
                                        <a class="waves-effect waves-light btn m-b-xs"
                                           onclick="newWindow('{{ url('/detail-order/'.$list->id) }}', 1200, 800)">
                                            <i class="material-icons left">visibility</i> Xem thông tin</a>
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



