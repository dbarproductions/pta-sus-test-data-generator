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
	 *     @type float $fill_rate         0.0–1.0 fraction of available spots to fill.
	 *     @type int   $user_pct          0–100 percentage of signups from tracked test users (rest are guests).
	 *     @type int   $cf_field_fill_pct 0-100 chance to fill each eligible signup-template Custom Field (default 80).
	 *     @type int   $wl_min_per_task   Min waitlist signups to add per fully-booked date on a waitlist-enabled task (default 1).
	 *     @type int   $wl_max_per_task   Max waitlist signups to add per fully-booked date on a waitlist-enabled task (default 4).
	 * }
	 * @return array {
	 *     @type int   $total         Total regular signups created.
	 *     @type int   $skipped       Spots that were full / returned false.
	 *     @type array $by_sheet      Signup counts keyed by sheet ID.
	 *     @type int   $waitlist_total Total waitlist signups created.
	 *     @type array $errors        Error strings.
	 * }
	 */
	public static function generate( $options ) {
		$options = wp_parse_args( $options, array(
			'fill_rate'         => 0.6,
			'user_pct'          => 50,
			'cf_field_fill_pct' => 80,
			'wl_min_per_task'   => 1,
			'wl_max_per_task'   => 4,
		) );

		$fill_rate = max( 0.0, min( 1.0, (float) $options['fill_rate'] ) );
		$user_pct  = max( 0, min( 100, absint( $options['user_pct'] ) ) );
		$cf_fill_pct = absint( $options['cf_field_fill_pct'] );
		$wl_min    = max( 0, absint( $options['wl_min_per_task'] ) );
		$wl_max    = max( $wl_min, absint( $options['wl_max_per_task'] ) );

		$sheet_ids  = PTG_Tracker::get_sheet_ids();
		$user_ids   = PTG_Tracker::get_user_ids();
		$total      = 0;
		$skipped    = 0;
		$waitlist_total = 0;
		$by_sheet   = array();
		$errors     = array();

		require PTG_PATH . 'includes/data/fake-names.php';

		$cf_active = class_exists( 'Pta_Volunteer_Sus_Custom_Fields' );
		$wl_active = class_exists( 'PTAVWL_Waitlist_Functions' );
		if ( $cf_active ) {
			require_once PTG_PATH . 'includes/class-ptg-custom-fields-generator.php';
		}

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

				// Resolve the signup template (task -> sheet fallback) and waitlist assigned to this task, once.
				$template_fields = array();
				if ( $cf_active ) {
					$template_id = PTAVCF_Assignment_Functions::get_assignment_id( $task, 'template' );
					if ( $template_id ) {
						$template        = new PTAVCF_Template( $template_id );
						$template_fields = $template->get_fields();
					}
				}
				$waitlist_id = ( $wl_active && ! empty( $task->waitlist_id ) ) ? absint( $task->waitlist_id ) : 0;

				foreach ( $dates as $date ) {
					// A task with a waitlist assigned is filled to capacity on this date so the
					// waitlist signups generated below actually make sense (the real waitlist
					// signup option only appears once a task has no spots left).
					$spots_to_fill = ( $waitlist_id > 0 )
						? absint( $task->qty )
						: (int) floor( $task->qty * $fill_rate );

					for ( $i = 0; $i < $spots_to_fill; $i++ ) {
						$person = self::random_person( $user_ids, $ptg_first_names, $ptg_last_names, $user_pct );

						$prefixed = array(
							'signup_firstname'  => $person['firstname'],
							'signup_lastname'   => $person['lastname'],
							'signup_email'      => $person['email'],
							'signup_phone'      => self::random_phone(),
							'signup_date'       => $date,
							'signup_user_id'    => $person['user_id'],
							'signup_validated'  => 1,
						);

						$signup_id = pta_sus_add_signup( $prefixed, $task->id, $task );

						if ( $signup_id && ! is_wp_error( $signup_id ) ) {
							$total++;
							$by_sheet[ $sheet_id ]++;

							if ( ! empty( $template_fields ) ) {
								$custom_values = self::generate_template_values( $template_fields, $cf_fill_pct );
								do_action( 'pta_sus_after_add_signup', array_merge( $prefixed, $custom_values ), $task->id, $signup_id );
							}
						} else {
							$skipped++;
						}
					}

					if ( $waitlist_id > 0 ) {
						$wl_count = wp_rand( $wl_min, $wl_max );
						for ( $w = 0; $w < $wl_count; $w++ ) {
							$person = self::random_person( $user_ids, $ptg_first_names, $ptg_last_names, $user_pct );

							$wl_posted = array(
								'signup_firstname' => $person['firstname'],
								'signup_lastname'  => $person['lastname'],
								'signup_email'     => $person['email'],
								'signup_phone'     => self::random_phone(),
								'signup_date'      => $date,
								'signup_item'      => '',
								'signup_item_qty'  => 1,
								'signup_user_id'   => $person['user_id'],
								'signup_validated' => 1,
								'allow_duplicates' => 'NO',
							);

							$waitlist_signup_id = PTAVWL_Waitlist_Functions::add_waitlist_signup( $wl_posted, $task->id );

							if ( $waitlist_signup_id && ! is_wp_error( $waitlist_signup_id ) ) {
								$waitlist_total++;
								if ( ! empty( $template_fields ) ) {
									$custom_values = self::generate_template_values( $template_fields, $cf_fill_pct );
									do_action( 'ptavwl_after_processing_waitlist_signup', $waitlist_signup_id, array_merge( $wl_posted, $custom_values ), $task->id );
								}
							}
						}
					}
				}
			}
		}

		return array(
			'total'          => $total,
			'skipped'        => $skipped,
			'by_sheet'       => $by_sheet,
			'waitlist_total' => $waitlist_total,
			'errors'         => $errors,
		);
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Pick a random test user (if any are tracked and the roll succeeds) or generate a guest.
	 *
	 * @return array { 'user_id' => int, 'firstname' => string, 'lastname' => string, 'email' => string }
	 */
	private static function random_person( $user_ids, $first_names, $last_names, $user_pct = 50 ) {
		$use_test_user = ( ! empty( $user_ids ) && wp_rand( 0, 99 ) < $user_pct );

		if ( $use_test_user ) {
			$user_id  = $user_ids[ array_rand( $user_ids ) ];
			$user_obj = get_userdata( $user_id );
			return array(
				'user_id'   => $user_id,
				'firstname' => $user_obj ? $user_obj->first_name : 'Test',
				'lastname'  => $user_obj ? $user_obj->last_name : 'User',
				'email'     => $user_obj ? $user_obj->user_email : "testuser.{$user_id}@example.test",
			);
		}

		$firstname = $first_names[ array_rand( $first_names ) ];
		$lastname  = $last_names[ array_rand( $last_names ) ];
		return array(
			'user_id'   => 0,
			'firstname' => $firstname,
			'lastname'  => $lastname,
			'email'     => strtolower( $firstname ) . '.' . strtolower( $lastname ) . '.' . wp_rand( 10, 99 ) . '@example.test',
		);
	}

	/**
	 * Generate random values for a signup template's fields, unprefixed
	 * (matching the shape of $_POST custom-field keys), skipping each field
	 * some of the time so generated data looks realistically incomplete.
	 *
	 * @param PTAVCF_Template_Field[] $template_fields Keyed by field slug.
	 * @param int                     $fill_pct        0-100 chance to fill each field.
	 * @return array
	 */
	private static function generate_template_values( array $template_fields, $fill_pct ) {
		$values = array();
		foreach ( $template_fields as $slug => $field ) {
			if ( wp_rand( 1, 100 ) > $fill_pct ) {
				continue;
			}
			$values[ $slug ] = PTG_Custom_Fields_Generator::generate_value_for_field( $field );
		}
		return $values;
	}

	private static function random_phone() {
		$area    = wp_rand( 200, 999 );
		$prefix  = wp_rand( 200, 999 );
		$line    = wp_rand( 1000, 9999 );
		return "({$area}) {$prefix}-{$line}";
	}
}
