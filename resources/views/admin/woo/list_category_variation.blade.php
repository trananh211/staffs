@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Category để chọn variations</div>
        </div>
        <div id="js-variation-category" class="js-view-right col s12" url="{{ url('ajax-choose-variations') }}">
            <div class="card">
                <div class="card-content">
                    <table class="responsive-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th>Store</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $i = 1; ?>
                        @foreach( $categories as $value)
                            <tr class="js-show js-show-{{$value->id}}">
                                <td>{{ $i++ }}</td>
                                <td>{{ $value->category_name }}</td>
                                <td>{{ $value->store_name }}</td>
                                <td>
                                    <span class="js-btn-variation-show-right waves-effect waves-light btn blue"
                                          data-catid="{{ $value->id }}" data-catname="{{ $value->category_name }}" data-store-id="{{ $value->store_id }}">
                                        Choose Variation</span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="js-right-colum col hidden">
            <div class="card">
                <div class="card-content">
                    <span id="js-category-title" class="card-title">
                        List Variations : <u><b><span id="js-category-name"></span></b></u>
                    </span><br>
                    <div class="row">
                        <form action="{{url('add-list-variation')}}" method="post" enctype="multipart/form-data"
                              class="col s12">
                            {{ csrf_field() }}
                            <div class="row hidden">
                                <div class="input-field col s12">
                                    <input id="js-cat_id" placeholder="Placeholder" name="category_id" type="text" value="{{ $value->id }}" class="validate">
                                    <label for="id">Category Id</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col s12">
                                    <label>Variations Select</label>
                                    <select id="js-select-variation" name="variations[]" class="browser-default" multiple size="30" style="height: 150px;">
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <button type="submit" class="waves-effect waves-light btn blue">
                                    Save
                                </button>
                                <span class="right waves-effect waves-light btn red btn-right-close">
                                    Close
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col s12">
            <div class="page-title">Danh sách Variation hiện có</div>
            <div class="right" title="Tool sẽ load toàn bộ variation còn thiếu từ order">
                <a href="{{ url('/update-variation') }}">Cập nhật Variation</a>
            </div>
        </div>
        <div class="col s12 m12 l12">
            <div class="row">
                <div class="card">
                    <div class="card-content">
                        <table id="review-job" class="display responsive-table datatable-example">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Store</th>
                                <th>Variation</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>New SKU</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tfoot>
                            <tr>
                                <th>#</th>
                                <th>Store</th>
                                <th>Variation</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>New SKU</th>
                                <th>Action</th>
                            </tr>
                            </tfoot>
                            <tbody>
                            @foreach ($variations as $key => $item)
                            <tr>
                                <td>{{ ++$key }}</td>
                                <td>{{ $item->store_name }}</td>
                                <td>{{ $item->variation_name }}</td>
                                <td>{{ ($item->category_name != '') ? $item->category_name : 'N/A' }}</td>
                                <td>{{ $item->price }} $</td>
                                <td>{{ $item->variation_sku }}</td>
                                <td>
                                    <a class="waves-effect waves-light btn modal-trigger" href="#modal{{$item->id}}">Edit</a>
                                    <!-- Modal Structure -->
                                    <div id="modal{{$item->id}}" class="modal">
                                        <div class="modal-content">
                                            <form action="{{ url('edit-variations') }}" method="post" class="col s12">
                                                {{ csrf_field() }}
                                                <div class="row hidden">
                                                    <div class="input-field col s12">
                                                        <input name="id" value="{{ $item->id }}" type="text" class="validate">
                                                        <label>Variation Id</label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="input-field col s6">
                                                        <input placeholder="Giá tiền gốc" id="base_cost" type="text" class="masked validate" name="price"
                                                               data-inputmask="'numericInput': true, 'mask': '$ 999,999.99', 'rightAlignNumerics':false"
                                                        >
                                                        <label for="base_cost">Base Cost ($)</label>
                                                    </div>
                                                    <div class="input-field col s6" title="Nếu có trường này. Sku sẽ thêm trường này vào vào phần cuối cùng.">
                                                        <input id="New_sku" type="text" class="validate" name="variation_sku">
                                                        <label for="New_sku">New Sku</label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <button type="submit" class="waves-effect waves-light btn blue">
                                                        Save
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
