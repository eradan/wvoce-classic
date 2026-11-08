<?php
/**
 * Descrizione per i motori di ricerca e anteprime per i social (Open Graph, Twitter). Con un plugin
 * SEO attivo lascia fare al plugin.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Testo semplice su una riga, tagliato sulla parola con "…" oltre $massimo caratteri.
 */
function vv_condivisione_taglia( $testo, $massimo = 160 ) {
	$testo = vv_testo_semplice( strip_shortcodes( (string) $testo ) );
	$testo = trim( (string) preg_replace( '/\s+/u', ' ', $testo ) );
	if ( mb_strlen( $testo ) <= $massimo ) {
		return $testo;
	}
	$testo  = mb_substr( $testo, 0, $massimo );
	$spazio = mb_strrpos( $testo, ' ' );
	if ( false !== $spazio && $spazio > $massimo / 2 ) {
		$testo = mb_substr( $testo, 0, $spazio );
	}
	return rtrim( $testo, ' ,;:.–-' ) . '…';
}

function vv_condivisione_plugin_seo() {
	return defined( 'WPSEO_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || class_exists( 'RankMath' );
}

/**
 * Calcolati una volta per pagina: li usano sia i meta sia i dati strutturati.
 *
 * @return array{titolo:string,descrizione:string,url:string,tipo:string,immagine:array,pubblicato:string,modificato:string}
 */
function vv_condivisione_dati() {
	static $dati = null;
	if ( null !== $dati ) {
		return $dati;
	}
	$dati = array(
		'titolo'      => wp_get_document_title(),
		'descrizione' => '',
		'url'         => '',
		'tipo'        => 'website',
		'immagine'    => array(),
		'pubblicato'  => '',
		'modificato'  => '',
	);

	if ( is_singular() ) {
		$contenuto      = get_queried_object();
		$dati['titolo'] = is_front_page() ? get_bloginfo( 'name' ) : single_post_title( '', false );
		$dati['url']    = (string) get_permalink( $contenuto );
		if ( ! post_password_required( $contenuto ) ) {
			$dati['descrizione'] = has_excerpt( $contenuto ) ? $contenuto->post_excerpt : $contenuto->post_content;
		}
		if ( 'post' === $contenuto->post_type ) {
			$dati['tipo']       = 'article';
			$dati['pubblicato'] = (string) get_post_time( 'c', true, $contenuto );
			$dati['modificato'] = (string) get_post_modified_time( 'c', true, $contenuto );
		}
		// Letta dal campo e non con has_post_thumbnail(), che "Senza immagine in testa" spegne.
		$dati['immagine'] = vv_condivisione_immagine( (int) get_post_meta( $contenuto->ID, '_thumbnail_id', true ), $dati['titolo'] );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$termine             = get_queried_object();
		$dati['titolo']      = single_term_title( '', false );
		$dati['url']         = (string) get_term_link( $termine );
		$dati['descrizione'] = term_description( $termine );
	} elseif ( is_home() ) {
		$pagina_articoli = (int) get_option( 'page_for_posts' );
		$dati['url']     = $pagina_articoli ? (string) get_permalink( $pagina_articoli ) : home_url( '/' );
	}

	if ( is_front_page() ) {
		$dati['url'] = home_url( '/' );
		if ( ! is_singular() || ! has_excerpt( get_queried_object() ) ) {
			$motto               = get_bloginfo( 'description' );
			$dati['descrizione'] = '' !== $motto ? $motto : (string) vv_mod( 'vv_hero_title' );
		}
		if ( ! $dati['immagine'] ) {
			$dati['immagine'] = vv_condivisione_immagine( vv_id_immagine_hero(), get_bloginfo( 'name' ) );
		}
	}

	$dati['descrizione'] = vv_condivisione_taglia( $dati['descrizione'] );
	if ( '' === $dati['descrizione'] ) {
		$dati['descrizione'] = vv_condivisione_taglia( get_bloginfo( 'description' ) );
	}
	$icona = $dati['immagine'] ? '' : get_site_icon_url( 512 );
	if ( $icona ) {
		$dati['immagine'] = array(
			'url'    => $icona,
			'width'  => 512,
			'height' => 512,
			'alt'    => get_bloginfo( 'name' ),
		);
	}
	return $dati;
}

/**
 * In dimensione "large"; vuota senza allegato.
 */
function vv_condivisione_immagine( $allegato_id, $alt_di_ripiego ) {
	$src = $allegato_id ? wp_get_attachment_image_src( $allegato_id, 'large' ) : false;
	if ( ! $src ) {
		return array();
	}
	$alt = (string) get_post_meta( $allegato_id, '_wp_attachment_image_alt', true );
	return array(
		'url'    => $src[0],
		'width'  => (int) $src[1],
		'height' => (int) $src[2],
		'alt'    => '' !== $alt ? $alt : $alt_di_ripiego,
	);
}

function vv_condivisione_head() {
	if ( vv_condivisione_plugin_seo() || is_404() || is_search() ) {
		return;
	}
	$dati     = vv_condivisione_dati();
	$immagine = $dati['immagine'];
	$meta     = array(
		array( 'name', 'description', $dati['descrizione'] ),
		array( 'property', 'og:locale', 'it_IT' ),
		array( 'property', 'og:site_name', get_bloginfo( 'name' ) ),
		array( 'property', 'og:type', $dati['tipo'] ),
		array( 'property', 'og:title', $dati['titolo'] ),
		array( 'property', 'og:description', $dati['descrizione'] ),
		array( 'property', 'og:url', $dati['url'] ),
		array( 'property', 'article:published_time', $dati['pubblicato'] ),
		array( 'property', 'article:modified_time', $dati['modificato'] ),
	);
	if ( $immagine ) {
		$meta[] = array( 'property', 'og:image', $immagine['url'] );
		$meta[] = array( 'property', 'og:image:width', (string) $immagine['width'] );
		$meta[] = array( 'property', 'og:image:height', (string) $immagine['height'] );
		$meta[] = array( 'property', 'og:image:alt', $immagine['alt'] );
	}
	$meta[] = array( 'name', 'twitter:card', $immagine && $immagine['width'] >= 600 ? 'summary_large_image' : 'summary' );

	foreach ( $meta as list( $attributo, $nome, $valore ) ) {
		if ( '' === (string) $valore ) {
			continue;
		}
		$valore = in_array( $nome, array( 'og:url', 'og:image' ), true ) ? esc_url( $valore ) : esc_attr( $valore );
		printf( '<meta %s="%s" content="%s">' . "\n", esc_attr( $attributo ), esc_attr( $nome ), $valore ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escape fatto sopra.
	}
}
add_action( 'wp_head', 'vv_condivisione_head', 5 );
