@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Danh sách Category</div>
        </div>
        <div id="js-keyword-category" class="js-view-right col s12" url="{{ url('ajax-get-all-keyword-category') }}">
            <div class="card">
                <div class="card-content">
                    <table class="responsive-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th>Store</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $i = 1; ?>
                        @foreach( $categories as $value)
                            <tr class="js-show js-show-{{$value->id}}">
                                <td>{{ $i++ }}</td>
                                <td>{{ $value->category_name }}</td>
                                <td>{{ $value->store_name }}</td>
                                <td>
                                    <span class="js-btn-show-right waves-effect waves-light btn blue"
                                          data-catid="{{ $value->id }}" data-catname="{{ $value->category_name }}">
                                        View</span>
                                    <a href="{{ url('delete-woo-category/'.$value->id) }}" class="waves-effect waves-light btn red">
                                        Delete</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="js-right-colum col hidden">
            <div class="card">
                <div class="card-content">
                    <span id="js-category-title" class="card-title">Input fields</span><br>
                    <div class="row">
                        <form action="{{url('add-list-keyword')}}" method="post" enctype="multipart/form-data" class="col s12">
                            {{ csrf_field() }}
                            <div class="row hidden">
                                <div class="input-field col s12">
                                    <input id="cat_id" placeholder="Placeholder" name="id" type="text" class="validate">
                                    <label for="id">Category Id</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field col s12">
                                    <textarea id="lst_keyword" class="materialize-textarea" name="lst_keyword"> ccccc </textarea>
                                    <label>Keywords</label>
                                </div>
                            </div>
                            <div class="row">
                                <button type="submit" class="waves-effect waves-light btn blue">
                                    Save
                                </button>
                                <span class="right waves-effect waves-light btn red btn-right-close">
                                    Close
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
