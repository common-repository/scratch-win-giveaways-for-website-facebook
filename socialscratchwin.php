<?php
/**
 * @package Scratch & win giveaways
 * @version 2.6.8
 */
/*
 Plugin Name: Scratch & win giveaways
 Plugin URI: http://appsmav.com
 Description: LAUNCH AWESOME SCRATCH CARD GIVEAWAYS- BOOST LEADS AND CONVERSIONS! BUILD LISTS. GET REFERRAL SALES.
 Version: 2.6.8
 Author: Appsmav
 Author URI: http://appsmav.com
 License: GPL2
*/
/*  Copyright 2015  Appsmav  (email : support@appsmav.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
define('SWIN_PLUGIN_BASE_PATH', dirname(__FILE__));

if(!class_exists('Appsmav_Scratchwin'))
{
    class Appsmav_Scratchwin 
    {
        public static $_plugin_version  = '2.6.8';
        protected static $_callback_url = 'https://win.appsmav.com/';
        protected static $_api_version  = 'api/v1/';
        protected static $_api_url	= 'https://clients.appsmav.com/api_v1.php';
        protected static $_c_sdk_url    = '//cdn.appsmav.com/win/assets/js/swin-widget-sdk.js?v=2.6.8';
        
        /**
         * Construct the plugin object
         */
        public function __construct()
        {
            // register actions
            add_action('admin_init', array(&$this, 'admin_init'));
            add_action('admin_menu', array(&$this, 'add_menu'));
            add_action('wp_footer', array( &$this,'apmswn_widget') );	
            add_action('admin_enqueue_scripts', array(&$this,'swin_font_styles'));
            add_action('parse_request', array(&$this,'apmswn_create_discount'));

            add_action('rest_api_init', array($this, 'register_rest_routes'), 10);

            //Install tab embed page changes
            add_action('save_post', array(&$this,'sw_save_post'), 10, 3);
            add_action('delete_post', array(&$this,'sw_delete_post'), 10, 3);

        } // END public function __construct
    
        /**
         * Activate the plugin
         */
        public static function activate()
        {
            update_option('apmswn_register', 2);
        } // END public static function activate
    
        /**
         * Deactivate the plugin
         */     
        public static function deactivate()
        {
            delete_option('apmswn_plgtyp');

            // Delete stored informations
            $id_shop = get_option('apmswn_shop_id', 0);
            $id_site = get_option('apmswn_appid', 0);
            $payload = get_option('apmswn_payload', 0 );
            $plugin_type = 'WP';
            if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
                $plugin_type = 'WOO';
            }

            $param = array('app'=>'swn', 'plugin_type'=>$plugin_type, 'status'=>'deactivate', 'id_shop'=>$id_shop, 'id_site'=>$id_site, 'payload'=>$payload);
            $url = self::$_callback_url . self::$_api_version . 'pluginStatus';

            wp_remote_post($url, array('body' => $param, 'timeout' => 10));

        } // END public static function deactivate

        /**
         * hook into WP's admin_init action hook
         */
        public function admin_init()
        {
            // Set up the settings for this plugin
            $this->init_settings();
            // Possibly do additional admin_init tasks
        } // END public static function activate

        /**
         * Initialize some custom settings
         */     
        public function init_settings()
        {
            // register the settings for this plugin
            add_action( 'wp_ajax_apmswncreate_account', array(&$this,'apmswn_ajax_cpcreate_account' ));
            add_action( 'wp_ajax_apmswncheck_settings', array(&$this,'apmswn_ajax_cpcheck_settings' ));
            add_action( 'wp_ajax_apmswncheck_login', array(&$this,'apmswn_ajax_apmswncheck_login' ));
        } // END public function init_custom_settings()

        public function apmswn_widget() 
        {
            $app_id =   get_option('apmswn_appid', 0);

            if(empty($app_id))
                return false;

            $id_site        = get_option('apmswn_appid');
            $arr['id_site'] = $id_site;
            $arr['error']   = 0;
            $cid            =	$cemail   =	$cname  =   '';

            if ( is_user_logged_in() )
            {
                $current_user = wp_get_current_user();
                $cid	=	$current_user->ID;
                $cemail	=	$current_user->user_email;
                $cname	=	$current_user->display_name;
            }

            $version = empty($_SERVER['REQUEST_TIME']) ? time() : $_SERVER['REQUEST_TIME'];
            
            echo '<script>var AMSWINConfig = {user : {name : "'.$cname.'", email : "'.$cemail.'", id : "'.$cid.'", country : ""}, site : {id : "'.$id_site.'", domain : "", platform : "wp", version: "'.self::$_plugin_version.'"}};
                (function(d, s, id) {
                    var js, amjs = d.getElementsByTagName(s)[0];
                    if (d.getElementById(id)) return;
                    js = d.createElement(s); js.id = id; js.async = true;
                    js.src = "'.self::$_c_sdk_url.'";
                    amjs.parentNode.insertBefore(js, amjs);
                }(document, "script", "swin-sdk"));
            </script>';
        }

        public static function apmswn_show_func($atts) {
            $id         =   isset($atts['id'])? trim($atts['id']) : '';

            if(empty($id)) 
                return '';
       
            $url        =   self::$_callback_url . 'contest/play/' . $id;
            $content    =   '<a class="swin-widget" href="'.$url.'" >Scratch & Win</a>';
            $js_url     =   self::$_callback_url . 'script.js';

            wp_enqueue_script('apmswn_frame_script', $js_url, array(), self::$_plugin_version, true);
            
            return $content;
        }

        /**
         * add a menu
         */     
        public function add_menu()
        {
            add_options_page('Scratch & Win Appsmav Settings', 'Scratch & Win Appsmav', 'manage_options', 'appsmavscratchwin', array(&$this, 'plugin_settings_page'));
        } // END public function add_menu()

        /**
         * Additional Styles
         * @since 1.0.0
         */
        public function swin_font_styles($hook) 
        {
            if('settings_page_appsmavscratchwin' != $hook)
                return;
            
            // register styles
            wp_register_style( 'bootstrap_css', plugins_url('/css/bootstrap.min.css', __FILE__) );
            wp_register_style( 'apmswn_appsmav_css', plugins_url('/css/socialscratchwin.css', __FILE__) );

            // enqueue styles	
            wp_enqueue_style('bootstrap_css');
            wp_enqueue_style('apmswn_appsmav_css');

            // enqueue scripts
            wp_enqueue_script( 'jquery_validity_script', plugins_url( '/js/jquery.validity.js',__FILE__ ),array(), self::$_plugin_version, true );
            wp_enqueue_script( 'apmswn_appsmav_script', plugins_url( '/js/socialscratchwin.js',__FILE__ ),array(), self::$_plugin_version, true );

        }

        /**
         * Menu Callback
         */     
        public function plugin_settings_page()
        {
            if(!current_user_can('manage_options'))
                wp_die(__('You do not have sufficient permissions to access this page.'));


            // Render the settings template
            $frame_url	= 'about:blank';
            if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )  &&  get_option('apmswn_plgtyp', 0 ) != 'WOO') 
                update_option( 'apmswn_register', 2 );
            else if(!in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )  && get_option('apmswn_plgtyp', 0 ) == 'WOO')
                update_option( 'apmswn_register', 2 );
				
            if(get_option('apmswn_register', 0 )	== 1)
            {
                $arr['id_shop']     =   get_option('apmswn_shop_id', 0 );
                $arr['admin_email'] =   get_option('apmswn_admin_email');
                $arr['payload']     =   get_option('apmswn_payload', 0 );
                $frame_url          =	self::$_callback_url.'autologin?id_shop='.$arr['id_shop'].'&admin_email='.urlencode($arr['admin_email']).'&payload='.$arr['payload'];
            }

            include(sprintf("%s/templates/settings.php", dirname(__FILE__)));

        } // END public function plugin_settings_page()
		
        public function apmswn_ajax_cpcheck_settings(){

            $raffd = isset($_POST['raffd']) ? sanitize_text_field($_POST['raffd']) : '';
            $email = get_option('apmswn_admin_email');
            if (isset($_POST['admin_email']))
                $email = sanitize_email($_POST['admin_email']);

            $param['email'] = $email;
            $param['raffd'] = $raffd;

            $param['shop_url'] = get_option('siteurl');
            $param["app"]      = 'swn';
            $param["action"]   = "verifyShopExists";
            $param['payload']  = get_option('apmswn_payload', 0);
            $param['plugin_type']   = 'WP';
            $param['plugin_version'] = self::$_plugin_version;
            if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
                $param['plugin_type'] = 'WOO';
            }
            $param["version"]  = 'new';

            $res = array();
            $res = self::_curlResp($param, self::$_api_url);

            if( !empty($res['is_shop']) && $res['is_shop'] == 1)
            {
                update_option('apmswn_admin_email', $email);
                update_option('apmswn_shop_id', $res['id_shop']);
                update_option('apmswn_appid', $res['id_site']);
                update_option('apmswn_payload', $res['pay_load']);
                update_option('apmswn_register', 1);
                update_option('apmswn_plgtyp', $param['plugin_type']);

                $res['cp_reg'] = 0;
                $res['frame_url'] = self::$_callback_url . 'autologin?id_shop=' . $res['id_shop'] . '&admin_email=' . urlencode($email) . '&payload=' . $res['pay_load'];

                // Update WP plugin status
                $param = array('app'=>'swn', 'plugin_type'=>$param['plugin_type'], 'status'=>'activate', 'id_shop'=>$res['id_shop'], 'id_site'=>$res['id_site'], 'payload'=>$res['pay_load']);
                $url = self::$_callback_url . self::$_api_version . 'pluginStatus';
                wp_remote_post($url, array('body' => $param, 'timeout' => 10));

            }
            else if (!empty($res['is_shop']) && $res['is_shop'] == 3)
            {
                update_option( 'apmswn_register', 3 );
                $res['cp_reg'] = 2;
            }
            else if (!empty($res['is_shop']) && $res['is_shop'] == 2)
            {
                $current_user = wp_get_current_user();
                
                $params = array();
                $params['action'] = 'createaccount';

                $params['firstname'] = $current_user->user_firstname;
                $params['lastname'] = $current_user->user_lastname;
                $params['companyname'] = get_bloginfo('name');
                $params['ip'] = $_SERVER['REMOTE_ADDR'];

                $params['notes'] = 'Wordpress';
                $params['app'] = 'swn';

                $params['email'] = $email;
                $params['raffd'] = $raffd;
                $params['url'] = get_option('siteurl');
                $params['name'] = get_bloginfo('name');
                $params['type'] = 'url';
                $params['plugin_type'] = 'WP';
                $params['shop_url'] = get_option('siteurl');
                $params['shop_name'] = get_option('blogname');

                if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
                    $params['plugin_type'] = 'WOO';
                }

                $params['exclusion_period'] = 0;
                $params['payload']  = get_option('apmswn_payload', 0);
                $params['campaign_name'] = 'REWARDS';
                $params['login_url'] = get_option('siteurl');
                $params['plugin_version'] = self::$_plugin_version;

                $res = array();
                $res = self::_curlResp($params, self::$_api_url);

                if ($res['error'] == 0)
                {
                    update_option('apmswn_shop_id', $res['id_shop']);
                    update_option('apmswn_appid', $res['id_site']);
                    update_option('apmswn_payload', $res['pay_load']);
                    update_option('apmswn_admin_email', $params['email']);
                    update_option('apmswn_register', 1);
                    update_option('apmswn_plgtyp', $params['plugin_type']);

                    $res['cp_reg'] = 0;
                    $res['frame_url'] = self::$_callback_url . 'autologin?id_shop=' . $res['id_shop'] . '&admin_email=' . urlencode($params['email']) . '&payload=' . $res['pay_load'];
                }
                else if ($res['error'] == 1)
                    $res['cp_reg'] = 1;
                else if ($res['error'] == 2 || $res['error'] == 3)
                {
                    update_option( 'apmswn_register', 3 );
                    $res['cp_reg'] = 2;
                }
                else
                    $res['cp_reg'] = 4;
            }
            else
            {
                $res['cp_reg'] = 1;
            }


            die(json_encode($res));
        }

        public function apmswn_ajax_apmswncheck_login()
        {
            try
            {
                if(empty($_POST['apmswn_login_email']) || !filter_var($_POST['apmswn_login_email'], FILTER_VALIDATE_EMAIL))
                    die("Please enter valid email");

                if(empty($_POST['apmswn_login_pwd']))
                    die("Please enter password");

                $res = array();
                $params = array();
                $email = sanitize_email($_POST['apmswn_login_email']);
                $adminEmailTemp        = get_option('apmswn_admin_email'); 
                $adminEmail            = empty($adminEmailTemp) ? $email : $adminEmailTemp;
                $params["action"]      = 'login'; 
                $params["app"]         = 'swn';
                $params['email']       = $email;
                $params['admin_email'] = $adminEmail;
                $params['password']    = sanitize_text_field( $_POST['apmswn_login_pwd'] );
                $params['shop_url']    = get_option('siteurl');
                $params['plugin_version'] = self::$_plugin_version;

                $resCurl = self::_curlResp($params, self::$_api_url);

                if ($resCurl['error'] == 0)
                {
                    update_option('apmswn_admin_email', $adminEmail);
                    update_option('apmswn_shop_id', $resCurl['id_shop']);
                    update_option('apmswn_appid', $resCurl['id_site']);
                    update_option('apmswn_payload', $resCurl['pay_load']);
                    update_option('apmswn_register', 1);

                    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))))
                        update_option('apmswn_plgtyp', 'WOO');
                    else
                        update_option('apmswn_plgtyp', 'WP');

                    $res['error']     = 0;
                    $res['frame_url'] = self::$_callback_url . 'autologin?id_shop=' . $resCurl['id_shop'] . '&admin_email=' . urlencode($adminEmail) . '&payload=' . $resCurl['pay_load'];

                    $plugin_type   = 'WP';
                    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
                        $plugin_type = 'WOO';
                    }
                    // Update WP plugin status
                    $param = array('app'=>'swn', 'plugin_type'=>$plugin_type, 'status'=>'activate', 'id_shop'=>$resCurl['id_shop'], 'id_site'=>$resCurl['id_site'], 'payload'=>$resCurl['pay_load']);
                    $url = self::$_callback_url . self::$_api_version . 'pluginStatus';
                    wp_remote_post($url, array('body' => $param, 'timeout' => 10));

                }
                else
                {
                    $res['error']   = 1;
                    $res['message'] = (!empty($resCurl['message'])) ? $resCurl['message'] : "Invalid Email / Password";
                }

            }
            catch (Exception $ex)
            {
                $res['error'] = 1;
                $res['message'] = $ex->getMessage();
            }

            die(json_encode($res));
        }

        public function apmswn_ajax_cpcreate_account(){
            self::callAcctRegister($_POST);
        }
		
        protected static function callAcctRegister($p)
        {
            if (empty($p['apmswn_reg_email_user']))
            {
                $res = array('cp_reg'=>4, 'message'=>'Enter valid email address');
                die(json_encode($res));
            }

            $params['action'] = 'createaccount';
            $params['firstname'] = sanitize_text_field($p['apmswn_reg_firstname']);
            $params['lastname'] = sanitize_text_field($p['apmswn_reg_lastname']);
            $params["raffd"] = sanitize_text_field($p['raffd']);
            $params['companyname'] = get_bloginfo('name');
            $params['email'] = sanitize_email($p['apmswn_reg_email_user']);
            $params["email_user"]   = sanitize_email($p['apmswn_reg_email_user']);
            $params['ip'] = $_SERVER['REMOTE_ADDR'];
            $params['notes'] = 'Wordpress';
            $params['app'] = 'swn';
            $params['type'] = 'url';
            $params['plugin_type'] = 'WP';
            $params['shop_url'] = get_option('siteurl');
            $params['url'] = get_option('siteurl');
            $params['shop_name'] = get_option('blogname');

            $params['campaign_name'] = 'REWARDS';
            $params['exclusion_period'] = 0;
            $params['campaign_only '] = 1;
            $params['plugin_version'] = self::$_plugin_version;

            $params['login_url'] = get_option('siteurl');

            if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
                $params['plugin_type'] = 'WOO';
            }

            $res = array();
            $res = self::_curlResp($params, self::$_api_url);

            if (isset($res['error']) && $res['error'] == 0)
            {
                update_option('apmswn_shop_id', $res['id_shop']);
                update_option('apmswn_admin_email', $params['email']);
                update_option('apmswn_appid', $res['id_site']);
                update_option('apmswn_payload', $res['pay_load']);
                update_option('apmswn_register', 1);
                update_option('apmswn_plgtyp', $params['plugin_type']);

                $res['frame_url'] = self::$_callback_url . 'autologin?id_shop=' . $res['id_shop'] . '&admin_email=' . urlencode($params['email']) . '&payload=' . $res['pay_load'];
                $res['cp_reg'] = 0;
            }
            else if (isset($res['error']) && $res['error'] == 1)
            {
                $res['cp_reg'] = 1;
            }
            else if (isset($res['error']) && $res['error'] == 2)
            {
                update_option( 'apmswn_register', 3 );
                $res['cp_reg'] = 2;
            }
            else
            {
                $res['cp_reg'] = 4;
            }

            die(json_encode($res));
        }
        
        protected static function _curlResp($param,$url)
        {  
            $response   =   wp_remote_post($url,array('body'=> $param,'timeout' => 180));
            
            if (!is_wp_error($response) && !empty($response['body']))
            {
                $respArr = json_decode($response['body'],true);
            }
            else
            {
                $respArr['error'] = 1;
                $respArr['error'] = $response->get_error_message();
            }
         
            return $respArr;
        }
        
        public function apmswn_create_discount()
        {
            global $wpdb;
            
            try
            {
                if(is_admin())
                    return;

                $useragent = empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'];
                if( ! strpos($useragent,'Appsmav'))
                    return;
                    
                //user email verification
                if( ! empty($_POST['verify_user']))
                {
                    $email         = sanitize_email( $_POST['verify_user']);
                    $user          = get_user_by('email', $email );
                    $resp['error'] = 1;
                    $resp['msg']   = 'No User Exist';

                    if(!empty($user))
                    {
                        $resp['error']  =   0;
                        $resp['msg']    =   'User Exist';
                        $resp['name']   =   $user->first_name . ' ' . $user->last_name;
                        $resp['id']     =   $user->ID;
                    }

                    header("Content-Type: application/json; charset=UTF-8");
                    die(json_encode($resp));
                }
                        
                if(empty($_POST['cpn_type']) || empty($_POST['swin_code']))
                    return;

                if( ! isset($_POST['cpn_value']) || ! isset($_POST['free_ship']) || ! isset($_POST['min_order']) || ! isset($_POST['cpn_descp']))
                    throw new Exception('InvalidRequest2');

                if(empty($_POST['id_coupon']) || empty($_POST['hash']))
                    throw new Exception('InvalidRequest');

                if( ! class_exists( 'WC_Integration'))
                    throw new Exception('WooPluginNotFound');

                if( ! in_array('woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option('active_plugins')))) 
                    throw new Exception('PluginDeactivated');

                    // Validate coupon types
                if ( ! in_array( wc_clean( $_POST['cpn_type'] ), array_keys( wc_get_coupon_types())))
                    throw new WC_CLI_Exception( 'woocommerce_cli_invalid_coupon_type', sprintf( __( 'Invalid coupon type - the coupon type must be any of these: %s', 'woocommerce' ), implode( ', ', array_keys( wc_get_coupon_types() ) ) ) );

                $assoc_args = array(
                    'code'                 => sanitize_text_field($_POST['swin_code']),
                    'type'                 => sanitize_text_field($_POST['cpn_type']),
                    'amount'               => empty($_POST['cpn_value']) ? 0 : sanitize_text_field($_POST['cpn_value']),
                    'individual_use'       => true,
                    'usage_limit'          => 1,
                    'usage_limit_per_user' => 1,
                    'enable_free_shipping' => sanitize_text_field($_POST['free_ship']),
                    'minimum_amount'       => sanitize_text_field($_POST['min_order']),
                    'description'          => sanitize_text_field($_POST['cpn_descp'])
                );

                if(!empty($_POST['usage_limit_per_user']))					
                    $assoc_args['usage_limit'] = '';

                if(get_option( 'woocommerce_enable_coupons' ) !== 'yes')	
                    update_option( 'woocommerce_enable_coupons', 'yes' );

                $coupon_code = apply_filters('woocommerce_coupon_code', $assoc_args['code']);

                // Check for duplicate coupon codes.
                $coupon_found = $wpdb->get_var( $wpdb->prepare( "
                        SELECT $wpdb->posts.ID
                        FROM $wpdb->posts
                        WHERE $wpdb->posts.post_type = 'shop_coupon'
                        AND $wpdb->posts.post_status = 'publish'
                        AND $wpdb->posts.post_title = '%s'
                 ", $coupon_code ) );

                if($coupon_found)
                    throw new Exception('DuplicateCoupon');

                $url        =   self::$_callback_url . self::$_api_version . 'wooCpnValidate';
                $app_id     =   get_option('apmswn_appid');
                $payload    =   get_option('apmswn_payload', 0);

                if(empty($app_id) || empty($payload))
                    throw new Exception('IntegrationMissing');

                $param      =   array(
                    'id_coupon'  => sanitize_text_field($_POST['id_coupon']),
                    'grcpn_code' => sanitize_text_field($_POST['grcpn_code']),
                    'hash'       => sanitize_text_field($_POST['hash']),
                    'amount'     => sanitize_text_field($_POST['cpn_value']),
                    'type'       => sanitize_text_field($_POST['cpn_type']),
                    'minimum_amount' => sanitize_text_field($_POST['min_order']),
                    'id_site'    => $app_id,
                    'payload'    => $payload,
                );

                $response = wp_remote_post($url, array('body' => $param, 'timeout' => 10));
                if (is_wp_error( $response ) || !isset($response['body'])) {
                    throw new Exception('Verification Request failed');
                }

                $res = json_decode($response['body'], true);
                if(empty($res) || $res['error'] == 1)
                    throw new Exception('VerificationFailed');

                $defaults = array(
                    'type'                         => 'fixed_cart',
                    'amount'                       => 0,
                    'individual_use'               => false,
                    'product_ids'                  => array(),
                    'exclude_product_ids'          => array(),
                    'usage_limit'                  => '',
                    'usage_limit_per_user'         => '',
                    'limit_usage_to_x_items'       => '',
                    'usage_count'                  => '',
                    'expiry_date'                  => '',
                    'enable_free_shipping'         => false,
                    'product_category_ids'         => array(),
                    'exclude_product_category_ids' => array(),
                    'exclude_sale_items'           => false,
                    'minimum_amount'               => '',
                    'maximum_amount'               => '',
                    'customer_emails'              => array(),
                    'description'                  => ''
                );

                $coupon_data = wp_parse_args( $assoc_args, $defaults );

                $new_coupon = array(
                    'post_title'   => $coupon_code,
                    'post_content' => '',
                    'post_status'  => 'publish',
                    'post_author'  => get_current_user_id(),
                    'post_type'    => 'shop_coupon',
                    'post_excerpt' => $coupon_data['description']
                );

                $id = wp_insert_post( $new_coupon, $wp_error = false );

                if(is_wp_error($id))
                    throw new WC_CLI_Exception( 'woocommerce_cli_cannot_create_coupon', $id->get_error_message());

                // Set coupon meta
                update_post_meta( $id, 'discount_type', $coupon_data['type'] );
                update_post_meta( $id, 'coupon_amount', wc_format_decimal( $coupon_data['amount'] ) );
                update_post_meta( $id, 'individual_use', ( !empty( $coupon_data['individual_use'] ) ) ? 'yes' : 'no' );
                update_post_meta( $id, 'product_ids', implode( ',', array_filter( array_map( 'intval', $coupon_data['product_ids'] ) ) ) );
                update_post_meta( $id, 'exclude_product_ids', implode( ',', array_filter( array_map( 'intval', $coupon_data['exclude_product_ids'] ) ) ) );
                update_post_meta( $id, 'usage_limit', absint( $coupon_data['usage_limit'] ) );
                update_post_meta( $id, 'usage_limit_per_user', absint( $coupon_data['usage_limit_per_user'] ) );
                update_post_meta( $id, 'limit_usage_to_x_items', absint( $coupon_data['limit_usage_to_x_items'] ) );
                update_post_meta( $id, 'usage_count', absint( $coupon_data['usage_count'] ) );

                if('' !== wc_clean( $coupon_data['expiry_date'] ))
                       $coupon_data['expiry_date'] = date( 'Y-m-d', strtotime($coupon_data['expiry_date']));

                update_post_meta( $id, 'expiry_date',  wc_clean( $coupon_data['expiry_date'] ) );
                update_post_meta( $id, 'free_shipping', ( !empty( $coupon_data['enable_free_shipping'] ) ) ? 'yes' : 'no' );
                update_post_meta( $id, 'product_categories', array_filter( array_map( 'intval', $coupon_data['product_category_ids'] ) ) );
                update_post_meta( $id, 'exclude_product_categories', array_filter( array_map( 'intval', $coupon_data['exclude_product_category_ids'] ) ) );
                update_post_meta( $id, 'exclude_sale_items', ( !empty( $coupon_data['exclude_sale_items'] ) ) ? 'yes' : 'no' );
                update_post_meta( $id, 'minimum_amount', wc_format_decimal( $coupon_data['minimum_amount'] ) );
                update_post_meta( $id, 'maximum_amount', wc_format_decimal( $coupon_data['maximum_amount'] ) );
                update_post_meta( $id, 'customer_email', array_filter( array_map( 'sanitize_email', $coupon_data['customer_emails'] ) ) );

                // Custom coupon settings
                if (!empty($_POST['custom_attributes']))
                {
                    $custom_attributes = stripslashes(sanitize_text_field($_POST['custom_attributes']));
                    $custom_attributes = json_decode($custom_attributes, true);
                    if (!empty($custom_attributes) && is_array($custom_attributes))
                    {
                        foreach ($custom_attributes as $prop_name => $prop_value) {
                            update_post_meta($id, $prop_name, wc_clean($prop_value));
                        }
                    }
                }

                $resp['error']	= 0;
                $resp['code'] 	= $coupon_code;
                $resp['id'] 	= $id;
                $resp['msg']	= 'Success';

            }
            catch (Exception $ex)
            {
                $resp['error']  =   1;
                $resp['msg']    =   $ex->getMessage();
            }

            header("Content-Type: application/json; charset=UTF-8");
            die(json_encode($resp));			
        }

        public function register_rest_routes()
        {
            try {
                $route = new Swin_API();
                $route->register_apis();
            } catch (Exception $ex) {

            }
        }

        public function include_files()
        {
            try
            {
                include(sprintf("%s/includes/swin-api.php", SWIN_PLUGIN_BASE_PATH));
            } catch (Exception $ex) {

            }
        }

    function sw_save_post($post_id, $post, $update)
    {
        try
        {
            // Only want to set if this is a old post!
            if (!$update || 'page' !== $post->post_type) {
                return;
            }

            $is_embed_landing_url = get_post_meta($post->ID, 'is_embed_landing_url', true);
            if ($is_embed_landing_url != 1) {
                return;
            }

            $url     = self::$_callback_url . self::$_api_version . 'wooInstallTabChange';
            $app_id  = get_option('apmswn_appid');
            $payload = get_option('apmswn_payload', 0);

            if(empty($app_id) || empty($payload)) {
                throw new Exception('IntegrationMissing');
            }

            $param = array(
                'id_site'   => $app_id,
                'payload'   => $payload,
                'id'        => $post->ID,
                'title'     => $post->post_title,
                'url'       => get_permalink($post->ID),
                'publish'   => $post->post_status == 'publish' ? 1 : 0,
                'is_embed_landing_url' => $is_embed_landing_url,
                'plugin_version' => self::$_plugin_version
            );

            $res = self::_curlResp($param, $url);
            if(empty($res) || $res['error'] == 1) {
                throw new Exception('VerificationFailed');
            }
        }
        catch (Exception $ex)
        {
            $resp['error'] = 1;
            $resp['msg']   = $ex->getMessage();  
        }
    }

    function sw_delete_post($post_id)
    {
        try
        {
            $url     = self::$_callback_url . self::$_api_version . 'wooInstallTabDelete';
            $app_id  = get_option('apmswn_appid');
            $payload = get_option('apmswn_payload', 0);

            if(empty($app_id) || empty($payload)) {
                throw new Exception('IntegrationMissing');
            }

            $is_embed_landing_url = get_post_meta($post_id, 'is_embed_landing_url', true);
            if ($is_embed_landing_url != 1) {
                return;
            }

            update_post_meta($post_id, 'is_embed_landing_url', 0);

            $param = array(
                'id_site' => $app_id,
                'payload' => $payload,
                'id'      => $post_id,
                'url'     => get_permalink($post_id),
                'plugin_version' => self::$_plugin_version
            );            

            $res = self::_curlResp($param, $url);
            if(empty($res) || $res['error'] == 1)
                throw new Exception('VerificationFailed');
        }
        catch (Exception $ex)
        {
            $resp['error'] = 1;
            $resp['msg']   = $ex->getMessage();            
        }
    }

    } // END class
} // END if(!class_exists())

if(class_exists('Appsmav_Scratchwin'))
{
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('Appsmav_Scratchwin', 'activate'));
    register_deactivation_hook(__FILE__, array('Appsmav_Scratchwin', 'deactivate'));

    // instantiate the plugin class
    $apmswn_appsmav = new Appsmav_Scratchwin();
	
    // Add the settings link to the plugins page
    function plugin_appsmavscratchwin_settings_link($links)
    { 
        $settings_link = '<a href="options-general.php?page=appsmavscratchwin">Settings</a>'; 
        array_unshift($links, $settings_link); 
        return $links; 
    }

    $plugin = plugin_basename(__FILE__);

    $apmswn_appsmav->include_files();

    add_filter("plugin_action_links_$plugin", 'plugin_appsmavscratchwin_settings_link');
    add_shortcode('social-appsmavscratchwin-show', array( 'Appsmav_Scratchwin', 'apmswn_show_func' ) );
    add_shortcode('swin-campaign', array( 'Appsmav_Scratchwin', 'apmswn_show_func' ) );
}
