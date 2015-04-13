<?php if ( ! defined( 'ABSPATH' ) ) exit;

class NF_Upgrade_Handler {

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
        $this->admin_page = $this->admin_register_url();

        $this->upgrades[] = "2.9 Form Conversion";
    }

    private function admin_register_url() {

        $parent_slug = NULL;

        $page_title = __( 'Ninja Forms Upgrade', 'ninja-forms' );

        $menu_title = __( 'Upgrade', 'ninja-forms' );

        $capabilities = apply_filters(
            'ninja_forms_admin_menu_capabilities',
            'manage_options'
        );

        $menu_slug = 'nf_upgrade';

        $function = array( $this, 'admin_display_function');

        return add_submenu_page( $parent_slug, $page_title, $menu_title, $capabilities, $menu_slug, $function );
    }

    public function admin_display_function() {
        echo '<h2>' . __( 'Ninja Forms Upgrade', 'ninja-forms' ) . '<h2>';

        echo '<p>Ninja Forms needs to run the following upgrades.</p>';

        echo '<ul>';
        foreach( $this->upgrades as $upgrade ) {
            echo '<ul>[ ] ' . $upgrade . '</ul>';
        }
        echo '</ul>';
    }
}

function NF_Upgrade_Handler() {
    return NF_Upgrade_Handler::instance();
}
add_action( 'admin_menu', 'NF_Upgrade_Handler' );
