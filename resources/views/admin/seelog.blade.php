@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Job Tables</div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <table id="idea-job" class="display responsive-table datatable-example">
                        <thead>
                        <tr>
                            <th class="center">#</th>
                            <th class="center">Name</th>
                            <th class="center">Action</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th class="center">#</th>
                            <th class="center">Name</th>
                            <th class="center">Action</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @if(sizeof($files) > 0)
                            @foreach($files as $key => $file)
                                <tr>
                                    <td class="center">{{ $key+1 }}</td>
                                    <td class="center">{{ $file->getFilename() }}</td>
                                    <td class="center">
                                        <a class="waves-effect waves-light blue btn"
                                           onclick="newWindow('{{ url('/detail-log/'.$file->getFilename()) }}', 1200, 800)"
                                        >Chi tiết</a>
                                        <a class="waves-effect waves-light red btn js-delete-log"
                                           data-name="{{ $file->getFilename() }}" data-url="{{ url('ajax-delete-log') }}"
                                        >Xóa File</a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="3" class="center">
                                    Khong co log file
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

