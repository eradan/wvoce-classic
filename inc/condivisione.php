<?php
/**
 * Descrizione per i motori di ricerca e anteprima per i social (Open Graph, Twitter): titolo,
 * riassunto e immagine in evidenza di articoli e pagine. Si spegne con un plugin SEO attivo o con
 * il filtro vv_condivisione.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Testo ripulito per una descrizione: niente tag né shortcode, spazi compressi, tagliato sulla
 * parola con "…" oltre $max caratteri.
 *
 * @param string $testo Testo di partenza.
 * @param int    $max   Lunghezza massima.
 */
function vv_condivisione_taglia( $testo, $max = 160 ) {
	$testo = html_entity_decode( wp_strip_all_tags( strip_shortcodes( (string) $testo ) ), ENT_QUOTES, 'UTF-8' );
	$testo = trim( (string) preg_replace( '/\s+/u', ' ', $testo ) );
	if ( mb_strlen( $testo, 'UTF-8' ) <= $max ) {
		return $testo;
	}
	$testo  = mb_substr( $testo, 0, $max, 'UTF-8' );
	$spazio = mb_strrpos( $testo, ' ', 0, 'UTF-8' );
	if ( false !== $spazio && $spazio > $max / 2 ) {
		$testo = mb_substr( $testo, 0, $spazio, 'UTF-8' );
	}
	return rtrim( $testo, ' ,;:.–-' ) . '…';
}

/**
 * È attivo un plugin che scrive già questi tag?
 */
function vv_condivisione_plugin_seo() {
	return defined( 'WPSEO_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || class_exists( 'RankMath' );
}

/**
 * Dati dell'anteprima per la pagina corrente.
 *
 * @return array{titolo:string,descrizione:string,url:string,tipo:string,immagine:array,pubblicato:string,modificato:string}
 */
function vv_condivisione_dati() {
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
		$post                = get_queried_object();
		$dati['titolo']      = is_front_page() ? get_bloginfo( 'name' ) : single_post_title( '', false );
		$dati['url']         = (string) get_permalink( $post );
		$dati['descrizione'] = has_excerpt( $post ) ? $post->post_excerpt : $post->post_content;
		if ( 'post' === $post->post_type ) {
			$dati['tipo']       = 'article';
			$dati['pubblicato'] = (string) get_post_time( 'c', true, $post );
			$dati['modificato'] = (string) get_post_modified_time( 'c', true, $post );
		}
		// L'immagine in evidenza vale anche con "Senza immagine in testa": chiesta direttamente, senza
		// passare da has_post_thumbnail().
		$dati['immagine'] = vv_condivisione_immagine( (int) get_post_meta( $post->ID, '_thumbnail_id', true ), $dati['titolo'] );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term                = get_queried_object();
		$dati['titolo']      = single_term_title( '', false );
		$dati['url']         = (string) get_term_link( $term );
		$dati['descrizione'] = term_description( $term );
	} elseif ( is_home() ) {
		$dati['url'] = home_url( '/' );
	}

	if ( is_front_page() ) {
		$dati['url'] = home_url( '/' );
		if ( ! is_singular() || ! has_excerpt( get_queried_object() ) ) {
			// Senza motto del sito, il titolo dell'hero ("Lettura ad alta voce, teatro e creatività").
			$motto               = get_bloginfo( 'description' );
			$dati['descrizione'] = '' !== $motto ? $motto : (string) vv_mod( 'vv_hero_title' );
		}
		if ( ! $dati['immagine'] ) {
			$dati['immagine'] = vv_condivisione_immagine( attachment_url_to_postid( (string) vv_hero_image_url() ), get_bloginfo( 'name' ) );
		}
	}

	$dati['descrizione'] = vv_condivisione_taglia( $dati['descrizione'] );
	if ( '' === $dati['descrizione'] ) {
		$dati['descrizione'] = vv_condivisione_taglia( get_bloginfo( 'description' ) );
	}
	if ( ! $dati['immagine'] && get_site_icon_url( 512 ) ) {
		$dati['immagine'] = array(
			'url'    => get_site_icon_url( 512 ),
			'width'  => 512,
			'height' => 512,
			'alt'    => get_bloginfo( 'name' ),
		);
	}
	return apply_filters( 'vv_condivisione_dati', $dati );
}

/**
 * Immagine di un allegato per l'anteprima (dimensione "large"), vuota se manca.
 *
 * @param int    $id  Allegato.
 * @param string $alt Testo alternativo di ripiego.
 */
function vv_condivisione_immagine( $id, $alt ) {
	$src = $id ? wp_get_attachment_image_src( $id, 'large' ) : false;
	if ( ! $src ) {
		return array();
	}
	$testo = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
	return array(
		'url'    => $src[0],
		'width'  => (int) $src[1],
		'height' => (int) $src[2],
		'alt'    => '' !== $testo ? $testo : $alt,
	);
}

/**
 * Stampa i tag nell'<head>.
 */
function vv_condivisione_head() {
	if ( ! apply_filters( 'vv_condivisione', ! vv_condivisione_plugin_seo() ) || is_404() || is_search() ) {
		return;
	}
	$d    = vv_condivisione_dati();
	$tags = array(
		array( 'name', 'description', $d['descrizione'] ),
		array( 'property', 'og:locale', 'it_IT' ),
		array( 'property', 'og:site_name', get_bloginfo( 'name' ) ),
		array( 'property', 'og:type', $d['tipo'] ),
		array( 'property', 'og:title', $d['titolo'] ),
		array( 'property', 'og:description', $d['descrizione'] ),
		array( 'property', 'og:url', $d['url'] ),
		array( 'property', 'article:published_time', $d['pubblicato'] ),
		array( 'property', 'article:modified_time', $d['modificato'] ),
	);
	if ( $d['immagine'] ) {
		$tags[] = array( 'property', 'og:image', $d['immagine']['url'] );
		$tags[] = array( 'property', 'og:image:width', (string) $d['immagine']['width'] );
		$tags[] = array( 'property', 'og:image:height', (string) $d['immagine']['height'] );
		$tags[] = array( 'property', 'og:image:alt', $d['immagine']['alt'] );
	}
	$grande = $d['immagine'] && $d['immagine']['width'] >= 600;
	$tags[] = array( 'name', 'twitter:card', $grande ? 'summary_large_image' : 'summary' );

	foreach ( $tags as list( $attr, $nome, $valore ) ) {
		if ( '' === (string) $valore ) {
			continue;
		}
		$valore = in_array( $nome, array( 'og:url', 'og:image' ), true ) ? esc_url( $valore ) : esc_attr( $valore );
		printf( '<meta %s="%s" content="%s">' . "\n", esc_attr( $attr ), esc_attr( $nome ), $valore ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escape fatto sopra.
	}
}
add_action( 'wp_head', 'vv_condivisione_head', 5 );
