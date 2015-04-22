<?php if ( ! defined( 'ABSPATH' ) ) exit;

class NF_Convert_Forms extends NF_Step_Processing {

	function __construct() {
		$this->action = 'convert_forms';

		parent::__construct();
	}

	public function loading() {
		global $wpdb;

        // Check that the table exists
        if( 0 == $wpdb->query( "SHOW TABLES LIKE '" . NINJA_FORMS_TABLE_NAME . "'" ) ) {
            return array('complete' => true);
        }

		// Get all our forms
		$forms = $wpdb->get_results( 'SELECT id FROM ' . NINJA_FORMS_TABLE_NAME, ARRAY_A );

        $this->database_setup_2_8();

		$x = 1;
		if ( is_array( $forms ) ) {
			foreach ( $forms as $form ) {
				$this->args['forms'][$x] = $form['id'];
				$x++;
			}
		}

		$form_count = count( $forms );
		$this->total_steps = $form_count;

		if( empty( $this->total_steps ) || $this->total_steps <= 1 ) {
			$this->total_steps = 1;
		}

		$args = array(
			'total_steps' 	=> $this->total_steps,
			'step' 			=> 1,
		);

//		$this->redirect = admin_url( 'admin.php?page=ninja-forms' );

		return $args;
	}

	public function step() {
		global $wpdb;

		// Get a list of forms that we've already converted.
		$completed_forms = get_option( 'nf_converted_forms', array() );

		
		if ( ! is_array( $completed_forms ) )
			$completed_forms = array();
		
		// Get our form ID
		$form_id = $this->args['forms'][ $this->step ];

		// Bail if we've already converted the db for this form.
		if ( in_array( $form_id, $completed_forms ) )
			return false;

		nf_29_update_form_settings( $form_id );

		$completed_forms[] = $form_id;

		update_option( 'nf_converted_forms', $completed_forms );

	}

	public function complete() {
		global $wpdb;
		update_option( 'nf_convert_forms_complete', true );
	}

    public function database_setup_2_8() {
        /**
         * Add our table structure for version 2.8.
         *
         * Duplicated from the Notification Conversion
         */

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        // Create our object meta table
        $sql = "CREATE TABLE IF NOT EXISTS ". NF_OBJECT_META_TABLE_NAME . " (
		  `id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `object_id` bigint(20) NOT NULL,
		  `meta_key` varchar(255) NOT NULL,
		  `meta_value` longtext NOT NULL,
		  PRIMARY KEY (`id`)
		) DEFAULT CHARSET=utf8;";

        dbDelta( $sql );

        // Create our object table
        $sql = "CREATE TABLE IF NOT EXISTS " . NF_OBJECTS_TABLE_NAME . " (
		  `id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `type` varchar(255) NOT NULL,
		  PRIMARY KEY (`id`)
		) DEFAULT CHARSET=utf8;";

        dbDelta( $sql );

        // Create our object relationships table

        $sql = "CREATE TABLE IF NOT EXISTS " . NF_OBJECT_RELATIONSHIPS_TABLE_NAME . " (
		  `id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `child_id` bigint(20) NOT NULL,
		  `parent_id` bigint(20) NOT NULL,
		  `child_type` varchar(255) NOT NULL,
		  `parent_type` varchar(255) NOT NULL,
		  PRIMARY KEY (`id`)
		) DEFAULT CHARSET=utf8;";

        dbDelta( $sql );
    }

}