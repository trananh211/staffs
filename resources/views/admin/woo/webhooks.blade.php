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
                            <td>1.1</td>
                            <td>{{ url('/api/new-order/') }}</td>
                            <td>Get new order from Woocommerce Store</td>
                        </tr>
                        <tr>
                            <td>1.2</td>
                            <td>{{ url('/api/update-product/') }}</td>
                            <td>Get Update product from Woocommerce Store</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><a href="{{ url('/api/update-sku/') }}">{{ url('/api/update-sku/') }}</a></td>
                            <td>Update SKU Woocommerce Store</td>
                        </tr>
                        <tr>
                            <td>3.1</td>
                            <td><a href="{{ url('/fulfillment/') }}">{{ url('/fulfillment/') }}</a></td>
                            <td>Fulfillment By Hand</td>
                        </tr>
                        <tr>
                            <td>3.2</td>
                            <td><a href="{{ url('/uploadFileDriver/') }}">{{ url('/uploadFileDriver/') }}</a></td>
                            <td>Upload Driver By Hand</td>
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
                            <td>6</td>
                            <td><a href="{{ url('/see-log/') }}">{{ url('/see-log/') }}</a></td>
                            <td>See Log</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
