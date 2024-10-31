<?php

// Check that we should be doing this
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit; // Exit if accessed directly
}


$api_url = 'https://win.appsmav.com/api/v1/';
try
{
    // Delete stored informations
    $id_shop = get_option('apmswn_shop_id', 0);
    $id_site = get_option('apmswn_appid', 0);
    $payload = get_option('apmswn_payload', 0 );
    $plugin_type = 'WP';
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        $plugin_type = 'WOO';
    }

    delete_option('apmswn_shop_id');
    delete_option('apmswn_appid');
    delete_option('apmswn_payload');
    delete_option('apmswn_admin_email');
    
    $param = array('app'=>'swn', 'plugin_type'=>$plugin_type, 'status'=>'delete', 'id_shop'=>$id_shop, 'id_site'=>$id_site, 'payload'=>$payload);
    $url = $api_url . 'pluginStatus';

    wp_remote_post($url, array('body' => $param, 'timeout' => 10));

}
catch(Exception $e){}



