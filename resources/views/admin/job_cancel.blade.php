@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Job Tables</div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <table class="responsive-table highlight">
                        <thead>
                        <tr>
                            <th class="center">#</th>
                            <th class="center" data-field="name">Category</th>
                            <th class="center" data-field="id">Order</th>
                            <th class="center" data-field="name">Variation</th>
                            <th class="center" data-field="price">Designer</th>
                            <th class="center" data-field="price">Date</th>
                            <th class="center" data-field="price">Status</th>
                            <th class="center" data-field="price">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(sizeof($lists) > 0)
                            <?php $i = 1; ?>
                            @foreach($lists as $key => $list)
                                <tr>
                                    <td class="center">{{ $i++ }}</td>
                                    <td class="center">
                                        @if($list->tool_category_name != '')
                                            {{ $list->tool_category_name }}
                                        @else
                                            <a href="{{ url('list-variation-category') }}" class="waves-effect waves-red btn-flat">Cần bổ sung</a>
                                        @endif
                                    </td>
                                    <td class="center"> {{ $list->sku.'-PID-'.$list->id }}</td>
                                    <td class="center">{{ $list->variation }}</td>
                                    <td class="center">{{ $list->worker_name }}</td>
                                    <td class="center">
                                        {!! compareTime($list->updated_at, date("Y-m-d H:i:s")) !!}
                                    </td>
                                    <td class="center">
                                        {!! statusJob($list->status, $list->redo, $list->reason) !!}
                                    </td>
                                    <td class="center">
                                        <a class="waves-effect waves-grey btn white modal-trigger"
                                           href="#modal{{ $key }}">Image</a>
                                        <div id="modal{{ $key }}" class="modal"
                                             style="z-index: 1003; display: none; opacity: 0; transform: scaleX(0.7); top: 250.516304347826px;">
                                            <div class="modal-content">
                                                <div class="card card-transparent">
                                                    <div class="card-content">
                                                        <span class="card-content">
                                                            Job: {{ $list->sku.'-PID-'.$list->id }} - Variation: {{ $list->variation }}
                                                        </span>
                                                        <div class="col s12 m12 l12">
                                                            {{-- Cập nhật category--}}
                                                            @if($list->tool_category_name == '')
                                                                <div class="card">
                                                                    <div class="card-content">
                                                                        <div class="row">
                                                                            <form action="{{ url('update-tool-category') }}" method="post" class="col s12">
                                                                                {{ csrf_field() }}
                                                                                <div class="row">
                                                                                    <div class="input-field col s12">
                                                                                        <input type="text" style="display: none;" name="design_id" value="{{ $list->design_id }}"/>
                                                                                    </div>
                                                                                    <div class="input-field col s6">
                                                                                        <select name="tool_category_id" required>
                                                                                            <option value="" disabled selected>Choose Category</option>
                                                                                            @foreach ($tool_categories as $category)
                                                                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                                                            @endforeach
                                                                                        </select>
                                                                                        <label>Choose Category</label>
                                                                                    </div>
                                                                                    <div class="col s6">
                                                                                        <button type="submit" class="btn btn-large waves-effect waves-light light-green"
                                                                                        > Cập nhật Category
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            </form>
                                                                        </div>

                                                                    </div>
                                                                </div>
                                                            @endif
                                                            {{-- End cập nhật category--}}
                                                            {{-- Cập nhật variations--}}
                                                            @if($list->variation == '')
                                                                <div class="card">
                                                                    <div class="card-content">
                                                                        <div class="row">
                                                                            <form action="{{ url('working-change-variation') }}" method="post" class="col s12">
                                                                                {{ csrf_field() }}
                                                                                <div class="row">
                                                                                    <div class="input-field col s12">
                                                                                        <input type="text" style="display: none;" name="design_id" value="{{ $list->design_id }}"/>
                                                                                    </div>
                                                                                    <div class="input-field col s6">
                                                                                        @if ($list->tool_category_id != '')
                                                                                            <select name="variation_name" required>
                                                                                                <option value="" disabled selected>Choose Variation</option>
                                                                                                @foreach ($variations as $vari)
                                                                                                    <?php
                                                                                                    $variation_name = ($vari->variation_real_name != '')? $vari->variation_real_name : $vari->variation_name;
                                                                                                    ?>
                                                                                                        @if ($list->tool_category_id == $vari->tool_category_id)
                                                                                                        <option value="{{ $vari->variation_name }}">
                                                                                                            {{ $variation_name }}
                                                                                                        </option>
                                                                                                        @endif
                                                                                                @endforeach
                                                                                            </select>
                                                                                        @else
                                                                                            <select name="variation_name" required>
                                                                                                <option value="" disabled selected>Choose Variation</option>
                                                                                                @foreach ($variations as $vari)
                                                                                                    <?php
                                                                                                    $variation_name = ($vari->variation_real_name != '')? $vari->variation_real_name : $vari->variation_name;
                                                                                                    ?>
                                                                                                    <option value="{{ $vari->variation_name }}">{{ $variation_name }}</option>
                                                                                                @endforeach
                                                                                            </select>
                                                                                        @endif
                                                                                        <label>Choose Variation</label>
                                                                                    </div>
                                                                                    <div class="col s6">
                                                                                        <button type="submit" class="btn btn-large waves-effect waves-light light-blue"
                                                                                        > Chọn Variation
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            </form>
                                                                        </div>

                                                                    </div>
                                                                </div>
                                                            @endif
                                                            {{-- End cập nhật variations--}}
                                                            <div class="card">
                                                                <div class="card-content">
                                                                    <a class="waves-effect waves-light btn blue m-b-xs"
                                                                       href="{{ url('keep-working-job/'.$list->id) }}"
                                                                    >
                                                                        <i class="material-icons left">present_to_all</i>Keep Job
                                                                    <a class="waves-effect waves-light btn red m-b-xs js-btn-redo"
                                                                       order_id="{{ $list->id }}">
                                                                        <i class="material-icons left">thumb_down</i>Làm
                                                                        lại</a>
                                                                    <div class="row js-redo-form-{{ $list->id }}"
                                                                         style="display: none;">
                                                                        <form action="{{ url('redo-designer') }}"
                                                                              method="post" class="col s12">
                                                                            {{ csrf_field() }}
                                                                            <div class="row">
                                                                                <div class="input-field col s10">
                                                                                    <i class="material-icons prefix">mode_edit</i>
                                                                                    <input type="text"
                                                                                           style="display: none;"
                                                                                           name="order_id"
                                                                                           value="{{ $list->id }}"/>
                                                                                    <textarea id="icon_prefix2"
                                                                                              name="reason"
                                                                                              class="materialize-textarea"></textarea>
                                                                                    <label for="icon_prefix2">Lý
                                                                                        do</label>
                                                                                </div>
                                                                                <div class="col s2">
                                                                                    <button type="submit"
                                                                                            class="btn-floating btn-large waves-effect waves-light red"
                                                                                    ><i class="material-icons">send</i>
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col s6">
                                                                <div class="card white">
                                                                    <span class="card-title">Ảnh Gốc</span>
                                                                    <div class="card-content center">
                                                                        @foreach(explode(",",$list->image) as $key => $image)
                                                                            @if ($key < 1)
                                                                                {!! thumb_c($image, env('THUMB'), basename($image)) !!}
                                                                            @endif
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col s6">
                                                                <div class="card white">
                                                                    <span class="card-title">Ảnh thiết kế</span>
                                                                    {{-- Redo --}}
                                                                    @if ($list->redo == 1)
                                                                        <div class="card red lighten-1">
                                                                            <div class="card-content">
                                                                                <p class="card-title">Redo</p>
                                                                                {!! html_entity_decode($list->reason) !!}
                                                                                </p>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                    {{-- End Redo--}}
                                                                    @if ($list->customer_note != '')
                                                                        <div class="card light-green lighten-3">
                                                                            <div class="card-content">
                                                                                <p class="card-title">Customer Note</p>
                                                                                {!! html_entity_decode($list->customer_note) !!}
                                                                                </p>
                                                                            </div>
                                                                        </div>
                                                                    @endif

                                                                    <div class="card">
                                                                        <div class="card-content">
                                                                            <ul class="collection">
                                                                                @foreach(explode("-;-;-",$list->detail) as $detail)
                                                                                    <li class="collection-item">{{ $detail }}</li>
                                                                                @endforeach
                                                                            </ul>
                                                                        </div>
                                                                    </div>
                                                                    <div class="card-content center">
                                                                        @if(array_key_exists($list->id, $images))
                                                                            @foreach($images[$list->id] as $img)
                                                                            {!! ($img['thumb'] != '') ? thumb_c($img['thumb'], env('THUMB'), '') : '' !!}

                                                                                <a title="Click vào đây để xem chi tiết ảnh rõ hơn nữa" href="{{ url(env('DIR_CHECK')).'/'.$img['name'] }}" target="_blank">
                                                                                    <div>{{ $img['name'] }}</div>
                                                                                </a>

                                                                            @endforeach
                                                                            <?php unset($images[$list->id]); ?>
                                                                        @endif
                                                                    </div>
                                                                </div>


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
                                <td colspan="8" class="center">
                                    Đã hết công việc kiểm tra Design. Vui lòng chuyển sang công việc xem phản hồi khách
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
