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
<script src="source/assets/js/alpha.min.js"></script>
<script src="{{ asset('source/assets/js/pages/form_elements.js') }}"></script>
<script src="source/assets/js/pages/dashboard.js"></script>
<script src="source/assets/js/pages/ui-modals.js"></script>


<script>
    if ($('#alert-dialog').length > 0)
    {
        $('#alert-dialog').delay(5000).fadeOut(500);
    }

    function newWindow(url, width, height) {
        myWindow=window.open(url,'','width=' + width + ',height=' + height);
    }
</script>
