<?php
/**
 * Ligase Entities View
 *
 * Entity management: Wikidata search, author E-E-A-T scores,
 * and entity graph overview.
 *
 * @package Ligase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$score_calculator = new Ligase_Score();

$authors = get_users( array(
	'has_published_posts' => true,
	'orderby'            => 'display_name',
	'order'              => 'ASC',
) );
?>

<h1><?php esc_html_e( 'Ligase — Encje', 'ligase' ); ?></h1>

<!-- Wikidata Search -->
<div class="ligase-card ligase-card-wide">
	<h2><?php esc_html_e( 'Wyszukiwanie Wikidata', 'ligase' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Wyszukaj encje w Wikidata, aby powiazac je z autorami lub organizacja (sameAs).', 'ligase' ); ?>
	</p>
	<div class="ligase-wikidata-search">
		<input type="text" id="ligase-wikidata-query" placeholder="<?php esc_attr_e( 'Wpisz nazwe osoby, organizacji lub tematu...', 'ligase' ); ?>" class="regular-text" />
		<button type="button" class="button button-primary" id="ligase-wikidata-btn">
			<?php esc_html_e( 'Szukaj', 'ligase' ); ?>
		</button>
	</div>
	<div id="ligase-wikidata-results" style="margin-top: 12px;"></div>
</div>

<!-- Author E-E-A-T Scores -->
<div class="ligase-card ligase-card-wide">
	<h2><?php esc_html_e( 'E-E-A-T Score autorow', 'ligase' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Ocena kompletnosci profilu autora pod katem sygnalow E-E-A-T (Experience, Expertise, Authoritativeness, Trustworthiness).', 'ligase' ); ?>
	</p>

	<?php if ( ! empty( $authors ) ) : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Autor', 'ligase' ); ?></th>
					<th style="width:100px;"><?php esc_html_e( 'E-E-A-T Score', 'ligase' ); ?></th>
					<th style="width:100px;"><?php esc_html_e( 'Rola', 'ligase' ); ?></th>
					<th><?php esc_html_e( 'Brakujace elementy', 'ligase' ); ?></th>
					<th style="width:100px;"><?php esc_html_e( 'Akcje', 'ligase' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $authors as $author ) : ?>
					<?php
					$author_score = $score_calculator->calculate_for_author( $author->ID );
					$a_score      = $author_score['score'];
					$a_recs       = $author_score['recommendations'];

					if ( $a_score >= 70 ) {
						$a_class = 'ligase-score-good';
					} elseif ( $a_score >= 40 ) {
						$a_class = 'ligase-score-warn';
					} else {
						$a_class = 'ligase-score-bad';
					}
					?>
					<tr>
						<td>
							<?php echo get_avatar( $author->ID, 24 ); ?>
							<?php echo esc_html( $author->display_name ); ?>
						</td>
						<td>
							<span class="ligase-score-badge <?php echo esc_attr( $a_class ); ?>">
								<?php echo esc_html( $a_score ); ?>/100
							</span>
						</td>
						<td><?php echo esc_html( implode( ', ', $author->roles ) ); ?></td>
						<td>
							<?php if ( ! empty( $a_recs ) ) : ?>
								<ul class="ligase-issues-list">
									<?php foreach ( array_slice( $a_recs, 0, 3 ) as $rec ) : ?>
										<li><?php echo esc_html( $rec ); ?></li>
									<?php endforeach; ?>
									<?php if ( count( $a_recs ) > 3 ) : ?>
										<li><em>+<?php echo esc_html( count( $a_recs ) - 3 ); ?> <?php esc_html_e( 'wiecej', 'ligase' ); ?></em></li>
									<?php endif; ?>
								</ul>
							<?php else : ?>
								<span class="ligase-badge ligase-badge-pass"><?php esc_html_e( 'Kompletny', 'ligase' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<a href="<?php echo esc_url( get_edit_user_link( $author->ID ) ); ?>" class="button button-small">
								<?php esc_html_e( 'Edytuj', 'ligase' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php esc_html_e( 'Brak autorow z opublikowanymi postami.', 'ligase' ); ?></p>
	<?php endif; ?>
</div>
