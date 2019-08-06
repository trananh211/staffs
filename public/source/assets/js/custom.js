$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    var table_idea = $('#idea-job').DataTable();

    $('#idea-job tbody').on('click', '.js-send-qc', function (e) {
        e.preventDefault();
        var url = $(this).attr('data-url');
        var idea_id = $(this).attr('data-id');
        $(this).parents('tr').addClass('js-remove-table');
        $.ajax({
            method: "POST",
            url: url,
            data: {idea_id: idea_id},
            dataType: 'JSON',
            // dataType: 'html',
            beforeSend: function() {
                $('#loading').fadeIn();
            },
            success: function (data) {
                if (data.status === 'success') {
                    Materialize.toast(data.message, 5000);
                    //xóa hàng đã chọn
                    table_idea
                        .row('.js-remove-table')
                        .remove()
                        .draw();
                } else {
                    $('.js-remove-table').removeClass('js-remove-table');
                    Materialize.toast(data.message, 5000);
                }
                $('#loading').fadeOut();
                // console.log('success');
                // console.log(data);
            },
            error: function (error) {
                $('#loading').fadeOut();
                window.location.reload();
                // console.log('error');
                // console.log(error);
            }
        })
    });

    $('#idea-job tbody').on('click', '.js-redo-button', function (e) {
        e.preventDefault();
        var id = $(this).attr('data-id');
        var url = $('.js-redo-form-' + id + ' .url').attr('data-url');
        var idea_id = $('.js-redo-form-' + id + ' .js-idea-id').val();
        var reason = $('.js-redo-form-' + id + ' .js-idea-reason').val();
        $(this).parents('tr').addClass('js-remove-table');
        $.ajax({
            method: "POST",
            url: url,
            data: {idea_id: idea_id, reason: reason},
            dataType: 'JSON',
            // dataType: 'html',
            success: function (data) {
                if (data.status === 'success') {
                    Materialize.toast(data.message, 5000);
                    //xóa hàng đã chọn
                    table_idea
                        .row('.js-remove-table')
                        .remove()
                        .draw();
                } else {
                    $('.js-remove-table').removeClass('js-remove-table');
                    Materialize.toast(data.message, 5000);
                }
                // console.log('success');
                // console.log(data);
            },
            error: function (error) {
                window.location.reload();
                // console.log('error');
                // console.log(error);
            }
        })
    });

    $('#idea-job tbody').on('click', '.js-upload-idea', function (e) {
        e.preventDefault();
        var idea_id = $(this).attr('data-id');
        var url = $(this).attr('data-url');
        $(this).parents('tr').addClass('js-remove-table');
        $.ajax({
            method: "POST",
            url: url,
            data: {idea_id: idea_id},
            dataType: 'JSON',
            // dataType: 'html',
            success: function (data) {
                if (data.status === 'success') {
                    Materialize.toast(data.message, 5000);
                    //xóa hàng đã chọn
                    table_idea
                        .row('.js-remove-table')
                        .remove()
                        .draw();
                } else {
                    $('.js-remove-table').removeClass('js-remove-table');
                    Materialize.toast(data.message, 5000);
                }
                // console.log('success');
                // console.log(data);
            },
            error: function (error) {
                window.location.reload();
                // console.log('error');
                // console.log(error);
            }
        })
    });

    // Write your custom Javascript codes here...
    if ($('#alert-dialog').length > 0) {
        $('#alert-dialog').delay(5000).fadeOut(500);
    }


    $('.js-btn-redo').click(function (e) {
        e.preventDefault();
        var order_id = $(this).attr('order_id');
        $('.js-redo-form-' + order_id).slideToggle("fast");
    });

    $('#idea-job tbody').on('click', '.js-take-job', function (e) {
        e.preventDefault();
        var url = $(this).attr('data-url');
        var working_id = $(this).attr('data-workingid');
        var woo_order_id = $(this).attr('data-woo-order-id');
        $(this).parents('tr').addClass('js-remove-table');
        $.ajax({
            method: "POST",
            url: url,
            data: {working_id: working_id, woo_order_id: woo_order_id},
            dataType: 'JSON',
            // dataType: 'html',
            success: function (data) {
                if (data.status === 'success') {
                    Materialize.toast(data.message, 5000);
                    //xóa hàng đã chọn
                    table_idea
                        .row('.js-remove-table')
                        .remove()
                        .draw();
                } else {
                    $('.js-remove-table').removeClass('js-remove-table');
                    Materialize.toast(data.message, 5000);
                }
                // console.log('success');
                // console.log(data);
            },
            error: function (error) {
                window.location.reload();
                // console.log('error');
                // console.log(error);
            }
        })
    });

    $('#idea-job tbody').on('click', '.js-delete-log', function (e) {
        e.preventDefault();
        var url = $(this).attr('data-url');
        var name = $(this).attr('data-name');
        $(this).parents('tr').addClass('js-remove-table');
        $.ajax({
            method: "POST",
            url: url,
            data: {name: name},
            dataType: 'JSON',
            // dataType: 'html',
            success: function (data) {
                if (data.status === 'success') {
                    Materialize.toast(data.message, 5000);
                    //xóa hàng đã chọn
                    table_idea
                        .row('.js-remove-table')
                        .remove()
                        .draw();
                } else {
                    $('.js-remove-table').removeClass('js-remove-table');
                    Materialize.toast(data.message, 5000);
                }
                // console.log('success');
                // console.log(data);
            },
            error: function (error) {
                window.location.reload();
                // console.log('error');
                // console.log(error);
            }
        })
    });

    $('.js-skip-product').on('click', function (e) {
        e.preventDefault();
        var list = checkbox();
        if (list.length == 0) {
            Materialize.toast('Bạn phải chọn sản phẩm trước đã!', 4000);
        } else {
            if(confirm("Bạn có chắc chắn thực hiện việc này?"))
            {
                var url = $(this).attr('data-url');
                $.ajax({
                    method: "POST",
                    url: url,
                    data: {list: list},
                    dataType: 'JSON',
                    // dataType: 'html',
                    success: function (data) {
                        Materialize.toast(data.message, 5000);
                        window.location.reload();
                        // console.log('success');
                        // console.log(data);
                    },
                    error: function (error) {
                        window.location.reload();
                        // console.log('error');
                        // console.log(error);
                    }
                })
            }
        }
    });

    function checkbox()
    {
        var list = new Array();
        $('.js-checkbox-one').each(function (index, value) {
            if ($(this).is(':checked')) {
                var product_id = $(this).parent('.js-data').attr('data-product-id');
                list.push(product_id);
            }
        });
        return list;
    }

    /*Woocommerce Product Create Automatic*/
    $('#woo-tem-choose-store').on('change' , function (e) {
        var optionSelected = $(this).find("option:selected");
        var id_store  = optionSelected.val();
        var consumer_key  = optionSelected.attr('con_key');
        var consumer_secret  = optionSelected.attr('con_sec');
        var url  = optionSelected.attr('url');
        $('.js_url').val(url);
        $('.js_con_key').val(consumer_key);
        $('.js_con_sec').val(consumer_secret);
        console.log(id_store+'--'+url+'--'+consumer_key+'--'+consumer_secret);
    });

    $('.js-get-status-doc').on('click', function (e) {
        var pro_dri = $(this).attr('pro_dri');
        $('.js-get-status-doc').removeClass('blue lighten-5');
        $(this).addClass('blue lighten-5');
        if ($('#js-folder').hasClass('js-full')){

            if ( ! $('#js-folder').hasClass('js-show')) {
                $('#js-folder').removeClass('s12').addClass('s5');
                $('#js-product').show();
            }
            $('.js-show-folder').hide();
            $('.js-show-folder-'+pro_dri).show();
            console.log('.js-show-folder-'+pro_dri);
        }
    });

    $('.js-closed').on('click', function (e) {
        $('.js-get-status-doc').removeClass('blue lighten-5');
        $('#js-folder').removeClass('s5').addClass('s12');
        $('#js-product').hide();
        $('.js-show-folder').hide();
    });
    /*End Woocommerce Product Create Automatic*/
});
