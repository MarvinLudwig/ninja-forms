<?php if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'NF_Upgrade_Handler' );
function NF_Upgrade_Handler() {
    return NF_Upgrade_Handler::instance();
}

class NF_Upgrade_Handler {

    const NF_PROCESSING_URL = 'admin.php?page=nf-processing&action=';

    public $admin_page;

    private $upgrades;


    public static function instance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }

        return $instance;
    }

    private function __construct() {

        wp_register_script(
            'nf-upgrade',
            //TODO Minimize Script
            NF_PLUGIN_URL . 'assets/js/dev/upgrade-handler.js',
            array( 'jquery' )
        );

        $this->admin_page = $this->admin_register_url();

        $this->upgrades[] = new NF_Upgrade( 'Update Email Settings', 'update_email_settings', 'nf_update_email_settings_complete');
        $this->upgrades[] = new NF_Upgrade( 'Convert Notifications', 'convert_notifications', 'nf_convert_notifications_complete');
        $this->upgrades[] = new NF_Upgrade( 'Convert Forms', 'convert_forms', 'nf_convert_forms_complete');

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
            echo '<ul>[' . ( $upgrade->flag ? '✓' : ' ' ) . '] ' . $upgrade->name . '</ul>';
        }
        echo '</ul>';
    }

    public function localize() {

        foreach( $this->upgrades as $upgrade ) {

            if( ! $upgrade->flag ) {
                $data['redirect'] = NF_Upgrade_Handler::NF_PROCESSING_URL . $upgrade->action;
                wp_localize_script( 'nf-upgrade', 'nf_upgrade_run', $data );
            }

        }
    }
}

class NF_Upgrade {

    public $name;

    public $action;

    public $flag;

    public function __construct( $name, $action, $option ) {
        $this->name = $name;
        $this->action = $action;
        $this->flag = get_option( $option, FALSE);
    }
}


