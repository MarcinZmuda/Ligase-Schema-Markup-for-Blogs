<?php
/**
 * Ligase Settings View
 *
 * WordPress Settings API form for organization data,
 * social links, and plugin behavior options.
 *
 * @package Ligase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h1><?php esc_html_e( 'Ligase — Ustawienia', 'ligase' ); ?></h1>

<form method="post" action="options.php">
	<?php
	settings_fields( Ligase_Settings::GROUP );
	do_settings_sections( 'ligase-ustawienia' );
	submit_button( __( 'Zapisz ustawienia', 'ligase' ) );
	?>
</form>
