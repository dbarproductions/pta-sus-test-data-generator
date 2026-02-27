<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates sign-up sheets and tasks using the core plugin's model layer.
 */
class PTG_Sheet_Generator {

	/**
	 * Generate sheets and tasks.
	 *
	 * @param array $options {
	 *     @type string $preset        'bake_sale'|'carnival'|'committee'|'volunteer_fair'|'random'
	 *     @type int    $count         Number of sheets to create.
	 *     @type int    $tasks_min     Minimum tasks per sheet.
	 *     @type int    $tasks_max     Maximum tasks per sheet.
	 *     @type int    $start_weeks   Weeks from today before first date.
	 *     @type int    $span_weeks    Width of date range in weeks.
	 *     @type string $type_override Optional forced sheet type (ignored for non-random presets).
	 * }
	 * @return array {
	 *     @type array $sheets  List of created sheet info arrays.
	 *     @type array $errors  List of error strings.
	 * }
	 */
	public static function generate( $options ) {
		$options = wp_parse_args( $options, array(
			'preset'        => 'random',
			'count'         => 3,
			'tasks_min'     => 2,
			'tasks_max'     => 5,
			'start_weeks'   => 1,
			'span_weeks'    => 4,
			'type_override' => '',
		) );

		require_once PTG_PATH . 'includes/data/presets.php';

		$preset_key = sanitize_key( $options['preset'] );
		if ( ! isset( $ptg_presets[ $preset_key ] ) ) {
			$preset_key = 'random';
		}
		$preset = $ptg_presets[ $preset_key ];

		$range_start = strtotime( '+' . absint( $options['start_weeks'] ) . ' weeks' );
		$range_end   = strtotime( '+' . ( absint( $options['start_weeks'] ) + absint( $options['span_weeks'] ) ) . ' weeks', mktime( 0, 0, 0 ) );

		$created = array();
		$errors  = array();
		$used_titles = array();

		for ( $s = 0; $s < absint( $options['count'] ); $s++ ) {
			$sheet_type = self::resolve_sheet_type( $preset, $options['type_override'] );
			$title      = self::pick_title( $preset, $used_titles );
			$used_titles[] = $title;

			$dates      = self::generate_dates( $sheet_type, $range_start, $range_end );

			$author = self::pick_random_author();
			$chairs = self::random_chairs();

			$prefixed = array(
				'sheet_title'          => $title,
				'sheet_type'           => $sheet_type,
				'sheet_first_date'     => $dates['first'],
				'sheet_last_date'      => $dates['last'],
				'sheet_details'        => '',
				'sheet_visible'        => 1,
				'sheet_author_id'      => $author['id'],
				'sheet_author_email'   => $author['email'],
				'sheet_reminder1_days' => 7,
				'sheet_reminder2_days' => 1,
				'sheet_chair_name'     => $chairs['names'],
				'sheet_chair_email'    => $chairs['emails'],
			);

			$sheet_id = pta_sus_add_sheet( $prefixed );

			if ( ! $sheet_id || is_wp_error( $sheet_id ) ) {
				$errors[] = is_wp_error( $sheet_id )
					? "Sheet error ({$title}): " . $sheet_id->get_error_message()
					: "Sheet creation failed: {$title}";
				continue;
			}

			PTG_Tracker::add_sheet( $sheet_id );

			$task_count = rand( absint( $options['tasks_min'] ), max( absint( $options['tasks_min'] ), absint( $options['tasks_max'] ) ) );
			$task_titles = self::pick_task_titles( $preset, $task_count );
			$tasks_created = 0;

			foreach ( $task_titles as $idx => $task_title ) {
				$task_dates  = self::task_dates_string( $sheet_type, $dates, $idx );
				$start_time  = self::random_time();
				$end_time    = self::offset_time( $start_time, 2 );
				$need_details = self::resolve_need_details( $preset );
				$details_text = ( 'YES' === $need_details ) ? $preset['details_text'] : '';

				$task_prefixed = array(
					'task_title'            => $task_title,
					'task_dates'            => $task_dates,
					'task_qty'              => rand( 2, 8 ),
					'task_time_start'       => $start_time,
					'task_time_end'         => $end_time,
					'task_need_details'     => $need_details,
					'task_details_text'     => $details_text,
					'task_allow_duplicates' => 'NO',
				);

				$task_id = pta_sus_add_task( $task_prefixed, $sheet_id );

				if ( $task_id && ! is_wp_error( $task_id ) ) {
					$tasks_created++;
				} else {
					$err_msg = is_wp_error( $task_id ) ? $task_id->get_error_message() : 'unknown error';
					$errors[] = "Task error on sheet {$sheet_id} ({$task_title}): {$err_msg}";
				}
			}

			$created[] = array(
				'id'         => $sheet_id,
				'title'      => $title,
				'type'       => $sheet_type,
				'task_count' => $tasks_created,
			);
		}

		return array( 'sheets' => $created, 'errors' => $errors );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	private static function resolve_sheet_type( $preset, $type_override ) {
		if ( 'random' === $preset['sheet_type'] ) {
			if ( $type_override && 'random' !== $type_override ) {
				return $type_override;
			}
			$types = array( 'Single', 'Multi-Day', 'Recurring', 'Ongoing' );
			return $types[ array_rand( $types ) ];
		}
		return $preset['sheet_type'];
	}

	private static function pick_title( $preset, $used ) {
		if ( 'random' === $preset['sheet_type'] ) {
			$adj  = $preset['title_adjectives'][ array_rand( $preset['title_adjectives'] ) ];
			$noun = $preset['title_nouns'][ array_rand( $preset['title_nouns'] ) ];
			return "{$adj} {$noun}";
		}

		// Prefer an unused title; if exhausted, allow repeats with a number suffix.
		$available = array_diff( $preset['titles'], $used );
		if ( ! empty( $available ) ) {
			return $available[ array_rand( $available ) ];
		}
		$base = $preset['titles'][ array_rand( $preset['titles'] ) ];
		return $base . ' ' . wp_rand( 2, 9 );
	}

	private static function pick_task_titles( $preset, $count ) {
		if ( 'random' === $preset['sheet_type'] ) {
			$titles = array();
			for ( $i = 1; $i <= $count; $i++ ) {
				$titles[] = "Volunteer Slot {$i}";
			}
			return $titles;
		}

		$pool = $preset['tasks'];
		shuffle( $pool );
		// If we need more tasks than pool has, wrap around.
		$titles = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$titles[] = $pool[ $i % count( $pool ) ];
		}
		return $titles;
	}

	private static function resolve_need_details( $preset ) {
		if ( 'random' === $preset['need_details'] ) {
			return ( wp_rand( 0, 1 ) ) ? 'YES' : 'NO';
		}
		return $preset['need_details'];
	}

	/**
	 * Generate sheet first/last dates based on type.
	 *
	 * @return array { 'first' => 'YYYY-MM-DD', 'last' => 'YYYY-MM-DD', 'all' => [...] }
	 */
	private static function generate_dates( $sheet_type, $range_start, $range_end ) {
		switch ( $sheet_type ) {
			case 'Ongoing':
				return array(
					'first' => '0000-00-00',
					'last'  => '0000-00-00',
					'all'   => array(),
				);

			case 'Recurring':
				// Weekly dates within range.
				$dates = array();
				$cur   = $range_start;
				while ( $cur <= $range_end ) {
					$dates[] = date( 'Y-m-d', $cur );
					$cur     = strtotime( '+1 week', $cur );
				}
				if ( empty( $dates ) ) {
					$dates[] = date( 'Y-m-d', $range_start );
				}
				return array(
					'first' => $dates[0],
					'last'  => end( $dates ),
					'all'   => $dates,
				);

			case 'Multi-Day':
				// 2–4 dates spread across range.
				$num   = rand( 2, 4 );
				$span  = $range_end - $range_start;
				$dates = array();
				$used  = array();
				for ( $i = 0; $i < $num; $i++ ) {
					$attempts = 0;
					do {
						$offset = wp_rand( 0, (int) ( $span / DAY_IN_SECONDS ) );
						$day    = date( 'Y-m-d', $range_start + $offset * DAY_IN_SECONDS );
						$attempts++;
					} while ( in_array( $day, $used, true ) && $attempts < 20 );
					$used[]  = $day;
					$dates[] = $day;
				}
				sort( $dates );
				return array(
					'first' => $dates[0],
					'last'  => end( $dates ),
					'all'   => $dates,
				);

			case 'Single':
			default:
				$span   = max( 0, (int) ( ( $range_end - $range_start ) / DAY_IN_SECONDS ) );
				$offset = wp_rand( 0, $span );
				$date   = date( 'Y-m-d', $range_start + $offset * DAY_IN_SECONDS );
				return array(
					'first' => $date,
					'last'  => $date,
					'all'   => array( $date ),
				);
		}
	}

	/**
	 * Build the task dates string from the sheet's date info.
	 *
	 * - Ongoing   → '0000-00-00'
	 * - Single    → single date string
	 * - Recurring → comma-separated list (all dates, shared by every task)
	 * - Multi-Day → single date per task, cycling through the available dates
	 *
	 * @param string $sheet_type  Sheet type string.
	 * @param array  $dates       Date info from generate_dates().
	 * @param int    $task_index  Zero-based task position (used for Multi-Day cycling).
	 * @return string
	 */
	private static function task_dates_string( $sheet_type, $dates, $task_index = 0 ) {
		if ( 'Ongoing' === $sheet_type ) {
			return '0000-00-00';
		}
		if ( 'Multi-Day' === $sheet_type ) {
			// Each task gets exactly one date; cycle through the pool so all dates are represented.
			if ( ! empty( $dates['all'] ) ) {
				return $dates['all'][ $task_index % count( $dates['all'] ) ];
			}
			return $dates['first'];
		}
		// Single and Recurring: all tasks share the same date(s).
		if ( ! empty( $dates['all'] ) ) {
			return implode( ',', $dates['all'] );
		}
		return $dates['first'];
	}

	/** Returns a random time in 'HH:MM am' format (12-hour). */
	private static function random_time() {
		$hour   = rand( 7, 18 );
		$minute = ( rand( 0, 1 ) ) ? '00' : '30';
		$ampm   = ( $hour >= 12 ) ? 'pm' : 'am';
		$hour12 = ( $hour > 12 ) ? $hour - 12 : ( ( 0 === $hour ) ? 12 : $hour );
		return sprintf( '%02d:%s %s', $hour12, $minute, $ampm );
	}

	/** Offsets a 12-hour time string by $hours hours. */
	private static function offset_time( $time_str, $hours ) {
		$ts = strtotime( $time_str );
		if ( ! $ts ) {
			return '11:00 am';
		}
		$new = strtotime( "+{$hours} hours", $ts );
		return date( 'g:i a', $new );
	}

	/**
	 * Returns a random signup_sheet_author from the tracked test users.
	 * Falls back to the current user if none are found.
	 *
	 * @return array { 'id' => int, 'email' => string }
	 */
	private static function pick_random_author() {
		$tracked_ids = PTG_Tracker::get_user_ids();

		if ( ! empty( $tracked_ids ) ) {
			$authors = get_users( array(
				'role'    => 'signup_sheet_author',
				'include' => $tracked_ids,
				'fields'  => array( 'ID', 'user_email' ),
			) );

			if ( ! empty( $authors ) ) {
				$pick = $authors[ array_rand( $authors ) ];
				return array( 'id' => (int) $pick->ID, 'email' => $pick->user_email );
			}
		}

		// Fall back to current user.
		$current = wp_get_current_user();
		return array( 'id' => (int) $current->ID, 'email' => $current->user_email );
	}

	/**
	 * Generates 1–3 random chair names and matching emails.
	 * Both are comma-separated strings of equal count.
	 *
	 * @return array { 'names' => string, 'emails' => string }
	 */
	private static function random_chairs() {
		// Use require so variables are always in scope regardless of prior includes.
		require PTG_PATH . 'includes/data/fake-names.php';
        /**
         * @var array $ptg_first_names
         * @var array $ptg_last_names
         */

		$count  = wp_rand( 1, 3 );
		$names  = array();
		$emails = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$first    = $ptg_first_names[ array_rand( $ptg_first_names ) ];
			$last     = $ptg_last_names[ array_rand( $ptg_last_names ) ];
			$names[]  = "{$first} {$last}";
			$emails[] = strtolower( $first ) . '.' . strtolower( $last ) . '.' . wp_rand( 10, 99 ) . '@example.test';
		}

		return array(
			'names'  => implode( ', ', $names ),
			'emails' => implode( ', ', $emails ),
		);
	}
}
