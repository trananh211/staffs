<!DOCTYPE html>
<html >
@include('popup.head')
<body>
<div class="mn-content fixed-sidebar fixed-sidebar-on-hidden">
    <main class="mn-inner inner-active-sidebar hidden-fixed-sidebar">
        <div class="middle-content">
            @yield('content')
        </div>
    </main>
</div>

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
        $('#upload_form').on('submit', function (event) {
            event.preventDefault();
            $.ajax({
                url: "{{ route('ajaxupload.action') }}",
                method: "POST",
                data: new FormData(this),
                dataType: 'JSON',
                contentType: false,
                cache: false,
                processData: false,
                success: function (data) {
                    $('#message').css('display', 'block');
                    $('#message').html(data.message);
                    if ($.trim(data.uploaded_image).length > 0){
                        $('#uploaded_image').html(data.uploaded_image);
                    }
                    // console.log(data);
                }
            })
        });
    });
</script>
</body>
</html>
