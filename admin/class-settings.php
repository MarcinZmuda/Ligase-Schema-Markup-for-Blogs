<?php
/**
 * Ligase Settings
 *
 * WordPress Settings API registration: sections, fields, and sanitization.
 *
 * @package Ligase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ligase_Settings {

	/**
	 * Settings group name.
	 *
	 * @var string
	 */
	const GROUP = 'ligase_settings_group';

	/**
	 * Option key in wp_options.
	 *
	 * @var string
	 */
	const KEY = 'ligase_options';

	/**
	 * Section IDs.
	 */
	const SECTION_ORG      = 'ligase_section_organization';
	const SECTION_SOCIAL   = 'ligase_section_social';
	const SECTION_BEHAVIOR = 'ligase_section_behavior';

	/**
	 * Register settings, sections, and fields. Called from admin_init.
	 *
	 * @return void
	 */
	public static function register() {
		register_setting(
			self::GROUP,
			self::KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		// ----- Section: Organization -----
		add_settings_section(
			self::SECTION_ORG,
			__( 'Organizacja', 'ligase' ),
			array( __CLASS__, 'render_section_org' ),
			'ligase-ustawienia'
		);

		self::add_field( 'org_name', __( 'Nazwa organizacji', 'ligase' ), self::SECTION_ORG, 'text' );
		self::add_field( 'org_description', __( 'Opis organizacji', 'ligase' ), self::SECTION_ORG, 'text' );
		self::add_field( 'org_logo', __( 'URL logo organizacji', 'ligase' ), self::SECTION_ORG, 'url' );
		self::add_field( 'org_email', __( 'E-mail organizacji', 'ligase' ), self::SECTION_ORG, 'email' );
		self::add_field( 'org_phone', __( 'Telefon organizacji', 'ligase' ), self::SECTION_ORG, 'text' );
		self::add_field( 'knows_about', __( 'Zna sie na (knowsAbout)', 'ligase' ), self::SECTION_ORG, 'text' );
		self::add_field( 'logo_width', __( 'Szerokosc logo (px)', 'ligase' ), self::SECTION_ORG, 'number' );
		self::add_field( 'logo_height', __( 'Wysokosc logo (px)', 'ligase' ), self::SECTION_ORG, 'number' );

		// ----- Section: Social & Entity -----
		add_settings_section(
			self::SECTION_SOCIAL,
			__( 'Social & Entity', 'ligase' ),
			array( __CLASS__, 'render_section_social' ),
			'ligase-ustawienia'
		);

		self::add_field( 'social_wikidata', __( 'Wikidata URL', 'ligase' ), self::SECTION_SOCIAL, 'url' );
		self::add_field( 'social_wikipedia', __( 'Wikipedia URL', 'ligase' ), self::SECTION_SOCIAL, 'url' );
		self::add_field( 'social_linkedin', __( 'LinkedIn URL', 'ligase' ), self::SECTION_SOCIAL, 'url' );
		self::add_field( 'social_facebook', __( 'Facebook URL', 'ligase' ), self::SECTION_SOCIAL, 'url' );
		self::add_field( 'social_twitter', __( 'Twitter / X URL', 'ligase' ), self::SECTION_SOCIAL, 'url' );
		self::add_field( 'social_youtube', __( 'YouTube URL', 'ligase' ), self::SECTION_SOCIAL, 'url' );

		// ----- Section: Behavior -----
		add_settings_section(
			self::SECTION_BEHAVIOR,
			__( 'Zachowanie', 'ligase' ),
			array( __CLASS__, 'render_section_behavior' ),
			'ligase-ustawienia'
		);

		self::add_field( 'speakable_selectors', __( 'Speakable CSS Selectors', 'ligase' ), self::SECTION_BEHAVIOR, 'text' );
		self::add_field( 'standalone_mode', __( 'Tryb standalone', 'ligase' ), self::SECTION_BEHAVIOR, 'checkbox' );
		self::add_field( 'force_output', __( 'Wymuszaj output', 'ligase' ), self::SECTION_BEHAVIOR, 'checkbox' );
		self::add_field( 'debug_mode', __( 'Tryb debug', 'ligase' ), self::SECTION_BEHAVIOR, 'checkbox' );
	}

	// -------------------------------------------------------------------------
	// Section Descriptions
	// -------------------------------------------------------------------------

	/**
	 * Render Organization section description.
	 *
	 * @return void
	 */
	public static function render_section_org() {
		echo '<p>' . esc_html__( 'Dane organizacji wykorzystywane w schema.org Organization i LocalBusiness.', 'ligase' ) . '</p>';
	}

	/**
	 * Render Social & Entity section description.
	 *
	 * @return void
	 */
	public static function render_section_social() {
		echo '<p>' . esc_html__( 'Profile spolecznosciowe i identyfikatory encji (sameAs).', 'ligase' ) . '</p>';
	}

	/**
	 * Render Behavior section description.
	 *
	 * @return void
	 */
	public static function render_section_behavior() {
		echo '<p>' . esc_html__( 'Opcje wplywajace na sposob dzialania wtyczki.', 'ligase' ) . '</p>';
	}

	// -------------------------------------------------------------------------
	// Field Helpers
	// -------------------------------------------------------------------------

	/**
	 * Shorthand for add_settings_field.
	 *
	 * @param string $id      Field key inside the option array.
	 * @param string $title   Human-readable label.
	 * @param string $section Section ID.
	 * @param string $type    Field type: text | url | email | number | checkbox.
	 * @return void
	 */
	private static function add_field( $id, $title, $section, $type ) {
		$callback = ( 'checkbox' === $type )
			? array( __CLASS__, 'render_checkbox' )
			: array( __CLASS__, 'render_field' );

		add_settings_field(
			'ligase_field_' . $id,
			$title,
			$callback,
			'ligase-ustawienia',
			$section,
			array(
				'id'        => $id,
				'type'      => $type,
				'label_for' => 'ligase_field_' . $id,
			)
		);
	}

	/**
	 * Render a standard input field (text, url, email, number).
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_field( $args ) {
		$options = get_option( self::KEY, self::defaults() );
		$id      = $args['id'];
		$type    = $args['type'];
		$value   = isset( $options[ $id ] ) ? $options[ $id ] : '';

		printf(
			'<input type="%1$s" id="%2$s" name="%3$s[%4$s]" value="%5$s" class="regular-text" %6$s />',
			esc_attr( $type ),
			esc_attr( 'ligase_field_' . $id ),
			esc_attr( self::KEY ),
			esc_attr( $id ),
			esc_attr( $value ),
			$id === 'speakable_selectors' ? 'placeholder="np. .entry-content > p:first-of-type, .post-content h2 + p"' : ''
		);

		if ( $id === 'speakable_selectors' ) {
			echo '<p class="description">'
				. esc_html__( 'Selektory CSS sekcji które mają być cytowane przez AI i asystentów głosowych. Zostaw puste aby wyłączyć Speakable. Sprawdź klasę kontenera treści swojego motywu (F12 w przeglądarce).', 'ligase' )
				. '</p>';
		}
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_checkbox( $args ) {
		$options = get_option( self::KEY, self::defaults() );
		$id      = $args['id'];
		$checked = ! empty( $options[ $id ] ) ? 'checked' : '';

		printf(
			'<input type="checkbox" id="%1$s" name="%2$s[%3$s]" value="1" %4$s />',
			esc_attr( 'ligase_field_' . $id ),
			esc_attr( self::KEY ),
			esc_attr( $id ),
			esc_attr( $checked )
		);
	}

	// -------------------------------------------------------------------------
	// Sanitization
	// -------------------------------------------------------------------------

	/**
	 * Sanitize callback for the entire option array.
	 *
	 * @param array $input Raw input from the settings form.
	 * @return array Sanitized values.
	 */
	public static function sanitize( $input ) {
		$clean = self::defaults();

		if ( ! is_array( $input ) ) {
			return $clean;
		}

		// --- Text fields ---
		$text_fields = array( 'org_name', 'org_description', 'org_phone', 'knows_about', 'speakable_selectors' );
		foreach ( $text_fields as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$clean[ $key ] = sanitize_text_field( wp_unslash( $input[ $key ] ) );
			}
		}

		// --- Email ---
		if ( isset( $input['org_email'] ) ) {
			$clean['org_email'] = sanitize_email( wp_unslash( $input['org_email'] ) );
		}

		// --- URL fields ---
		$url_fields = array(
			'org_logo',
			'social_wikidata',
			'social_wikipedia',
			'social_linkedin',
			'social_facebook',
			'social_twitter',
			'social_youtube',
		);
		foreach ( $url_fields as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$clean[ $key ] = esc_url_raw( wp_unslash( $input[ $key ] ) );
			}
		}

		// --- Numeric fields ---
		$numeric_fields = array( 'logo_width', 'logo_height' );
		foreach ( $numeric_fields as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$clean[ $key ] = absint( $input[ $key ] );
			}
		}

		// --- Checkbox fields ---
		$checkbox_fields = array( 'standalone_mode', 'force_output', 'debug_mode', 'health_report_enabled' );
		foreach ( $checkbox_fields as $key ) {
			$clean[ $key ] = ! empty( $input[ $key ] ) ? 1 : 0;
		}

		return $clean;
	}

	// -------------------------------------------------------------------------
	// Defaults
	// -------------------------------------------------------------------------

	/**
	 * Return the default option values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'org_name'         => '',
			'org_description'  => '',
			'org_logo'         => '',
			'org_email'        => '',
			'org_phone'        => '',
			'knows_about'      => '',
			'speakable_selectors' => '',
			'social_wikidata'  => '',
			'social_wikipedia' => '',
			'social_linkedin'  => '',
			'social_facebook'  => '',
			'social_twitter'   => '',
			'social_youtube'   => '',
			'standalone_mode'  => 0,
			'force_output'     => 0,
			'debug_mode'       => 0,
			'health_report_enabled' => 0,
			'logo_width'       => 0,
			'logo_height'      => 0,
		);
	}
}
