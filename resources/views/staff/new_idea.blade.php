@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Idea Tables</div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
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
                    <table id="review-job" class="display responsive-table datatable-example">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Job</th>
                            <th>Qc</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Job</th>
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
                                    <td>{{ $list->title }}</td>
                                    <td>Idea-{{ $list->id }}</td>
                                    <td>{{ (array_key_exists($list->qc_id,$users))? $users[$list->qc_id] : '' }}</td>
                                    <td>{!! statusJob($list->status, $list->redo, $list->reason) !!}</td>
                                    <td>{!! compareTime($list->updated_at, $now) !!}</td>
                                    <td>
                                        <!-- Modal Trigger -->
                                        <a class="waves-effect waves-light btn modal-trigger" href="#modal{{ $key }}">
                                            Chi tiết
                                        </a>
                                        <!-- Modal Structure -->
                                        <div id="modal{{ $key }}" class="modal">
                                            <div class="modal-content">
                                                <h4>Modal Header</h4>
                                                <div class="col s12 m12 l12">
                                                    <div class="card">
                                                        <div class="card-content">
                                                            <ul>
                                                                <li>Bạn phải lưu file Mockup với tên : <b>Idea-{{ $list->id }}_mockup </b></li>
                                                                <li>Bạn phải lưu file Design với tên : <b>Idea-{{ $list->id }}_1, Idea-{{ $list->id }}_2,
                                                                        ... </b></li>
                                                                <li>Và sử dụng những yêu cầu dưới đây để làm file sản xuất.</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col s6 m12 l6">
                                                    <div class="card">
                                                        <div class="card-content">
                                                            <p class="card-title">Image</p>
                                                            <div class="material-placeholder" style="">
                                                                 {!!   thumb_w($list->path, '320' ,$list->name) !!}
                                                            </div>
                                                            <div>
                                                                <a class="btn" href="{{ url($list->path) }}" download>Download</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col s16 m12 l6">
                                                    @if ($list->redo == 1)
                                                        <div class="card red lighten-1">
                                                            <div class="card-content">
                                                                <p class="card-title">Redo</p>
                                                                {{ $list->reason }}
                                                            </div>
                                                        </div>
                                                    @endif
                                                    <div class="card">
                                                        <div class="card-content">
                                                            <p class="card-title">Note</p>
                                                            {!! html_entity_decode($list->require) !!}
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
                                <td colspan="7">
                                    Chưa có công việc. Vui lòng báo với quản lý của bạn để nhận việc mới.
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
