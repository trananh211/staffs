@extends('master')
@section('content')
    <div class="row">
        <div class="col s12">
            <div class="page-title">Create New Job</div>
        </div>
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <div class="row">
                        <div class="col s12">
                            <div class="alert" id="message" style="display: none"></div>
                            <form class="p-v-xs" method="post" id="js_new_job" enctype="multipart/form-data">
                                {{ csrf_field() }}
                                <div class="file-field input-field">
                                    <div class="file-field input-field">
                                        <div class="btn teal lighten-1">
                                            <span>File</span>
                                            <input type="file" multiple name="files[]" required>
                                        </div>
                                        <div class="file-path-wrapper">
                                            <input class="file-path validate" type="text">
                                        </div>
                                    </div>
                                    <div class="input-field col s12">
                                        <input type="text" class="validate" name="title"
                                               placeholder="Mug-NCAA, Pillow 1 NFL..." required>
                                        <label>Tiêu đề</label>
                                    </div>
                                    <div class="input-field col s12">
                                        <textarea id="textarea1" required name="require" class="materialize-textarea"
                                                  length="500"></textarea>
                                        <label for="textarea1">Yêu cầu công việc</label>
                                    </div>
                                    <div class="input-field col s12">
                                        <select name="worker" required>
                                            <option value="" disabled selected>Choose your option</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                        <label>Chọn nhân viên</label>
                                    </div>
                                    <div class="col s12">
                                        <button type="submit" class="right waves-effect waves-light btn blue">
                                            Giao Việc
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <br>
                            <span id="uploaded_image"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
