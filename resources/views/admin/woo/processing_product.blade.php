@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Trạng thái Template</div>
        </div>
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <table>
                        <thead>
                        <tr>
                            <th>#</th>
                            <th data-field="id">Name</th>
                            <th data-field="name">Time</th>
                            <th data-field="price">Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($lists as $key => $list)
                            <tr>
                                <td>{{ ++$key }}</td>
                                <td>{{ $list->name }}</td>
                                <td>{!! compareTime($list->updated_at, date("Y-m-d H:i:s")) !!}</td>
                                <td>{{ $list->status }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
