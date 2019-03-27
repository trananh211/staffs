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
                            <th>Image</th>
                            <th>Name</th>
                            <th>Job</th>
                            <th>Designer</th>
                            <th>Qc</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Job</th>
                            <th>Designer</th>
                            <th>Qc</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @if(sizeof($lists) > 0)
                            @foreach($lists as $key => $list)
                                <tr>
                                    <td>{{ $key+1 }}</td>
                                    <td>{!! thumb($list['path'],50,$list['name']) !!}</td>
                                    <td>{{ $list['title'] }}</td>
                                    <td>Idea-{{ $list['id'] }}</td>
                                    <td>{{ $list['worker'] }}</td>
                                    <td>{{ $list['qc'] }}</td>
                                    <td>{!! statusJob($list['status'], $list['redo'], $list['reason']) !!}</td>
                                    <td>{!! $list['date'] !!}</td>
                                    <td>Action</td>
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
