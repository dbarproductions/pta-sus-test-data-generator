<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the admin page, tab rendering, and POST dispatch for the Test Data Generator.
 */
class PTG_Admin {

	const PAGE_SLUG   = '_test_data_generator';
	const NONCE_ACTION = 'ptg_action';
	const CAP         = 'manage_signup_sheets';

	// Parent menu slug discovered from core plugin.
	const PARENT_SLUG = 'pta-sus-settings';

	// -----------------------------------------------------------------------
	// Menu registration
	// -----------------------------------------------------------------------

	public static function register_menu() {
        global $pta_test_data_generator_page;
        $pta_test_data_generator_page = add_submenu_page(
			self::PARENT_SLUG.'_sheets',
			__( 'Test Data Generator', 'pta-sus-test-data-generator' ),
			__( 'Test Data Generator', 'pta-sus-test-data-generator' ),
			self::CAP,
			self::PARENT_SLUG . self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	// -----------------------------------------------------------------------
	// Assets
	// -----------------------------------------------------------------------

	public static function enqueue_assets( $hook ) {
		// Only load on our page.
		if ( false === strpos( $hook, self::PAGE_SLUG ) ) {
			return;
		}
		wp_enqueue_style(
			'ptg-admin',
			PTG_URL . 'assets/ptg-admin.css',
			array(),
			PTG_VERSION
		);
		wp_enqueue_script(
			'ptg-admin',
			PTG_URL . 'assets/ptg-admin.js',
			array( 'jquery' ),
			PTG_VERSION,
			true
		);
	}

	// -----------------------------------------------------------------------
	// Page rendering
	// -----------------------------------------------------------------------

	public static function render_page() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'pta-sus-test-data-generator' ) );
		}

		$active_tab = isset( $_GET['ptg_tab'] ) ? sanitize_key( $_GET['ptg_tab'] ) : 'users';
		$valid_tabs = array( 'users', 'sheets', 'signups', 'cleanup' );
		if ( ! in_array( $active_tab, $valid_tabs, true ) ) {
			$active_tab = 'users';
		}

		// Retrieve transient result data if a redirect just happened.
		$result = get_transient( 'ptg_result_' . get_current_user_id() );
		if ( $result ) {
			delete_transient( 'ptg_result_' . get_current_user_id() );
		}
		?>
		<div class="wrap" id="ptg-wrap">
			<h1><?php esc_html_e( 'PTA SUS Test Data Generator', 'pta-sus-test-data-generator' ); ?></h1>

			<p class="description">
				<?php esc_html_e( 'Quickly populate your development environment with realistic sign-up sheet data. For local/dev use only.', 'pta-sus-test-data-generator' ); ?>
			</p>

			<?php self::render_tab_nav( $active_tab ); ?>

			<?php if ( $result ) : ?>
				<?php self::render_result( $result ); ?>
			<?php endif; ?>

			<div id="ptg-panel-users" class="ptg-tab-panel <?php echo 'users' === $active_tab ? 'ptg-panel-active' : ''; ?>">
				<?php self::render_users_tab(); ?>
			</div>

			<div id="ptg-panel-sheets" class="ptg-tab-panel <?php echo 'sheets' === $active_tab ? 'ptg-panel-active' : ''; ?>">
				<?php self::render_sheets_tab(); ?>
			</div>

			<div id="ptg-panel-signups" class="ptg-tab-panel <?php echo 'signups' === $active_tab ? 'ptg-panel-active' : ''; ?>">
				<?php self::render_signups_tab(); ?>
			</div>

			<div id="ptg-panel-cleanup" class="ptg-tab-panel <?php echo 'cleanup' === $active_tab ? 'ptg-panel-active' : ''; ?>">
				<?php self::render_cleanup_tab(); ?>
			</div>
		</div>
		<?php
	}

	private static function render_tab_nav( $active ) {
		$tabs = array(
			'users'   => __( '1. Users', 'pta-sus-test-data-generator' ),
			'sheets'  => __( '2. Sheets & Tasks', 'pta-sus-test-data-generator' ),
			'signups' => __( '3. Signups', 'pta-sus-test-data-generator' ),
			'cleanup' => __( '4. Cleanup', 'pta-sus-test-data-generator' ),
		);
		echo '<nav class="ptg-tab-nav">';
		foreach ( $tabs as $slug => $label ) {
			$class = ( $slug === $active ) ? ' ptg-tab-active' : '';
			printf(
				'<a href="#" data-tab="%s" class="%s">%s</a>',
				esc_attr( $slug ),
				esc_attr( ltrim( $class ) ),
				esc_html( $label )
			);
		}
		echo '</nav>';
	}

	// -----------------------------------------------------------------------
	// Tab: Users
	// -----------------------------------------------------------------------

	private static function render_users_tab() {
		$admin_url = admin_url( 'admin-post.php' );
		?>
		<form method="post" action="<?php echo esc_url( $admin_url ); ?>">
			<?php wp_nonce_field( self::NONCE_ACTION ); ?>
			<input type="hidden" name="action" value="ptg_generate_users">
			<input type="hidden" name="ptg_tab"  value="users">

			<table class="form-table ptg-form-table">
				<tr>
					<th><label for="ptg-managers"><?php esc_html_e( '# of Manager-role users', 'pta-sus-test-data-generator' ); ?></label></th>
					<td><input type="number" id="ptg-managers" name="ptg_count_manager" value="2" min="0" max="50"></td>
				</tr>
				<tr>
					<th><label for="ptg-authors"><?php esc_html_e( '# of Author-role users', 'pta-sus-test-data-generator' ); ?></label></th>
					<td><input type="number" id="ptg-authors" name="ptg_count_author" value="3" min="0" max="50"></td>
				</tr>
				<tr>
					<th><label for="ptg-subscribers"><?php esc_html_e( '# of Subscriber-role users', 'pta-sus-test-data-generator' ); ?></label></th>
					<td><input type="number" id="ptg-subscribers" name="ptg_count_subscriber" value="10" min="0" max="100"></td>
				</tr>
			</table>

			<?php submit_button( __( 'Generate Users', 'pta-sus-test-data-generator' ), 'primary', 'ptg_submit_users' ); ?>
		</form>
		<?php
	}

	// -----------------------------------------------------------------------
	// Tab: Sheets & Tasks
	// -----------------------------------------------------------------------

	private static function render_sheets_tab() {
		$admin_url = admin_url( 'admin-post.php' );
		$presets   = array(
			'bake_sale'      => __( 'Bake Sale', 'pta-sus-test-data-generator' ),
			'carnival'       => __( 'School Carnival', 'pta-sus-test-data-generator' ),
			'committee'      => __( 'Committee Meetings', 'pta-sus-test-data-generator' ),
			'volunteer_fair' => __( 'Volunteer Fair', 'pta-sus-test-data-generator' ),
			'random'         => __( 'Randomize', 'pta-sus-test-data-generator' ),
		);
		$sheet_types = array(
			''          => __( '— Match Preset —', 'pta-sus-test-data-generator' ),
			'Single'    => __( 'Single', 'pta-sus-test-data-generator' ),
			'Multi-Day' => __( 'Multi-Day', 'pta-sus-test-data-generator' ),
			'Recurring' => __( 'Recurring', 'pta-sus-test-data-generator' ),
			'Ongoing'   => __( 'Ongoing', 'pta-sus-test-data-generator' ),
			'random'    => __( 'Random', 'pta-sus-test-data-generator' ),
		);
		?>
		<form method="post" action="<?php echo esc_url( $admin_url ); ?>">
			<?php wp_nonce_field( self::NONCE_ACTION ); ?>
			<input type="hidden" name="action"  value="ptg_generate_sheets">
			<input type="hidden" name="ptg_tab" value="sheets">

			<table class="form-table ptg-form-table">
				<tr>
					<th><label for="ptg-preset"><?php esc_html_e( 'Scenario preset', 'pta-sus-test-data-generator' ); ?></label></th>
					<td>
						<select id="ptg-preset" name="ptg_preset">
							<?php foreach ( $presets as $val => $label ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="ptg-sheet-count"><?php esc_html_e( 'Number of sheets', 'pta-sus-test-data-generator' ); ?></label></th>
					<td><input type="number" id="ptg-sheet-count" name="ptg_count" value="3" min="1" max="20"></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Tasks per sheet', 'pta-sus-test-data-generator' ); ?></th>
					<td>
						<span class="ptg-range-group">
							<label for="ptg-tasks-min"><?php esc_html_e( 'Min', 'pta-sus-test-data-generator' ); ?></label>
							<input type="number" id="ptg-tasks-min" name="ptg_tasks_min" value="2" min="1" max="20" style="width:60px;">
							<label for="ptg-tasks-max"><?php esc_html_e( 'Max', 'pta-sus-test-data-generator' ); ?></label>
							<input type="number" id="ptg-tasks-max" name="ptg_tasks_max" value="5" min="1" max="20" style="width:60px;">
						</span>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Date range', 'pta-sus-test-data-generator' ); ?></th>
					<td>
						<span class="ptg-range-group">
							<?php esc_html_e( 'Start', 'pta-sus-test-data-generator' ); ?>
							<input type="number" name="ptg_start_weeks" value="1" min="0" max="52" style="width:60px;">
							<?php esc_html_e( 'week(s) from today, span', 'pta-sus-test-data-generator' ); ?>
							<input type="number" name="ptg_span_weeks" value="4" min="1" max="52" style="width:60px;">
							<?php esc_html_e( 'week(s)', 'pta-sus-test-data-generator' ); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th><label for="ptg-sheet-type"><?php esc_html_e( 'Sheet type override', 'pta-sus-test-data-generator' ); ?></label></th>
					<td>
						<select id="ptg-sheet-type" name="ptg_type_override" disabled>
							<?php foreach ( $sheet_types as $val => $label ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Only available when preset is "Randomize".', 'pta-sus-test-data-generator' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Generate Sheets & Tasks', 'pta-sus-test-data-generator' ), 'primary', 'ptg_submit_sheets' ); ?>
		</form>
		<?php
	}

	// -----------------------------------------------------------------------
	// Tab: Signups
	// -----------------------------------------------------------------------

	private static function render_signups_tab() {
		$admin_url   = admin_url( 'admin-post.php' );
		$sheet_count = count( PTG_Tracker::get_sheet_ids() );
		?>
		<form method="post" action="<?php echo esc_url( $admin_url ); ?>">
			<?php wp_nonce_field( self::NONCE_ACTION ); ?>
			<input type="hidden" name="action"  value="ptg_generate_signups">
			<input type="hidden" name="ptg_tab" value="signups">

			<?php if ( 0 === $sheet_count ) : ?>
				<div class="ptg-notice-warning">
					<?php esc_html_e( 'No generated sheets found. Use the "Sheets & Tasks" tab first.', 'pta-sus-test-data-generator' ); ?>
				</div>
			<?php else : ?>
				<p class="description">
					<?php
					printf(
						/* translators: %d = number of sheets */
						esc_html__( 'Signups will be spread across the %d tracked sheet(s) created in the Sheets & Tasks tab.', 'pta-sus-test-data-generator' ),
						$sheet_count
					);
					?>
				</p>
			<?php endif; ?>

			<table class="form-table ptg-form-table">
				<tr>
					<th><label for="ptg-fill-range"><?php esc_html_e( 'Fill rate', 'pta-sus-test-data-generator' ); ?></label></th>
					<td>
						<span class="ptg-range-group">
							<input type="range" id="ptg-fill-range" min="0" max="100" value="60">
							<input type="number" id="ptg-fill-number" name="ptg_fill_rate" value="60" min="0" max="100" style="width:64px;">
							<span><?php esc_html_e( '% of available spots', 'pta-sus-test-data-generator' ); ?></span>
							<strong id="ptg-fill-display" class="ptg-fill-display">60%</strong>
						</span>
					</td>
				</tr>
				<tr>
					<th><label for="ptg-user-pct"><?php esc_html_e( 'User mix', 'pta-sus-test-data-generator' ); ?></label></th>
					<td>
						<span class="ptg-range-group">
							<input type="number" id="ptg-user-pct" name="ptg_user_pct" value="50" min="0" max="100" style="width:64px;">
							<span>
								<?php esc_html_e( '% from generated test users,', 'pta-sus-test-data-generator' ); ?>
								<strong id="ptg-guest-pct">50</strong>
								<?php esc_html_e( '% guest signups', 'pta-sus-test-data-generator' ); ?>
							</span>
						</span>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Generate Signups', 'pta-sus-test-data-generator' ), 'primary', 'ptg_submit_signups' ); ?>
		</form>
		<?php
	}

	// -----------------------------------------------------------------------
	// Tab: Cleanup
	// -----------------------------------------------------------------------

	private static function render_cleanup_tab() {
		$summary   = PTG_Tracker::get_summary();
		$admin_url = admin_url( 'admin-post.php' );
		?>
		<h2><?php esc_html_e( 'Generated Data Summary', 'pta-sus-test-data-generator' ); ?></h2>

		<div class="ptg-summary-boxes">
			<div class="ptg-summary-box">
				<div class="ptg-count"><?php echo absint( $summary['users'] ); ?></div>
				<div class="ptg-label"><?php esc_html_e( 'Users', 'pta-sus-test-data-generator' ); ?></div>
			</div>
			<div class="ptg-summary-box">
				<div class="ptg-count"><?php echo absint( $summary['sheets'] ); ?></div>
				<div class="ptg-label"><?php esc_html_e( 'Sheets', 'pta-sus-test-data-generator' ); ?></div>
			</div>
			<div class="ptg-summary-box">
				<div class="ptg-count"><?php echo absint( $summary['tasks'] ); ?></div>
				<div class="ptg-label"><?php esc_html_e( 'Tasks', 'pta-sus-test-data-generator' ); ?></div>
			</div>
			<div class="ptg-summary-box">
				<div class="ptg-count"><?php echo absint( $summary['signups'] ); ?></div>
				<div class="ptg-label"><?php esc_html_e( 'Signups', 'pta-sus-test-data-generator' ); ?></div>
			</div>
		</div>

		<!-- Delete Users -->
		<div class="ptg-delete-section">
			<h3><?php esc_html_e( 'Test Users', 'pta-sus-test-data-generator' ); ?></h3>
			<p><?php esc_html_e( 'Removes all tracked test users from WordPress.', 'pta-sus-test-data-generator' ); ?></p>
			<form method="post" action="<?php echo esc_url( $admin_url ); ?>">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<input type="hidden" name="action"      value="ptg_delete_data">
				<input type="hidden" name="ptg_tab"     value="cleanup">
				<input type="hidden" name="ptg_delete"  value="users">
				<input type="submit"
				       class="button button-secondary ptg-delete-confirm"
				       value="<?php esc_attr_e( 'Delete Test Users', 'pta-sus-test-data-generator' ); ?>"
				       data-confirm="<?php esc_attr_e( 'Delete all tracked test users? This cannot be undone.', 'pta-sus-test-data-generator' ); ?>">
			</form>
		</div>

		<!-- Delete Sheets -->
		<div class="ptg-delete-section">
			<h3><?php esc_html_e( 'Test Sheets, Tasks & Signups', 'pta-sus-test-data-generator' ); ?></h3>
			<p><?php esc_html_e( 'Removes all tracked sheets (cascades to tasks and signups on those sheets).', 'pta-sus-test-data-generator' ); ?></p>
			<form method="post" action="<?php echo esc_url( $admin_url ); ?>">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<input type="hidden" name="action"      value="ptg_delete_data">
				<input type="hidden" name="ptg_tab"     value="cleanup">
				<input type="hidden" name="ptg_delete"  value="sheets">
				<input type="submit"
				       class="button button-secondary ptg-delete-confirm"
				       value="<?php esc_attr_e( 'Delete Test Sheets & Signups', 'pta-sus-test-data-generator' ); ?>"
				       data-confirm="<?php esc_attr_e( 'Delete all tracked sheets, tasks, and signups? This cannot be undone.', 'pta-sus-test-data-generator' ); ?>">
			</form>
		</div>

		<!-- Delete All -->
		<div class="ptg-delete-all-section">
			<h3><?php esc_html_e( 'Delete ALL Test Data', 'pta-sus-test-data-generator' ); ?></h3>
			<p><?php esc_html_e( 'Removes all tracked users, sheets, tasks, and signups in one operation.', 'pta-sus-test-data-generator' ); ?></p>
			<form method="post" action="<?php echo esc_url( $admin_url ); ?>">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<input type="hidden" name="action"      value="ptg_delete_data">
				<input type="hidden" name="ptg_tab"     value="cleanup">
				<input type="hidden" name="ptg_delete"  value="all">
				<input type="submit"
				       id="ptg-delete-all-btn"
				       class="button button-primary"
				       style="background:#d63638;border-color:#d63638;"
				       value="<?php esc_attr_e( 'Delete ALL Test Data', 'pta-sus-test-data-generator' ); ?>">
			</form>
		</div>
		<?php
	}

	// -----------------------------------------------------------------------
	// Result renderer
	// -----------------------------------------------------------------------

	private static function render_result( $result ) {
		if ( empty( $result ) || ! isset( $result['type'] ) ) {
			return;
		}
		echo '<div class="notice notice-success is-dismissible ptg-result">';
		switch ( $result['type'] ) {
			case 'users':
				self::render_users_result( $result );
				break;
			case 'sheets':
				self::render_sheets_result( $result );
				break;
			case 'signups':
				self::render_signups_result( $result );
				break;
			case 'delete':
				self::render_delete_result( $result );
				break;
		}
		if ( ! empty( $result['errors'] ) ) {
			echo '<ul style="color:#d63638;margin-top:10px;">';
			foreach ( $result['errors'] as $err ) {
				echo '<li>' . esc_html( $err ) . '</li>';
			}
			echo '</ul>';
		}
		echo '</div>';
	}

	private static function render_users_result( $result ) {
		$created = isset( $result['created'] ) ? $result['created'] : array();
		echo '<h3>' . sprintf(
			/* translators: %d = count */
			esc_html__( 'Created %d user(s)', 'pta-sus-test-data-generator' ),
			count( $created )
		) . '</h3>';
		if ( ! empty( $created ) ) {
			echo '<table><thead><tr><th>' . esc_html__( 'Username', 'pta-sus-test-data-generator' ) . '</th><th>' . esc_html__( 'Display Name', 'pta-sus-test-data-generator' ) . '</th><th>' . esc_html__( 'Role', 'pta-sus-test-data-generator' ) . '</th><th>' . esc_html__( 'Email', 'pta-sus-test-data-generator' ) . '</th></tr></thead><tbody>';
			foreach ( $created as $u ) {
				printf(
					'<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
					esc_html( $u['username'] ),
					esc_html( $u['name'] ),
					esc_html( $u['role'] ),
					esc_html( $u['email'] )
				);
			}
			echo '</tbody></table>';
			echo '<div class="ptg-password-note">' . sprintf(
				/* translators: %s = password */
				esc_html__( 'Temporary password for all generated users: %s', 'pta-sus-test-data-generator' ),
				'<code>' . esc_html( PTG_User_Generator::TEST_PASSWORD ) . '</code>'
			) . '</div>';
		}
	}

	private static function render_sheets_result( $result ) {
		$sheets = isset( $result['sheets'] ) ? $result['sheets'] : array();
		echo '<h3>' . sprintf(
			/* translators: %d = count */
			esc_html__( 'Created %d sheet(s)', 'pta-sus-test-data-generator' ),
			count( $sheets )
		) . '</h3>';
		if ( ! empty( $sheets ) ) {
			echo '<table><thead><tr><th>' . esc_html__( 'Title', 'pta-sus-test-data-generator' ) . '</th><th>' . esc_html__( 'Type', 'pta-sus-test-data-generator' ) . '</th><th>' . esc_html__( 'Tasks Created', 'pta-sus-test-data-generator' ) . '</th></tr></thead><tbody>';
			foreach ( $sheets as $sh ) {
				printf(
					'<tr><td>%s</td><td>%s</td><td>%d</td></tr>',
					esc_html( $sh['title'] ),
					esc_html( $sh['type'] ),
					absint( $sh['task_count'] )
				);
			}
			echo '</tbody></table>';
		}
	}

	private static function render_signups_result( $result ) {
		echo '<h3>' . sprintf(
			/* translators: %d = count */
			esc_html__( 'Created %d signup(s)', 'pta-sus-test-data-generator' ),
			absint( $result['total'] ?? 0 )
		) . '</h3>';
		if ( ! empty( $result['skipped'] ) ) {
			echo '<p>' . sprintf(
				/* translators: %d = count */
				esc_html__( '%d spot(s) skipped (already full or rejected by validation).', 'pta-sus-test-data-generator' ),
				absint( $result['skipped'] )
			) . '</p>';
		}
		if ( ! empty( $result['by_sheet'] ) ) {
			echo '<table><thead><tr><th>' . esc_html__( 'Sheet ID', 'pta-sus-test-data-generator' ) . '</th><th>' . esc_html__( 'Signups Created', 'pta-sus-test-data-generator' ) . '</th></tr></thead><tbody>';
			foreach ( $result['by_sheet'] as $sheet_id => $count ) {
				printf(
					'<tr><td>%d</td><td>%d</td></tr>',
					absint( $sheet_id ),
					absint( $count )
				);
			}
			echo '</tbody></table>';
		}
	}

	private static function render_delete_result( $result ) {
		echo '<h3>' . esc_html__( 'Deletion complete', 'pta-sus-test-data-generator' ) . '</h3>';
		if ( isset( $result['users'] ) ) {
			echo '<p>' . sprintf(
				/* translators: %d = count */
				esc_html__( 'Deleted %d user(s).', 'pta-sus-test-data-generator' ),
				absint( $result['users'] )
			) . '</p>';
		}
		if ( isset( $result['sheets'] ) ) {
			echo '<p>' . sprintf(
				/* translators: %d = count */
				esc_html__( 'Deleted %d sheet(s) (including their tasks and signups).', 'pta-sus-test-data-generator' ),
				absint( $result['sheets'] )
			) . '</p>';
		}
	}

	// -----------------------------------------------------------------------
	// POST handlers
	// -----------------------------------------------------------------------

	public static function handle_generate_users() {
		check_admin_referer( self::NONCE_ACTION );
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'pta-sus-test-data-generator' ) );
		}

		$counts = array(
			'manager'    => absint( $_POST['ptg_count_manager']    ?? 0 ),
			'author'     => absint( $_POST['ptg_count_author']     ?? 0 ),
			'subscriber' => absint( $_POST['ptg_count_subscriber'] ?? 0 ),
		);

		$result         = PTG_User_Generator::generate( $counts );
		$result['type'] = 'users';

		self::store_result( $result );
		self::redirect_back( 'users' );
	}

	public static function handle_generate_sheets() {
		check_admin_referer( self::NONCE_ACTION );
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'pta-sus-test-data-generator' ) );
		}

		$options = array(
			'preset'        => sanitize_key( $_POST['ptg_preset']        ?? 'random' ),
			'count'         => absint( $_POST['ptg_count']               ?? 3 ),
			'tasks_min'     => absint( $_POST['ptg_tasks_min']           ?? 2 ),
			'tasks_max'     => absint( $_POST['ptg_tasks_max']           ?? 5 ),
			'start_weeks'   => absint( $_POST['ptg_start_weeks']         ?? 1 ),
			'span_weeks'    => absint( $_POST['ptg_span_weeks']          ?? 4 ),
			'type_override' => sanitize_key( $_POST['ptg_type_override'] ?? '' ),
		);

		$result         = PTG_Sheet_Generator::generate( $options );
		$result['type'] = 'sheets';

		self::store_result( $result );
		self::redirect_back( 'sheets' );
	}

	public static function handle_generate_signups() {
		check_admin_referer( self::NONCE_ACTION );
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'pta-sus-test-data-generator' ) );
		}

		$fill_raw = isset( $_POST['ptg_fill_rate'] ) ? floatval( $_POST['ptg_fill_rate'] ) : 60.0;

		$options = array(
			'fill_rate' => $fill_raw / 100.0,
			'user_pct'  => absint( $_POST['ptg_user_pct'] ?? 50 ),
		);

		$result         = PTG_Signup_Generator::generate( $options );
		$result['type'] = 'signups';

		self::store_result( $result );
		self::redirect_back( 'signups' );
	}

	public static function handle_delete_data() {
		check_admin_referer( self::NONCE_ACTION );
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'pta-sus-test-data-generator' ) );
		}

		$what   = sanitize_key( $_POST['ptg_delete'] ?? 'all' );
		$result = array( 'type' => 'delete' );

		switch ( $what ) {
			case 'users':
				$result['users'] = PTG_Tracker::delete_users();
				break;
			case 'sheets':
				$result['sheets'] = PTG_Tracker::delete_sheets();
				break;
			case 'all':
			default:
				$deleted          = PTG_Tracker::delete_all();
				$result['users']  = $deleted['users'];
				$result['sheets'] = $deleted['sheets'];
				break;
		}

		self::store_result( $result );
		self::redirect_back( 'cleanup' );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private static function store_result( $result ) {
		set_transient( 'ptg_result_' . get_current_user_id(), $result, 60 );
	}

	private static function redirect_back( $tab ) {
		$url = add_query_arg(
			array(
				'page'    => self::PARENT_SLUG . self::PAGE_SLUG,
				'ptg_tab' => $tab,
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
