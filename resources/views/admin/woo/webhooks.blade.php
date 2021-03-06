@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Example webhook url Table</span>
                    <p>Add <code>url link</code> to the webhook your website.</p><br>
                    <table class="highlight">
                        <thead>
                        <tr>
                            <th data-field="number">#</th>
                            <th data-field="id">Url</th>
                            <th data-field="name">Event</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>First</td>
                            <td><a href="{{ url('/see-log/') }}">{{ url('/see-log/') }}</a></td>
                            <td>See Log</td>
                        </tr>
                        <tr>
                            <td>1.1</td>
                            <td>{{ url('/api/new-order/') }} <br> Test Local:  {{ url('/api/test-new-order/1') }}</td>
                            <td>Get new order from Woocommerce Store</td>
                        </tr>
                        <tr>
                            <td>1.2</td>
                            <td>{{ url('/api/update-order/') }} <br> </td>
                            <td>Get Update Order from Woocommerce Store</td>
                        </tr>
                        <tr>
                            <td>1.3</td>
                            <td>{{ url('/api/update-product/') }} <br> Test Local:  {{ url('/api/test-update-product/1') }}</td>
                            <td>Get Update product from Woocommerce Store</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><a href="{{ url('/api/update-sku/') }}">{{ url('/api/update-sku/') }}</a></td>
                            <td>Update SKU Woocommerce Store</td>
                        </tr>
                        <tr>
                            <td>2.1</td>
                            <td><a href="{{ url('/api/update-design-id/') }}">{{ url('/api/update-design-id/') }}</a></td>
                            <td>Update Design ID</td>
                        </tr>
                        <tr>
                            <td>3.1</td>
                            <td><a href="<?php echo e(url('/fulfillment/')); ?>"><?php echo e(url('/fulfillment/')); ?></a></td>
                            <td>Fulfillment By Hand</td>
                        </tr>
                        <tr>
                            <td>3.2</td>
                            <td><a href="<?php echo e(url('/upload-file-driver/')); ?>"><?php echo e(url('/upload-file-driver/')); ?></a></td>
                            <td>Upload Driver Custom</td>
                        </tr>
                        <tr>
                            <td>3.2</td>
                            <td><a href="<?php echo e(url('/upload-file-driver-auto/')); ?>"><?php echo e(url('/upload-file-driver-auto/')); ?></a></td>
                            <td>Upload Driver Auto</td>
                        </tr>
                        <tr>
                            <td>4.1</td>
                            <td><a href="{{ url('/getFileTracking/') }}">{{ url('/getFileTracking/') }}</a></td>
                            <td>Read file Tracking from Google Driver By Hand</td>
                        </tr>
                        <tr>
                            <td>4.2</td>
                            <td><a href="{{ url('/getInfoTracking/') }}">{{ url('/getInfoTracking/') }}</a></td>
                            <td>Get info tracking number from Api</td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td><a href="{{ url('/autoGenThumb/') }}">{{ url('/autoGenThumb/') }}</a></td>
                            <td>Auto Gen Thumbnail</td>
                        </tr>
                        <tr>
                            <td>6.1</td>
                            <td><a href="{{ url('/test-upload/') }}">{{ url('/test-upload/') }}</a></td>
                            <td>Product Upload</td>
                        </tr>
                        <tr>
                            <td>6.2</td>
                            <td><a href="{{ url('/test-image/') }}">{{ url('/test-image/') }}</a></td>
                            <td>Image Upload</td>
                        </tr>

                        <tr>
                            <td>7</td>
                            <td><a href="{{ url('/tracking-number/') }}">{{ url('/tracking-number/') }}</a></td>
                            <td>Tracking Number</td>
                        </tr>

                        <tr>
                            <td>7.1</td>
                            <td><a href="{{ url('/api/paypal-update/') }}">{{ url('/api/paypal-update/') }}</a></td>
                            <td>Update Payment</td>
                        </tr>

                        <tr>
                            <td>7.2</td>
                            <td><a href="{{ url('/api/paypal-id/') }}">{{ url('/api/paypal-id/') }}</a></td>
                            <td>Paypay ID</td>
                        </tr>
                        <tr>
                            <td>8</td>
                            <td><a href="{{ url('/deleted-categories/') }}">{{ url('/deleted-categories/') }}</a></td>
                            <td>Xóa Categories trong woocomerce</td>
                        </tr>
                        <tr>
                            <td>8.1</td>
                            <td><a href="{{ url('/update-variation/') }}">{{ url('/update-variation/') }}</a></td>
                            <td>Cập nhật mới variation vào 1 bảng để kiểm soát</td>
                        </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
