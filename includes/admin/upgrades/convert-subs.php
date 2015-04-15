<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Submission conversion
 * This class handles converting pre-2.7 submissions to the new CPT storage.
 *
 * @package     Ninja Forms
 * @subpackage  Classes/Submissions
 * @copyright   Copyright (c) 2014, WPNINJAS
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.7
*/

class NF_Convert_Subs extends NF_Step_Processing {

	/**
	 * Get things started
	 * 
	 * @since 2.7
	 * @access public
	 */
	public function __construct() {
        $this->action = 'convert_subs';

        parent::__construct();
	}

    public function loading() {

    }

    public function step() {
        $this->step   = isset( $_GET['step'] )  ? absint( $_GET['step'] )  : 1;
        $total  = isset( $_GET['total'] ) ? absint( $_GET['total'] ) : false;
        $this->number  = isset( $_GET['custom'] ) ? absint( $_GET['custom'] ) : 1;

        if ( get_option( 'nf_convert_subs_num' ) ) {
            $number = get_option( 'nf_convert_subs_num' );
        }

        $form_id  = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;

        update_option( 'nf_convert_subs_step', $this->step );

        $old_sub_count = $this->count_old_subs();

        if( empty( $total ) || $total <= 1 ) {
            $total = round( ( $old_sub_count / 100 ), 0 ) + 2;
        }

        if ( $step <= $total ) {
            if ($step == 1) {
                $begin = 0;
            } else {
                $begin = ($step - 1) * 100;
            }

            $subs_results = $this->get_old_subs($begin, 100);

            if (is_array($subs_results) && !empty($subs_results)) {

                foreach ($subs_results as $sub) {
                    if ($form_id != $sub['form_id']) {
                        $form_id = $sub['form_id'];
                        $number = 1;
                    }
                    $converted = get_option('nf_converted_subs');
                    if (empty($converted))
                        $converted = array();

                    if (!in_array($sub['id'], $converted)) {
                        $this->convert($sub, $number);

                        $converted[] = $sub['id'];
                        update_option('nf_converted_subs', $converted);
                        $number++;
                        update_option('nf_convert_subs_num', $number);
                    }
                }
            }

            $step++;

            $redirect = add_query_arg(array(
                'page' => 'nf-upgrades',
                'nf-upgrade' => 'upgrade_subs_to_cpt',
                'step' => $step,
                'custom' => $number,
                'total' => $total,
                'form_id' => $form_id
            ), admin_url('index.php'));
            wp_redirect($redirect);
            exit;
        } else {
            update_option( 'nf_convert_subs_step', 'complete' );
            delete_option( 'nf_convert_subs_num' );
            wp_redirect( admin_url( 'admin.php?page=nf-upgrade' ) ); exit;
        }
    }

	/**
	 * Grab our old submissions
	 * 
	 * @since 2.7
	 * @access public
	 * @return $sub_results
	 */
	public function get_old_subs( $begin = '', $count = '' ) {
		global $wpdb;

		if ( $begin == '' && $count == '' ) {
			$limit = '';
		} else {
			$limit = ' LIMIT ' . $begin . ',' . $count;
		}
		$subs_results = $wpdb->get_results( 'SELECT * FROM ' . NINJA_FORMS_SUBS_TABLE_NAME . ' WHERE `action` != "mp_save" ORDER BY `form_id` ASC, `id` ASC ' . $limit, ARRAY_A );
		//Now that we have our sub results, let's loop through them and remove any that don't match our args array.
		if( is_array( $subs_results ) AND ! empty( $subs_results ) ) {
			foreach( $subs_results as $key => $val ) { //Initiate a loop that will run for all of our submissions.
				//Set our $data variable. This variable contains an array that looks like: array('field_id' => 13, 'user_value' => 'Hello World!').
				if( is_serialized( $subs_results[$key]['data'] ) ) {
					$subs_results[ $key ]['data'] = unserialize( $subs_results[ $key ]['data'] );
				}
			}
		}
		return $subs_results;
	}

	/**
	 * Count our old submissions
	 * 
	 * @since 2.7
	 * @access public
	 * @return $count
	 */
	public function count_old_subs() {
		global $wpdb;
		$count = $wpdb->get_results( 'SELECT COUNT(*) FROM '. NINJA_FORMS_SUBS_TABLE_NAME . ' WHERE `action` != "mp_save"', ARRAY_A );
		if ( is_array ( $count ) && ! empty ( $count ) ) {
			return $count[0]['COUNT(*)'];
		} else {
			return false;
		}
	}


	/**
	 * Convert a submission
	 * 
	 * @since 2.7
	 * @access public
	 * @return bool
	 */
	public function convert( $sub, $num ) {

		if ( isset ( $sub['id'] ) ) {
			$old_id = $sub['id'];
			unset( $sub['id'] );
		}

		if ( isset ( $sub['form_id'] ) ) {
			$form_id = $sub['form_id'];
			unset ( $sub['form_id'] );			
		}

		if ( isset ( $sub['action'] ) ) {
			$action = $sub['action'];
			unset ( $sub['action'] );			
		}

		if ( isset ( $sub['user_id'] ) ) {
			$user_id = $sub['user_id'];
			unset ( $sub['user_id'] );			
		}

		if ( isset ( $sub['date_updated'] ) ) {
			$date_updated = $sub['date_updated'];
			unset ( $sub['date_updated'] );
		}

		if ( isset ( $sub['status'] ) )
			unset ( $sub['status'] );

		if ( isset ( $sub['saved'] ) )
			unset ( $sub['saved'] );

		$sub_id = Ninja_Forms()->subs()->create( $form_id );
		Ninja_Forms()->sub( $sub_id )->update_action( $action );
		Ninja_Forms()->sub( $sub_id )->update_user_id( $user_id );
		Ninja_Forms()->sub( $sub_id )->update_seq_num( $num );
		Ninja_Forms()->sub( $sub_id )->update_date_submitted( $date_updated );
		Ninja_Forms()->sub( $sub_id )->update_date_modified( $date_updated );
		Ninja_Forms()->sub( $sub_id )->add_meta( '_old_id', $old_id );

		if ( isset ( $sub['data'] ) ) {
			foreach ( $sub['data'] as $data ) {
				$field_id = $data['field_id'];
				$value = $data['user_value'];
				Ninja_Forms()->sub( $sub_id )->add_field( $field_id, $value );
			}
			unset ( $sub['data'] );
		}

		if ( ! empty ( $sub ) ) {
			foreach ( $sub as $key => $value ) {
				if ( $value !== '' ) {
					Ninja_Forms()->sub( $sub_id )->add_meta( '_' . $key, $value );
				}
			}
		}
	}
}