@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Category để chọn variations</div>
            <div class="right" title="Tool get chung toàn bộ category từ tất cả store">
                <!-- Modal Trigger -->
                <a class="waves-effect waves-light  modal-trigger" href="#modal-new-category">Tạo Mới Category</a>
            </div>

            <!-- Modal Structure -->
            <div id="modal-new-category" class="modal">
                <div class="modal-content">
                    <div class="page-title">Tạo Mới Category</div>
                    <form action="{{url('add-new-tool-category')}}" method="post" enctype="multipart/form-data"
                          class="col s12">
                        {{ csrf_field() }}
                        <div class="row">
                            <div class="input-field col s12">
                                <input placeholder="Category Name" name="tool_category_name" required type="text" class="validate">
                                <label for="id">Category Name</label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="input-field col s12">
                                <textarea name="note" class="materialize-textarea"></textarea>
                                <label for="id">Note</label>
                            </div>
                        </div>
                        <div class="row">
                            <button type="submit" class="waves-effect waves-light btn blue">
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div id="js-variation-category" class="js-view-right col s12" url="{{ url('ajax-choose-variations') }}">
            <div class="card">
                <div class="card-content">
                    <table class="responsive-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th>Variation</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $i = 1; ?>
                        @foreach( $categories as $value)
                            <tr class="js-show js-show-{{$value->id}}" title="{{ $value->note }}">
                                <td>{{ $i++ }}</td>
                                <td>{{ $value->category_name }}</td>
                                <td>
                                    <span class="js-btn-variation-show-right waves-effect waves-light btn blue"
                                          data-catid="{{ $value->id }}" data-catname="{{ $value->category_name }}">
                                        Variation
                                    </span>
                                </td>
                                <td>
                                    <!-- Modal Trigger -->
                                    <a class="waves-effect waves-light btn modal-trigger" href="#modal-cate-{{ $value->id }}">
                                        Edit
                                    </a>
                                    <!-- Modal Structure -->
                                    <div id="modal-cate-{{ $value->id }}" class="modal">
                                        <div class="modal-content">
                                            <form action="{{url('edit-tool-category')}}" method="post" enctype="multipart/form-data"
                                                  class="col s12">
                                                {{ csrf_field() }}
                                                <div class="row hidden">
                                                    <div class="input-field col s12">
                                                        <input name="tool_category_id" required type="text" value="{{ $value->id }}" >
                                                        <label for="id">Category Id</label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="input-field col s12">
                                                        <input placeholder="Category Name" name="tool_category_name" required type="text" class="validate"
                                                               value="{{ $value->category_name }}" >
                                                        <label for="id">Category Name</label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="input-field col s12">
                                                        <textarea name="note" class="materialize-textarea">{{ $value->note }}</textarea>
                                                        <label for="id">Note</label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <button type="submit" class="waves-effect waves-light btn blue">
                                                        Submit
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <a class="waves-effect waves-light btn red" href="{{ url('delete-tool-category/'.$value->id) }}"
                                       onclick="return confirm('Bạn có chắc chắn muốn xóa category này?');"
                                       title="Xóa category và cập nhật lại toàn bộ variation liên quan"
                                    >Delete</a>
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
                                    <input id="js-cat_id" placeholder="Placeholder" name="tool_category_id" type="text" class="validate">
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
                <a href="{{ url('/update-variation') }}" title="Lấy toàn bộ danh sách mới từ order">Cập nhật Variation</a>
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
                                <th>Name</th>
                                <th>Variation</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Factory Sku</th>
                                <th>New Product SKU</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tfoot>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Variation</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Factory Sku</th>
                                <th>New Product SKU</th>
                                <th>Action</th>
                            </tr>
                            </tfoot>
                            <tbody>
                            @foreach ($variations as $key => $item)
                            <tr>
                                <td>{{ ++$key }}</td>
                                <td>{{ ($item->variation_real_name != '')? $item->variation_real_name : 'N/A' }}</td>
                                <td>{{ $item->variation_name }}</td>
                                <td>{{ ($item->category_name != '') ? $item->category_name : 'N/A' }}</td>
                                <td>{{ $item->price }} $</td>
                                <td>{{ ($item->factory_sku != '')? $item->factory_sku : 'N/A' }}</td>
                                <td>{{ ($item->variation_sku != '')? $item->variation_sku : 'N/A' }}</td>
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
                                                        <input id="js-cat_id" name="variation_real_name" required type="text" class="validate"
                                                               placeholder="ví dụ: US6(EU 37), Youth (56 x 43 inches / 140 x 110 cm)"
                                                               value="{{ $item->variation_real_name }}"
                                                        >
                                                        <label for="id">Variation Nice Name</label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="input-field col s4">
                                                        <input placeholder="Giá tiền gốc" required id="base_cost" type="text" class="masked validate" name="price"
                                                               data-inputmask="'numericInput': true, 'mask': '$ 999,999.99', 'rightAlignNumerics':false"
                                                               value="{{ $item->price }}"
                                                        >
                                                        <label for="base_cost">Base Cost ($)</label>
                                                    </div>
                                                    <div class="input-field col s4" title="Nếu có trường này. Sku sẽ thêm trường này vào vào phần cuối cùng.">
                                                        <input id="New_sku" type="text" class="validate" name="variation_sku"
                                                               placeholder="Sku sẽ được thêm ở cuối cùng sku thật. VD: BlackUS29"
                                                               value="{{ $item->variation_sku }}"
                                                        >
                                                        <label for="New_sku">New Sku</label>
                                                    </div>
                                                    <div class="input-field col s4" title="ID sản xuất của nhà máy.">
                                                        <input id="factory_sku" type="text" class="validate" name="factory_sku"
                                                               placeholder="VD: SHB, B009, B006"
                                                               value="{{ $item->factory_sku }}"
                                                        >
                                                        <label for="factory_sku">Factory Sku</label>
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
