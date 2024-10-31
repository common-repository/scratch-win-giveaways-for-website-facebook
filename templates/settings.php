<div class="wrap">
<h1></h1> <!-- Don't delete. This is for WP message push. -->
<?php 
    $grRegisterAr          = get_option('apmswn_register',0);
    $displayRegisterBlock  = ($grRegisterAr == 0)?"block":"none";
    $displaySettingBlock   = ($grRegisterAr == 1)?"block":"none";
    $displayLoaderBlock    = ($grRegisterAr == 2)?"block":"none";
    $displayLoginBlock     = ($grRegisterAr == 3)?"block":"none";
?>
<input type="hidden" id="grRegisterAr" value="<?php echo $grRegisterAr?>" />

    <div class="grWrap">
        <div class="grContent">

            <div class="grHead">
                <h1>Scratch & <b>Win!</b></h1>
            </div>

            <div class="ConnectBlock grBlkNonFrame" id="loaderBlock" style="min-height:350px;display:<?php echo $displayLoaderBlock?>;">
                <form class="formGr form-horizontal" method="post" action="#" id="verifyForm" style="display:none">
                    <input type="hidden" name="action" value="check_gr_settings" />
                    <p class="subtitle">Verify your Email</p>
                    <div class="inputBox">
                        <input type="text" data-type="email" name="admin_email" id="admin_email" value="<?php echo get_option('apmswn_admin_email'); ?>"
                            maxlength="250" class="form-control" placeholder="Email" title="Email" />
                        <u></u><i></i>
                    </div>
                    <div class="form-group">
                        <input type="button" id="verifyButton" value="Update" onclick="callVerify();" class="btn btn-success btn-lg" />
                    </div>
                    <div class="alertBox" style="display:none;"><div><i></i> <span class="error_msg"></span></div></div>
                </form>
                <div class="modal js-loading-bar3" style="background:rgba(51, 51, 51, 0.85);display:none;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-body" style="text-align:center; padding: 20px;">
                                <img src="<?php echo plugin_dir_url( __FILE__ )?>../img/loader.gif" style='margin: 20px 0 10px;'>
                                <!--<div style=" margin: 20px;">Saving...</div>-->
                                <p style="margin: 10px 0; font-size: 16px;">Verifying Your Account. This may take a few seconds. Please do not refresh or close the browser. Thanks......</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form for registeration Starts here -->
            <div class="ConnectBlock grBlkNonFrame" id="registerBlock" style="display:<?php echo $displayRegisterBlock?>;">
                <form class="formGr form-horizontal" method="post" action="#" id="registerForm">
                    <input type="hidden" name="action" value="apmswncreate_account" />
                    <?php $current_user = wp_get_current_user();?>

                    <p class="subtitle">Get started with <mark><b>free full-featured</b> plan</mark> No Obligations. No Commitment.</p>
                    
                    <div class="inputBox">
                        <input type="text" data-type="email" name="apmswn_reg_email_user" id="apmswn_reg_email_user" value="<?php echo get_option('apmswn_admin_email'); ?>"
                            maxlength="250" class="form-control" placeholder="Email" title="Email" />
                            <u></u><i></i>
                    </div>
                    <div class="inputBox">
                        <input type="text" name="apmswn_reg_firstname" maxlength="100" id="apmswn_reg_firstname" value="<?php echo $current_user->user_firstname; ?>" 
                            class="form-control" placeholder="First Name" title="First Name" />
                        <u></u><i></i>
                    </div>
                    <div class="inputBox">
                        <input type="text" name="apmswn_reg_lastname" maxlength="100" id="apmswn_reg_lastname" value="<?php echo $current_user->user_lastname; ?>" 
                            class="form-control" placeholder="Last Name" title="Last Name" />
                        <u></u><i></i>
                    </div>
                    <div class="checkBox"><span>By clicking 'NEXT' you agree to our <a href='https://appsmav.com/terms.php' target="_blank">Terms &amp; Conditions</a> and <a href='https://appsmav.com/privacy.php' target="_blank">Privacy Policy</a></span></div>

                    <div class="form-group">
                        <input type="button" id="createButton" value="Next" onclick="callRegister();" class="btn btn-success btn-lg" />
                    </div>
                    <div class="alertBox" style="display:none;"><div><i></i> <span class="error_msg"></span></div></div>
                </form>
                <div class="modal js-loading-bar" style="background:rgba(51, 51, 51, 0.85);display:none;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-body" style="text-align:center; padding: 50px;">
                                <img src="<?php echo plugin_dir_url( __FILE__ )?>../img/loader.gif" style='margin: 20px 0 10px;'>
                                <!--<div style=" margin: 20px;">Saving...</div>-->
                                <p style="margin: 10px 0; font-size: 16px;">Creating your account. This may take a few seconds. Please do not refresh or close the browser. Thanks......</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Form for registeration ends here -->

            <!-- Form for login Starts here -->
            <div class="ConnectBlock grBlkNonFrame" id="loginBlock" style="display:<?php echo $displayLoginBlock?>;">
                <form class="formGr form-horizontal" method="post" action="#" id="loginForm">
                    <input type="hidden" name="action" value="apmswncheck_login" />
                    <p class="subtitle">Login with your Email and Password</p>
                    <div class="inputBox">
                        <input type="text" data-type="email" name="apmswn_login_email" id="apmswn_login_email" value="" 
                            maxlength="250" class="form-control" placeholder="Email" title="Email" />
                        <u></u><i></i>
                    </div>
                    <div class="inputBox">
                        <input type="password" name="apmswn_login_pwd" id="apmswn_login_pwd" value="" 
                            maxlength="250" class="form-control" placeholder="Password" title="Password" />
                        <u></u><i></i>
                    </div>
                    <div class="form-group">
                        <input type="button" id="loginButton" value="Login" onclick="callLogin();" class="btn btn-success btn-lg" />
                    </div>
                    <div class="checkBox">
                        <a href="https://appsmav.com/customer/pwreset.php" style="color:#007bff" target="_blank"> Forgot password?</a>
                    </div>
                    <div class="alertBox" style="display:none;"><div><i></i> <span class="error_msg"></span></div></div>
                </form>
            </div>
            <!-- Form for login ends here -->


            <section class="gr_400_section grBlkNonFrame" style="display:none">
                <img src="<?php echo plugin_dir_url( __FILE__ )?>../img/img-400-error.jpg" alt="Error 400">
                <h2><b>Error!</b><br/> Invalid Shop!</h2>
            </section>

            <!-- After Login Block -->
            <div class="ConnectBlock text-center" id="settingBlock" style="display:<?php echo $displaySettingBlock?>;">
                <figure>
                    <img src="<?php echo plugin_dir_url( __FILE__ )?>../img/scratch-and-win-banner.jpg" alt="Scratch and Win" />
                </figure>
                <a href="<?php echo $frame_url?>" target="_blank" class="btn btn-success btn-lg" id="gr_launch_link">
                    <span>LAUNCH APPLICATION</span>
                    <img src="<?php echo plugin_dir_url( __FILE__ )?>../img/icon-link.png" height="14" alt="Scratch and Win" />
                </a>
            </div>

        </div>

        <p class="helpText">If you face any problem installing this app, simply email to <a href="mailto:sales@appsmav.com">sales@appsmav.com</a>.<br>
         Our team will work with you to correctly install &amp; configure Scratch & Win.</p>
    </div>

    <input type="hidden" name="raffd" id="raffd" value="" />
    <script src="//cdn.appsmav.com/am/lib/js/chat.js"></script>
    <!-- Form for setting ends here -->
</div>
