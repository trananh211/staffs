@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Add Tracking To Paypal</div>
        </div>
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <div class="row">
                        <form action="{{url('pay-tracking')}}" method="post" enctype="multipart/form-data">
                            {{ csrf_field() }}
                            <div class="file-field input-field">
                                <div class="file-field input-field">
                                    <div class="btn teal lighten-1">
                                        <span>File Paypal</span>
                                        <input name="paypal_file" type="file">
                                    </div>
                                    <div class="file-path-wrapper">
                                        <input class="file-path validate" type="text">
                                    </div>
                                </div>
                                <div class="file-field input-field">
                                    <div class="btn teal lighten-1">
                                        <span>File Tracking</span>
                                        <input name="tracking_file" type="file">
                                    </div>
                                    <div class="file-path-wrapper">
                                        <input class="file-path validate" type="text">
                                    </div>
                                </div>
                                <div class="col s12">
                                    <button type="submit" class="right waves-effect waves-light btn blue">
                                        Add Tracking
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
