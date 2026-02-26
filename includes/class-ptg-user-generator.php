<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates WordPress test users with PTA volunteer roles.
 */
class PTG_User_Generator {

	const TEST_PASSWORD = 'TestPass123!';

	/**
	 * Generate test users.
	 *
	 * @param array $counts {
	 *     @type int $manager    Number of signup_sheet_manager users.
	 *     @type int $author     Number of signup_sheet_author users.
	 *     @type int $subscriber Number of subscriber users.
	 * }
	 * @return array {
	 *     @type array  $created  List of created user info arrays.
	 *     @type array  $errors   List of error strings.
	 * }
	 */
	public static function generate( $counts ) {
		$counts = wp_parse_args( $counts, array(
			'manager'    => 0,
			'author'     => 0,
			'subscriber' => 0,
		) );

		$role_map = array(
			'manager'    => 'signup_sheet_manager',
			'author'     => 'signup_sheet_author',
			'subscriber' => 'subscriber',
		);

		$created = array();
		$errors  = array();

		foreach ( $role_map as $slug => $role ) {
			$count = absint( $counts[ $slug ] );
			for ( $i = 1; $i <= $count; $i++ ) {
				$suffix   = self::next_suffix( $slug );
				$username = "testuser_{$slug}_{$suffix}";
				$email    = "testuser.{$slug}.{$suffix}@example.test";

				// Skip if already exists.
				if ( username_exists( $username ) || email_exists( $email ) ) {
					$errors[] = "Skipped: {$username} already exists.";
					continue;
				}

				$display_name = self::random_display_name();

				$user_id = wp_insert_user( array(
					'user_login'   => $username,
					'user_email'   => $email,
					'display_name' => $display_name,
					'first_name'   => explode( ' ', $display_name )[0],
					'last_name'    => explode( ' ', $display_name )[1] ?? '',
					'user_pass'    => self::TEST_PASSWORD,
					'role'         => $role,
				) );

				if ( is_wp_error( $user_id ) ) {
					$errors[] = "Error creating {$username}: " . $user_id->get_error_message();
					continue;
				}

				PTG_Tracker::add_user( $user_id );

				$created[] = array(
					'id'       => $user_id,
					'username' => $username,
					'email'    => $email,
					'name'     => $display_name,
					'role'     => $role,
				);
			}
		}

		return array( 'created' => $created, 'errors' => $errors );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Find the next available numeric suffix for a given slug so we never collide.
	 */
	private static function next_suffix( $slug ) {
		$i = 1;
		while ( username_exists( "testuser_{$slug}_{$i}" ) ) {
			$i++;
		}
		return $i;
	}

	private static function random_display_name() {
		// Use require (not require_once) so variables are always assigned in this scope,
		// even if the file was already included elsewhere.
		require PTG_PATH . 'includes/data/fake-names.php';
		$first = $ptg_first_names[ array_rand( $ptg_first_names ) ];
		$last  = $ptg_last_names[ array_rand( $ptg_last_names ) ];
		return "{$first} {$last}";
	}
}
