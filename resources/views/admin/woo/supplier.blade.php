@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Thông tin Supplier</span>
                    <p>Thêm <code>info về aliexpress link, skype, wechat ...</code> và đánh giá supplier vào mục note
                    </p>
                    <span class="right top">
                        <a href="{{ url('woo-get-new-supplier') }}"
                           class="btn-floating btn-large waves-effect waves-light red">
                            <i class="material-icons">add</i></a>
                    </span>
                    <br>

                    <table class="highlight">
                        <thead>
                        <tr>
                            <th data-field="number">#</th>
                            <th data-field="id">Name</th>
                            <th data-field="id">Review</th>
                            <th data-field="name">Note</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if( sizeof($lists) > 0)
                            <?php $i = 1; ?>
                            @foreach($lists as $sup)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td>{{ $sup->name }}</td>
                                    <td>{{ $sup->status }}</td>
                                    <td>{{ html_entity_decode($sup->note) }}</td>
                                    <td>
                                        <a class="waves-effect waves-light btn modal-trigger" href="#modal{{$sup->id}}">Edit</a>
                                        <!-- Modal Structure -->
                                        <div id="modal{{$sup->id}}" class="modal">
                                            <div class="modal-content">
                                                <h4>Chỉnh sửa thông tin Supplier</h4>
                                                <form class="col s12" action="{{url('woo-add-new-supplier')}}"
                                                      method="post">
                                                    {{ csrf_field() }}
                                                    <div class="row">
                                                        <div class="input-field col s6">
                                                            <input placeholder="Ví dụ Junchen China" name="name"
                                                                   value="{{ $sup->name }}"
                                                                   type="text" class="validate" required>
                                                            <label for="first_name">Supplier Name</label>
                                                        </div>
                                                        <div class="input-field col s6">
                                                            <select name="status" required>
                                                                <option value="" disabled selected>Choose your option
                                                                </option>
                                                                <?php for($j = 1; $j <= 5; $j++) { ?>
                                                                <option
                                                                    {{ ($j == $sup->status) ? 'selected' : '' }} value="{{ $j }}">
                                                                    Rate {{ $j }}</option>
                                                                <?php } ?>
                                                            </select>
                                                            <label>Rate Select</label>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="input-field col s12">
                                                            <textarea id="textarea1" name="note"
                                                                      class="materialize-textarea" length="520">
                                                                {{ html_entity_decode($sup->note) }}
                                                            </textarea>
                                                            <label for="textarea1" class="">Ghi chú -
                                                                <small>Aliexpress link, skype, wechat, có sản phẩm gì
                                                                    ...
                                                                </small>
                                                            </label>
                                                            <span class="character-counter"
                                                                  style="float: right; font-size: 12px; height: 1px;"></span>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <input type="text" name="id" value="{{ $sup->id }}" hidden>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col s12">
                                                            <button type="submit"
                                                                    class="right waves-effect waves-light btn blue">
                                                                Cập nhật
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        <a onclick="return confirm('Bạn có chắc chắn muốn xóa supplier này?');"
                                           href="{{ url('woo-delete-supplier/'.$sup->id) }}"
                                           class="waves-effect waves-light btn">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="4">Hiện tại chưa liên hệ được supplier nào. Chủ động liên hệ và lưu vào đây
                                    nhé
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
