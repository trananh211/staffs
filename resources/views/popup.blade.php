<!DOCTYPE html>
<html >
@if (Session::has('error'))
    <div class="row" id="alert-dialog">
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content red lighten-4 center">
                    <div class="widget-content alert-content">
                        <div class="alert alert-success alert-block">
                            <h5 class="alert-heading">Cảnh báo!!</h5>
                            {{ Session('error') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@if (Session::has('success'))
    <div class="row" id="alert-dialog">
        <div class="col s12 m12 l12">
            <div class="card">
                <div class="card-content green lighten-1">
                    <div class="widget-content alert-content">
                        <div class="alert alert-success alert-block">
                            <h5 class="alert-heading">Thành Công!!</h5>
                            {{ Session('success') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@include('popup.head')
<body>
<div class="row">
    <div id="loading" class="col s12 m6 l6 hidden">
        <div class="preloader-wrapper big active">
            <span>Đang Up.</span>
            <div class="spinner-layer spinner-red-only">
                <div class="circle-clipper left">
                    <div class="circle"></div>
                </div>
                <div class="gap-patch">
                    <div class="circle"></div>
                </div>
                <div class="circle-clipper right">
                    <div class="circle"></div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@yield('content')

<!-- Javascripts -->
<script src="{{ asset('source/assets/plugins/jquery/jquery-2.2.0.min.js') }}"></script>
<script src="{{ asset('source/assets/plugins/materialize/js/materialize.min.js') }}"></script>
<script src="{{ asset('source/assets/plugins/material-preloader/js/materialPreloader.min.js') }}"></script>
<script src="{{ asset('source/assets/plugins/jquery-blockui/jquery.blockui.js') }}"></script>
<script src="{{ asset('source/assets/js/pages/ui-carousel.js') }}"></script>
<script src="{{ asset('source/assets/plugins/google-code-prettify/prettify.js') }}"></script>
<script src="{{ asset('source/assets/js/pages/media.js') }}"></script>
<script src="{{ asset('source/assets/plugins/dropzone/dropzone.min.js') }}"></script>
<script src="{{ asset('source/assets/plugins/dropzone/dropzone-amd-module.min.js') }}"></script>
<script src="{{ asset('source/assets/js/alpha.min.js') }}"></script>


<script>
    $(document).ready(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('#upload_form').on('submit', function (event) {
            event.preventDefault();
            var url = $('#js-info-job span.url').attr('data-url');
            $.ajax({
                url: url,
                method: "POST",
                data: new FormData(this),
                // dataType: 'html',
                dataType: 'JSON',
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function(){
                    $("#loading").show();
                },
                complete: function(){
                    $("#loading").hide();
                },
                success: function (data) {
                    $('#message').css('display', 'block');
                    $('#message').html(data.message);
                    if ($.trim(data.uploaded_image).length > 0){
                        $('#uploaded_image').html(data.uploaded_image);
                    }
                    // console.log(data);
                }
            });
        });

        function showAlert(message)
        {
            Materialize.toast(message, 1000);
        }

        /** Chỉnh sửa trường fulfill excel*/

        /*Hiển thị giá trị tại danh sách tiêu đề bên tay phải*/
        function showListTitle(key_title, title, title_fixed)
        {
            var fixed = (title_fixed != '')? title_fixed : '.';
            var count = $('#js-show-title li').length + 1;
            var li = '<li class="collection-item js-li-show-'+ key_title +'"><div> '+count+' <span class="js-title-result hidden">'+ key_title +'-'+ title +'-'+ fixed +'</span> ('+ key_title +') - '+ title +' - '+ fixed +' <a href="#" data-title='+ key_title +' class="js-remove secondary-content"><i class="material-icons">delete</i></a></div></li>';
            $('#js-show-title').append(li);

            $('.js-key-title .option-title-'+key_title).addClass('hidden');
        }

        /* Hiển thị table mẫu ở trên cùng*/
        function showTableTitle(key_title, title, title_fixed)
        {
            var th = '<td class="js-table-'+key_title+'">'+ title +'</td>';
            var value = (title_fixed != '')? title_fixed : '.';
            var td = '<td class="js-table-'+key_title+'">'+ value +'</td>';
            $('#js-table-show-title tr.th').append(th);
            $('#js-table-show-title tr.td').append(td);
        }

        $('.js-view').on('click', function (e) {
            e.preventDefault();
            var key_title = $('.js-key-title').val();
            var title = $('.js-title').val();
            var title_fixed = $('.js-title-fixed').val();

            if (title == '') {
                var message = "Bạn phải điền giá trị title";
                showAlert(message);
                $('.js-title').focus();
            } else {
                showListTitle(key_title, title, title_fixed);
                showTableTitle(key_title, title, title_fixed);
                $('.js-title').val('');
                $('.js-title-fixed').val('');
                $('.js-key-title').val(0);
                titleRemove();
            }
        });

        titleRemove();

        function titleRemove() {
            $(document).on("click",".js-remove",function(){
                var key_title = $.trim($(this).attr('data-title'));
                console.log(key_title);
                $('.js-li-show-'+key_title).remove();
                $('.js-table-'+key_title).remove();
                $('.option-title-'+key_title).removeClass('hidden');
                addDataTitle();
            });
            addDataTitle();
        }

        function addDataTitle()
        {
            var title_data = '';
            if ($('#js-show-title li .js-title-result').length > 0)
            {
                $.each($('#js-show-title li .js-title-result'), function (index, value) {
                    title_data += $(this).text()+';';
                });
            }
            $('#js-data-title').val(title_data);
        }
        /** END Chỉnh sửa trường fulfill excel*/
    });
</script>
<style>
    #loading {
        position: fixed;
        right: 10px;
        bottom: 20px;
        width: 100px;
        height: 100px;
        z-index: 9999;
        background: none;
        border:none;
    }
</style>
</body>
</html>
