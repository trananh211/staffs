@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Idea Tables</div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <table id="review-job" class="display responsive-table datatable-example">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Job</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Designer</th>
                            <th>Qc</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Job</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Designer</th>
                            <th>Qc</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @if(sizeof($lists) > 0)
                            @foreach($lists as $key => $list)
                                <tr>
                                    <th>{{ $key+1 }}</th>
                                    <th>{{ $list['id'] }}</th>
                                    <th>{{ $list['name'] }}</th>
                                    <th>{{ $list['status'] }}</th>
                                    <th>{{ $list['worker'] }}</th>
                                    <th>{{ $list['qc'] }}</th>
                                    <th>{{ $list['date'] }}</th>
                                    <th>Action</th>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8">
                                    Bạn chưa tạo job cho nhân viên làm. Vui lòng tạo job và quay lại đây.
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
