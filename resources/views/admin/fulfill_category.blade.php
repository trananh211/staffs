@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Fulfill Orders</div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <table id="review-job" class="display responsive-table datatable-example">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Order</th>
                            <th>Sku</th>
                            <th>Variation</th>
                            <th>Name</th>
                            <th>Date</th>
                            <th>Tracking</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Order</th>
                            <th>Sku</th>
                            <th>Variation</th>
                            <th>Name</th>
                            <th>Date</th>
                            <th>Tracking</th>
                            <th>Action</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @foreach ($fulfills as $key => $fulfill)
                        <tr>
                            <th>{{ ++$key }}</th>
                            <th>{{ $fulfill->number }}</th>
                            <th>{{ $fulfill->sku }}</th>
                            <th>{{ $fulfill->variation_detail }}</th>
                            <th>
                                {{ $fulfill->fullname }}
                                <span class="hidden">
                                    {{ $fulfill->email }}
                                </span>
                            </th>
                            <th>{!! compareTime($fulfill->created_at, date("Y-m-d H:i:s")) !!}</th>
                            <th>Tracking</th>
                            <th>
                                <!-- Modal Trigger -->
                                <a class="waves-effect waves-light btn modal-trigger" href="#modal{{ $fulfill->id }}">Edit</a>

                                <!-- Modal Structure -->
                                <div id="modal{{ $fulfill->id }}" class="modal">
                                    <div class="modal-content">
                                        {{-- Form edit info customer --}}
                                        <form class="col s12" action="{{url('edit-info-fulfills')}}" method="post">
                                            {{ csrf_field() }}
                                            <div class="row">
                                                <div class="input-field col s2 hidden">
                                                    <input name="fulfill_id" type="text" value="{{ $fulfill->id }}" class="validate" required>
                                                    <label for="fulfill_id">Fulfill ID</label>
                                                </div>
                                                <div class="input-field col s2 hidden">
                                                    <input name="woo_order_id" type="text" value="{{ $fulfill->woo_order_id }}" class="validate" required>
                                                    <label for="woo_order_id">Woo Order ID Name</label>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="input-field col s6">
                                                    <input  id="fullname" name="fullname"  type="text" class="validate" required value="{{ $fulfill->fullname }}">
                                                    <label for="fullname" class="active">Full Name</label>
                                                </div>
                                                <div class="input-field col s6">
                                                    <input  id="email" name="email"  type="email" class="validate" required value="{{ $fulfill->email }}">
                                                    <label for="email" class="active">Email</label>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="input-field col s8">
                                                    <input id="address" name="address" type="text" required value="{{ $fulfill->address }}" class="validate">
                                                    <label for="address" class="active">Address</label>
                                                </div>
                                                <div class="input-field col s4">
                                                    <input id="phone" name="phone" value="{{ $fulfill->phone }}" type="text" required class="validate">
                                                    <label for="phone" class="">Phone</label>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="input-field col s3">
                                                    <input id="city" name="city" required value="{{ $fulfill->city }}" type="text" class="validate">
                                                    <label for="city" class="">City</label>
                                                </div>
                                                <div class="input-field col s3">
                                                    <input id="state" name="state" value="{{ $fulfill->state }}" type="text" class="validate">
                                                    <label for="state" class="">State</label>
                                                </div>
                                                <div class="input-field col s3">
                                                    <input id="country" name="country" required value="{{ $fulfill->country }}" type="text" class="validate">
                                                    <label for="country" class="">Country</label>
                                                </div>
                                                <div class="input-field col s3">
                                                    <input id="postcode" name="postcode" required value="{{ $fulfill->postcode }}" type="text" class="validate">
                                                    <label for="postcode" class="">Zip Code</label>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="input-field col s12">
                                                    <textarea id="customer_note" name="customer_note" style="height: 80px;" value="{{ $fulfill->customer_note }}"></textarea>
                                                    <label for="customer_note" class="">Customer Note</label>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col s12">
                                                    <button type="submit" class="right waves-effect waves-light btn blue">Update</button>
                                                </div>
                                            </div>
                                        </form>
                                        {{-- End Form edit info customer --}}
                                    </div>
                                </div>
                            </th>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <pre>
                        <?php print_r($fulfills); ?>
                    </pre>
                </div>
            </div>
        </div>
    </div>

@endsection
