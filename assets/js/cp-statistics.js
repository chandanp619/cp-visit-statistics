jQuery(window).load(function(){
    jQuery('form.ppsSubscribeForm input[type="email"]').on('blur',function(){
        var eml = jQuery(this).val();
        if(eml != ''){
            jQuery.ajax({
                url:AJAX_URL+'?action=updtIPEmail&email='+eml,
                method:'post'
            }).done(function(){
                
            });
        }
            
    });
})   
    
