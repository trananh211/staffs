@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Idea Tables</div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <table id="idea-job" class="display responsive-table datatable-example">
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
                            <?php $i = 1; ?>
                            @foreach($lists as $key => $list)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td>{!! thumb($list['path'],50,$list['name']) !!}</td>
                                    <td>{{ $list['title'] }}</td>
                                    <td>Idea-PID-{{ $list['id'] }}</td>
                                    <td>{{ $list['worker'] }}</td>
                                    <td>{{ $list['qc'] }}</td>
                                    <td>{!! statusJob($list['status'], $list['redo'], $list['reason']) !!}</td>
                                    <td>{!! $list['date'] !!}</td>
                                    <td>
                                        <a class="waves-effect waves-light btn modal-trigger"
                                           href="#modal{{ $key }}">
                                            Chi tiết
                                        </a>
                                        <div id="modal{{ $key }}" class="modal">
                                            <div class="modal-content">
                                                <h4>Check Idea</h4>
                                                <div class="card-content">
                                                    @if(array_key_exists($list['id'],$idea_files))
                                                        <div class="col s12 m12 l12">
                                                            <div class="card">
                                                                <div class="card-content">
                                                                    <a class="js-send-qc waves-effect waves-light btn green m-b-xs"
                                                                       href="#"
                                                                       data-url="{{ url('ajax-idea-send-qc') }}"
                                                                       data-id="{{ $list['id'] }}"
                                                                    >
                                                                        <i class="material-icons left">present_to_all</i>Gửi
                                                                        Support
                                                                    </a>
                                                                    <a class="waves-effect waves-light btn red m-b-xs js-btn-redo"
                                                                       order_id="{{ $list['id'] }}">
                                                                        <i class="material-icons left">thumb_down</i>Làm
                                                                        lại</a>
                                                                    <div class="row js-redo-form-{{ $list['id'] }}"
                                                                         style="display: none;">
                                                                        <form action="#" id="js-redo-idea"
                                                                              enctype="multipart/form-data"
                                                                              class="col s12">
                                                                            {{ csrf_field() }}
                                                                            <div class="row">
                                                                                <div class="input-field col s10">
                                                                                    <i class="material-icons prefix">mode_edit</i>
                                                                                    <input type="text"
                                                                                           style="display: none;"
                                                                                           data-url="{{ url('ajax-redo-idea') }}"
                                                                                           class="url"
                                                                                    />
                                                                                    <input type="text"
                                                                                           style="display: none;"
                                                                                           class="js-idea-id"
                                                                                           name="idea_id"
                                                                                           value="{{ $list['id'] }}"/>
                                                                                    <textarea id="icon_prefix2"
                                                                                              name="reason"
                                                                                              class="js-idea-reason materialize-textarea"></textarea>
                                                                                    <label for="icon_prefix2">Lý
                                                                                        do</label>
                                                                                </div>
                                                                                <div class="col s2">
                                                                                    <a class="js-redo-button btn-floating btn-large waves-effect waves-light red"
                                                                                       data-id="{{ $list['id'] }}"
                                                                                    ><i class="material-icons">send</i>
                                                                                    </a>
                                                                                </div>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    <div class="col s6 m12 l6">
                                                        @if ($list['redo'] == 1)
                                                            <div class="card red lighten-1">
                                                                <div class="card-content">
                                                                    <p class="card-title">Redo</p>
                                                                    {!! html_entity_decode($list['reason']) !!}
                                                                </div>
                                                            </div>
                                                        @endif
                                                        <div class="card">
                                                            <div class="card-content">
                                                                <p class="card-title">Note</p>
                                                                {!! html_entity_decode($list['require']) !!}
                                                            </div>
                                                        </div>
                                                        <div class="card">
                                                            <div class="card-content">
                                                                <p class="card-title">Image</p>
                                                                <div class="material-placeholder" style="">
                                                                    {!! thumb_w($list['path'],env('THUMB'), $list['name']) !!}
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>

                                                    <div class="col s16 m12 l6">
                                                        <div class="card">
                                                            <div class="card-content">
                                                                <p class="card-title">Ảnh thiết kế</p>
                                                                @if(array_key_exists($list['id'],$idea_files))
                                                                    @foreach($idea_files[$list['id']] as $img)
                                                                        <div>
                                                                            {!! thumb_w($img['idea_files_path'], env('THUMB'), $img['idea_files_name']) !!}
                                                                            <h6>{{ $img['idea_files_name'] }}</h6>
                                                                        </div>
                                                                    @endforeach
                                                                @else
                                                                    <p>
                                                                        Hiện tại chưa Designer chưa làm xong công việc
                                                                        này.
                                                                    </p>
                                                                @endif
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
