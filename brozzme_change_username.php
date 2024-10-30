<?php
/*
Plugin Name: Brozzme Change Username
Plugin URI: https://brozzme.com/change-user-name
Description: Easily change a WordPress username, save time, increase security.
Version: 1.0
Author: Benoti
Author URI: https://brozzme.com
*/

class brozzme_change_username{

    public function __construct()
    {
        // Define plugin constants
        $this->basename = plugin_basename(__FILE__);
        $this->directory_path = plugin_dir_path(__FILE__);
        $this->directory_url = plugins_url(dirname($this->basename));

        // group menu ID
        $this->plugin_dev_group = 'Brozzme';
        $this->plugin_dev_group_id = 'brozzme-plugins';

        // plugin info
        $this->plugin_name = 'Brozzme Change username';
        $this->plugin_slug = 'brozzme-change-username';
        $this->settings_page_slug = 'brozzme-change-username';
        $this->plugin_version = '1.0';
        $this->plugin_txt_domain = 'brozzme-change-username';

        $this->_define_constants();

        // Run our activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook( __FILE__, array($this, 'deactivate') );
        register_uninstall_hook(    __DIR__ .'/uninstall.php', 'brozzme_change_username_plugin_uninstall' );

        /* init */
        add_action( 'admin_enqueue_scripts', array( $this, '_add_settings_styles') );

        $this->_init();

    }

    /**
     *
     */
    public function _define_constants(){
        defined('BFSL_PLUGINS_DEV_GROUPE')    or define('BFSL_PLUGINS_DEV_GROUPE', $this->plugin_dev_group);
        defined('BFSL_PLUGINS_DEV_GROUPE_ID') or define('BFSL_PLUGINS_DEV_GROUPE_ID', $this->plugin_dev_group_id);
        defined('BFSL_PLUGINS_URL') or define('BFSL_PLUGINS_URL', $this->directory_url);
        defined('BFSL_PLUGINS_SLUG') or define('BFSL_PLUGINS_SLUG', $this->plugin_slug);

        defined('B7ECU')    or define('B7ECU', $this->plugin_name);
        defined('B7ECU_BASENAME')    or define('B7ECU_BASENAME', $this->basename);
        defined('B7ECU_DIR')    or define('B7ECU_DIR', $this->directory_path);
        defined('B7ECU_DIR_URL')    or define('B7ECU_DIR_URL', $this->directory_url);
        defined('B7ECU_SETTINGS_SLUG')  or define('B7ECU_SETTINGS_SLUG', $this->settings_page_slug);
        defined('B7ECU_PLUGIN_SLUG')  or define('B7ECU_PLUGIN_SLUG', $this->plugin_slug);
        defined('B7ECU_VERSION')        or define('B7ECU_VERSION', $this->plugin_version);
        defined('B7ECU_TEXT_DOMAIN')    or define('B7ECU_TEXT_DOMAIN', $this->plugin_txt_domain);
    }

    /**
     *
     */
    public function _init(){

        load_plugin_textdomain($this->plugin_txt_domain, false, $this->plugin_slug . '/languages');

        add_action('admin_enqueue_scripts', array($this, '_enqueue_ressources') );
        // Add Menu
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));

        $this->_admin_page();

    }



    /**
     * @param $hook
     */
    public function _enqueue_ressources($hook){
            if($hook != 'users_page_brozzme-change-username' || $hook == 'brozzme_page_brozzme-change-username') {
                return;
            }
            wp_register_script('jquery.validate',plugins_url('js/jquery.validate.min.js',__FILE__),array('jquery'), false, false);
            wp_enqueue_script('jquery.validate');
            wp_register_script('util',plugins_url('js/util.js',__FILE__),array('jquery'), false, false);
            wp_enqueue_script('util');

            wp_register_style( 'select2css', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css', false, '1.0', 'all' );
            wp_enqueue_style( 'select2css' );
            wp_register_script('jsselect2', plugins_url('js/select2.min.js',__FILE__),array('jquery'), '1.0', 'all');
            wp_enqueue_script( 'jsselect2' );
    }

    /**
     *
     */
    public function _admin_page(){

        if (!class_exists('brozzme_plugins_page')){
            include_once ($this->directory_path . 'includes/brozzme_plugins_page.php');
        }
        include_once $this->directory_path . 'includes/brozzme_change_username_profils.php';

       // include_once $this->directory_path . 'includes/class.template-tags.php';
        include_once $this->directory_path . 'includes/brozzmeCUSettings.php';

        new brozzmeCUPSettings();

    }

    /**
     * @param $hook
     */
    public function _add_settings_styles($hook){
        if($hook == 'toplevel_page_' . $this->plugin_dev_group_id || $hook == 'users_page_brozzme-change-username'){
            wp_enqueue_style( $this->plugin_txt_domain, plugin_dir_url( __FILE__ ) . 'css/brozzme-admin-css.css');
        }
        if($hook == 'users_page_brozzme-change-username') {
            wp_enqueue_style( 'bcustyle', plugin_dir_url( __FILE__ ) . 'css/bcu_admin.css');
        }
    }

    /**
     * @param $links
     * @return array
     */
    public function add_action_links($links)
    {
        $mylinks = array(
            '<a href="' . admin_url('admin.php?page='. $this->settings_page_slug) . '">' . __('Settings', B7ECU_TEXT_DOMAIN) . '</a>',
        );
        return array_merge($links, $mylinks);
    }

    /**
     *
     */
    public function activate(){

    }

    /**
     *
     */
    public function desactivate(){

    }

}

new brozzme_change_username();