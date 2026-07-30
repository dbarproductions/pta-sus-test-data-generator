<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates plausible random values for Custom Fields extension fields,
 * keyed off each field's input_type. Shared by sheet/task-level field
 * generation and signup-template field generation (regular + waitlist).
 */
class PTG_Custom_Fields_Generator {

	private static $words = array(
		'apple', 'river', 'mountain', 'garden', 'window', 'purple', 'silver', 'quiet',
		'bright', 'gentle', 'wooden', 'golden', 'meadow', 'lantern', 'harbor', 'valley',
		'summer', 'winter', 'coffee', 'basket', 'ribbon', 'pencil', 'ladder', 'candle',
	);

	/**
	 * Generate a random value appropriate for the given field's input_type.
	 *
	 * @param PTAVCF_Field $field Field (or PTAVCF_Template_Field, which extends it).
	 * @return mixed String for most types, array for multiselect, bool for checkbox.
	 */
	public static function generate_value_for_field( $field ) {
		$type = $field->get_input_type();

		switch ( $type ) {
			case 'textarea':
			case 'html':
				return self::random_sentence() . ' ' . self::random_sentence();

			case 'tel':
				return self::random_phone();

			case 'email':
				return self::random_word() . '.' . self::random_word() . '.' . wp_rand( 100, 999 ) . '@example.test';

			case 'url':
				return 'https://example.test/' . self::random_word() . '-' . wp_rand( 100, 999 );

			case 'number':
				return wp_rand( 1, 100 );

			case 'zip':
				return str_pad( (string) wp_rand( 0, 99999 ), 5, '0', STR_PAD_LEFT );

			case 'checkbox':
				return (bool) wp_rand( 0, 1 );

			case 'select':
			case 'radio':
				return self::random_option_key( $field );

			case 'multiselect':
				return self::random_option_keys( $field, wp_rand( 1, 3 ) );

			case 'date':
				$days_ago = wp_rand( 0, 365 * 50 );
				return date( 'Y-m-d', current_time( 'timestamp' ) - ( $days_ago * DAY_IN_SECONDS ) );

			case 'time':
				return sprintf( '%02d:%02d', wp_rand( 0, 23 ), ( wp_rand( 0, 1 ) ? 0 : 30 ) );

			case 'text':
			default:
				return ucfirst( self::random_word() ) . ' ' . self::random_word();
		}
	}

	private static function random_word() {
		return self::$words[ array_rand( self::$words ) ];
	}

	private static function random_sentence() {
		$count = wp_rand( 5, 10 );
		$words = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$words[] = self::random_word();
		}
		return ucfirst( implode( ' ', $words ) ) . '.';
	}

	private static function random_phone() {
		return sprintf( '(%d) %d-%d', wp_rand( 200, 999 ), wp_rand( 200, 999 ), wp_rand( 1000, 9999 ) );
	}

	/**
	 * Pick one random option key from a select/radio field, excluding the blank placeholder.
	 *
	 * @param PTAVCF_Field $field
	 * @return string
	 */
	private static function random_option_key( $field ) {
		$options = self::get_option_keys( $field );
		if ( empty( $options ) ) {
			return '';
		}
		return $options[ array_rand( $options ) ];
	}

	/**
	 * Pick up to $count random option keys from a multiselect field.
	 *
	 * @param PTAVCF_Field $field
	 * @param int          $count
	 * @return array
	 */
	private static function random_option_keys( $field, $count ) {
		$options = self::get_option_keys( $field );
		if ( empty( $options ) ) {
			return array();
		}
		shuffle( $options );
		return array_slice( $options, 0, min( $count, count( $options ) ) );
	}

	private static function get_option_keys( $field ) {
		$options = $field->get_property( 'options' );
		if ( ! is_array( $options ) ) {
			return array();
		}
		$keys = array_keys( $options );
		return array_values( array_filter( $keys, function ( $key ) {
			return '' !== $key;
		} ) );
	}
}
