@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">List Variation Product</div>
            <span class="right">
                <a href="{{ url('woo-convert-variation') }}" class="waves-effect waves-light btn">Tạo mới</a>
            </span>
        </div>
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <table class="striped">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th data-field="id">Name</th>
                            <th data-field="name">Supplier Name</th>
                            <th data-field="price">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $i = 1; ?>
                        @foreach ($lists as $list)
                        <tr>
                            <td>{{ $i++ }}</td>
                            <td>{{ $list->variation_name }}</td>
                            <td>{{ $list->supplier_name }}</td>
                            <td>
                                Edit |
                                <a onclick="return confirm('Bạn có chắc chắn muốn xóa variation change này?');"
                                   href="{{ url('woo-delete-convert-variation/'.$list->id) }}"
                                   class="waves-effect red waves-light btn">Xóa</a>
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
