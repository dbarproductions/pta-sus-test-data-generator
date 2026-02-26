<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates signups on previously generated sheets/tasks.
 */
class PTG_Signup_Generator {

	/**
	 * Generate signups across all tracked sheets.
	 *
	 * @param array $options {
	 *     @type float $fill_rate  0.0–1.0 fraction of available spots to fill.
	 *     @type int   $user_pct   0–100 percentage of signups from tracked test users (rest are guests).
	 * }
	 * @return array {
	 *     @type int   $total    Total signups created.
	 *     @type int   $skipped  Spots that were full / returned false.
	 *     @type array $by_sheet Signup counts keyed by sheet ID.
	 *     @type array $errors   Error strings.
	 * }
	 */
	public static function generate( $options ) {
		$options = wp_parse_args( $options, array(
			'fill_rate' => 0.6,
			'user_pct'  => 50,
		) );

		$fill_rate = max( 0.0, min( 1.0, (float) $options['fill_rate'] ) );
		$user_pct  = max( 0, min( 100, absint( $options['user_pct'] ) ) );

		$sheet_ids  = PTG_Tracker::get_sheet_ids();
		$user_ids   = PTG_Tracker::get_user_ids();
		$total      = 0;
		$skipped    = 0;
		$by_sheet   = array();
		$errors     = array();

		require PTG_PATH . 'includes/data/fake-names.php';

		foreach ( $sheet_ids as $sheet_id ) {
			$by_sheet[ $sheet_id ] = 0;

			if ( ! class_exists( 'PTA_SUS_Task_Functions' ) ) {
				$errors[] = 'PTA_SUS_Task_Functions class not found.';
				continue;
			}

			$tasks = PTA_SUS_Task_Functions::get_tasks( $sheet_id );
			if ( ! is_array( $tasks ) || empty( $tasks ) ) {
				continue;
			}

			foreach ( $tasks as $task ) {
				$dates = $task->get_dates_array();
				if ( empty( $dates ) ) {
					// Ongoing tasks may have a single placeholder date.
					$dates = array( $task->dates );
				}

				foreach ( $dates as $date ) {
					$spots_to_fill = (int) floor( $task->qty * $fill_rate );
					for ( $i = 0; $i < $spots_to_fill; $i++ ) {
						$use_test_user = ( ! empty( $user_ids ) && wp_rand( 0, 99 ) < $user_pct );

						if ( $use_test_user ) {
							$user_id   = $user_ids[ array_rand( $user_ids ) ];
							$user_obj  = get_userdata( $user_id );
							$firstname = $user_obj ? $user_obj->first_name  : 'Test';
							$lastname  = $user_obj ? $user_obj->last_name   : 'User';
							$email     = $user_obj ? $user_obj->user_email  : "testuser.{$user_id}@example.test";
						} else {
							$user_id   = 0;
							$firstname = $ptg_first_names[ array_rand( $ptg_first_names ) ];
							$lastname  = $ptg_last_names[ array_rand( $ptg_last_names ) ];
							$email     = strtolower( $firstname ) . '.' . strtolower( $lastname ) . '.' . wp_rand( 10, 99 ) . '@example.test';
						}

						$phone = self::random_phone();

						$prefixed = array(
							'signup_firstname'  => $firstname,
							'signup_lastname'   => $lastname,
							'signup_email'      => $email,
							'signup_phone'      => $phone,
							'signup_date'       => $date,
							'signup_user_id'    => $user_id,
							'signup_validated'  => 1,
						);

						$signup_id = pta_sus_add_signup( $prefixed, $task->id, $task );

						if ( $signup_id && ! is_wp_error( $signup_id ) ) {
							$total++;
							$by_sheet[ $sheet_id ]++;
						} else {
							$skipped++;
						}
					}
				}
			}
		}

		return array(
			'total'    => $total,
			'skipped'  => $skipped,
			'by_sheet' => $by_sheet,
			'errors'   => $errors,
		);
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	private static function random_phone() {
		$area    = wp_rand( 200, 999 );
		$prefix  = wp_rand( 200, 999 );
		$line    = wp_rand( 1000, 9999 );
		return "({$area}) {$prefix}-{$line}";
	}
}
