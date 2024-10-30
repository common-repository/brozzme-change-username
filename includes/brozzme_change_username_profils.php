<?php
/**
 * Created by PhpStorm.
 * User: benoti
 * Date: 21/11/2017
 * Time: 17:23
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class brozzme_change_username_profils
{
    public function __construct()
    {
        add_action( 'admin_enqueue_scripts', array($this, 'username_changer_admin_scripts') );
        add_action( 'wp_ajax_change_username',  array($this, 'username_changer_ajax_username_change') );
    }

    function username_changer_admin_scripts() {
        // Use minified libraries if SCRIPT_DEBUG is turned off
        $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

        $minimum_length = 3;
        $screen         = get_current_screen();

        wp_enqueue_style( B7ECU_TEXT_DOMAIN, B7ECU_DIR_URL . '/css/bcu_admin.css', array(), B7ECU_VERSION );
        wp_enqueue_script( B7ECU_TEXT_DOMAIN, B7ECU_DIR_URL . '/js/bcu_admin.js', array( 'jquery' ), B7ECU_VERSION );
        wp_localize_script( B7ECU_TEXT_DOMAIN, 'username_changer_vars', array(
            'nonce'                => wp_create_nonce( 'change_username' ),
            'ajaxurl'              => admin_url( 'admin-ajax.php' ),
            'change_button_label'  => __( 'Change Username', B7ECU_TEXT_DOMAIN ) ,
            'save_button_label'    => __( 'Save Username', B7ECU_TEXT_DOMAIN ) ,
            'cancel_button_label'  => __( 'Cancel', B7ECU_TEXT_DOMAIN ) ,
            'please_wait_message'  => __( 'Please wait...', B7ECU_TEXT_DOMAIN ),
            'error_short_username' => __( 'Username is too short, the minimum length is {minlength} characters.', B7ECU_TEXT_DOMAIN ),
            'current_screen'       => $screen->id,
            'can_change_username'  => username_changer_can_change_own_username(),
            'minimum_length'       => $minimum_length
        ) );
    }

    /**
     * Process username change requests through AJAX
     *
     * @return      void
     */
    public function username_changer_ajax_username_change() {
        $response = array(
            'success'   => false,
            'new_nonce' => wp_create_nonce( 'change_username' )
        );

        // Validate nonce
        check_ajax_referer( 'change_username', 'security' );

        // Validate request
        if ( empty( $_POST['new_username'] ) || empty( $_POST['old_username'] ) ) {
            $response['message'] = __( 'Invalid request.', B7ECU_TEXT_DOMAIN );
            wp_send_json( $response );
        }

        $old_username     = trim( strip_tags( $_POST['old_username'] ) );
        $old_username_tag = esc_attr( $_POST['old_username'] );
        $new_username     = trim( strip_tags( $_POST['new_username'] ) );
        $new_username_tag = esc_attr( $_POST['new_username'] );
        $current_user     = wp_get_current_user();
        $current_username = $current_user->user_login;

        // Make sure the user can change this username
        if ( ! current_user_can( 'edit_users' ) ) {
            if ( $current_username != $old_username || ! username_changer_can_change_own_username() ) {
                $response['message'] = __( 'You do not have the correct permissions to change this username.', B7ECU_TEXT_DOMAIN );
                wp_send_json( $response );
            }
        }

        // Validate new username
        if ( ! validate_username( $new_username ) ) {
            $response['message'] = __( 'The username contains invalid characters. Please enter a valid username.', B7ECU_TEXT_DOMAIN );
            wp_send_json( $response );
        }

        // Make sure new username isn't on the illegal logins list
        $illegal_user_logins = array_map( 'strtolower', (array) apply_filters( 'illegal_user_logins', array() ) );
        if ( in_array( $new_username, $illegal_user_logins ) ) {
            $response['message'] = __( 'Sorry, that username is not allowed.', B7ECU_TEXT_DOMAIN );
            wp_send_json( $response );
        }

        // Make sure the new username isn't already taken
        if ( username_exists( $new_username ) ) {
            $response['message'] = sprintf(__( 'The username %s is already in use. Please try again.', B7ECU_TEXT_DOMAIN ), $new_username_tag, $old_username_tag );
            wp_send_json( $response );
        }

        // Change the username
        $success = $this->username_changer_process( $old_username, $new_username );

        if ( $success ) {
            $response['success'] = true;
            $response['message'] = __( 'Username successfully changed.', B7ECU_TEXT_DOMAIN );


        } else {
            $response['message'] = __( 'An unknown error occurred.', B7ECU_TEXT_DOMAIN );
        }

        wp_send_json( $response );
    }

    public function username_changer_process( $old_username, $new_username ) {
        global $wpdb;

        $return = false;

        // One last sanity check to ensure the user exists
        $user_id = username_exists( $old_username );

        if ( $user_id ) {

            // Update username!
            $q = $wpdb->prepare( "UPDATE $wpdb->users SET user_login = %s WHERE user_login = %s", $new_username, $old_username );

            if ( false !== $wpdb->query( $q ) ) {
                // Update user_nicename
                $qnn = $wpdb->prepare( "UPDATE $wpdb->users SET user_nicename = %s WHERE user_login = %s", $new_username, $new_username, $old_username );
                $wpdb->query( $qnn );

                // Update display_name
                $qdn = $wpdb->prepare( "UPDATE $wpdb->users SET display_name = %s WHERE user_login = %s", $new_username, $new_username );
                $wpdb->query( $qdn );

                // Update nickname
                $nickname = get_user_meta( $user_id, 'nickname', true );
                if ( $nickname ) {
                    update_user_meta( $user_id, 'nickname', $new_username );
                }

                // If the user is a Super Admin, update their permissions
                if ( is_multisite() && is_super_admin( $user_id ) ) {
                    grant_super_admin( $user_id );
                }

                $return = true;
            }

            return $return;
        }

    }

    public function _sanitize_username($user_name){

        $user_name = str_replace('_', ' ', $user_name);
        $user_name = ucfirst($user_name);
        return $user_name;
    }

}


new brozzme_change_username_profils();




/**
 * Get an array of user roles
 *
 * @return      array $roles The available user roles
 */
if(!function_exists('username_changer_get_user_roles')){
    function username_changer_get_user_roles() {
        global $wp_roles;

        $roles = $wp_roles->get_names();

        // Administrator can always edit
        unset( $roles['administrator'] );

        return apply_filters( 'username_changer_user_roles', $roles );
    }
}



/**
 * Check if a user can change a given username
 *
 * @return      bool $allowed Whether or not this user can change their username
 */
if(!function_exists('username_changer_can_change_own_username')){
    function username_changer_can_change_own_username() {
        $allowed = false;

        if ( is_user_logged_in() ) {
            $allowed_roles = get_option( 'allowed_roles', array() );
            $user_data     = wp_get_current_user();
            $user_roles    = $user_data->roles;

            if ( in_array( 'administrator', $user_roles ) ) {
                $allowed = true;
            } elseif ( is_array( $user_roles ) ) {
                foreach ( $user_roles as $user_role => $role_name ) {
                    if ( in_array( $user_role, $allowed_roles ) ) {
                        $allowed = true;
                    }
                }
            }
        }

        return apply_filters( 'username_changer_can_change_own_username', $allowed );
    }
}
