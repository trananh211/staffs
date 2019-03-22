$( document ).ready(function() {
    
    // Write your custom Javascript codes here...
    if ($('#alert-dialog').length > 0)
    {
        $('#alert-dialog').delay(5000).fadeOut(500);
    }

    $('.js-btn-redo').click(function (e) {
        e.preventDefault();
        var order_id = $(this).attr('order_id');
        $('.js-redo-form-'+order_id).slideToggle("fast");
    });
});
