<?php

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 14/06/2017
 * Time: 21:14
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class brozzmeCUPSettings
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_pages'), 110);
        add_action('admin_init', array($this, 'settings_fields'));

        //autocomplete
        add_action('admin_init', array($this, '_autocomplete_init'));
        add_action( 'admin_print_footer_scripts-users_page_brozzme-change-username', array($this, 'select2jquery_inline'), 9999 );


    }

    /**
     *
     */
    public function _autocomplete_init(){
        add_action('wp_ajax_get_listing_names', array($this, 'autocomplete_ajax_users_listings') );
    }

    /**
     *
     */
    public function add_admin_pages(){

        add_submenu_page(BFSL_PLUGINS_DEV_GROUPE_ID,
            __('Change username', B7ECU_TEXT_DOMAIN),
            __('Change username', B7ECU_TEXT_DOMAIN),
            'manage_options',
            B7ECU_SETTINGS_SLUG,
            array($this, 'settings_page')
        );

        add_submenu_page('users.php',
            __('Change username', B7ECU_TEXT_DOMAIN),
            __('Change username', B7ECU_TEXT_DOMAIN),
            'manage_options',
            B7ECU_SETTINGS_SLUG,
            array($this, 'settings_page')
        );
    }

    /**
     *
     */
    public function settings_page(){

        global $wpdb;

        $bprefix_Message = '';

        if((isset($_POST['b7e_cu_hidden'])) && $_POST['b7e_cu_hidden'] == 'Y' && (isset($_POST['Submit']) && trim($_POST['Submit'])==__('Change username', B7ECU_TEXT_DOMAIN ))) {

            $old_username = $_POST['b7e_cu_old_username'];
            update_option('b7e_cu_old_username', $old_username);

            $new_username = $_POST['b7e_cu_new'];
            update_option('b7e_cu_new', $new_username);



            if($_POST['b7e_cu_new'] =='' || strlen($_POST['b7e_cu_new']) < 2 )
            {
                $bprefix_Message .= __('Please provide a proper username.', B7ECU_TEXT_DOMAIN);
            }
            else{
                $result = $this->username_changer_process($old_username, $new_username);
                    // check for errors
                    if (!empty($result))
                    {
                        $bprefix_Message .= '<span class="dashicons dashicons-yes" style="color:green;"></span>&nbsp;' . __('The username has been successfully changed to ', B7ECU_TEXT_DOMAIN) .' <b>'.$new_username.'</b> !<br/>';

                    }// End if username successfully renamed
                    else {
                        $bprefix_Message .= '<span class="dashicons dashicons-warning" style="color:red;"></span>' . __('An error has occurred and username could not be updated!', B7ECU_TEXT_DOMAIN);
                    }
                    $_POST['b7e_cu_hidden'] = 'n';

                    $new_updated_username = $new_username;
            }

        } else {

        }
        ?>
        <div class="wrap">
            <div id="brozzme-header" style="">
                <img src="https://ps.w.org/brozzme-add-plugins-thumbnails/assets/icon-128x128.png" />
                <h2 style="text-transform: uppercase;top: 10px;position: relative;">Brozzme Change Username</h2>
            </div>
            <?php
            $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general_settings';
            ?>
            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo B7ECU_SETTINGS_SLUG;?>&tab=general_settings" class="nav-tab <?php echo $active_tab == 'general_settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Change Username', B7ECU_TEXT_DOMAIN );?></a>
                <a href="admin.php?page=brozzme-plugins" class="nav-tab <?php echo $active_tab == 'brozzme' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Brozzme', B7ECU_TEXT_DOMAIN );?></a>

            </h2>

            <?php if($active_tab == 'brozzme'){
            }
            else{
               ?>
                <form id="b7e_cu_form" name="b7e_cu_form" method="post" action="" >
                <input type="hidden" name="b7e_cu_hidden" value="Y">
                <div id="cdtp" class="brozzme-info-cu " style="padding-left:10px;">
                    <h3 class="hndle" style="cursor: default;"><span class="dashicons dashicons-groups"></span>  <span><?php _e('Improve security', B7ECU_TEXT_DOMAIN);?></span></h3>
                <div class="inside">
                    <div class="success">
                        <?php echo $bprefix_Message;?>
                    </div><!-- success div -->
                    <?php if(isset( $_POST['b7e_cu_hidden'] ) && $_POST['b7e_cu_hidden'] == 'Y') { ?>
                        <div class="updated">
                            <p><strong><?php _e('Options saved.' ); ?></strong></p>
                        </div><!-- updated div -->
                    <?php } ?>
                    <div class="cdp-container" >

                        <?php
                        $all_users = $this->_all_users();
                        $uggly_count = 0;
                        foreach ($all_users as $user){
                            $security_check = $this->_detect_uggly_username($user->user_login);
                            if($security_check === true){
                                $uggly_logins[] = array($user->user_login, $user->user_email);
                                $uggly_count += 1;
                            }
                        }
                        if($uggly_count != 0){
                        ?>
                        <div style="border:1px solid red;color:red;font-weight: bold;padding :5px "> <span class="dashicons dashicons-shield"></span>&nbsp;<?php echo $uggly_count . ' ' .__('user(s) must be rename as soon as possible', B7ECU_TEXT_DOMAIN);?></br>
                           <ul>
                            <?php foreach ($uggly_logins as $uggly_login) {
                                echo '<li>'. __('Username : ', B7ECU_TEXT_DOMAIN) .$uggly_login[0] . ' ('. $uggly_login[1].')</li>';
                            }?></ul>
                        </div>
                            <?php } ?>
                        <div style="line-height: 3.4em;">

                        <label for="b7e_cu_old_username" class="label01" > <span class="ttl02"><?php _e('Select Username: ', B7ECU_TEXT_DOMAIN ); ?>
                                <span class="required">*</span></span></label>
                            <?php echo $this->_autocomplete_user_input(); ?>

                        <label for="b7e_cu_new" class="label01"> <span class="ttl02"><?php _e('New Username: ', B7ECU_TEXT_DOMAIN ); ?>
                                <span class="required">*</span></span>
                            <input type="text" name="b7e_cu_new" value="" size="20" id="b7e_cu_new" required>
                            <input type="hidden" name="" value="Y"/>
                        </label>
                        <p class="margin-top:10px"><?php _e('<b>Allowed characters:</b> all latin alphanumeric as well as the <strong>_</strong> (underscore).', B7ECU_TEXT_DOMAIN);?></p>
                        <p class="submit">
                            <input type="submit" name="Submit" class="button button-primary" value="<?php _e('Change username', B7ECU_TEXT_DOMAIN ); ?>" />
                        </p></div><!-- container div -->
                    </div>
                </div><!-- inside div -->
                </div><!-- postbox div -->
                </form>
                <script>
                    (function( $ ) {
                        $(function() {

                            $('.b7e_cu_old_username').change(function() {

                                $('#b7e_cu_new').val($(this).find("option:selected").attr('value'));
                            });
                        });
                    })( jQuery );
                </script>
                </div>
                <?php
            }
            ?>
            <?php
    }

    /**
     *
     */
    public function settings_fields(){

    }

    /**
     *
     */
    public function _autocomplete_user_input(){
        $users = $this->_all_users();
        ?>
        <div class="b7ecu-users">
            <label for="select2_b7ecu_users"><?php _e( 'List of users:', B7ECU_TEXT_DOMAIN ); ?></label><br/>
            <select id="select2_b7ecu_users" name="b7e_cu_old_username" class="b7e_cu_old_username" >
                <?php
                if ( $users ) {

                    echo '<option class="hidden-connect" value="">'.__('Type your search', B7ECU_TEXT_DOMAIN).'</option>'."\n";
                    foreach ( $users as $user ) {

                        $the_user = new WP_User( $user->ID );
                        if( $the_user->roles[0] === 'administrator' ) {
                            $class = "admin";
                            echo '<option class="' . $class . '" value="'.$user->user_login.'">' . $user->user_login . ' (' . $the_user->roles[0] . ')</option>'."\n";
                        } elseif( $the_user->roles[0] === 'super_admin' ) {
                            $class = "admin";
                            echo '<option class="' . $class . '" value="'.$user->user_login.'">' . $user->user_login . ' (' . $the_user->roles[0] . ')</option>'."\n";
                        } else {
                            $class = "hidden-connect";
                            echo '<option class="' . $class . '" value="'.$user->user_login.'">' . $user->user_login . ' (' . $the_user->roles[0] . ')</option>'."\n";
                        }

                    }
                } ?>
            </select>
        </div>
        <?php
    }

    /**
     * @return array
     */
    public function _all_users(){
        global $wpdb;
        if ( function_exists( 'get_users' ) ) {
            $all_users = get_users();
        } else {
            $usersID = $wpdb->get_col('SELECT ID, user_login FROM ' . $wpdb->users . ' ORDER BY ID ASC');
            foreach ($usersID as $uid) {
                $all_users[] = get_userdata($uid);
            }
        }

        return $all_users;
    }

    /**
     * @param $user_login
     * @return bool
     */
    public function _detect_uggly_username($user_login){

        $user_login = strtolower($user_login);

        if(strpos($user_login, 'admin') !== false){
            return true;
        }
    }

    /**
     * Process a username change
     *
     * @param       string $old_username The old (current) username
     * @param       string $new_username The new username
     * @return      bool $return Whether or not we completed successfully
     */
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
                    $qnn = $wpdb->prepare( "UPDATE $wpdb->users SET user_nicename = %s WHERE user_login = %s AND user_nicename = %s", $new_username, $new_username, $old_username );
                    $wpdb->query( $qnn );

                    // Update display_name
                    $qdn = $wpdb->prepare( "UPDATE $wpdb->users SET display_name = %s WHERE user_login = %s AND display_name = %s", $new_username, $new_username, $old_username );
                    $wpdb->query( $qdn );

                    // Update nickname
                    $nickname = get_user_meta( $user_id, 'nickname', true );
                    if ( $nickname == $old_username ) {
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

    /**
     *
     */
    function select2jquery_inline() {
        ?>
        <style type="text/css">
            .select2-container {margin: 0 2px 0 2px;}
            .tablenav.top #doaction, #doaction2, #post-query-submit {margin: 0px 4px 0 4px;}
            .b7ecu-users span.select2.select2-container.select2-container--default {
                width: 50% !important;}
        </style>
        <script type='text/javascript'>
            jQuery(document).ready(function ($) {
                $('#select2_b7ecu_users').select2({
                    ajax: {
                        url: ajaxurl, // AJAX URL is predefined in WordPress admin
                        dataType: 'json',
                        delay: 250, // delay in ms while typing when to perform a AJAX search
                        data: function (params) {
                            return {
                                q: params.term, // search query
                                action: 'get_listing_names' // AJAX action for admin-ajax.php
                            };
                        },
                        processResults: function (data) {
                            //console.log(data);
                            var options = [];
                            if (data) {

                                // data is the array of arrays, and each of them contains ID and the Label of the option
                                $.each(data, function (index, text) { // do not forget that "index" is just auto incremented value
                                    options.push({id: text['user_login'], text: text['text']});
                                });
                            }
                            return {
                                results: options
                            };
                        },
                        cache: true
                    },
                    placeholder: '<?php _e( 'Type your search', B7ECU_TEXT_DOMAIN);?>',
                    allowClear: true,
                    language: {
                    // You can find all of the options in the language files provided in the
                    // build. They all must be functions that return the string that should be
                    // displayed.
                    inputTooShort: function () {
                        return "<?php _e('Please enter 2 or more characters', B7ECU_TEXT_DOMAIN);?>";
                    }
                },
                    minimumInputLength: 2 // the minimum of symbols to input before perform a search
                });
            });
        </script>
        <?php
    }

    /**
     *
     */
    public function autocomplete_ajax_users_listings(){

        $results    = array();
        $user_query = new WP_User_Query(
            array(
                'search'         => '*' . sanitize_text_field( $_GET['q'] ) . '*',
                'search_columns' => array( 'user_login', 'user_email', 'user_nicename', 'ID' ),
                'number'         => 10,
                'exclude'        => array( get_current_user_id() )
            )
        );

        $users = $user_query->get_results();
        foreach ( $users as $user ) :
            $results[] = array(
                'user_login'   => $user->user_login,
                'text' => $user->user_login . ' (' . $user->user_email . ')'
            );
        endforeach;

        echo json_encode( $results );
        die;
    }
}