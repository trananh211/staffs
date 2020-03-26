@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">List template</span><br>
                    <div class="row">
                        <table class="striped">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th data-field="id">Product Name</th>
                                <th data-field="type">Type</th>
                                <th data-field="status">Status</th>
                                <th data-field="price">Product ID</th>
                                <th data-field="name">Store Name</th>
                                <th>Action</th>
                                <th>Delete</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if( sizeof($lists) > 0)
                                <?php
                                    $i = 1;
                                    //trạng thái đã bị xóa sản phẩm hoặc template
                                    $deleted = [23,24];
                                ?>
                            @foreach( $lists as $list)
                            <tr>
                                <td>{{ $i++ }}</td>
                                <td>{{ $list->product_name }}</td>
                                <td>
                                    {!! ($list->website_id != '') ? '<span class="green">Scrap</span>' : '<span class="blue">Up</span>' !!}
                                </td>
                                <td>
                                    @if (in_array($list->status, $deleted))
                                        @if($list->status == 23)
                                            <span class="deep-orange">Deleting</span>
                                        @elseif ($list->status == 24)
                                            <span class="red">Deleted</span>
                                        @endif
                                    @else
                                        @if($list->status == 0)
                                            <span class="yellow">Running</span>
                                        @elseif($list->status == 2)
                                            <span class="orange">Changing</span>
                                        @else
                                            <span class="green">Done</span>
                                        @endif
                                    @endif
                                </td>
                                <td>{{ $list->template_id }}</td>
                                <td>{{ $list->store_name }}</td>
                                <td>
                                    @if ($list->website_id != '')
                                    <a class="waves-effect waves-light btn modal-trigger" href="#modal{{$list->id}}">Edit</a>
                                    <!-- Modal Structure -->
                                    <div id="modal{{$list->id}}" class="modal">
                                        <div class="modal-content">
                                            <h4>Chỉnh sửa thông tin Template</h4>
                                            <span class="red">Variation sẽ không được sửa. Nếu muốn thay đổi hãy tạo template mới.</span>
                                            <form class="col s12" action="{{url('woo-update-template')}}"
                                                  method="post">
                                                {{ csrf_field() }}
                                                <div class="row">
                                                    <input type="text" name="id" value="{{ $list->id }}" hidden>
                                                </div>
                                                <div class="row">
                                                    <div class="input-field col s6">
                                                        <input placeholder="Ex: Low top ZA103" name="product_name"
                                                               value="{{ $list->product_name }}"
                                                               type="text" class="validate" required>
                                                        <label for="first_name">Product Name</label>
                                                    </div>
                                                    <div class="input-field col s6">
                                                        <input placeholder="Ex: ZA103" name="product_code"
                                                               value="{{ $list->product_code }}"
                                                               type="text" class="validate">
                                                        <label for="first_name">Product Code</label>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="input-field col s4">
                                                        <input placeholder="Ex: 26.99" name="sale_price"
                                                               value="{{ ($list->sale_price > 0)? $list->sale_price : '' }}"
                                                               type="text" class="validate">
                                                        <label for="first_name">Sale Price (USD)</label>
                                                    </div>
                                                    <div class="input-field col s4">
                                                        <input placeholder="Ex: 26.99" name="origin_price"
                                                               value="{{ ($list->origin_price > 0)? $list->origin_price : '' }}"
                                                               type="text" class="validate">
                                                        <label for="first_name">Origin Price (USD)</label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="input-field col s6">
                                                        <input placeholder="Ex Delete: Low top" name="product_name_exclude"
                                                               value="{{ $list->product_name_exclude }}"
                                                               type="text" class="validate">
                                                        <label for="first_name">Product Name Exclude</label>
                                                    </div>
                                                    <div class="input-field col s6">
                                                        <input placeholder="Ex change to: High Top" name="product_name_change"
                                                               value="{{ $list->product_name_change }}"
                                                               type="text" class="validate">
                                                        <label for="first_name">Product Name Change</label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col s12">
                                                        <button type="submit"
                                                                class="right waves-effect waves-light btn blue">
                                                            Cập nhật
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                        <a onclick="return confirm('Bạn có chắc chắn muốn cập nhật template này?');"
                                           href="{{ url('woo-scan-template/'.$list->id) }}"
                                           class="waves-effect waves-light btn green">
                                            ReScan
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    @if (! in_array($list->status, $deleted))
                                    <a onclick="return confirm('Bạn có chắc chắn muốn xóa toàn bộ sản phẩm của Template này?');"
                                       href="{{ url('woo-deleted-all-product/'.$list->id.'&'.(($list->website_id != '') ? 1 : 0 )) }}"
                                       class="waves-effect waves-light btn orange ">
                                        All Products
                                    </a>
                                    @else
                                        <a href="#" disabled="disabled" class="waves-effect waves-light btn"> All Product</a>
                                    @endif
                                    <a onclick="return confirm('Bạn có chắc chắn muốn xóa template này?');"
                                       href="{{ url('woo-deleted-all-template/'.$list->id) }}"
                                       class="waves-effect waves-light btn red">
                                        Template
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                                @else
                                <tr>
                                    <td colspan="6">
                                        Hiện tại đang không có template nào
                                    </td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
