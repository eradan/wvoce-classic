<?php
/**
 * Breadcrumb ("Home › Chi siamo › Statuto e organigramma") su tutte le pagine tranne la home.
 *
 * Il percorso segue il menu principale, così rispecchia la struttura che vede chi naviga
 * (per esempio Dicono di noi sta sotto Chi siamo). Se una voce non è nel menu si usano
 * le pagine madri; per gli articoli la pagina collegata alla loro categoria o a una sua madre
 * (campo "Categorie collegate" nel riquadro VivaVoce della pagina), poi la voce di menu della
 * categoria (Progetti → archivio), oppure la categoria stessa.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Voci del menu principale, indicizzate per ID.
 *
 * @return array<int,WP_Post>
 */
function vv_primary_menu_items() {
	static $items = null;
	if ( null !== $items ) {
		return $items;
	}
	$items     = array();
	$locations = get_nav_menu_locations();
	if ( empty( $locations['primary'] ) ) {
		return $items;
	}
	foreach ( (array) wp_get_nav_menu_items( $locations['primary'] ) as $item ) {
		$items[ (int) $item->ID ] = $item;
	}
	return $items;
}

/**
 * Percorso nel menu fino a un oggetto (pagina o categoria), voce compresa.
 *
 * @param string $type      post_type | taxonomy.
 * @param string $object    page | category.
 * @param int    $object_id ID dell'oggetto.
 * @return array<int,array{0:string,1:string}>|null Coppie [titolo, url] o null se non è nel menu.
 */
function vv_menu_trail( $type, $object, $object_id ) {
	$items = vv_primary_menu_items();
	foreach ( $items as $item ) {
		if ( $item->type === $type && $item->object === $object && (int) $item->object_id === (int) $object_id ) {
			$trail = array();
			for ( $cur = $item; $cur; $cur = $items[ (int) $cur->menu_item_parent ] ?? null ) {
				array_unshift( $trail, array( $cur->title, $cur->url ) );
				if ( count( $trail ) > 6 ) {
					break; // Menu malformato: niente cicli infiniti.
				}
			}
			return $trail;
		}
	}
	return null;
}

/**
 * Percorso di una pagina: dal menu, altrimenti pagine madri.
 *
 * @param int $page_id Pagina.
 * @return array<int,array{0:string,1:string}>
 */
function vv_page_trail( $page_id ) {
	$trail = vv_menu_trail( 'post_type', 'page', $page_id );
	if ( null !== $trail ) {
		return $trail;
	}
	$trail = array();
	foreach ( array_reverse( get_post_ancestors( $page_id ) ) as $ancestor ) {
		$trail[] = array( get_the_title( $ancestor ), get_permalink( $ancestor ) );
	}
	$trail[] = array( get_the_title( $page_id ), get_permalink( $page_id ) );
	return $trail;
}

/**
 * Pagina collegata a una categoria (campo vv_categoria della pagina).
 *
 * @param string $slug Slug della categoria.
 * @return int ID della pagina o 0.
 */
function vv_page_for_category( $slug ) {
	static $map = null;
	if ( null === $map ) {
		$map   = array();
		$pages = get_posts( array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'meta_key'       => 'vv_categoria', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );
		foreach ( $pages as $page_id ) {
			foreach ( explode( ',', (string) get_post_meta( $page_id, 'vv_categoria', true ) ) as $cat ) {
				if ( $cat && ! isset( $map[ $cat ] ) ) {
					$map[ $cat ] = (int) $page_id;
				}
			}
		}
		// I soci fondatori stanno in Chi siamo anche senza impostare il collegamento.
		if ( ! isset( $map['soci'] ) ) {
			$chi = get_page_by_path( 'chi-siamo' );
			if ( $chi ) {
				$map['soci'] = (int) $chi->ID;
			}
		}
	}
	return $map[ $slug ] ?? 0;
}

/**
 * Pagina collegata a una categoria o, se non ce n'è, alla prima categoria madre che ne ha una:
 * "Spettacoli e incontri" e "Partecipazioni" stanno nella pagina di "Eventi".
 *
 * @param WP_Term $term Categoria.
 * @return int ID della pagina o 0.
 */
function vv_page_for_term( $term ) {
	foreach ( array_merge( array( $term->term_id ), get_ancestors( $term->term_id, 'category', 'taxonomy' ) ) as $id ) {
		$cat  = get_term( $id, 'category' );
		$page = $cat && ! is_wp_error( $cat ) ? vv_page_for_category( $cat->slug ) : 0;
		if ( $page ) {
			return $page;
		}
	}
	return 0;
}

/**
 * Percorso di una categoria: dal menu, altrimenti pagina collegata (sua o di una madre, seguita
 * allora dalla categoria stessa), altrimenti la sola categoria.
 *
 * @param WP_Term $term Categoria.
 * @return array<int,array{0:string,1:string}>
 */
function vv_category_trail( $term ) {
	$trail = vv_menu_trail( 'taxonomy', 'category', $term->term_id );
	if ( null !== $trail ) {
		return $trail;
	}
	$page = vv_page_for_term( $term );
	if ( $page ) {
		$trail = vv_page_trail( $page );
		if ( vv_page_for_category( $term->slug ) !== $page ) {
			$trail[] = array( $term->name, get_category_link( $term ) );
		}
		return $trail;
	}
	return array( array( $term->name, get_category_link( $term ) ) );
}

/**
 * Voci del breadcrumb per la richiesta corrente, dalla home all'ultima (senza link).
 *
 * @return array<int,array{0:string,1:string}>
 */
function vv_breadcrumb_items() {
	$items = array( array( 'Home', home_url( '/' ) ) );

	if ( is_front_page() ) {
		return array();
	} elseif ( is_page() ) {
		$items = array_merge( $items, vv_page_trail( get_queried_object_id() ) );
	} elseif ( is_singular( 'post' ) ) {
		$post    = get_queried_object();
		$section = array();
		$cats    = array_filter( get_the_category( $post->ID ), static fn( $c ) => 'nessuna-categoria' !== $c->slug && 'uncategorized' !== $c->slug );
		// Prima una categoria con pagina collegata (Soci → Chi siamo), poi una presente nel menu, poi la prima.
		foreach ( $cats as $cat ) {
			if ( vv_page_for_term( $cat ) ) {
				$section = vv_page_trail( vv_page_for_term( $cat ) );
				break;
			}
		}
		if ( ! $section ) {
			foreach ( $cats as $cat ) {
				$trail = vv_menu_trail( 'taxonomy', 'category', $cat->term_id );
				if ( $trail ) {
					$section = $trail;
					break;
				}
			}
		}
		if ( ! $section && $cats ) {
			$first   = reset( $cats );
			$section = array( array( $first->name, get_category_link( $first ) ) );
		}
		$items   = array_merge( $items, $section );
		$items[] = array( get_the_title( $post ), get_permalink( $post ) );
	} elseif ( is_category() ) {
		$items = array_merge( $items, vv_category_trail( get_queried_object() ) );
	} elseif ( is_search() ) {
		$items[] = array( sprintf( 'Risultati per “%s”', get_search_query() ), '' );
	} elseif ( is_404() ) {
		$items[] = array( 'Pagina non trovata', '' );
	} elseif ( is_archive() ) {
		$items[] = array( wp_strip_all_tags( get_the_archive_title() ), '' );
	} else {
		return array();
	}

	// Niente doppioni consecutivi (es. voce di menu e pagina con lo stesso titolo).
	$clean = array();
	foreach ( $items as $item ) {
		$item[0] = vv_plain_letters( wp_strip_all_tags( html_entity_decode( (string) $item[0], ENT_QUOTES ) ), false );
		if ( ! $clean || end( $clean )[1] !== $item[1] || '' === $item[1] ) {
			$clean[] = $item;
		}
	}
	return $clean;
}

/**
 * Voce del menu della sezione corrente: classe vv-current sulle voci il cui indirizzo è nel percorso
 * della pagina (un corso accende "Cosa facciamo" e "Corsi e laboratori", un ritaglio "Chi siamo").
 * WordPress segna da solo solo la pagina stessa e le sue madri, non la sezione di un articolo.
 *
 * @param string[] $classes Classi della voce.
 * @param WP_Post  $item    Voce di menu.
 * @return string[]
 */
function vv_menu_current_section( $classes, $item ) {
	static $urls = null;
	if ( null === $urls ) {
		$urls = array();
		foreach ( array_slice( vv_breadcrumb_items(), 1 ) as $crumb ) { // senza "Home"
			if ( $crumb[1] ) {
				$urls[] = untrailingslashit( $crumb[1] );
			}
		}
	}
	if ( $urls && ! empty( $item->url ) && in_array( untrailingslashit( $item->url ), $urls, true ) ) {
		$classes[] = 'vv-current';
	}
	return $classes;
}
add_filter( 'nav_menu_css_class', 'vv_menu_current_section', 10, 2 );

/**
 * HTML del breadcrumb, con dati strutturati BreadcrumbList per i motori di ricerca.
 */
function vv_breadcrumbs() {
	$items = vv_breadcrumb_items();
	if ( count( $items ) < 2 ) {
		return '';
	}
	$last = count( $items ) - 1;
	$html = '<nav class="vv-breadcrumbs" aria-label="Percorso"><ol>';
	$ld   = array();
	foreach ( $items as $i => list( $title, $url ) ) {
		if ( $i === $last || ! $url ) {
			$html .= '<li><span aria-current="page">' . esc_html( $title ) . '</span></li>';
		} else {
			$html .= '<li><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></li>';
		}
		$ld[] = array_filter( array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $title,
			'item'     => $url ? $url : null,
		) );
	}
	$html .= '</ol></nav>';
	$html .= '<script type="application/ld+json">' . wp_json_encode( array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $ld,
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
	return $html;
}

/**
 * Stampa il breadcrumb prima del contenuto (pagine, articoli, archivi, ricerca, 404).
 */
function vv_print_breadcrumbs() {
	$html = vv_breadcrumbs();
	if ( $html ) {
		echo '<div class="vv-breadcrumbs-wrap col-md-12">' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'sydney_before_content', 'vv_print_breadcrumbs', 5 );
