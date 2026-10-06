<?php
/**
 * Articolo singolo: testo + scheda "In breve" a lato (poi altre edizioni, rassegna stampa e prossimi
 * eventi), navigazione precedente/successivo a card, "Gli altri soci fondatori" in fondo alle pagine dei soci.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sostituisce il contenuto dell'articolo di Sydney.
 */
function vv_swap_single_template() {
	if ( is_singular( 'post' ) ) {
		remove_action( 'sydney_single_content', 'sydney_single_template' );
		add_action( 'sydney_single_content', 'vv_single_template' );
	}
}
add_action( 'wp', 'vv_swap_single_template' );

/**
 * Due colonne: articolo (colonna di lettura) e scheda laterale.
 */
function vv_single_template() {
	?>
	<div id="primary" class="content-area col-md-12 no-sidebar vv-single">
		<main id="main" class="post-wrap" role="main">
			<?php while ( have_posts() ) : the_post(); ?>
				<div class="vv-single__grid">
					<div class="vv-single__main">
						<?php get_template_part( 'content', 'single' ); ?>
					</div>
					<aside class="vv-single__aside" aria-label="Informazioni">
						<?php echo vv_single_aside( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</aside>
				</div>
				<?php
				// Commenti sotto la griglia: su mobile la scheda "In breve" arriva subito dopo l'articolo.
				if ( comments_open() || get_comments_number() ) {
					echo '<div class="vv-single__comments">';
					comments_template();
					echo '</div>';
				}
				?>
			<?php endwhile; ?>
		</main>
	</div>
	<?php
}

/**
 * Scheda laterale: quando, dove, categoria; poi "Altre edizioni", "Ne hanno parlato" e "Da non perdere".
 *
 * @param int $post_id Articolo.
 * @return string HTML.
 */
function vv_single_aside( $post_id ) {
	$dates = vv_event_dates( $post_id );
	$luogo = get_post_meta( $post_id, 'vv_luogo', true );
	$cats  = array_filter( get_the_category( $post_id ), static fn( $c ) => 'nessuna-categoria' !== $c->slug && 'uncategorized' !== $c->slug );

	$html  = '<div class="vv-card vv-card--info vv-accent--giallo"><div class="vv-card__body">';
	$html .= '<h2 class="vv-info__title">In breve</h2><dl class="vv-info">';
	$html .= '<dt>' . ( $dates['has_meta'] ? 'Quando' : 'Pubblicato' ) . '</dt><dd><time datetime="' . esc_attr( wp_date( 'Y-m-d', $dates['start'] ) ) . '">' . esc_html( $dates['label'] ) . '</time></dd>';
	if ( $luogo ) {
		$html .= '<dt>Dove</dt><dd>' . esc_html( $luogo ) . '</dd>';
	}
	if ( $cats ) {
		// Il link porta alla pagina della sezione (Eventi, Corsi e laboratori…), se c'è.
		$html .= '<dt>' . ( count( $cats ) > 1 ? 'Categorie' : 'Categoria' ) . '</dt><dd class="vv-info__chips">';
		foreach ( $cats as $cat ) {
			$page  = vv_page_for_term( $cat );
			$html .= '<a class="vv-chip" href="' . esc_url( $page ? get_permalink( $page ) : get_category_link( $cat ) ) . '">' . esc_html( $cat->name ) . '</a>';
		}
		$html .= '</dd>';
	}
	$html .= '</dl></div></div>';

	return $html . vv_aside_related( $post_id ) . vv_aside_more( $post_id );
}

/**
 * Tag "di percorso" di un articolo (Il gioco del teatro, Corre Voce, ALICE…): tutti tranne quelli
 * generici su pubblico e linguaggio, che legherebbero fra loro mezzo sito.
 *
 * @param int $post_id Articolo.
 * @return int[] ID dei tag.
 */
function vv_path_tags( $post_id ) {
	$generic = apply_filters( 'vv_generic_tags', array( 'bambini', 'ragazzi', 'adulti', 'teatro', 'lettura-ad-alta-voce', 'narrazione', 'scrittura', 'musica', 'multimedia' ) );
	$tags    = get_the_tags( $post_id );
	return $tags ? array_values( wp_list_pluck( array_filter( $tags, static fn( $t ) => ! in_array( $t->slug, $generic, true ) ), 'term_id' ) ) : array();
}

/**
 * "Altre edizioni" (stesso percorso) e "Ne hanno parlato" (ritagli dello stesso percorso);
 * su un ritaglio, "Di cosa parla": gli articoli del percorso.
 *
 * @param int $post_id Articolo.
 * @return string HTML.
 */
function vv_aside_related( $post_id ) {
	$tags = vv_path_tags( $post_id );
	if ( ! $tags ) {
		return '';
	}
	$press = get_category_by_slug( 'rassegna-stampa' );
	$base  = array(
		'tag__in'      => $tags,
		'post__not_in' => array( $post_id ),
	);
	$others = vv_posts_in( '', 4, $base + ( $press ? array( 'category__not_in' => array( $press->term_id ) ) : array() ) );
	if ( $press && has_category( $press->term_id, $post_id ) ) {
		return vv_aside_list( 'Di cosa parla', $others );
	}
	$clips = $press ? vv_posts_in( '', 4, $base + array( 'cat' => $press->term_id ) ) : array();
	return vv_aside_list( 'Altre edizioni', $others ) . vv_aside_list( 'Ne hanno parlato', $clips );
}

/**
 * "Da non perdere": i prossimi 3 eventi; se non ce ne sono, gli "In evidenza", poi i più recenti.
 * Nella colonna laterale degli articoli e delle pagine.
 *
 * @param int $exclude_id Articolo da escludere (0 nelle pagine).
 * @return string HTML.
 */
function vv_aside_more( $exclude_id = 0 ) {
	$extra = $exclude_id ? array( 'post__not_in' => array( $exclude_id ) ) : array();
	$more  = vv_posts_by_period( 'eventi', 'futuro', 3, $extra );
	if ( ! $more ) {
		$more = vv_posts_in( '', 3, $extra + array(
			'meta_query' => array( array( 'key' => VV_FEATURED_KEY, 'value' => '1' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		) );
	}
	if ( ! $more ) {
		$more = vv_posts_in( '', 3, $extra );
	}
	return vv_aside_list( 'Da non perdere', $more );
}

/**
 * Elenco di link con miniatura nella colonna laterale.
 *
 * @param string    $title Titolo del blocco.
 * @param WP_Post[] $posts Articoli.
 * @return string HTML (vuoto senza articoli).
 */
function vv_aside_list( $title, $posts ) {
	if ( ! $posts ) {
		return '';
	}
	$html = '<div class="vv-aside-more"><h2 class="vv-info__title">' . esc_html( $title ) . '</h2><ul class="vv-aside-list">';
	foreach ( $posts as $p ) {
		$thumb = get_the_post_thumbnail( $p, 'thumbnail', array( 'alt' => '', 'loading' => 'lazy' ) );
		$html .= '<li><a href="' . esc_url( get_permalink( $p ) ) . '">' . $thumb . '<span>' . esc_html( vv_plain_letters( wp_strip_all_tags( get_the_title( $p ) ), false ) ) . '</span></a></li>';
	}
	return $html . '</ul></div>';
}

/*
 * Navigazione precedente/successivo: funzione "pluggable" di Sydney, ridefinita qui
 * (il child si carica prima del padre): due link allineati con etichetta.
 */
if ( ! function_exists( 'sydney_post_navigation' ) ) :
	/**
	 * Link all'articolo precedente e successivo.
	 */
	function sydney_post_navigation() {
		if ( ! apply_filters( 'sydney_single_post_nav_enable', true ) || ! get_theme_mod( 'single_post_show_post_nav', 1 ) ) {
			return;
		}
		$previous = is_attachment() ? get_post( get_post()->post_parent ) : get_adjacent_post( false, '', true );
		$next     = get_adjacent_post( false, '', false );
		if ( ! $next && ! $previous ) {
			return;
		}

		echo '<nav class="vv-post-nav" aria-label="Altri articoli">';
		foreach ( array( 'prev' => $previous, 'next' => $next ) as $dir => $p ) {
			if ( ! $p ) {
				echo '<span class="vv-post-nav__empty"></span>';
				continue;
			}
			printf(
				'<a class="vv-post-nav__link vv-post-nav__link--%1$s" href="%2$s" rel="%3$s"><span class="vv-post-nav__label">%4$s</span><span class="vv-post-nav__title">%5$s</span></a>',
				esc_attr( $dir ),
				esc_url( get_permalink( $p ) ),
				'prev' === $dir ? 'prev' : 'next',
				'prev' === $dir ? '‹ Articolo precedente' : 'Articolo successivo ›',
				esc_html( vv_plain_letters( wp_strip_all_tags( get_the_title( $p ) ), false ) )
			);
		}
		echo '</nav>';
	}
endif;

/**
 * In fondo alla pagina di un socio fondatore: gli altri soci.
 *
 * @param string $content Contenuto.
 */
function vv_other_founders( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() || ! has_category( 'soci' ) ) {
		return $content;
	}
	$others = vv_posts_in( 'soci', 12, array(
		'post__not_in' => array( get_the_ID() ),
		'orderby'      => 'title',
		'order'        => 'ASC',
	) );
	if ( ! $others ) {
		return $content;
	}
	return $content . '<section class="vv-founders"><h2 class="vv-section-title">Gli altri soci fondatori</h2>'
		. vv_render_cards( $others, 'persona', array( 'colonne' => 3 ) ) . '</section>';
}
add_filter( 'the_content', 'vv_other_founders', 30 );
