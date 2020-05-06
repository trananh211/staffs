@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">List Carriers 17 Track</div>
        </div>
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <table id="review-job" class="display responsive-table datatable-example">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th data-field="id">17 Track Carriers</th>
                            <th data-field="name">Paypal Carriers</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $i = 1; ?>
                        @if (sizeof($carriers) > 0)
                            @foreach($carriers as $item)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td>{{ $item->name}}</td>
                                    <td>
                                        {{ (array_key_exists($item->paypal_carrier_id, $paypal_carriers))? $paypal_carriers[$item->paypal_carrier_id] : 'n/a' }}
                                    </td>
                                    <td>
                                        <!-- Modal Trigger -->
                                        <a class="waves-effect waves-light blue btn modal-trigger"
                                           href="#modal{{ $item->id }}">Edit</a>

                                        <!-- Modal Structure -->
                                        <div id="modal{{ $item->id }}" class="modal modal-fixed-footer">
                                            <form class="col s12" action="{{url('edit-17track-carrier')}}"
                                                  method="post">
                                                {{ csrf_field() }}
                                                <div class="modal-content">
                                                    <h4>Edditing : {{ $item->name}}</h4>
                                                    <p>
                                                    <div class="row">
                                                        <div class="input-field col s6 hidden">
                                                            <input name="id" type="text" class="validate" value="{{ $item->id }}" required>
                                                            <label for="first_name">17 Track Carrier Id</label>
                                                        </div>
                                                        <div class="input-field col s6">
                                                            <input name="name" type="text" class="validate" value="{{ $item->name }}">
                                                            <label for="first_name">17 Track Carrier Id</label>
                                                        </div>
                                                        <div class="input-field col s6">
                                                            <select name="paypal_carrier_id" required>
                                                                <option value="" disabled selected>Choose your option
                                                                </option>
                                                                @foreach ($paypal_carriers as $pp_carrier_id => $carrier)
                                                                    <option
                                                                        {{ ($pp_carrier_id == $item->paypal_carrier_id) ? 'selected' : '' }}
                                                                        value="{{ $pp_carrier_id }}">
                                                                        {{ $carrier }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <label>Paypal Carriers Select</label>
                                                        </div>
                                                    </div>
                                                    </p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit"
                                                            class="right waves-effect waves-light btn blue">
                                                        Cập nhật
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td class="center" colspan="4">
                                    Chưa có Carriers nào được cập nhật từ tracking number.
                                </td>
                            </tr>
                        @endif

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
