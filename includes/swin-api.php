<?php

if( ! defined('ABSPATH'))
    exit;

class Swin_API extends WP_REST_Controller
{
    public function register_apis()
    {
        register_rest_route('swinwoo/v1', '/getPage', array(
            array(
                'methods'               =>  WP_REST_Server::READABLE,
                'callback'              =>  array($this, 'get_page'),
                'permission_callback'   =>  array($this, 'check_api_permission'),
                'args'                  =>  array()
            )
        ));
        register_rest_route('swinwoo/v1', '/addPage', array(
            array(
                'methods'               =>  WP_REST_Server::CREATABLE,
                'callback'              =>  array($this, 'add_page'),
                'permission_callback'   =>  array($this, 'check_api_permission'),
                'args'                  =>  array()
            )
        ));
        register_rest_route('swinwoo/v1', '/editPage', array(
            array(
                'methods'               =>  WP_REST_Server::EDITABLE,
                'callback'              =>  array($this, 'edit_page'),
                'permission_callback'   =>  array($this, 'check_api_permission'),
                'args'                  =>  array()
            )
        ));
        register_rest_route('swinwoo/v1', '/deletePage', array(
            array(
                'methods'               =>  WP_REST_Server::EDITABLE,
                'callback'              =>  array($this, 'delete_page'),
                'permission_callback'   =>  array($this, 'check_api_permission'),
                'args'                  =>  array()
            )
        ));

        register_rest_route('swinwoo/v1', '/getversion', array(
            'methods'               => 'POST',
            'callback'              => array($this, 'getversion'),
            'permission_callback'   => array($this, 'check_api_permission'),
            'args'                  => array()
        ));
        register_rest_route('swinwoo/v1', '/resetInstallation', array(
            array(
                'methods'               =>  'POST',
                'callback'              =>  array($this, 'reset_installation'),
                'permission_callback'   =>  array($this, 'check_api_permission_lite'),
                'args'                  =>  array()
            )
        ));
        register_rest_route('swinwoo/v1', '/verifyCouponCode', array(
            array(
                'methods'               =>  'POST',
                'callback'              =>  array($this, 'verify_coupon_code'),
                'permission_callback'   =>  array($this, 'check_api_permission'),
                'args'                  =>  array()
            )
        ));

         register_rest_route('swinwoo/v1', '/deleteCouponCode', array(
            array(
                'methods'               =>  'POST',
                'callback'              =>  array($this, 'delete_coupon_code'),
                'permission_callback'   =>  array($this, 'check_api_permission'),
                'args'                  =>  array()
            )
        ));
         
        register_rest_route('swinwoo/v1', '/createcustomer', array(
            'methods'                   => 'POST',
            'callback'                  =>  array($this, 'createcustomer'),
            'permission_callback'       =>  array($this, 'check_api_permission'),
            'args'                      =>  array()
        ));
    }

    public function check_api_permission($request)
    {
        if (strpos($request->get_header('user_agent'), 'Appsmav') === false) {
            return false;
        } else {
            $payload = get_option('apmswn_payload', 0);
            $post_payload = sanitize_text_field($_POST['payload']);

            if (empty($_POST['payload']) || $payload != $post_payload) {
                return false;
            }
        }
        return true;
    }

    public function check_api_permission_lite($request)
    {
        if (strpos($request->get_header('user_agent'), 'Appsmav') === false) {
            return false;
        }
        return true;
    }

    public function getversion($request)
    {
        try {
            $version = '';
            if (class_exists('Appsmav_Scratchwin')) {
                $version = Appsmav_Scratchwin::$_plugin_version;
            }

            $data = array('error' => 0, 'plugin_version' => $version);
        } catch (Exception $e) {
            $data['error'] = 1;
            $data['msg'] = $e->getMessage();
        }
        return new WP_REST_Response($data, 200);
    }

    public function get_page($request)
    {
        $data = array('error' => 0);

        try
        {
            if (empty($_POST['id'])) {
                throw new Exception('Invalid Page');
            }

            $id_post = sanitize_text_field($_POST['id']);
            if (!get_post_status($id_post)) {
                throw new Exception('Invalid Page');
            }

            $page = get_post($id_post);
            if(is_wp_error($page)) {
                throw new Exception('cannot_update_page'. $page->get_error_message());
            }

            $data['error']	= 0;
            $data['id'] 	= $page->ID;
            $data['url'] 	= get_permalink($id);
            $data['is_embed_landing_url'] = get_post_meta(get_the_ID(), 'is_embed_landing_url');
            $data['msg']	= 'Success';
        }
        catch(Exception $e)
        {
            $data['error']          =   1;
            $data['error_message']  =   $e->getMessage();
        }

        return new WP_REST_Response($data, 200);
    }

    public function add_page($request)
    {
        $data   =   array('error' => 0);

        try
        {
            if (empty($_POST['title'])) {
                throw new Exception('Invalid Title');
            }

            if (empty($_POST['content'])) {
                throw new Exception('Invalid Content');
            }

            $new_page = array(
                'post_title'   => sanitize_text_field($_POST['title']),
                'post_content' => sanitize_text_field($_POST['content']),
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'meta_input'   => array(
                    'is_embed_landing_url' => 1
                )
            );

            $id = wp_insert_post( $new_page, $wp_error = false );

            if(is_wp_error($id)) {
                throw new Exception('cannot_create_page'. $id->get_error_message());
            }

            $data['error'] = 0;
            $data['id']    = $id;
            $data['url']   = get_permalink($id);
            $data['msg']   = 'Success';
        }
        catch(Exception $e)
        {
            $data['error']         = 1;
            $data['error_message'] = $e->getMessage();
        }

        return new WP_REST_Response($data, 200);
    }

    public function edit_page($request)
    {
        $data = array('error' => 0);

        try
        {
            if (isset($_POST['title']) && empty($_POST['title']) && !isset($_POST['publish'])) {
                throw new Exception('Invalid Title');
            }

            if (empty($_POST['id'])) {
                throw new Exception('Invalid Page');
            }

            $params['ID'] = sanitize_text_field($_POST['id']);
            if (!get_post_status($params['ID'])) {
                throw new Exception('Invalid Page');
            }

            if (isset($_POST['publish']))
            {
                $publish_status = sanitize_text_field($_POST['publish']);
                $params['post_status'] = ($publish_status == 1) ? 'publish' : 'draft';
                update_post_meta($params['ID'], 'is_embed_landing_url', $publish_status);
            }
            else
            {
                $params['post_title'] = sanitize_text_field($_POST['title']);
            }

            $id = wp_update_post( $params, $wp_error = true );

            if(is_wp_error($id))
                throw new Exception('cannot_update_page'. $id->get_error_message());

            $page_info = get_post($id);

            $data['error'] = 0;
            $data['id']    = $page_info->ID;
            $data['title'] = $page_info->post_title;
            $data['url']   = get_permalink($page_info->ID);
            $data['msg']   = 'Success';
        }
        catch(Exception $e)
        {
            $data['error']         = 1;
            $data['error_message'] = $e->getMessage();
        }

        return new WP_REST_Response($data, 200);
    }

    public function delete_page($request)
    {
        $data   =   array('error' => 0);

        try
        {
            if (empty($_POST['id'])) {
                throw new Exception('Invalid Page');
            }

            $id_page = sanitize_text_field($_POST['id']);
            if (!get_post_status($id_page)) {
                throw new Exception('Invalid Page');
            }

            if(!wp_delete_post($id_page, true)) {
                throw new Exception('cannot_delete_page');
            }

            $data['error'] = 0;
            $data['msg']   = 'Success';
        }
        catch(Exception $e)
        {
            $data['error']         = 1;
            $data['error_message'] = $e->getMessage();
        }

        return new WP_REST_Response($data, 200);
    }

    public function reset_installation($request)
    {
        try
        {
            $data['error'] = 0;

            // Reset flags to show login screen
            update_option('apmswn_register', 3);

            $data['msg'] = 'yes';
        }
        catch(Exception $e) {
            $data['error'] = 1;
            $data['msg']   = $e->getMessage();
        }

        return new WP_REST_Response($data, 200);
    }

    public function verify_coupon_code($request)
    {
        try
        {
            $data['error'] = 0;

            if (empty($_POST['coupon_code'])) {
                throw new Exception('Coupon code cannot be empty. Please check');
            }

            $coupon_code = sanitize_text_field($_POST['coupon_code']);

            $coupon = new WC_Coupon($coupon_code);
            if (!empty($coupon->id))
            {
                $data['msg'] = 'Yes';
                $data['coupon'] = json_decode($coupon, true);
            }
            else
                $data['msg'] = 'No';
        }
        catch(Exception $e) {
            $data['error'] = 1;
            $data['msg']   = $e->getMessage();
        }

        $result = new WP_REST_Response($data, 200);
        // Set headers.
        $result->set_headers(array('Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0'));
        return $result;
    }

    public function delete_coupon_code($request)
    {
        try
        {
            $data['error'] = 0;

            if (empty($_POST['coupon_code'])) {
                throw new Exception('Invalid coupon code');
            }

            $coupon_code = sanitize_text_field($_POST['coupon_code']);
            $coupon = new WC_Coupon($coupon_code);
            if (!empty($coupon->id))
            {
                $validate_usage = empty($_POST['validate_usage']) ? 0 : $_POST['validate_usage'];
                if(!empty($validate_usage) && (!isset($coupon->usage_count) || $coupon->usage_count != 0))
                {
                    $data['id'] = $coupon->id;
                    $data['usage_count'] = $coupon->usage_count;
                    throw new Exception('Coupon code already used');
                }

                $post_id = wp_delete_post($coupon->id, TRUE);
                if ( is_wp_error( $post_id ) ) {
                    throw new Exception( $post_id->get_error_message());
                }

                $data['msg'] = 'Successfully Deleted';
            }
            else {
                $data['msg'] = 'Coupon code not found.';
            }

        }
        catch(Exception $e) {
            $data['error'] = 1;
            $data['msg']   = $e->getMessage();
        }

        $result = new WP_REST_Response($data, 200);
        // Set headers.
        $result->set_headers(array('Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0'));
        return $result;
    }

    public function createcustomer() 
    {
        $data = array();
        try {
            $email = sanitize_text_field(trim($_POST['email']));
            $user_name = sanitize_text_field(trim($_POST['user_name']));
            $first_name = sanitize_text_field(trim($_POST['first_name']));
            $last_name = sanitize_text_field(trim($_POST['last_name']));

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address");
            }

            if (empty($user_name)) {
                throw new Exception("Invalid user name");
            }
			
			if (empty($first_name)) {
                throw new Exception("Invalid first name");
            }

            $user = get_user_by('email', $email);
            if (!empty($user)) {
                throw new Exception("Email id already exists");
            }

            $user = get_user_by('login', $user_name);
            if (!empty($user)) {
                $user_name = $email;
                $user = get_user_by('login', $user_name);
                if (!empty($user)) {
                    throw new Exception("Username already exists");
                }
            }

            $user_details = array(
                'user_email' => $email,
                'user_login' => $user_name,
                'first_name' => $first_name,
                'last_name' => $last_name
            );

            $user_id = wp_insert_user($user_details);
            if (is_wp_error($user_id)) {
                throw new Exception($user_id->get_error_message());
            }

            $user = get_user_by('id', $user_id);
            if (
                !empty($user) && !empty($user->data) && !empty($user->data->user_email) 
                && $user->data->user_email == $email
            ) {
                $data = array(
                    'error' => 0,
                    'id' => $user_id,
                );
            } else {
                throw new Exception("User creation failed");
            }
        } catch (Exception $e) {
            $data['error'] = 1;
            $data['msg'] = $e->getMessage();
        }

        $data['plugin_version'] = Appsmav_Scratchwin::$_plugin_version;

        $result = new WP_REST_Response($data, 200);

        // Set headers.
        $result->set_headers(array('Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0'));
        return $result;
    }
}