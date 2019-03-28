@extends('popup')
@section('content')
    <?php
    if($up_id == env('UP_IDEA')){
        $url = route('ajaxIdeaUpload.action');
        $title = 'IDEA DESIGN';
    } else if ($up_id == env('UP_ORDER')) {
        $url = route('ajaxupload.action');
        $title = 'ORDER DESIGN';
    }
    ?>
    <div class="row">
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content">
                    <div class="container">
                        <span class="card-title">Upload Image - {{ $title }}</span>
                        <div class="alert" id="message" style="display: none"></div>
                        <form method="post" id="upload_form" enctype="multipart/form-data">
                            {{ csrf_field() }}
                            <div class="form-group">
                                <table class="table bordered">
                                    <tr>
                                        <td width="40%" align="right"><label>Select File for Upload</label></td>
                                        <td width="30"><input type="file" multiple name="files[]"
                                                              id="select_file" required /></td>
                                        <td width="30%" align="left">
                                            <input type="submit" name="upload" id="upload" class="btn btn-primary"
                                                   value="Upload">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="center">
                                            <div class="yellow lighten-5">
                                                Chỉ chấp nhận định dạng jpg, png và nhỏ hơn 10MB
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </form>
                        <br/>
                        <span id="uploaded_image"></span>
                        <span id="js-info-job" style="display: none">
                            <span class="url" data-url="{{ $url }}"></span>
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
