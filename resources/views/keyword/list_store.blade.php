@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Danh sách Store</div>
        </div>
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <div class="row">
                        <form class="col s12" action="{{ url('process-feed-store') }}" method="post">
                            {{ csrf_field() }}
                            <div class="row">
                                <div class="input-field col s6">
                                    <select name="store_id" id="js-store-feed">
                                        <option disabled>Choose your option</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store['id'] }}">{{ $store['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <label>Store Select</label>
                                </div>
                                <div class="col s6">
                                    <label>Category Select</label>
                                    <select id="js-category-feed" name="lst_category" class="browser-default">
                                        <option value="all">All</option>
                                        @foreach($categories as $category)
                                            <option class="js-store js-store-{{ $category['store_id'] }}"
                                                    value="{{ $category['id'] }}">{{ $category['name'] }}
                                                - {{ $category['store_id'] }}
                                            </option>
                                        @endforeach
                                    </select>

                                </div>
                            </div>
                            <div class="row">
                                <div class="col s12">
                                    <button
                                        onclick="return confirm('Bạn có chắc chắn thực hiện hành động tạo feed này?');"
                                        type="submit" name="action" value="feed"
                                        class="waves-effect waves-light btn blue">
                                        Tạo Feed
                                    </button>
                                    <button
                                        onclick="return confirm('Bạn có chắc chắn thực hiện hành động kiểm tra lại sản phẩm của store này?');"
                                        type="submit" name="action" value="check_again"
                                        class="waves-effect waves-light btn green">
                                        Check Product
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if (sizeof($lst_requests) > 0)
            <div class="col s12">
                <div class="page-title">Category Re Check</div>
            </div>
            <div class="row">
                <div class="col s12 m12 l12">
                    <div class="card invoices-card">
                        <div class="card-content">
                            <table id="list-order" class="display responsive-table datatable-example">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Category</th>
                                    <th>Store</th>
                                    <th>Status</th>
                                    <th>Processing</th>
                                    <th>Time</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tfoot>
                                <tr>
                                    <th>#</th>
                                    <th>Category</th>
                                    <th>Store</th>
                                    <th>Status</th>
                                    <th>Processing</th>
                                    <th>Time</th>
                                    <th>Action</th>
                                </tr>
                                </tfoot>
                                <tbody>
                                    @foreach($lst_requests as $key => $v)
                                        <tr>
                                            <td>{{ $key+1 }}</td>
                                            <td>{{ $v->category_name }}</td>
                                            <td>{{ $v->store_name }}</td>
                                            <td>
                                                {!! statusJob($v->status, 0, '') !!}
                                            </td>
                                            <td class="center">
                                                @if (isset($feeds[$v->store_id][$v->category_name]))
                                                    <?php $feed = $feeds[$v->store_id][$v->category_name]; ?>
                                                    {{ (isset($feed['done'])? sizeof($feed['done']) : 0) }} /
                                                    {{ sizeof($feeds[$v->store_id][$v->category_name]['all']) }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                {!! compareTime($v->created_at, date("Y-m-d H:i:s")) !!}
                                            </td>
                                            <td>
                                                @if( $v->status == 0)
                                                    Delete
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <pre>
                <?php print_r($lst_requests); ?>
            </pre>
        @endif

    </div>
@endsection
