<?php if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'NF_Upgrade_Handler' );
function NF_Upgrade_Handler() {
    return NF_Upgrade_Handler::instance();
}

class NF_Upgrade_Handler {

    private static $instance;

    public $admin_page;

    private $upgrades;


    public static function instance()
    {

        if ( ! isset( self::$instance ) ) {
            self::$instance = new NF_Upgrade_Handler();
        }

        return self::$instance;
    }

    private function __construct() {

        wp_register_script(
            'nf-upgrade',
            //TODO Minimize Script
            NF_PLUGIN_URL . 'assets/js/dev/upgrade-handler.js',
            array( 'jquery' )
        );

        $this->upgrades[] = new NF_Upgrade(
            'Upgrade Submissions',
            'admin.php?page=nf-processing&action=convert_subs',
            'nf_convert_subs_complete',
            1
        );

        $this->upgrades[] = new NF_Upgrade(
            'Upgrade Email Settings',
            'admin.php?page=nf-processing&action=update_email_settings',
            'nf_update_email_settings_complete',
            1
        );

        $this->upgrades[] = new NF_Upgrade(
            'Upgrade Forms',
            'admin.php?page=nf-processing&action=convert_forms',
            'nf_convert_forms_complete',
            1
        );

        $this->upgrades[] = new NF_Upgrade(
            'Upgrade Notifications',
            'admin.php?page=nf-processing&action=convert_notifications',
            'nf_convert_notifications_complete',
            1
        );

        $this->admin_page = $this->admin_register_url();

        $this->localize();

        wp_enqueue_script( 'nf-upgrade' );
    }

    private function admin_register_url() {

        $parent_slug = NULL;

        $page_title = __( 'Ninja Forms Upgrade', 'ninja-forms' );

        $menu_title = __( 'Upgrade', 'ninja-forms' );

        $capabilities = apply_filters(
            'ninja_forms_admin_menu_capabilities',
            'manage_options'
        );

        $menu_slug = 'nf-upgrade';

        $function = array( $this, 'admin_display_function');

        return add_submenu_page( $parent_slug, $page_title, $menu_title, $capabilities, $menu_slug, $function );
    }

    public function admin_display_function() {
        echo '<h2>' . __( 'Ninja Forms Upgrade', 'ninja-forms' ) . '<h2>';

        echo '<p>Ninja Forms needs to run the following upgrades:</p>';

        echo '<ul>';
        foreach( $this->upgrades as $upgrade ) {
            echo '<ul>[' . ( $upgrade->flag ? '✓' : ' ' ) . '] <a href="' . $upgrade->url . '">' . $upgrade->name . '</a></ul>';
        }
        echo '</ul>';
    }

    public function localize() {

        foreach( $this->upgrades as $upgrade ) {

            if( $upgrade->complete_value != $upgrade->flag ) {
                wp_localize_script( 'nf-upgrade', 'nf_upgrade_run', array( 'redirect' => $upgrade->url ) );
                return;
            }

        }
    }
}

class NF_Upgrade {

    public $name;

    public $url;

    public $flag;

    public $complete_value;

    public function __construct( $name, $url, $option, $complete_value ) {
        $this->name = $name;
        $this->url = $url;
        $this->flag = get_option( $option, FALSE);
        $this->complete_value = $complete_value;
    }
}


