@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Fulfill Orders</div>
            <div class="right">
                <!-- Modal Trigger -->
                <a class="waves-effect waves-light btn modal-trigger blue" href="#modal-uptracking">Up Tracking</a>

                <!-- Modal Structure -->
                <div id="modal-uptracking" class="modal">
                    <div class="modal-content">
                        <ul id="js-noti" class="text-darken-2"></ul>
                        <form class="col s12" method="post" id="form-up-tracking" data-url="{{ url('action-up-tracking') }}">
                            {{ csrf_field() }}
                            <div class="row">
                                <div class="input-field col s6">
                                    <input type="file" multiple name="files[]" required />
                                </div>
                            </div>
                            <div class="row">
                                <div class="col s12">
                                    <button type="submit" class="right waves-effect waves-light btn blue">Update</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <a href="{{ url('action-fulfill-now') }}" class="waves-effect waves-light btn green">Fullfill Now</a>
            </div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <table id="review-job" class="display responsive-table datatable-example">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>File</th>
                            <th>Order</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>File</th>
                            <th>Order</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @foreach ($fulfills as $key => $fulfill)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $fulfill->created_at }}</td>
                            <td>{{ $fulfill->name }}</td>
                            <td>{{ $fulfill->date_fulfill }}</td>
                            <td>{{ $fulfill->count }}</td>
                            <td>{!! compareTime($fulfill->updated_at, date("Y-m-d H:i:s")) !!}</td>
                            <td>
                                <a class="waves-effect waves-light green btn" href="{{ url('fulfill-get-file').'/'.$fulfill->id }}">Download</a>
                                <a class="waves-effect waves-light blue btn" href="{{ url('fulfill-rescan-file').'/'.$fulfill->id }}">Re Scan</a>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
