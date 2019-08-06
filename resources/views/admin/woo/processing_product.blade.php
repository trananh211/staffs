@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Trạng thái Template</div>
        </div>
        <div class="col s12" id="js-folder">
            <div class="card">
                <div class="card-content">
                    <table class="striped">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th data-field="id">Name</th>
                            <th data-field="name">Time</th>
                            <th data-field="price">Up/All</th>
                            <th>*</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $i = 1 ?>
                        @foreach($lists as $key => $list)
                            <tr class="js-get-status-doc" pro_dri="{{ $list->id }}" title="Ấn vào đây để xem thông tin upload">
                                <td>{{ $i++ }}</td>
                                <td>{{ $list->name }}</td>
                                <td>{!! compareTime($list->updated_at, date("Y-m-d H:i:s")) !!}</td>
                                <td>
                                    <?php
                                    if (array_key_exists($list->id, $pro_status)) {
                                        $status = $pro_status[$list->id];
                                        echo '<div class="chip #388e3c green darken-2">' . $status['done'] . '</div>';
                                        echo ' / ';
                                        if ($status['done'] == $status['all']) {
                                            echo '<div class="chip #388e3c green darken-2">' . $status['all'] . '</div>';
                                        } else {
                                            echo '<div class="chip #78909c blue-grey lighten-1">' . $status['all'] . '</div>';
                                        }
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if (array_key_exists($list->id, $pro_status)) {
                                        echo ($pro_status[$list->id]['uploading'] > 0) ? '<div class="chip #eeff41 lime accent-2">' . $pro_status[$list->id]['uploading'] . '</div>' : '';
                                    }
                                    ?>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col s7" id="js-product" style="display: none;">
            @foreach($pro_upload as $woo_product_folder_id => $products)
                <div class="row js-show-folder js-show-folder-{{ $woo_product_folder_id }}" style="display: none;">
                    <div class="card">
                        <div class="card-content">
                            <table class="striped">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th data-field="id">Name</th>
                                    <th data-field="name">Product Name</th>
                                    <th data-field="price">Product Link</th>
                                    <th data-field="image">Images</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $i = 1 ?>
                                @foreach($products as $list)
                                    <tr>
                                        <td>{{ $i++ }}</td>
                                        <td>
                                            <?php
                                            if ($list['status'] == 0) {
                                                echo '<div class="chip #4fc3f7 light-blue lighten-2">' . $list['name'] . '</div>';
                                            } else if ($list['status'] == 1) {
                                                echo '<div class="chip #eeff41 lime accent-2">' . $list['name'] . '</div>';
                                            } else if ($list['status'] == 3) {
                                                echo '<div class="chip #388e3c green darken-2">' . $list['name'] . '</div>';
                                            }
                                            ?>
                                        </td>
                                        <td>{{ $list['woo_product_name'] }}</td>
                                        <td><a href="{{ $list['woo_slug'] }}" target="_blank">{{ $list['woo_slug'] }}</a></td>
                                        <td>{{ $list['images'] }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
