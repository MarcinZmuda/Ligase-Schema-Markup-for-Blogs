<?php
/**
 * Ligase - AI Search Readiness Score
 *
 * Calculates site-level, per-post, and E-E-A-T author readiness scores
 * based on structured data quality signals.
 *
 * @package Ligase
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ligase_Score
 *
 * AI Search Readiness Score calculator for site, post, and author levels.
 */
class Ligase_Score {

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	private array $options;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->options = (array) get_option( 'ligase_options', array() );
	}

	// =========================================================================
	// Site-level score
	// =========================================================================

	/**
	 * Calculate the site-level AI search readiness score.
	 *
	 * @return array{score: int, checks: array, recommendations: array}
	 */
	public function calculate(): array {
		$cache_key = 'ligase_site_score';
		$cached = get_transient( $cache_key );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$checks          = array();
		$recommendations = array();
		$total           = 0;

		// 1. @graph entity linking (15 pts).
		$check = $this->check_graph_linking();
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 2. sameAs + Wikidata (15 pts).
		$check = $this->check_sameas_wikidata();
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 3. knowsAbout on Organization (10 pts).
		$check = $this->check_knows_about();
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 4. Images >= 1200px (15 pts).
		$check = $this->check_images_1200();
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 5. dateModified current (10 pts).
		$check = $this->check_date_modified_current();
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 6. Person -> Org @id (10 pts).
		$check = $this->check_person_org_link();
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 7. Organization has logo (10 pts).
		$check = $this->check_org_logo();
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 8. BreadcrumbList on all posts (5 pts).
		$check = $this->check_breadcrumbs();
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 9. SearchAction on WebSite (5 pts).
		$check = $this->check_search_action();
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 10. At least one author with sameAs (5 pts).
		$check = $this->check_author_sameas();
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		$result = array(
			'score'           => max( 0, min( 100, $total ) ),
			'checks'          => $checks,
			'recommendations' => array_values( array_filter( $recommendations ) ),
		);

		set_transient( $cache_key, $result, HOUR_IN_SECONDS );

		return $result;
	}

	// =========================================================================
	// Per-post score
	// =========================================================================

	/**
	 * Calculate the readiness score for a single post.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array{score: int, checks: array, recommendations: array}
	 */
	public function calculate_for_post( int $post_id ): array {
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== 'post' ) {
			return array(
				'score'           => 0,
				'checks'          => array(),
				'recommendations' => array( 'Wpis nie istnieje.' ),
			);
		}

		$meta            = (array) get_post_meta( $post_id );
		$checks          = array();
		$recommendations = array();
		$total           = 0;

		// 1. Headline present and <= 110 chars (15 pts).
		$headline = get_the_title( $post );
		$passed   = ! empty( $headline ) && mb_strlen( $headline ) <= 110;
		$check    = $this->make_check(
			'post_headline',
			'Naglowek (headline)',
			$passed,
			$passed ? 15 : 0,
			15,
			$passed ? '' : 'Dodaj naglowek do wpisu (maksymalnie 110 znakow).'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 2. datePublished ISO8601 (10 pts).
		$date_published = get_the_date( 'c', $post );
		$passed         = ! empty( $date_published ) && $this->is_valid_iso8601( $date_published );
		$check          = $this->make_check(
			'post_date_published',
			'Data publikacji (datePublished)',
			$passed,
			$passed ? 10 : 0,
			10,
			$passed ? '' : 'Ustaw poprawna date publikacji w formacie ISO 8601.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 3. dateModified ISO8601 (10 pts).
		$date_modified = get_the_modified_date( 'c', $post );
		$passed        = ! empty( $date_modified ) && $this->is_valid_iso8601( $date_modified );
		$check         = $this->make_check(
			'post_date_modified',
			'Data modyfikacji (dateModified)',
			$passed,
			$passed ? 10 : 0,
			10,
			$passed ? '' : 'Ustaw poprawna date modyfikacji w formacie ISO 8601.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 4. Image present and >= 1200px (15 pts).
		$passed = false;
		if ( has_post_thumbnail( $post ) ) {
			$img_src = wp_get_attachment_image_src( get_post_thumbnail_id( $post ), 'full' );
			if ( $img_src && (int) $img_src[1] >= 1200 ) {
				$passed = true;
			}
		}
		$check = $this->make_check(
			'post_image',
			'Obraz wyrozniajacy (image >= 1200px)',
			$passed,
			$passed ? 15 : 0,
			15,
			$passed ? '' : 'Dodaj obraz wyrozniajacy o szerokosci co najmniej 1200px.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 5. Author @id linking (10 pts).
		$author_id = (int) $post->post_author;
		$passed    = $author_id > 0 && ! empty( get_the_author_meta( 'display_name', $author_id ) );
		$check     = $this->make_check(
			'post_author_id',
			'Powiazanie autora (@id)',
			$passed,
			$passed ? 10 : 0,
			10,
			$passed ? '' : 'Przypisz autora do wpisu, aby umozliwic linkowanie @id.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 6. Publisher @id linking (10 pts).
		$org_name = $this->options['organization_name'] ?? get_bloginfo( 'name' );
		$passed   = ! empty( $org_name );
		$check    = $this->make_check(
			'post_publisher_id',
			'Powiazanie wydawcy (@id)',
			$passed,
			$passed ? 10 : 0,
			10,
			$passed ? '' : 'Skonfiguruj nazwe organizacji w ustawieniach Ligase.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 7. Breadcrumb present (5 pts).
		$breadcrumb_enabled = ! empty( $this->options['enable_breadcrumb'] );
		$check              = $this->make_check(
			'post_breadcrumb',
			'Lista nawigacyjna (BreadcrumbList)',
			$breadcrumb_enabled,
			$breadcrumb_enabled ? 5 : 0,
			5,
			$breadcrumb_enabled ? '' : 'Wlacz schemat BreadcrumbList w ustawieniach wtyczki.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 8. Description present (5 pts).
		$excerpt = $post->post_excerpt;
		$passed  = ! empty( $excerpt );
		$check   = $this->make_check(
			'post_description',
			'Opis (description)',
			$passed,
			$passed ? 5 : 0,
			5,
			$passed ? '' : 'Dodaj zajawke (excerpt) do wpisu, ktora posluzy jako opis.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 9. Keywords / tags (5 pts).
		$tags   = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );
		$passed = ! empty( $tags );
		$check  = $this->make_check(
			'post_keywords',
			'Slowa kluczowe (keywords/tagi)',
			$passed,
			$passed ? 5 : 0,
			5,
			$passed ? '' : 'Dodaj tagi do wpisu, aby wzbogacic dane strukturalne o slowa kluczowe.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 10. articleSection (5 pts).
		$categories = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );
		$passed     = ! empty( $categories );
		$check      = $this->make_check(
			'post_article_section',
			'Sekcja artykulu (articleSection)',
			$passed,
			$passed ? 5 : 0,
			5,
			$passed ? '' : 'Przypisz kategorie do wpisu (uzywanej jako articleSection).'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 11. wordCount > 300 (5 pts).
		$word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );
		$passed     = $word_count > 300;
		$check      = $this->make_check(
			'post_word_count',
			'Dlugosc tresci (wordCount > 300)',
			$passed,
			$passed ? 5 : 0,
			5,
			$passed ? '' : "Wpis zawiera tylko {$word_count} slow. Zalecane minimum to 300."
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 12. inLanguage present (5 pts).
		$locale = get_locale();
		$passed = ! empty( $locale );
		$check  = $this->make_check(
			'post_in_language',
			'Jezyk (inLanguage)',
			$passed,
			$passed ? 5 : 0,
			5,
			$passed ? '' : 'Ustaw jezyk witryny w ustawieniach WordPress.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		return array(
			'score'           => max( 0, min( 100, $total ) ),
			'checks'          => $checks,
			'recommendations' => array_values( array_filter( $recommendations ) ),
		);
	}

	// =========================================================================
	// E-E-A-T Author score
	// =========================================================================

	/**
	 * Calculate the E-E-A-T readiness score for an author.
	 *
	 * @param int $user_id WordPress user ID.
	 *
	 * @return array{score: int, checks: array, recommendations: array}
	 */
	public function calculate_for_author( int $user_id ): array {
		$cache_key = 'ligase_author_score_' . $user_id;
		$cached = get_transient( $cache_key );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return array(
				'score'           => 0,
				'checks'          => array(),
				'recommendations' => array( 'Uzytkownik nie istnieje.' ),
			);
		}

		$checks          = array();
		$recommendations = array();
		$total           = 0;

		// 1. display_name present (10 pts).
		$passed = ! empty( $user->display_name );
		$check  = $this->make_check(
			'author_display_name',
			'Nazwa wyswietlana (display_name)',
			$passed,
			$passed ? 10 : 0,
			10,
			$passed ? '' : 'Ustaw nazwe wyswietlana w profilu autora.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 2. Bio / description present (15 pts).
		$bio    = get_user_meta( $user_id, 'description', true );
		$passed = ! empty( $bio );
		$check  = $this->make_check(
			'author_bio',
			'Biografia (description)',
			$passed,
			$passed ? 15 : 0,
			15,
			$passed ? '' : 'Dodaj biografie w profilu autora - wzmacnia sygnal E-E-A-T.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 3. jobTitle present (10 pts).
		$job_title = get_user_meta( $user_id, 'ligase_job_title', true );
		$passed    = ! empty( $job_title );
		$check     = $this->make_check(
			'author_job_title',
			'Stanowisko (jobTitle)',
			$passed,
			$passed ? 10 : 0,
			10,
			$passed ? '' : 'Dodaj stanowisko (jobTitle) w profilu autora.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 4. knowsAbout present (15 pts).
		$knows_about = get_user_meta( $user_id, 'ligase_knows_about', true );
		$passed      = ! empty( $knows_about );
		$check       = $this->make_check(
			'author_knows_about',
			'Obszary wiedzy (knowsAbout)',
			$passed,
			$passed ? 15 : 0,
			15,
			$passed ? '' : 'Dodaj obszary wiedzy (knowsAbout) w profilu autora - kluczowe dla AI.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 5. sameAs LinkedIn (10 pts).
		$linkedin = get_user_meta( $user_id, 'ligase_linkedin', true );
		$passed   = ! empty( $linkedin ) && str_contains( $linkedin, 'linkedin.com' );
		$check    = $this->make_check(
			'author_linkedin',
			'Profil LinkedIn (sameAs)',
			$passed,
			$passed ? 10 : 0,
			10,
			$passed ? '' : 'Dodaj link do profilu LinkedIn w ustawieniach autora.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 6. sameAs Twitter (5 pts).
		$twitter = get_user_meta( $user_id, 'ligase_twitter', true );
		$passed  = ! empty( $twitter );
		$check   = $this->make_check(
			'author_twitter',
			'Profil Twitter/X (sameAs)',
			$passed,
			$passed ? 5 : 0,
			5,
			$passed ? '' : 'Dodaj link do profilu Twitter/X w ustawieniach autora.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 7. sameAs Wikidata (20 pts).
		$wikidata = get_user_meta( $user_id, 'ligase_wikidata', true );
		$passed   = ! empty( $wikidata ) && str_contains( $wikidata, 'wikidata.org' );
		$check    = $this->make_check(
			'author_wikidata',
			'Profil Wikidata (sameAs)',
			$passed,
			$passed ? 20 : 0,
			20,
			$passed ? '' : 'Dodaj link do encji Wikidata - najsilniejszy sygnal tożsamości dla AI.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 8. Avatar / image present (10 pts).
		$avatar  = get_avatar_url( $user_id );
		$passed  = ! empty( $avatar ) && ! str_contains( $avatar, 'gravatar.com/avatar/?d=' );
		$check   = $this->make_check(
			'author_avatar',
			'Zdjecie profilowe (image)',
			$passed,
			$passed ? 10 : 0,
			10,
			$passed ? '' : 'Dodaj zdjecie profilowe (Gravatar lub lokalne) autora.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		// 9. worksFor linked (5 pts).
		$works_for = get_user_meta( $user_id, 'ligase_works_for', true );
		$passed    = ! empty( $works_for );
		$check     = $this->make_check(
			'author_works_for',
			'Miejsce pracy (worksFor)',
			$passed,
			$passed ? 5 : 0,
			5,
			$passed ? '' : 'Powiaz autora z organizacja (worksFor) w ustawieniach profilu.'
		);
		$checks[] = $check;
		$total   += $check['points'];
		if ( ! $check['passed'] ) {
			$recommendations[] = $check['recommendation'];
		}

		$result = array(
			'score'           => max( 0, min( 100, $total ) ),
			'checks'          => $checks,
			'recommendations' => array_values( array_filter( $recommendations ) ),
		);

		set_transient( $cache_key, $result, HOUR_IN_SECONDS * 6 );

		return $result;
	}

	// =========================================================================
	// Private: site-level check methods
	// =========================================================================

	/**
	 * Check if schemas use @graph entity linking with @id references.
	 *
	 * @return array
	 */
	private function check_graph_linking(): array {
		$use_graph = ! empty( $this->options['use_graph'] );

		return $this->make_check(
			'site_graph_linking',
			'Linkowanie encji @graph (@id)',
			$use_graph,
			$use_graph ? 15 : 0,
			15,
			$use_graph ? '' : 'Wlacz tryb @graph w ustawieniach wtyczki, aby polaczyc encje za pomoca @id.'
		);
	}

	/**
	 * Check if Organization has Wikidata URL in sameAs.
	 *
	 * @return array
	 */
	private function check_sameas_wikidata(): array {
		$same_as = $this->options['organization_same_as'] ?? array();

		if ( is_string( $same_as ) ) {
			$same_as = array_filter( array_map( 'trim', explode( "\n", $same_as ) ) );
		}

		$has_wikidata = false;
		foreach ( $same_as as $url ) {
			if ( str_contains( $url, 'wikidata.org' ) ) {
				$has_wikidata = true;
				break;
			}
		}

		return $this->make_check(
			'site_sameas_wikidata',
			'sameAs z Wikidata (Organization)',
			$has_wikidata,
			$has_wikidata ? 15 : 0,
			15,
			$has_wikidata ? '' : 'Dodaj URL Wikidata do pola sameAs organizacji - kluczowe dla wiarygodnosci AI.'
		);
	}

	/**
	 * Check if Organization has knowsAbout field.
	 *
	 * @return array
	 */
	private function check_knows_about(): array {
		$knows_about = $this->options['organization_knows_about'] ?? '';
		$passed      = ! empty( $knows_about );

		return $this->make_check(
			'site_knows_about',
			'Obszary wiedzy organizacji (knowsAbout)',
			$passed,
			$passed ? 10 : 0,
			10,
			$passed ? '' : 'Dodaj knowsAbout do organizacji, aby wskazac tematy, w ktorych jestes ekspertem.'
		);
	}

	/**
	 * Check if all post featured images meet 1200px recommendation.
	 *
	 * @return array
	 */
	private function check_images_1200(): array {
		$post_ids = $this->get_sample_posts( 50 );

		$all_pass = true;

		foreach ( $post_ids as $pid ) {
			if ( ! has_post_thumbnail( $pid ) ) {
				$all_pass = false;
				break;
			}

			$img_src = wp_get_attachment_image_src( get_post_thumbnail_id( $pid ), 'full' );

			if ( ! $img_src || (int) $img_src[1] < 1200 ) {
				$all_pass = false;
				break;
			}
		}

		return $this->make_check(
			'site_images_1200',
			'Obrazy >= 1200px',
			$all_pass,
			$all_pass ? 15 : 0,
			15,
			$all_pass ? '' : 'Upewnij sie, ze wszystkie obrazy wyrozniajace maja co najmniej 1200px szerokosci.'
		);
	}

	/**
	 * Check if published posts have recent dateModified.
	 *
	 * @return array
	 */
	private function check_date_modified_current(): array {
		$recent_posts = $this->get_sample_posts( 20 );

		$one_year_ago = strtotime( '-1 year' );
		$all_current  = true;

		foreach ( $recent_posts as $pid ) {
			$modified = get_the_modified_date( 'U', $pid );

			if ( $modified && (int) $modified < $one_year_ago ) {
				$all_current = false;
				break;
			}
		}

		return $this->make_check(
			'site_date_modified',
			'Aktualna data modyfikacji (dateModified)',
			$all_current,
			$all_current ? 10 : 0,
			10,
			$all_current ? '' : 'Niektore wpisy nie byly aktualizowane od ponad roku. Zaktualizuj je.'
		);
	}

	/**
	 * Check if authors are linked to Organization via worksFor.
	 *
	 * @return array
	 */
	private function check_person_org_link(): array {
		$authors = get_users(
			array(
				'role__in' => array( 'author', 'editor', 'administrator' ),
				'fields'   => 'ID',
			)
		);

		$all_linked = ! empty( $authors );

		foreach ( $authors as $uid ) {
			$works_for = get_user_meta( $uid, 'ligase_works_for', true );

			if ( empty( $works_for ) ) {
				$all_linked = false;
				break;
			}
		}

		return $this->make_check(
			'site_person_org',
			'Powiazanie autorow z organizacja (worksFor)',
			$all_linked,
			$all_linked ? 10 : 0,
			10,
			$all_linked ? '' : 'Powiaz wszystkich autorow z organizacja za pomoca pola worksFor.'
		);
	}

	/**
	 * Check if Organization has a logo configured.
	 *
	 * @return array
	 */
	private function check_org_logo(): array {
		$logo   = $this->options['organization_logo'] ?? '';
		$passed = ! empty( $logo );

		return $this->make_check(
			'site_org_logo',
			'Logo organizacji',
			$passed,
			$passed ? 10 : 0,
			10,
			$passed ? '' : 'Dodaj logo organizacji w ustawieniach Ligase.'
		);
	}

	/**
	 * Check if BreadcrumbList schema is enabled.
	 *
	 * @return array
	 */
	private function check_breadcrumbs(): array {
		$enabled = ! empty( $this->options['enable_breadcrumb'] );

		return $this->make_check(
			'site_breadcrumbs',
			'Lista nawigacyjna (BreadcrumbList)',
			$enabled,
			$enabled ? 5 : 0,
			5,
			$enabled ? '' : 'Wlacz schemat BreadcrumbList w ustawieniach wtyczki.'
		);
	}

	/**
	 * Check if SearchAction is configured on WebSite schema.
	 *
	 * @return array
	 */
	private function check_search_action(): array {
		$enabled = ! empty( $this->options['enable_search_action'] );

		return $this->make_check(
			'site_search_action',
			'Akcja wyszukiwania (SearchAction)',
			$enabled,
			$enabled ? 5 : 0,
			5,
			$enabled ? '' : 'Wlacz SearchAction w schemacie WebSite w ustawieniach wtyczki.'
		);
	}

	/**
	 * Check if at least one author has sameAs profile links.
	 *
	 * @return array
	 */
	private function check_author_sameas(): array {
		$authors = get_users(
			array(
				'role__in' => array( 'author', 'editor', 'administrator' ),
				'fields'   => 'ID',
			)
		);

		$has_sameas = false;

		foreach ( $authors as $uid ) {
			$linkedin = get_user_meta( $uid, 'ligase_linkedin', true );
			$twitter  = get_user_meta( $uid, 'ligase_twitter', true );
			$wikidata = get_user_meta( $uid, 'ligase_wikidata', true );

			if ( ! empty( $linkedin ) || ! empty( $twitter ) || ! empty( $wikidata ) ) {
				$has_sameas = true;
				break;
			}
		}

		return $this->make_check(
			'site_author_sameas',
			'Profil autora z sameAs',
			$has_sameas,
			$has_sameas ? 5 : 0,
			5,
			$has_sameas ? '' : 'Dodaj przynajmniej jednemu autorowi linki do profili (LinkedIn, Twitter, Wikidata).'
		);
	}

	// =========================================================================
	// Private helpers
	// =========================================================================

	/**
	 * Build a standardized check result array.
	 *
	 * @param string $id             Unique check identifier.
	 * @param string $label          Human-readable label (Polish).
	 * @param bool   $passed         Whether the check passed.
	 * @param int    $points         Points earned.
	 * @param int    $max_points     Maximum possible points.
	 * @param string $recommendation Recommendation text if failed (Polish).
	 *
	 * @return array{id: string, label: string, passed: bool, points: int, max_points: int, recommendation: string}
	 */
	/**
	 * Get a sample of published post IDs (cached per request).
	 */
	private function get_sample_posts( int $limit = 50 ): array {
		static $cache = [];
		$key = 'sample_' . $limit;
		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}
		$cache[ $key ] = get_posts( array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'fields'         => 'ids',
		) );
		return $cache[ $key ];
	}

	private function make_check(
		string $id,
		string $label,
		bool $passed,
		int $points,
		int $max_points,
		string $recommendation
	): array {
		return array(
			'id'             => $id,
			'label'          => $label,
			'passed'         => $passed,
			'points'         => $points,
			'max_points'     => $max_points,
			'recommendation' => $recommendation,
		);
	}

	/**
	 * Validate an ISO 8601 date string.
	 *
	 * @param string $date Date string.
	 *
	 * @return bool
	 */
	private function is_valid_iso8601( string $date ): bool {
		return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}(:\d{2})?([+-]\d{2}:\d{2}|Z)?)?$/', $date );
	}
}
