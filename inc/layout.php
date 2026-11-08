<?php
/**
 * Scheletri delle pagine (due colonne, blocco finale), colonna laterale, sottopagine e Pagine elenco.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Il contenuto di Sydney e la colonna laterale; i commenti sotto la griglia, così su telefono la
 * colonna arriva subito dopo il testo.
 *
 * @param string   $parte   Parte del contenuto di Sydney: single o page.
 * @param callable $colonna Riceve l'ID del contenuto e restituisce l'HTML della colonna.
 */
function vv_due_colonne( $classi, $parte, $etichetta, callable $colonna ) {
	?>
	<div id="primary" class="content-area col-md-12 no-sidebar <?php echo esc_attr( $classi ); ?>">
		<main id="main" class="post-wrap" role="main">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<div class="vv-single__grid">
					<div class="vv-single__main">
						<?php get_template_part( 'content', $parte ); ?>
					</div>
					<aside class="vv-single__aside" aria-label="<?php echo esc_attr( $etichetta ); ?>">
						<?php echo $colonna( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</aside>
				</div>
				<?php
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
 * La pagina di Sydney più un blocco finale: modelli Pagina hub e Pagina elenco.
 *
 * @param callable $blocco Riceve l'ID della pagina e restituisce l'HTML del blocco.
 */
function vv_pagina_con_blocco_finale( callable $blocco ) {
	$classi = sydney_sidebar_position() . ' ' . apply_filters( 'sydney_content_area_class', 'col-md-9' );
	?>
	<div id="primary" class="content-area <?php echo esc_attr( $classi ); ?>">
		<main id="main" class="post-wrap" role="main">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'content', 'page' );
				echo '<div class="vv-page-extra">' . $blocco( get_the_ID() ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			endwhile;
			?>
		</main>
	</div>
	<?php
}

/**
 * Pubblicate, nell'ordine dell'editor.
 *
 * @return WP_Post[]
 */
function vv_pagine_figlie( $madre_id ) {
	return get_pages( array(
		'parent'      => $madre_id,
		'sort_column' => 'menu_order,post_title',
		'post_status' => 'publish',
	) );
}

/**
 * I prossimi 3 eventi; senza, il blocco non c'è.
 */
function vv_da_non_perdere( $escluso_id = 0 ) {
	$extra = $escluso_id ? array( 'post__not_in' => array( $escluso_id ) ) : array();
	return vv_elenco_laterale( 'Da non perdere', vv_eventi_del_periodo( 'eventi', 'futuro', 3, $extra ) );
}

function vv_elenco_laterale( $titolo, $articoli ) {
	if ( ! $articoli ) {
		return '';
	}
	_prime_post_caches( array_filter( array_map( 'get_post_thumbnail_id', $articoli ) ) );
	$html = '<div class="vv-aside-more"><h2 class="vv-info__title">' . esc_html( $titolo ) . '</h2><ul class="vv-aside-list">';
	foreach ( $articoli as $articolo ) {
		// Senza immagine un riquadro della stessa misura, così i titoli restano in colonna.
		$miniatura = get_the_post_thumbnail( $articolo, 'thumbnail', array( 'alt' => '', 'loading' => 'lazy' ) );
		$miniatura = $miniatura ? $miniatura : '<span class="vv-aside-list__vuoto" aria-hidden="true"></span>';
		$html     .= '<li><a href="' . esc_url( get_permalink( $articolo ) ) . '">' . $miniatura . '<span>' . esc_html( wp_strip_all_tags( get_the_title( $articolo ) ) ) . '</span></a></li>';
	}
	return $html . '</ul></div>';
}

function vv_card_sottopagine( $madre_id ) {
	$figlie = vv_pagine_figlie( $madre_id );
	return vv_griglia_card( $figlie, 'pagina', array( 'colonne' => count( $figlie ) >= 3 ? 3 : 2 ) );
}

/**
 * Le card in fondo alla Pagina hub, a meno che il testo non le metta altrove con [vv_sottopagine].
 */
function vv_card_hub( $pagina_id ) {
	return has_shortcode( (string) get_post_field( 'post_content', $pagina_id ), 'vv_sottopagine' ) ? '' : vv_card_sottopagine( $pagina_id );
}

/**
 * Almeno 9 card grandi in alto, nell'ordine di vv_ordina_eventi(): prima quelli in programma, poi se
 * non bastano i più recenti; sotto il resto, anno per anno. Senza date (i progetti) una sola griglia.
 */
function vv_elenco_html( $categorie ) {
	$articoli = vv_articoli( $categorie, -1 );
	if ( ! $articoli ) {
		return '';
	}
	$adesso = time();
	$eventi = array();
	foreach ( $articoli as $articolo ) {
		$date     = vv_date_articolo( $articolo );
		$eventi[] = array( $articolo, $date['inizio'], $date['fine'], $date );
	}
	if ( ! array_filter( $eventi, static fn( $e ) => $e[3]['con_date'] ) ) {
		return '<section class="vv-elenco vv-elenco--schede">' . vv_griglia_card( $articoli, 'evento' ) . '</section>';
	}

	$minimo       = 9;
	$ordinati     = vv_ordina_eventi( $eventi, $adesso ); // senza date un articolo finisce fra i conclusi
	$in_programma = array_filter( $ordinati, static fn( $e ) => $e[3]['con_date'] && 'concluso' !== vv_fase_evento( $e[3], $adesso ) );
	$passati      = array_values( array_diff_key( $ordinati, $in_programma ) );
	$riempitivi   = array_slice( $passati, 0, max( 0, $minimo - count( $in_programma ) ) );
	$titolo       = match ( true ) {
		! $in_programma => 'Ultimi appuntamenti',
		! $riempitivi   => 'In programma',
		default         => 'In programma e ultimi appuntamenti',
	};

	$html = '<section class="vv-elenco vv-elenco--prossimi"><h2 class="vv-section-title">' . esc_html( $titolo ) . '</h2>'
		. vv_griglia_card( array_column( array_merge( $in_programma, $riempitivi ), 0 ), 'evento' ) . '</section>';
	return $html . vv_archivio_per_anno( array_slice( $passati, count( $riempitivi ) ) );
}

/**
 * @param array $eventi array( contenuto, inizio, fine ), già in ordine.
 */
function vv_archivio_per_anno( $eventi ) {
	if ( ! $eventi ) {
		return '';
	}
	$per_anno = array();
	foreach ( $eventi as $evento ) {
		$per_anno[ wp_date( 'Y', $evento[1] ) ][] = $evento[0];
	}
	krsort( $per_anno );
	$html = '<section class="vv-elenco vv-elenco--archivio"><h2 class="vv-section-title">Archivio</h2>';
	foreach ( $per_anno as $anno => $articoli ) {
		$html .= '<h3 class="vv-year">' . esc_html( $anno ) . '</h3>'
			. vv_griglia_card( $articoli, 'evento', array( 'colonne' => 4, 'titolo_livello' => 'h4' ) );
	}
	return $html . '</section>';
}
