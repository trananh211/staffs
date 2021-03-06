<!-- Javascripts -->
<script src="source/assets/plugins/jquery/jquery-2.2.0.min.js"></script>
<script src="source/assets/plugins/materialize/js/materialize.min.js"></script>
<script src="source/assets/plugins/material-preloader/js/materialPreloader.min.js"></script>
<script src="source/assets/plugins/jquery-blockui/jquery.blockui.js"></script>
<script src="source/assets/plugins/waypoints/jquery.waypoints.min.js"></script>
<script src="source/assets/plugins/counter-up-master/jquery.counterup.min.js"></script>
<script src="source/assets/plugins/jquery-sparkline/jquery.sparkline.min.js"></script>
<script src="source/assets/plugins/chart.js/chart.min.js"></script>
<script src="source/assets/plugins/flot/jquery.flot.min.js"></script>
<script src="source/assets/plugins/flot/jquery.flot.time.min.js"></script>
<script src="source/assets/plugins/flot/jquery.flot.symbol.min.js"></script>
<script src="source/assets/plugins/flot/jquery.flot.resize.min.js"></script>
<script src="source/assets/plugins/flot/jquery.flot.tooltip.min.js"></script>
<script src="source/assets/plugins/curvedlines/curvedLines.js"></script>
<script src="source/assets/plugins/peity/jquery.peity.min.js"></script>
<script src="source/assets/plugins/google-code-prettify/prettify.js"></script>
<script src="{{ asset('source/assets/plugins/datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="source/assets/js/alpha.min.js"></script>
<script src="{{ asset('source/assets/js/pages/form_elements.js') }}"></script>
<script src="{{ asset('source/assets/js/pages/table-data.js') }}"></script>
<script src="{{ asset('source/assets/js/pages/ui-modals.js') }}"></script>
<script src="{{ asset('source/assets/js/custom.js') }}"></script>


<script>
    function newWindow(url, width, height) {
        myWindow=window.open(url,'','width=' + width + ',height=' + height);
    }

    $(document).ready(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        var table = $('#review-job').DataTable();

        $('#review-job tbody').on( 'click', '.js-done-job', function (e) {
            e.preventDefault();
            var working_id = $(this).attr('working_id');
            var design_id = $(this).attr('design_id');
            $(this).parents('tr').addClass('js-remove-table');
            $.ajax({
                method: "POST",
                url: "{{ route('ajaxdonejob.action') }}",
                data: {working_id : working_id, design_id: design_id},
                dataType: 'JSON',
                // dataType: 'html',
                success: function (data) {
                    if (data.status === 'success')
                    {
                        Materialize.toast(data.message, 4000);
                        //xóa hàng đã chọn
                        table
                            .row('.js-remove-table')
                            .remove()
                            .draw();
                    } else {
                        $('.js-remove-table').removeClass('js-remove-table');
                        Materialize.toast(data.message, 5000);
                    }
                },
                error: function (error) {
                    window.location.reload();
                    console.log(error);
                }
            })
        } );

        $('#js_new_job').on('submit', function (event) {
            event.preventDefault();
            $.ajax({
                url: "{{ route('ajaxnewjob.action') }}",
                method: "POST",
                data: new FormData(this),
                dataType: 'json',
                // dataType: 'html',
                cache: false,
                processData: false,
                contentType: false,
                success: function (data) {
                    $('#message').css('display', 'block');
                    $('#message').html(data.message);
                    if ($.trim(data.img).length > 0){
                        $('#uploaded_image').html(data.img);
                    }
                    $('#js_new_job')[0].reset();
                    // console.log(data);
                    // console.log(data.message);
                    // console.log(data.img);
                },
                error: function (error) {
                    console.log('error');
                    Materialize.toast('Xảy ra lỗi. Mời bạn thử lại 1 lần nữa', 5000);
                    window.location.reload().delay(5000);
                }
            })
        });
    });
</script>
