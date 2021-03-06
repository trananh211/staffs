@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Danh Sách công việc đang làm</div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <table id="idea-job" class="display responsive-table datatable-example">
                        <thead>
                        <tr>
                            <th class="center">#</th>
                            <th class="center" data-field="id">Order</th>
                            <th class="center" data-field="name">Item Name</th>
                            <th class="center" data-field="price">Designer</th>
                            <th class="center" data-field="price">Date</th>
                            <th class="center" data-field="price">Status</th>
                            <th class="center" data-field="price">Link</th>
                            <th class="center" data-field="price">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($lists as $key => $list)
                                <tr>
                                    <td class="center">{{ $key+1 }}</td>
                                    <td class="center"> {{ $list->sku.'-PID-'.$list->id }}</td>
                                    <td class="center">{{ $list->name }}</td>
                                    <td class="center">{{ $list->worker_name }}</td>
                                    <td class="center">
                                        {!! compareTime($list->updated_at, date("Y-m-d H:i:s")) !!}
                                    </td>
                                    <td class="center">
                                        {!! statusJob($list->status, $list->redo, $list->reason) !!}
                                    </td>
                                    <td class="center">
                                        <a class="waves-effect m-b-xs" href="{{ url($list->permalink) }}"
                                           target="_blank">
                                            {{ substr($list->permalink,0,30) }} ...
                                        </a>
                                    </td>
                                    <td class="center">

                                        <!-- Modal Trigger -->
                                        <a class="waves-effect waves-light btn blue modal-trigger" href="#modal{{ $list->id }}">
                                            Yêu Cầu
                                        </a>
                                        <!-- Modal Structure -->
                                        <div id="modal{{ $list->id }}" class="modal">
                                            <div class="modal-content">
                                                <!-- Chi tiết-->
                                                <div class="row">
                                                    <?php
                                                    $images = explode(',', $list->image);
                                                    $del = explode('-;-;-', $list->detail);
                                                    ?>
                                                    <div class="col s12 m12 l12">
                                                        <div class="card">
                                                            <div class="card-content">
                                                                <ul>
                                                                    <li>Bạn phải lưu file Mockup với tên : <b>{{ $list->sku.'-PID-'.$list->id }}
                                                                            _mockup </b></li>
                                                                    <li>Bạn phải lưu file Design với tên : <b>{{ $list->sku.'-PID-'.$list->id }}_1, _L, _Left, _Front, _Back, _B
                                                                            ... </b></li>
                                                                    <li>Tên sản phẩm : {{ $list->name }}</li>
                                                                    <li>Link gốc sản phẩm : {{ $list->permalink }}</li>
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
                                                                    @foreach($images as $image)
                                                                        <img class="materialboxed responsive-img initialized" src="{{ $image }}" alt="" style="">
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col s16 m12 l6">
                                                        @if ($list->redo == 1)
                                                            <div class="card red lighten-1">
                                                                <div class="card-content">
                                                                    <p class="card-title">Redo</p>
                                                                    {!! html_entity_decode($list->reason) !!}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        @endif
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
                                                                <p class="card-title">Note</p>
                                                                <table class="responsive-table">
                                                                    <thead>
                                                                    <tr>
                                                                        <th data-field="id">Title</th>
                                                                        <th data-field="name">Value</th>
                                                                    </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                    @foreach($del as $value)
                                                                        <?php

                                                                        $tmp = explode(' :', $value);
                                                                        $title = $tmp[0];
                                                                        $tmp2 = (isset($tmp[1]) ? $tmp[1] : '');
                                                                        if (strlen($tmp2) > 0) {
                                                                            $tmp2 = explode(",", $tmp2);
                                                                        }
                                                                        ?>
                                                                        <tr>
                                                                            <td><?php echo $tmp[0]; ?></td>
                                                                            <td>
                                                                                @if (is_array($tmp2))
                                                                                    @foreach ($tmp2 as $k => $val)
                                                                                        <?php
                                                                                        $k++;
                                                                                        if (strpos($val, 'http') !== false) {
                                                                                            echo "<div><a href='$val' target='_blank' download='$val'>
                                                                Image $k
                                                            </a></div>";
                                                                                        } else {
                                                                                            echo $val;
                                                                                        }
                                                                                        ?>
                                                                                    @endforeach
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach

                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- End Chi tiết-->
                                            </div>
                                        </div>

                                        <a class="js-take-job waves-effect waves-light white btn"
                                           data-woo-order-id="{{ $list->design_id }}"
                                           data-workingid="{{$list->id}}" data-url="{{ url('ajax-take-job') }}"
                                        >
                                            Đòi Job
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
