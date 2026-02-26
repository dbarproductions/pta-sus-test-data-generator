<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracks generated IDs so we can bulk-delete test data later.
 *
 * Option schema:
 *   ptg_generated_data => [
 *       'users'  => [ int, ... ],
 *       'sheets' => [ int, ... ],
 *   ]
 */
class PTG_Tracker {

	const OPTION_KEY = 'ptg_generated_data';

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	private static function get_data() {
		$data = get_option( self::OPTION_KEY, array() );
		if ( ! isset( $data['users'] ) )  { $data['users']  = array(); }
		if ( ! isset( $data['sheets'] ) ) { $data['sheets'] = array(); }
		return $data;
	}

	private static function save_data( $data ) {
		update_option( self::OPTION_KEY, $data, false );
	}

	// -----------------------------------------------------------------------
	// Writers
	// -----------------------------------------------------------------------

	public static function add_user( $id ) {
		$id   = absint( $id );
		$data = self::get_data();
		if ( $id && ! in_array( $id, $data['users'], true ) ) {
			$data['users'][] = $id;
			self::save_data( $data );
		}
	}

	public static function add_sheet( $id ) {
		$id   = absint( $id );
		$data = self::get_data();
		if ( $id && ! in_array( $id, $data['sheets'], true ) ) {
			$data['sheets'][] = $id;
			self::save_data( $data );
		}
	}

	// -----------------------------------------------------------------------
	// Readers
	// -----------------------------------------------------------------------

	public static function get_user_ids() {
		return self::get_data()['users'];
	}

	public static function get_sheet_ids() {
		return self::get_data()['sheets'];
	}

	/**
	 * Returns a summary array with counts for display on the Cleanup tab.
	 */
	public static function get_summary() {
		$data        = self::get_data();
		$user_count  = count( $data['users'] );
		$sheet_count = count( $data['sheets'] );
		$task_count  = 0;
		$signup_count = 0;

		foreach ( $data['sheets'] as $sheet_id ) {
			if ( class_exists( 'PTA_SUS_Task_Functions' ) ) {
				$tasks = PTA_SUS_Task_Functions::get_tasks( $sheet_id );
				if ( is_array( $tasks ) ) {
					$task_count += count( $tasks );
					foreach ( $tasks as $task ) {
						if ( class_exists( 'PTA_SUS_Signup_Functions' ) ) {
							$signups = PTA_SUS_Signup_Functions::get_signups( array( 'task_id' => $task->id ) );
							if ( is_array( $signups ) ) {
								$signup_count += count( $signups );
							}
						}
					}
				}
			}
		}

		return array(
			'users'   => $user_count,
			'sheets'  => $sheet_count,
			'tasks'   => $task_count,
			'signups' => $signup_count,
		);
	}

	// -----------------------------------------------------------------------
	// Deleters
	// -----------------------------------------------------------------------

	public static function delete_users() {
		$data    = self::get_data();
		$deleted = 0;
		foreach ( $data['users'] as $user_id ) {
			if ( wp_delete_user( $user_id ) ) {
				$deleted++;
			}
		}
		$data['users'] = array();
		self::save_data( $data );
		return $deleted;
	}

	public static function delete_sheets() {
		$data    = self::get_data();
		$deleted = 0;
		if ( class_exists( 'PTA_SUS_Sheet_Functions' ) ) {
			foreach ( $data['sheets'] as $sheet_id ) {
				if ( PTA_SUS_Sheet_Functions::delete_sheet( $sheet_id ) ) {
					$deleted++;
				}
			}
		}
		$data['sheets'] = array();
		self::save_data( $data );
		return $deleted;
	}

	public static function delete_all() {
		$users   = self::delete_users();
		$sheets  = self::delete_sheets();
		self::clear();
		return array( 'users' => $users, 'sheets' => $sheets );
	}

	public static function clear() {
		delete_option( self::OPTION_KEY );
	}
}
