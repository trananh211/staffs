@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Danh Sách công việc đang làm</div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <table id="idea-job" class="display responsive-table datatable-example">
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
                            @foreach($lists as $key => $list)
                                <tr>
                                    <td class="center">{{ $key+1 }}</td>
                                    <td class="center"> {{ $list->number.'-PID-'.$list->id }}</td>
                                    <td class="center">{{ $list->name }}</td>
                                    <td class="center">{{ $list->worker_name }}</td>
                                    <td class="center">
                                        {!! compareTime($list->updated_at, date("Y-m-d H:i:s")) !!}
                                    </td>
                                    <td class="center">
                                        {!! statusJob($list->status, $list->redo, $list->reason) !!}
                                    </td>
                                    <td class="center">
                                        <a class="waves-effect m-b-xs" href="{{ url($list->permalink) }}"
                                           target="_blank">
                                            {{ substr($list->permalink,0,30) }} ...
                                        </a>
                                    </td>
                                    <td class="center">
                                        <a class="waves-effect waves-light blue btn"
                                           onclick="newWindow('{{ url('/detail-order/'.$list->id) }}', 1200, 800)"
                                        >Chi tiết</a>
                                        <a class="js-take-job waves-effect waves-light white btn"
                                           data-woo-order-id="{{ $list->woo_order_id }}"
                                           data-workingid="{{$list->id}}" data-url="{{ url('ajax-take-job') }}"
                                        >
                                            Đòi Job
                                        </a>
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
