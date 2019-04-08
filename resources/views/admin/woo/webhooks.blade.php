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
                            <th data-field="id">Url</th>
                            <th data-field="name">Event</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>{{ url('/api/new-order/') }}</td>
                            <td>Get new order from Woocommerce Store</td>
                        </tr>
                        <tr>
                            <td>{{ url('/api/update-product/') }}</td>
                            <td>Get Update product from Woocommerce Store</td>
                        </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
