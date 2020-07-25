@extends('popup')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">List Driver Folder Upload</span><br>
                    <div class="row">
                        <table class="striped">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th data-field="id">Driver Name</th>
                                <th data-field="status">Status</th>
                                <th data-field="product">Product</th>
                                <th data-field="price">Template Name</th>
                                <th data-field="name">Store Name</th>
                                <th>Time Create</th>
                                <th>Action</th>
                                <th>Delete</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if( sizeof($templates) > 0)
                                <?php
                                $i = 1;
                                //trạng thái đã bị xóa sản phẩm hoặc template
                                $deleted = [23,24];
                                ?>
                                @foreach( $templates as $list)
                                    <tr>
                                        <td>{{ $i++ }}</td>
                                        <td>{{ $list->woo_driver_folder_name }}</td>
                                        <td>
                                            @if (in_array($list->woo_driver_folder_status, $deleted))
                                                @if($list->woo_driver_folder_status == 23)
                                                    <span class="deep-orange">Deleting</span>
                                                @elseif ($list->woo_driver_folder_status == 24)
                                                    <span class="red">Deleted</span>
                                                @endif
                                            @else
                                                <span class="green">Normal</span>
                                            @endif
                                        </td>
                                        <td>
                                            30
                                        </td>
                                        <td>{{ $list->product_name }}</td>
                                        <td>{{ $list->store_name }}</td>
                                        <td>
                                            {!! ($list->created_at != null)? compareTime($list->created_at, date("Y-m-d H:i:s")) : '' !!}
                                        </td>
                                        <td>
                                            Scan | Update | Change
                                        </td>
                                        <td>
                                            @if (! in_array($list->woo_driver_folder_status, $deleted))
                                                <a onclick="return confirm('Bạn có chắc chắn muốn xóa toàn bộ sản phẩm của Template này?');"
                                                   href="{{ url('woo-deleted-driver-folder/'.$list->woo_driver_folder_id) }}"
                                                   class="waves-effect waves-light btn red">
                                                    Delete Product
                                                </a>
                                            @else
                                                <a href="#" disabled="disabled" class="waves-effect waves-light btn"> Deleting</a>
                                            @endif
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
    <div>
        <pre>
            <?php
                //print_r($templates);
            ?>
        </pre>
    </div>
@endsection
