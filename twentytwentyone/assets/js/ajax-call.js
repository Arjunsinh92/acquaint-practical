jQuery(document).ready(function() {
    jQuery(".form-select").change(function(){
        jQuery(".loader-img").show();
        var ptype = jQuery("#products-opt").val();
        var data = {
            'action' : 'load_posts_by_ajax',
            'type' : ptype,
        }
        jQuery.ajax({
            type: "POST",
            url : ajaxurl,
            data : data,
            success: function(data) {
                jQuery(".loader-img").hide();
                if(data){
                    jQuery('.ajax-result').html( data );
                }else{
                    jQuery('.ajax-result').html( 'No products found' );
                }
            },
        });
    });
});