@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Delete Categories on Stores Woocommerce</div>
        </div>
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <div class="row">
                        <form class="col s12" action="{{url('action-deleted-categories')}}" method="post">
                            {{ csrf_field() }}
                            <div class="row">
                                <div class="input-field col s6">
                                    <select name="store_id">
                                        <option disabled>Choose your option</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                                        @endforeach
                                    </select>
                                    <label>Store Select</label>
                                </div>
                                <div class="col s6">
                                    <button onclick="return confirm('Bạn có chắc chắn muốn xóa toàn bộ categories của store này?');" type="submit" class="right waves-effect waves-light btn blue">
                                        Xóa Toàn Bộ
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
