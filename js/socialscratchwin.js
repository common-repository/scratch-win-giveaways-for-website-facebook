function validateEmail($email) {
    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
    return emailReg.test( $email );
}

function callRegister(){

    // Validate the form
    jQuery.validity.start();
    jQuery('#apmswn_reg_email_user').require('Please add email');
    jQuery('#apmswn_reg_email_user').match('email','Please add valid email');
    jQuery('#apmswn_reg_firstname').require('Please add first name');
    jQuery('#apmswn_reg_lastname').require('Please add last name');
    var result = jQuery.validity.end();

    if(result.valid)
    {
        jQuery('#createButton').val('Saving Settings..');
        jQuery('#createButton').attr('disabled','disabled');

        var $modal = jQuery('.js-loading-bar');
        $modal.show();
        jQuery('.modal-backdrop').appendTo('#registerBlock');

        jQuery.post(
            ajaxurl,
            jQuery('#registerForm').serialize()+'&raffd='+jQuery('#raffd').val(), 
            function(response){

                if(response.cp_reg == 0)
                {
                    jQuery('#gr_launch_link').attr('href', response.frame_url);
                    setTimeout(function(){
                        jQuery('#settingBlock').show();
                        jQuery('#registerBlock, .grBlkNonFrame').hide();
                        $modal.hide();
                    }, 500);
                }
                else if(response.cp_reg == 2)
                {
                    jQuery('.error_msg').html(response.message);
                    jQuery('#loaderBlock, #registerBlock').hide();
                    jQuery('.alertBox').show();
                    jQuery('#loginBlock').show();
                    $modal.hide();
                }
                else
                {
                    if(typeof response.message !== "undefined") {
                        jQuery('.error_msg').html(response.message);
                        jQuery('.alertBox').show();
                    }

                    jQuery('#createButton').removeAttr('disabled');
                    jQuery('#createButton').val('Next');
                    $modal.hide();
                }
            },'json'
        );
    }
    else
    {
        //alert('Please clear errors while input.');
        return false;
    }
}

function callVerify(){

    // Validate the form
    jQuery.validity.start();
    jQuery('#admin_email').require('Please add email');
    jQuery('#admin_email').match('email','Please add proper format email');
    var result = jQuery.validity.end();

    if(result.valid)
    {
        jQuery('#verifyButton').val('Updating Settings..');
        jQuery('#verifyButton').attr('disabled','disabled');

        var $modal = jQuery('.js-loading-bar3');
        $modal.show();
        jQuery('.modal-backdrop').appendTo('#loaderBlock');

        jQuery.post(
            ajaxurl,
            jQuery('#verifyForm').serialize()+'&raffd='+jQuery('#raffd').val(), 
            function(response){

                if(response.cp_reg == 1)
                {
                    jQuery('.error_msg').html(response.msg);
                    jQuery('.alertBox').show();
                    jQuery('#verifyForm').show();
                    jQuery('#verifyButton').val('Update');
                    jQuery('#verifyButton').removeAttr('disabled');
                    $modal.hide();
                }
                else if(response.cp_reg == 0)
                {
                    jQuery('#gr_launch_link').attr('href', response.frame_url);
                    setTimeout(function(){
                        jQuery('#settingBlock').show();
                        jQuery('#loaderBlock, .grBlkNonFrame').hide();
                        $modal.hide();
                    },500);
                }
                else
                {
                    jQuery('#registerBlock').show();
                    jQuery('#loaderBlock').hide();
                    $modal.hide();
                }
            },'json'
        );
    }
}

function callLoader(){

    var $modal = jQuery('.js-loading-bar3');
    $modal.show();

    jQuery.post(
        ajaxurl,
        {action:'apmswncheck_settings',raffd: jQuery('#raffd').val()}, 
        function(response){

            if(response.cp_reg == 0){
                jQuery('#gr_launch_link').attr('href', response.frame_url);
                setTimeout(function(){
                    jQuery('#settingBlock').show();
                    jQuery('#loaderBlock, .grBlkNonFrame').hide();
                    $modal.hide();
                },500);
            }
            else if(response.cp_reg == 2 || response.cp_reg == 3)
            {
                if(typeof response.message !== "undefined") {
                    jQuery('.error_msg').html(response.message);
                    jQuery('.alertBox').show();
                }
                jQuery('#loginBlock').show();
                jQuery('#loaderBlock').hide();
                $modal.hide();
            }
            else
            {
                if(typeof response.message !== "undefined") {
                    jQuery('.error_msg').html(response.message);
                    jQuery('.alertBox').show();
                }

                jQuery('#registerBlock').show();
                jQuery('#loaderBlock').hide();
                $modal.hide();
            }
        },'json'
    );
}

function callLogin() {

    // Validate login form
    jQuery.validity.start();
    jQuery('#apmswn_login_email').require('Please add email');
    jQuery('#apmswn_login_email').match('email','Please add valid email');
    jQuery('#apmswn_login_pwd').require('Please add password');
    var result = jQuery.validity.end();

    if(result.valid)
    {
        jQuery('#loginButton').val('Checking Login..');
        jQuery('#loginButton').attr('disabled','disabled');

        var $modal = jQuery('.js-loading-bar');
        $modal.show();

        jQuery.post(
            ajaxurl, 			
            jQuery('#loginForm').serialize()+'&raffd='+jQuery('#raffd').val(), 
            function(response){
                if(response.error == 0)
                {
                    jQuery('#gr_launch_link').attr('href', response.frame_url);
                    setTimeout(function(){
                        jQuery('#settingBlock').show();
                        jQuery('#loginBlock, .grBlkNonFrame').hide();
                        $modal.hide();
                    },500);
                }
                else
                {
                    if(typeof response.message !== "undefined") {
                        jQuery('.error_msg').html(response.message);
                        jQuery('.alertBox').show();
                    }

                    jQuery('#loginButton').removeAttr('disabled');
                    jQuery('#loginButton').val('Login');
                    $modal.hide();
                }
            },'json'
        );

    }else{
        return false;
    }
}

jQuery(document).ready(function(){		

    if(jQuery('#grRegisterAr').val() == 2){
        callLoader();
    }

    // Added for success tick
    jQuery('.inputBox input').on('input', function(){
        var re = /\S+@\S+\.\S+/;
        if(jQuery(this).data('type') == 'email') {
            if( jQuery(this).val() != '' && re.test( jQuery(this).val() ) ) {
                jQuery(this).parent().addClass('success').removeClass('errorBox');
                if(jQuery(this).parent().find('.error').length > 0) {
                    jQuery(this).parent().find('.error').remove();
                }
            }
            else {
                jQuery(this).parent().removeClass('success').addClass('errorBox');
            }
        }
        else if( jQuery(this).val() != '') {
            jQuery(this).parent().addClass('success').removeClass('errorBox');
            if(jQuery(this).parent().find('.error').length > 0) {
                jQuery(this).parent().find('.error').remove();
            }
        }
        else {
            jQuery(this).parent().removeClass('success').addClass('errorBox');
        }
    });
});
