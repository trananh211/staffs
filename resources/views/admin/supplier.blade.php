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
                            <th class="center">Item Name</th>
                            <th class="center">Designer</th>
                            <th class="center">QC</th>
                            <th class="center">Date</th>
                            <th class="center">Tracking</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th class="center">#</th>
                            <th class="center">Order</th>
                            <th class="center">Item Name</th>
                            <th class="center">Designer</th>
                            <th class="center">QC</th>
                            <th class="center">Date</th>
                            <th class="center">Tracking</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @if(sizeof($lists) > 0)
                            @foreach($lists as $key => $list)
                                <tr>
                                    <td class="center">{{ $key+1 }}</td>
                                    <td class="center"> {{ $list->number.'-PID-'.$list->id }}</td>
                                    <td class="center">{{ $list->name }}</td>
                                    <td class="center">
                                        {{ $list->worker_name }}
                                    </td>
                                    <td class="center">
                                        {{ $list->qc_name }}
                                    </td>
                                    <td class="center">
                                        {{ $list->updated_at }}
                                    </td>
                                    <td class="center">
                                        LNC1234566899
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="center">
                                    Đã gửi toàn bộ . Vui lòng chuyển sang công việc xem phản hồi khách
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
