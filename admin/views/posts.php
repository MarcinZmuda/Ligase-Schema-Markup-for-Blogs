<?php
/**
 * Ligase Posts View
 *
 * Lists all published posts with their schema score,
 * type, and quick actions (scan, fix, preview JSON-LD).
 *
 * @package Ligase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$per_page = 20;

$query = new WP_Query( array(
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
	'orderby'        => 'date',
	'order'          => 'DESC',
) );

$total_posts = $query->found_posts;
$total_pages = $query->max_num_pages;

$score_calculator = new Ligase_Score();
?>

<h1><?php esc_html_e( 'Ligase — Posty', 'ligase' ); ?></h1>

<div class="ligase-toolbar">
	<button type="button" class="button button-primary" id="ligase-scan-all">
		<?php esc_html_e( 'Skanuj wszystkie', 'ligase' ); ?>
	</button>
	<button type="button" class="button" id="ligase-fix-all" data-threshold="50">
		<?php esc_html_e( 'Napraw ponizej 50 pkt', 'ligase' ); ?>
	</button>
	<button type="button" class="button" id="ligase-bulk-fix" style="display:none;">
		<?php esc_html_e( 'Napraw zaznaczone', 'ligase' ); ?>
	</button>
	<span class="ligase-toolbar-info">
		<?php printf( esc_html__( 'Znaleziono %d postow', 'ligase' ), $total_posts ); ?>
	</span>
</div>

<div id="ligase-posts-notice" class="ligase-notice" style="display:none;"></div>

<table class="wp-list-table widefat fixed striped ligase-posts-table">
	<thead>
		<tr>
			<th style="width:30px;"><input type="checkbox" id="ligase-posts-check-all" /></th>
			<th style="width:40px;"><?php esc_html_e( 'ID', 'ligase' ); ?></th>
			<th><?php esc_html_e( 'Tytul', 'ligase' ); ?></th>
			<th style="width:100px;"><?php esc_html_e( 'Schema Score', 'ligase' ); ?></th>
			<th style="width:120px;"><?php esc_html_e( 'Typ schema', 'ligase' ); ?></th>
			<th style="width:100px;"><?php esc_html_e( 'Data', 'ligase' ); ?></th>
			<th style="width:200px;"><?php esc_html_e( 'Akcje', 'ligase' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php if ( $query->have_posts() ) : ?>
			<?php while ( $query->have_posts() ) : $query->the_post(); ?>
				<?php
				$post_id     = get_the_ID();
				$post_score  = $score_calculator->calculate_for_post( $post_id );
				$score_val   = $post_score['score'];
				$schema_type = get_post_meta( $post_id, '_ligase_schema_type', true ) ?: 'Article';

				if ( $score_val >= 70 ) {
					$score_class = 'ligase-score-good';
				} elseif ( $score_val >= 40 ) {
					$score_class = 'ligase-score-warn';
				} else {
					$score_class = 'ligase-score-bad';
				}
				?>
				<tr data-post-id="<?php echo esc_attr( $post_id ); ?>">
					<td><input type="checkbox" class="ligase-post-check" value="<?php echo esc_attr( $post_id ); ?>" /></td>
					<td><?php echo esc_html( $post_id ); ?></td>
					<td>
						<a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>">
							<?php echo esc_html( get_the_title() ); ?>
						</a>
					</td>
					<td>
						<span class="ligase-score-badge <?php echo esc_attr( $score_class ); ?>">
							<?php echo esc_html( $score_val ); ?>/100
						</span>
					</td>
					<td><code><?php echo esc_html( $schema_type ); ?></code></td>
					<td><?php echo esc_html( get_the_date( 'Y-m-d' ) ); ?></td>
					<td>
						<button type="button" class="button button-small ligase-btn-scan" data-post-id="<?php echo esc_attr( $post_id ); ?>">
							<?php esc_html_e( 'Skanuj', 'ligase' ); ?>
						</button>
						<button type="button" class="button button-small ligase-btn-fix" data-post-id="<?php echo esc_attr( $post_id ); ?>">
							<?php esc_html_e( 'Napraw', 'ligase' ); ?>
						</button>
						<button type="button" class="button button-small ligase-btn-preview" data-post-id="<?php echo esc_attr( $post_id ); ?>">
							<?php esc_html_e( 'JSON-LD', 'ligase' ); ?>
						</button>
					</td>
				</tr>
			<?php endwhile; ?>
			<?php wp_reset_postdata(); ?>
		<?php else : ?>
			<tr>
				<td colspan="7"><?php esc_html_e( 'Brak opublikowanych postow.', 'ligase' ); ?></td>
			</tr>
		<?php endif; ?>
	</tbody>
</table>

<?php if ( $total_pages > 1 ) : ?>
	<div class="tablenav bottom">
		<div class="tablenav-pages">
			<?php
			$page_links = paginate_links( array(
				'base'      => add_query_arg( 'paged', '%#%' ),
				'format'    => '',
				'prev_text' => '&laquo;',
				'next_text' => '&raquo;',
				'total'     => $total_pages,
				'current'   => $paged,
			) );
			if ( $page_links ) {
				echo wp_kses_post( $page_links );
			}
			?>
		</div>
	</div>
<?php endif; ?>

<!-- JSON-LD Preview Modal -->
<div id="ligase-json-modal" class="ligase-modal" style="display:none;">
	<div class="ligase-modal-content">
		<div class="ligase-modal-header">
			<h3><?php esc_html_e( 'Podglad JSON-LD', 'ligase' ); ?></h3>
			<button type="button" class="ligase-modal-close">&times;</button>
		</div>
		<pre id="ligase-json-output" class="ligase-json-preview"></pre>
	</div>
</div>
