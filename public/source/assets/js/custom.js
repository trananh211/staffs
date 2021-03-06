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
            beforeSend: function () {
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
            }
        })
    });

    /* Redo new Sku*/
    $('.js-show-redo-sku').on('click',function (e) {
        e.preventDefault();
        var order_id = $(this).attr('order_id');
        $( ".js-show-redo-sku-"+order_id ).toggle();
    });

    $('.js-redo-new-submit').on('click',function (e) {
        e.preventDefault();
        var order_id = $(this).attr('order-id');
        var working_id = $(this).attr('working-id');
        var design_id = $(this).attr('design-id');
        var sku = $('.js-redo-new-sku-'+order_id).val();
        var worker_id = $('select.js-redo-new-designer-'+order_id).val();
        var new_variation = $('select.js-redo-new-variation-'+order_id).val();
        var reason = $('.js-redo-new-reason-'+order_id).val();
        var url = $('.js-redo-new-url').attr('url');
        var data = {
            order_id: order_id, working_id: working_id, design_id: design_id, sku: sku, worker_id: worker_id,
            reason: reason, new_variation: new_variation
        };

        $.ajax({
            method: "POST",
            url: url,
            data: data,
            dataType: 'JSON',
            // dataType: 'html',
            success: function (data) {
                if (data.status === 'success') {
                    Materialize.toast(data.message, 5000);
                    //xóa hàng đã chọn
                    $('.js-remove-tr-'+order_id).remove();
                    if (data.layout === 'refresh')
                    {
                        window.location.reload();
                    }
                    // console.log('success');
                } else {
                    // console.log('error');
                    Materialize.toast(data.message, 5000);
                }
                // console.log(data);
            },
            error: function (error) {
                window.location.reload();
                console.log(error);
            }
        })
    });
    /*End redo new sku*/

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
        if (confirm('Bạn có chắc chắn muốn thực hiện trả job này hay không?')) {
            var url = $(this).attr('data-url');
            var working_id = $(this).attr('data-workingid');
            var design_id = $(this).attr('data-woo-order-id');
            $(this).parents('tr').addClass('js-remove-table');
            $.ajax({
                method: "POST",
                url: url,
                data: {working_id: working_id, design_id: design_id},
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
        }
    });

    $('#idea-job tbody').on('click', '.js-give-job-other-staff', function (e) {
        e.preventDefault();
        if (confirm('Bạn có chắc chắn muốn thực hiện chuyển job này hay không?')) {
            var url = $(this).attr('data-url');
            var working_id = $(this).attr('data-workingid');
            var design_id = $(this).attr('data-woo-order-id');
            var staff_id = $('#js-change-staff-id-'+working_id).val();
            var data = {working_id: working_id, design_id: design_id, staff_id: staff_id};
            $(this).parents('tr').addClass('js-remove-table');
            $.ajax({
                method: "POST",
                url: url,
                data: data,
                dataType: 'JSON',
                // dataType: 'html',
                success: function (data) {
                    if (data.status === 'success') {
                        Materialize.toast(data.message, 2000);
                        //xóa hàng đã chọn
                        table_idea
                            .row('.js-remove-table')
                            .remove()
                            .draw();
                    } else {
                        $('.js-remove-table').removeClass('js-remove-table');
                        Materialize.toast(data.message, 2000);
                    }
                    // console.log('success');
                    console.log(data);
                },
                error: function (error) {
                    window.location.reload();
                    // console.log('error');
                    // console.log(error);
                }
            })
        }
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
            if (confirm("Bạn có chắc chắn thực hiện việc này?")) {
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

    function checkbox() {
        var list = new Array();
        $('.js-checkbox-one').each(function (index, value) {
            if ($(this).is(':checked')) {
                var product_id = $(this).parent('.js-data').attr('data-product-id');
                list.push(product_id);
            }
        });
        return list;
    }

    /*re send email to customer*/
    $('.js-re-send-email').on( 'click', function (e) {
        e.preventDefault();
        var working_id = $(this).attr('working_id');
        var design_id = $(this).attr('design_id');
        var url = $(this).attr('data-url');
        $(this).parents('tr').addClass('js-remove-table');
        $.ajax({
            method: "POST",
            url: url,
            data: {working_id : working_id, design_id: design_id},
            dataType: 'JSON',
            // dataType: 'html',
            success: function (data) {
                Materialize.toast(data.message, 5000);
                // console.log(data);
            },
            error: function (error) {
                window.location.reload();
                console.log(error);
            }
        })
    } );

    /*Woocommerce Product Create Automatic*/
    $('#woo-tem-choose-store').on('change', function (e) {
        var optionSelected = $(this).find("option:selected");
        var id_store = optionSelected.val();
        var consumer_key = optionSelected.attr('con_key');
        var consumer_secret = optionSelected.attr('con_sec');
        var url = optionSelected.attr('url');
        $('.js_url').val(url);
        $('.js_con_key').val(consumer_key);
        $('.js_con_sec').val(consumer_secret);
        console.log(id_store + '--' + url + '--' + consumer_key + '--' + consumer_secret);
    });

    $('.js-get-status-doc').on('click', function (e) {
        var pro_dri = $(this).attr('pro_dri');
        $('.js-get-status-doc').removeClass('blue lighten-5');
        $(this).addClass('blue lighten-5');
        if ($('#js-folder').hasClass('js-full')) {

            if (!$('#js-folder').hasClass('js-show')) {
                $('#js-folder').removeClass('s12').addClass('s5');
                $('#js-product').show();
            }
            $('.js-show-folder').hide();
            $('.js-show-folder-' + pro_dri).show();
            console.log('.js-show-folder-' + pro_dri);
        }
    });

    $('.js-closed').on('click', function (e) {
        $('.js-get-status-doc').removeClass('blue lighten-5');
        $('#js-folder').removeClass('s5').addClass('s12');
        $('#js-product').hide();
        $('.js-show-folder').hide();
    });

    //add variation
    $('.js-variation-add').on('click', function (e) {
        var variation_old = $('.js-variation-v1').val();
        var variation_compare = $('.js-variation-v2').val();
        var variation_new = $('.js-variation-v3').val();
        var variation_sku = $('.js-variation-v-sku').val();
        if (variation_old == '' || variation_compare == '' || variation_new == '') {
            alert('Mời bạn nhập đầy đủ các trường giá trị quy đổi.');
        } else {
            var num = $('.js-variation-item').length;
            var item = num + 1;
            var random = Math.random().toString(36).substring(7);
            var text = '<li class="js-variation-item collection-item js-collection-item-' + num + '">\n' +
                '                                <span class="js-variation-number">' + item + '.</span>\n' +
                '                                <span class="js-variation-data-' + num + '">\n' +
                '                                    <span class="grey-text text-darken-2 js-variation-data-old">' + variation_old + '</span> -\n' +
                '                                    <span class="orange-text text-darken-2 js-variation-data-compare">' + variation_compare + '</span> -\n' +
                '                                    <span class="green-text text-darken-2 js-variation-data-new">' + variation_new + '</span> -\n' +
                '                                    <span class="brown-text text-darken-2 js-variation-data-sku">' + variation_sku + '</span>\n' +
                '                                </span>\n' +
                '                                <a href="javascript:void(0);" class="pull-right right remove-variation-item-' + random + '"><i class="material-icons">delete</i></a>\n' +
                '                            </li>\n' +
                '<script>$(".remove-variation-item-' + random + '").on("click", function () {\n' +
                '        $(this).parent().remove();\n' +
                '    });</script>';
            $('ul.collection').prepend(text);
        }
    });

    $('.js-variation-finish').on('click', function (e) {
        e.preventDefault();
        var num = $('.js-variation-item').length;
        var variation_name = $('.js-variation-name').val();
        var variation_suplier = $('.js-variation-suplier').val();
        var json_data = $('.js-variation-full').val();
        // var json_data = [];
        // // var variation_old, variation_new, variation_compare, variation_sku;
        // $('.js-variation-item').each(function (index, value) {
        //     var variation_old = $('.js-variation-data-' + index + ' .js-variation-data-old').text();
        //     var variation_compare = $('.js-variation-data-' + index + ' .js-variation-data-compare').text();
        //     var variation_new = $('.js-variation-data-' + index + ' .js-variation-data-new').text();
        //     var variation_sku = $('.js-variation-data-' + index + ' .js-variation-data-sku').text();
        //     // console.log(index+'---'+variation_old+'---'+variation_compare+'---'+variation_new+'---'+variation_sku);
        //     var data = {
        //         'variation_old': variation_old,
        //         'variation_compare': variation_compare,
        //         'variation_new': variation_new,
        //         'variation_sku': variation_sku,
        //     };
        //     json_data.push(data);
        // });

        if (variation_name.length == 0) {
            Materialize.toast('Bạn phải chọn đặt tên cho Variation Change này!', 4000);
        } else if (variation_suplier == null) {
            Materialize.toast('Bạn phải chọn suplier!', 4000);
        } else if (json_data.length == 0) {
            Materialize.toast('Bạn phải tạo mới các biến variation!', 4000);
        } else {
            // if (confirm("Bạn đã chắc chắn tạo đủ hết variation chưa?")) {
                var url = $('#js-variation-url').attr('data-url');
                $.ajax({
                    method: "POST",
                    url: url,
                    data: {
                        'variation_name': variation_name,
                        'variation_suplier': variation_suplier,
                        'json_data': json_data
                    },
                    dataType: 'JSON',
                    // dataType: 'html',
                    success: function (data) {
                        // console.log('success');
                        // console.log(data);
                        alert(data.message);
                        if (data.result == 'success') {
                            $(location).attr('href', data.url);
                        } else {
                            window.location.reload();
                        }
                    },
                    error: function (error) {
                        // window.location.reload();
                        console.log('error');
                        console.log(error);
                    }
                })
            }
        // }
    });

    $(".remove-variation-item").on("click", function () {
        $(this).parent().remove();
    });

    $('.js-variation-name').on('blur', function () {
        checkVariationChange();
    });

    $('.js-variation-suplier').on('change', function () {
        checkVariationChange();
    });

    function checkVariationChange() {
        var variation_name = $('.js-variation-name').val();
        var variation_suplier = $('.js-variation-suplier').val();
        if (variation_name.length > 0 && variation_suplier != null) {
            var url = $('#js-variation-check').attr('data-url');
            $.ajax({
                method: "POST",
                url: url,
                data: {'variation_name': variation_name, 'variation_suplier': variation_suplier},
                dataType: 'JSON',
                // dataType: 'html',
                success: function (data) {
                    // Materialize.toast(data.message, 5000);
                    // window.location.reload();
                    // console.log('success');
                    // console.log(data);
                    if (data.result != 'success') {
                        console.log(data.result + ' da vao den day roi');
                        $('.js-variation-name').val('').focus();
                    }
                    $('#js-variation-check-result').html(data.message);
                },
                error: function (error) {
                    // window.location.reload();
                    console.log('error');
                    console.log(error);
                    Materialize.toast("Xảy ra lỗi. Mời bạn tải lại trang", 5000);
                }
            })
        }
    }

    /*End Woocommerce Product Create Automatic*/

    /*Keyword*/
    $(".js-btn-show-right").click(function(){
        var url = $('#js-keyword-category').attr('url');
        var cat_name = $(this).attr('data-catname');
        var cat_id = $(this).attr('data-catid');
        var dt = {'cat_name': cat_name, 'cat_id': cat_id};
        $.ajax({
            method: "POST",
            url: url,
            data: dt,
            dataType: 'JSON',
            // dataType: 'html',
            success: function (data) {
                Materialize.toast(data.message, 5000);
                // window.location.reload();
                // console.log('success');
                // console.log(data);
                if (data.result == 'success') {
                    showDataKeyword(data)
                    console.log(data);
                }
            },
            error: function (error) {
                // window.location.reload();
                console.log('error');
                console.log(error);
                Materialize.toast("Xảy ra lỗi. Mời bạn tải lại trang", 5000);
            }
        });
    });

    $(".js-btn-variation-show-right").click(function(){
        var url = $('#js-variation-category').attr('url');
        var cat_name = $(this).attr('data-catname');
        var cat_id = $(this).attr('data-catid');
        var dt = {'cat_name': cat_name, 'cat_id': cat_id};
        $.ajax({
            method: "POST",
            url: url,
            data: dt,
            dataType: 'JSON',
            // dataType: 'html',
            success: function (data) {
                Materialize.toast(data.message, 5000);
                console.log(data)
                if (data.result == 'success') {
                    showDataVariation(data)
                    // console.log(data);
                }
            },
            error: function (error) {
                // window.location.reload();
                // console.log('error');
                console.log(error);
                Materialize.toast("Xảy ra lỗi. Mời bạn tải lại trang", 5000);
            }
        });
    });

    function showDataVariation(data) {
        $('.js-show').removeClass('blue lighten-5');
        $('.js-show-'+data.cat_id).addClass('blue lighten-5');
        showRight();
        if (data.variations.length > 0)
        {
            addOptionVariation(data, 'js-select-variation');
        }
    }

    function addOptionVariation(data, selectId) {
        $('#js-category-name').html(data.cat_name);
        $('input#js-cat_id').val(data.cat_id);
        $('#'+selectId+' option').remove();
        var items = data.variations;
        var text;
        $.each(items, function (i, item) {
            if (item.tool_category_name != '')
            {
                text = item.variation_name+'('+item.tool_category_name+')';
            } else {
                text = item.variation_name;
            }
            if (item.selected == 1)
            {
                $('#'+selectId).append($('<option>', {
                    value: item.id,
                    text : text,
                    selected: true
                }));
            } else {
                $('#'+selectId).append($('<option>', {
                    value: item.id,
                    text : text
                }));
            }
        });
    }

    function showDataKeyword(data)
    {
        $('#js-category-title').html(data.cat_name);
        $('#cat_id').val(data.cat_id);
        $('#lst_keyword').val(data.list_keyword);
        $('.js-show').removeClass('blue lighten-5');
        $('.js-show-'+data.cat_id).addClass('blue lighten-5');
        showRight();
    }

    function showRight() {
        //show righ
        $('.js-view-right').removeClass('s12').addClass('s6');
        $('.js-right-colum').removeClass('hidden').addClass('s6');
    }

    function hideRight()
    {
        $('.js-view-right').removeClass('s6').addClass('s12');
        $('.js-right-colum').removeClass('s6').addClass('hidden');
        $('.js-show').removeClass('blue lighten-5');
    }

    $('.btn-right-close').on('click', function () {
        hideRight();
    });

    //make category feed
    $('#js-store-feed').change(function () {
       var store_id = $(this).val();
       $('#js-category-feed .js-store').hide();
       $('#js-category-feed .js-store-'+store_id).show();
        $('#js-category-feed').val('all');
    });

    /*End Keyword*/

    $('#form-up-tracking').on('submit', function (event) {
        event.preventDefault();
        var url = $('#form-up-tracking').attr('data-url');
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
                $('#js-noti').html(data.message);
                // console.log(data);
            }
        });
    });
});
