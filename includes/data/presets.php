<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preset scenario definitions for the Sheet Generator.
 *
 * Each preset defines:
 *   sheet_type    - PTA sheet type string
 *   titles        - pool of sheet title strings
 *   tasks         - pool of task title strings
 *   need_details  - 'YES' or 'NO'
 *   details_text  - label shown to volunteers if need_details = YES
 */

$ptg_presets = array(

	'bake_sale' => array(
		'sheet_type'   => 'Single',
		'titles'       => array(
			'Spring Bake Sale',
			'Fall Bake Sale',
			'Holiday Fundraiser Bake Sale',
			'Winter Bake Sale',
		),
		'tasks'        => array(
			'Chocolate Chip Cookies (2 doz)',
			'Brownies (1 pan)',
			'Cupcakes (1 doz)',
			'Lemon Bars (1 pan)',
			'Snickerdoodles (2 doz)',
			'Setup / Cleanup',
			'Cash Box / Cashier',
			'Packaging & Labeling',
		),
		'need_details' => 'NO',
		'details_text' => '',
	),

	'carnival' => array(
		'sheet_type'   => 'Multi-Day',
		'titles'       => array(
			'Spring Carnival',
			'Fall Festival',
			'Field Day',
			'School Fun Fair',
		),
		'tasks'        => array(
			'Game Booth',
			'Food Booth',
			'Ticket Sales',
			'First Aid Station',
			'Parking Attendant',
			'Face Painting',
			'Bounce House Monitor',
			'Information Booth',
			'Setup Crew',
			'Cleanup Crew',
		),
		'need_details' => 'NO',
		'details_text' => '',
	),

	'committee' => array(
		'sheet_type'   => 'Recurring',
		'titles'       => array(
			'PTA Executive Committee',
			'Fundraising Committee',
			'Events Committee',
			'Curriculum Committee',
			'Safety Committee',
		),
		'tasks'        => array(
			'Meeting Facilitator',
			'Minutes Taker',
			'Hospitality',
			'Agenda Prep',
			'Treasurer Report',
			'Communications Lead',
		),
		'need_details' => 'YES',
		'details_text' => 'Role Notes',
	),

	'volunteer_fair' => array(
		'sheet_type'   => 'Ongoing',
		'titles'       => array(
			'Volunteer Opportunities Fair',
			'Back to School Fair',
			'Community Resource Fair',
		),
		'tasks'        => array(
			'Registration Desk',
			'Tour Guide',
			'Information Table',
			'Photography',
			'Social Media Coverage',
			'Greeter',
			'Refreshment Station',
		),
		'need_details' => 'NO',
		'details_text' => '',
	),

	'random' => array(
		'sheet_type'   => 'random',
		'titles'       => array(
			'Annual %s Event',
			'Community %s Drive',
			'School %s Day',
			'PTA %s Fundraiser',
			'Neighborhood %s Fair',
		),
		// Adjectives and nouns used for random title generation (see PTG_Sheet_Generator)
		'title_adjectives' => array(
			'Spring', 'Fall', 'Winter', 'Summer', 'Annual', 'Community',
			'Family', 'School', 'Weekend', 'Friday',
		),
		'title_nouns' => array(
			'Fundraiser', 'Cleanup', 'Celebration', 'Fair', 'Drive',
			'Workshop', 'Potluck', 'Social', 'Showcase', 'Festival',
		),
		'tasks'        => array(
			'Volunteer Slot %d',
		),
		'need_details' => 'random',
		'details_text' => 'Additional Notes',
	),

);
