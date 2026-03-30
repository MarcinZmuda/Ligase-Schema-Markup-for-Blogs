<?php
/**
 * Ligase Tools View
 *
 * Utility tools: importer, validator, auto-repair, cache, health report, import/export.
 *
 * @package Ligase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$importer = new Ligase_Importer();
$sources  = $importer->detect_sources();

$last_report = class_exists( 'Ligase_Health_Report' ) ? Ligase_Health_Report::get_last_report() : false;
$opts        = get_option( 'ligase_options', [] );
?>

<h1><?php esc_html_e( 'Ligase — Narzedzia', 'ligase' ); ?></h1>

<div id="ligase-tools-notice" class="ligase-notice" style="display:none;"></div>

<!-- ================================================================== -->
<!-- Import from SEO Plugins -->
<!-- ================================================================== -->
<div class="ligase-card ligase-card-wide">
	<h2><?php esc_html_e( 'Import z wtyczek SEO', 'ligase' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Jednym kliknieciem zaimportuj ustawienia (nazwa, logo, social links, dane autorow) z Yoast SEO, Rank Math lub All in One SEO.', 'ligase' ); ?>
	</p>

	<div class="ligase-import-sources" style="margin-top: 12px;">
		<?php foreach ( $sources as $key => $source ) : ?>
			<div class="ligase-import-source" style="display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
				<strong style="min-width: 140px;"><?php echo esc_html( $source['name'] ); ?></strong>
				<?php if ( $source['available'] ) : ?>
					<span class="ligase-badge ligase-badge-pass"><?php esc_html_e( 'Dane dostepne', 'ligase' ); ?></span>
					<button type="button" class="button button-primary ligase-import-seo-btn" data-source="<?php echo esc_attr( $key ); ?>">
						<?php esc_html_e( 'Importuj', 'ligase' ); ?>
					</button>
				<?php else : ?>
					<span class="ligase-badge" style="background: #f3f4f6; color: #9ca3af;"><?php esc_html_e( 'Brak danych', 'ligase' ); ?></span>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<div id="ligase-import-seo-result" style="margin-top: 12px;"></div>
</div>

<!-- ================================================================== -->
<!-- Schema Validator -->
<!-- ================================================================== -->
<div class="ligase-card ligase-card-wide">
	<h2><?php esc_html_e( 'Walidator schema', 'ligase' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Sprawdz wygenerowany JSON-LD pod katem wymogow Google. Wybierz post i kliknij Waliduj.', 'ligase' ); ?>
	</p>
	<div style="display: flex; gap: 8px; align-items: center; margin-top: 8px;">
		<input type="number" id="ligase-validate-post-id" placeholder="<?php esc_attr_e( 'ID posta', 'ligase' ); ?>" class="small-text" min="1" />
		<button type="button" class="button button-primary" id="ligase-validate-btn">
			<?php esc_html_e( 'Waliduj', 'ligase' ); ?>
		</button>
	</div>
	<div id="ligase-validate-result" style="margin-top: 12px; display: none;">
		<div id="ligase-validate-summary" style="margin-bottom: 12px;"></div>
		<div id="ligase-validate-errors"></div>
		<div id="ligase-validate-warnings" style="margin-top: 8px;"></div>
		<details style="margin-top: 12px;">
			<summary style="cursor: pointer; font-weight: 600;"><?php esc_html_e( 'Podglad JSON-LD', 'ligase' ); ?></summary>
			<pre id="ligase-validate-json" class="ligase-json-preview" style="max-height: 400px; overflow: auto; margin-top: 8px;"></pre>
		</details>
	</div>
</div>

<!-- ================================================================== -->
<!-- Auto Repair -->
<!-- ================================================================== -->
<div class="ligase-card ligase-card-wide">
	<h2><?php esc_html_e( 'Auto-naprawa', 'ligase' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Automatyczne naprawy typowych problemow ze schema na wszystkich opublikowanych postach.', 'ligase' ); ?>
	</p>

	<div class="ligase-repair-options">
		<label class="ligase-checkbox-row">
			<input type="checkbox" name="ligase_repair[]" value="fix_dates" checked />
			<?php esc_html_e( 'Napraw formaty dat (ISO 8601)', 'ligase' ); ?>
		</label>
		<label class="ligase-checkbox-row">
			<input type="checkbox" name="ligase_repair[]" value="truncate_headlines" checked />
			<?php esc_html_e( 'Skroc naglowki powyzej 110 znakow', 'ligase' ); ?>
		</label>
		<label class="ligase-checkbox-row">
			<input type="checkbox" name="ligase_repair[]" value="convert_article_to_blogposting" />
			<?php esc_html_e( 'Konwertuj Article na BlogPosting', 'ligase' ); ?>
		</label>
	</div>

	<div class="ligase-actions" style="margin-top: 12px;">
		<button type="button" class="button button-primary" id="ligase-run-repair">
			<?php esc_html_e( 'Uruchom naprawe', 'ligase' ); ?>
		</button>
		<span id="ligase-repair-status" class="ligase-status-text"></span>
	</div>
</div>

<!-- ================================================================== -->
<!-- Health Report -->
<!-- ================================================================== -->
<div class="ligase-card ligase-card-wide">
	<h2><?php esc_html_e( 'Raport zdrowia schema', 'ligase' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Tygodniowy email z podsumowaniem problemow ze schema. Wlacz ponizej lub uruchom recznie.', 'ligase' ); ?>
	</p>

	<form method="post" action="options.php" style="margin-bottom: 12px;">
		<?php settings_fields( 'ligase_settings_group' ); ?>
		<label class="ligase-checkbox-row">
			<input type="checkbox" name="ligase_options[health_report_enabled]" value="1" <?php checked( ! empty( $opts['health_report_enabled'] ) ); ?> />
			<?php esc_html_e( 'Wlacz tygodniowy raport email', 'ligase' ); ?>
		</label>
		<?php submit_button( __( 'Zapisz', 'ligase' ), 'secondary', 'submit', false ); ?>
	</form>

	<?php if ( $last_report ) : ?>
		<p class="description">
			<?php printf(
				esc_html__( 'Ostatni raport: %s | Score: %d/100 | Problemow: %d', 'ligase' ),
				esc_html( $last_report['date'] ?? '—' ),
				(int) ( $last_report['score'] ?? 0 ),
				(int) ( $last_report['issues'] ?? 0 )
			); ?>
		</p>
	<?php endif; ?>

	<div class="ligase-actions">
		<button type="button" class="button" id="ligase-send-health-report">
			<?php esc_html_e( 'Wyslij raport teraz', 'ligase' ); ?>
		</button>
	</div>
</div>

<!-- ================================================================== -->
<!-- Cache Management -->
<!-- ================================================================== -->
<div class="ligase-card ligase-card-wide">
	<h2><?php esc_html_e( 'Cache', 'ligase' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Wyczysc cache schema, aby wymusic ponowne generowanie JSON-LD na wszystkich stronach.', 'ligase' ); ?>
	</p>
	<div class="ligase-actions">
		<button type="button" class="button" id="ligase-clear-cache">
			<?php esc_html_e( 'Wyczysc cache schema', 'ligase' ); ?>
		</button>
	</div>
</div>

<!-- ================================================================== -->
<!-- Import / Export Settings -->
<!-- ================================================================== -->
<div class="ligase-card ligase-card-wide">
	<h2><?php esc_html_e( 'Import / Eksport ustawien Ligase', 'ligase' ); ?></h2>

	<div class="ligase-import-export">
		<div class="ligase-export-section">
			<h3><?php esc_html_e( 'Eksport', 'ligase' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Pobierz aktualne ustawienia wtyczki i dane autorow jako plik JSON.', 'ligase' ); ?>
			</p>
			<button type="button" class="button" id="ligase-export-btn">
				<?php esc_html_e( 'Eksportuj ustawienia', 'ligase' ); ?>
			</button>
		</div>

		<div class="ligase-import-section" style="margin-top: 16px;">
			<h3><?php esc_html_e( 'Import', 'ligase' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Wgraj plik JSON z ustawieniami. Uwaga: istniejace ustawienia zostana nadpisane.', 'ligase' ); ?>
			</p>
			<div class="ligase-import-controls">
				<textarea id="ligase-import-json" rows="6" class="large-text" placeholder="<?php esc_attr_e( 'Wklej JSON tutaj...', 'ligase' ); ?>"></textarea>
				<button type="button" class="button" id="ligase-import-btn" style="margin-top: 8px;">
					<?php esc_html_e( 'Importuj ustawienia', 'ligase' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>
