<?php
/**
 * Ligase Auditor View
 *
 * Schema auditor panel: detect conflicting plugins,
 * scan existing schema quality, and batch-replace weak schema.
 *
 * @package Ligase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$auditor  = new Ligase_Auditor();
$detected = $auditor->get_detected_plugins();
$options  = get_option( 'ligase_options', array() );
?>

<h1><?php esc_html_e( 'Ligase — Audytor schema', 'ligase' ); ?></h1>

<!-- Plugin Conflicts -->
<div class="ligase-card ligase-card-wide">
	<h2><?php esc_html_e( 'Wykryte wtyczki SEO', 'ligase' ); ?></h2>
	<?php if ( ! empty( $detected ) ) : ?>
		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Wtyczka', 'ligase' ); ?></th>
					<th style="width:120px;"><?php esc_html_e( 'Wersja', 'ligase' ); ?></th>
					<th style="width:120px;"><?php esc_html_e( 'Status', 'ligase' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $detected as $name => $version ) : ?>
					<tr>
						<td><?php echo esc_html( $name ); ?></td>
						<td><?php echo esc_html( $version ); ?></td>
						<td>
							<span class="ligase-badge ligase-badge-warn"><?php esc_html_e( 'Aktywna', 'ligase' ); ?></span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description">
			<?php esc_html_e( 'Te wtyczki moga generowac wlasna schema. Wlacz tryb standalone w ustawieniach, aby Ligase zastepowala ich schema.', 'ligase' ); ?>
		</p>
	<?php else : ?>
		<p class="ligase-notice ligase-notice-success">
			<?php esc_html_e( 'Nie wykryto zadnych konfliktujacych wtyczek SEO.', 'ligase' ); ?>
		</p>
	<?php endif; ?>
</div>

<!-- Auditor Controls -->
<div class="ligase-card ligase-card-wide">
	<h2><?php esc_html_e( 'Skanowanie i naprawa', 'ligase' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Audytor skanuje istniejaca schema na Twoich postach, ocenia ja w skali 0-100, i moze zastapic slaba schema lepsza wersja.', 'ligase' ); ?>
	</p>

	<div class="ligase-auditor-controls">
		<div class="ligase-control-group">
			<label for="ligase-audit-threshold">
				<?php esc_html_e( 'Prog zastepowania (score):', 'ligase' ); ?>
			</label>
			<input type="number" id="ligase-audit-threshold" value="50" min="0" max="100" step="5" class="small-text" />
		</div>

		<div class="ligase-control-group">
			<label for="ligase-audit-mode">
				<?php esc_html_e( 'Tryb:', 'ligase' ); ?>
			</label>
			<select id="ligase-audit-mode">
				<option value="scan"><?php esc_html_e( 'Tylko skan (nie zmienia nic)', 'ligase' ); ?></option>
				<option value="supplement"><?php esc_html_e( 'Uzupelniaj (dodaje brakujace pola)', 'ligase' ); ?></option>
				<option value="replace"><?php esc_html_e( 'Zastap (pelna zamiana)', 'ligase' ); ?></option>
			</select>
		</div>
	</div>

	<div class="ligase-actions" style="margin-top: 16px;">
		<button type="button" class="button button-primary" id="ligase-run-audit">
			<?php esc_html_e( 'Uruchom audyt', 'ligase' ); ?>
		</button>
		<span id="ligase-audit-status" class="ligase-status-text"></span>
	</div>
</div>

<!-- Audit Results -->
<div class="ligase-card ligase-card-wide" id="ligase-audit-results" style="display:none;">
	<h2><?php esc_html_e( 'Wyniki audytu', 'ligase' ); ?></h2>
	<div id="ligase-audit-summary" class="ligase-stats-row" style="margin-bottom:16px;"></div>
	<table class="wp-list-table widefat fixed striped" id="ligase-audit-table">
		<thead>
			<tr>
				<th style="width:40px;"><input type="checkbox" id="ligase-audit-check-all" /></th>
				<th><?php esc_html_e( 'Post', 'ligase' ); ?></th>
				<th style="width:80px;"><?php esc_html_e( 'Score', 'ligase' ); ?></th>
				<th><?php esc_html_e( 'Problemy', 'ligase' ); ?></th>
				<th style="width:120px;"><?php esc_html_e( 'Zrodlo', 'ligase' ); ?></th>
			</tr>
		</thead>
		<tbody></tbody>
	</table>

	<div class="ligase-actions" style="margin-top: 16px;">
		<button type="button" class="button button-primary" id="ligase-apply-audit">
			<?php esc_html_e( 'Zastosuj naprawy dla zaznaczonych', 'ligase' ); ?>
		</button>
	</div>
</div>
