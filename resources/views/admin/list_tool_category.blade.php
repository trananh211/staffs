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
                            <th>Category</th>
                            <th>Type Fulfill</th>
                            <th>Exclude Text</th>
                            <th>Edit</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th>Type Fulfill</th>
                            <th>Exclude Text</th>
                            <th>Edit</th>
                            <th>Action</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @if(sizeof($categories) > 0)
                            @foreach($categories as $key => $category)
                                <tr>
                                    <td>{{ ++$key }}</td>
                                    <td>{{ $category->name }}</td>
                                    <td>{{ $list_type[$category->type_fulfill_id] }}</td>
                                    <td>{{ $category->exclude_text }}</td>
                                    <td>
                                        <!-- Modal Trigger -->
                                        <a class="waves-effect waves-light btn modal-trigger" href="#modal{{ $category->id }}">Edit</a>
                                        <!-- Modal Structure -->
                                        <div id="modal{{ $category->id }}" class="modal">
                                            <div class="row">
                                            <div class="modal-content">
                                                <h4>Edit Category: {{ $category->name }}</h4>
                                                <form class="col s12" action="{{url('edit-category-fulfill')}}" method="post">
                                                    {{ csrf_field() }}
                                                    <div class="row">

                                                        <div class="input-field col s6">
                                                            <small>Chọn dạng sẽ hiển thị link fulfill</small>
                                                            <select name="type_fulfill_id" required>
                                                                {{--<option value="" disabled>Choose your option</option>--}}
                                                                @foreach ($list_type as $type_id => $type)
                                                                    <option value="{{ $type_id }}" {{ ($type_id == $category->type_fulfill_id)? 'selected': '' }}>
                                                                        {{ $type }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <label>Type Design Url Select</label>
                                                        </div>
                                                        <div class="input-field col s6 hidden">
                                                            <input value="{{ $category->id }}" name="tool_category_id" type="text" class="validate">
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="input-field col s12">
                                                            <small>Phân cách các link bằng dấu này. (Chỉ sử dụng cho dạng multil file)</small>
                                                            <input
                                                                value="{{ ($category->exclude_text != '')? $category->exclude_text : '' }}"
                                                                placeholder="Ví dụ: ; hoặc , " name="exclude_text" type="text" class="validate">
                                                            <label for="first_name">Exclude Text</label>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col s12">
                                                            <button type="submit" class="right waves-effect waves-light btn blue">
                                                                Cập nhật
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a onclick="newWindow('{{ url('make-template-category/'.$category->id) }}', 1200, 800)"
                                           class="waves-effect waves-light btn green m-b-xs">
                                            <i class="material-icons left">present_to_all</i>Template Fulfill
                                        </a>
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
