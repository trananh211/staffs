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
                                <th data-field="price">Product ID</th>
                                <th data-field="name">Store Name</th>
                                <th data-field="name">Supplier</th>
                                <th data-field="name">Base Price</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if( sizeof($lists) > 0)
                                <?php $i = 1; ?>
                            @foreach( $lists as $list)
                            <tr>
                                <td>{{ $i++ }}</td>
                                <td>{{ $list->product_name }}</td>
                                <td>{{ $list->template_id }}</td>
                                <td>{{ $list->store_name }}</td>
                                <td>{{ ($list->sup_name != null)? $list->sup_name : 'Chưa có sup' }}</td>
                                <td>{{ ($list->base_price != null)? '$ '.$list->base_price : 'Chưa đàm phán giá' }}</td>
                                <td>
                                    <a class="waves-effect waves-light btn modal-trigger" href="#modal{{$list->id}}">Edit</a>
                                    <!-- Modal Structure -->
                                    <div id="modal{{$list->id}}" class="modal">
                                        <div class="modal-content">
                                            <h4>Chỉnh sửa thông tin Template</h4>
                                            <form class="col s12" action="{{url('woo-update-template')}}"
                                                  method="post">
                                                {{ csrf_field() }}
                                                <div class="row">
                                                    <input type="text" name="id" value="{{ $list->id }}" hidden>
                                                </div>
                                                <div class="row">
                                                    <div class="input-field col s6">
                                                        <input placeholder="Ví dụ Low top Za103" name="product_name"
                                                               value="{{ $list->product_name }}"
                                                               type="text" class="validate" required>
                                                        <label for="first_name">Product Name</label>
                                                    </div>
                                                    <div class="input-field col s6">
                                                        <select name="supplier_id" required>
                                                            <option value="" disabled selected>Choose your option
                                                            </option>
                                                            @foreach( $suppliers as $supplier)
                                                                <option
                                                                    {{ ($supplier->id == $list->supplier_id) ? 'selected' : '' }} value="{{ $supplier->id }}">
                                                                    {{ $supplier->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <label>Supplier Select</label>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="input-field col s6">
                                                        <select name="variation_change_id">
                                                            <option value="0" selected>Choose your option
                                                            </option>
                                                            @foreach( $variation_changes as $variation_change)
                                                                <option
                                                                    {{ ($variation_change->id == $list->variation_change_id) ? 'selected' : '' }} value="{{ $variation_change->id }}">
                                                                    {{ $variation_change->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <label>Change Variation</label>
                                                    </div>
                                                    <div class="input-field col s6">
                                                        <input placeholder="Ví dụ 26.6" name="base_price"
                                                               value="{{ ($list->base_price != null)? $list->base_price : '' }}"
                                                               type="text" class="validate" required>
                                                        <label for="first_name">Base Price (USD)</label>
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
                                       href="{{ url('woo-update-template/'.$list->id) }}"
                                       class="waves-effect waves-light btn">
                                        Update
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                                @else
                                <tr>
                                    <td colspan="5">
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
