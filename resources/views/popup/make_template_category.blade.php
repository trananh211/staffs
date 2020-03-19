@extends('popup')
@section('content')
    <div class="col s12 m12 l12">
        <div class="row">
            {{-- Show Table Example--}}
            <div class="col s12">
                <div class="card white">
                    <div class="card-content center">
                        <span class="card-title">Xem trước File Excel</span>
                        <div class="row">
                            <table id="js-table-show-title" class="example-table">
                                <thead>
                                <tr class="th">
                                        @foreach ($excel_titles as $item)
                                            <td class="js-table-{{ $item['key_title'] }}">{{ $item['title'] }}</td>
                                        @endforeach
                                </tr>
                                </thead>
                                <tbody>
                                <tr class="td">
                                        @foreach ($excel_titles as $item)
                                            <td class="js-table-{{ $item['key_title'] }}">{{ ($item['fixed'] != '') ? $item['fixed'] : '.'  }}</td>
                                        @endforeach
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <form action="{{ url('new-template-category') }}" method="post" class="col s12">
                                {{ csrf_field() }}
                                <div class="row hidden">
                                    <div class="input-field col s10">
                                        <i class="material-icons prefix">mode_edit</i>
                                        <input type="text" name="tool_category_id" value="{{ $tool_category_id }}"/>
                                        <textarea id="js-data-title" name="data_title" class="materialize-textarea"></textarea>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col s2">
                                        <button type="submit" class="btn waves-effect waves-light blue">
                                            Đồng Ý
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Show Table Example--}}

            {{-- Add Colum--}}
            <div class="col s7">
                <div class="card white">

                    <div class="card-content center">
                        <span class="card-title">Chọn Tiêu Đề</span>
                        <div class="row">
                            <div class="input-field col s4">
                                <select class="js-key-title browser-default" tabindex="-1" style="width: 100%" id="basic">
                                    <option class="js-option option-default" value="0">Choose option</option>
                                    @foreach ($lst_titles as $group => $titles)
                                        <optgroup label="{{ $group }}">
                                            @foreach ($titles as $title => $example)
                                                @if (array_key_exists($title, $excel_titles))
                                                    <option class="js-option option-title-{{ $title }} hidden" value="{{ $title }}"><b>{{ $title }}</b> (ex: {{ $example }})</option>
                                                @else
                                                    <option class="js-option option-title-{{ $title }}" value="{{ $title }}"><b>{{ $title }}</b> (ex: {{ $example }})</option>
                                                @endif

                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="input-field col s4">
                                <input placeholder="Ex: Size" id="title" type="text" class="js-title validate">
                                <label for="title">Title</label>
                            </div>
                            <div class="input-field col s4">
                                <input placeholder="Ex: One_Size" id="value" type="text"
                                       class="js-title-fixed validate">
                                <label for="value">Fixed Value</label>
                            </div>
                        </div>

                        <div class="row">
                            <a class="js-view waves-effect waves-light btn right">Xem Trước</a>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Add Colum--}}

            {{-- Show Colum--}}
            <div class="col s5">
                <div class="card white">
                    <div class="card-content center">
                        <span class="card-title">Danh Sách Tiêu Đề</span>
                        <ul id="js-show-title" class="collection with-header">
                            <?php $i = 1; ?>
                            @foreach ($excel_titles as $item)
                                <li class="collection-item js-li-show-{{ $item['key_title'] }}">
                                    <div>
                                        {{$i}}.
                                        <span class="js-title-result hidden">
                                            {{ $item['key_title'] }}-{{ $item['title'] }}-{{ ($item['fixed'] != '')? $item['fixed']: '.' }}
                                        </span>
                                        ({{ $item['key_title'] }}) - {{ $item['title'] }} - {{ ($item['fixed'] != '')? $item['fixed']: '.' }}
                                        <a href="#" data-title="{{ $item['key_title'] }}" class="js-remove secondary-content"><i
                                                class="material-icons">delete</i></a>
                                    </div>
                                </li>
                                <?php $i++; ?>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            {{-- Show Colum--}}
        </div>
    </div>
@endsection

