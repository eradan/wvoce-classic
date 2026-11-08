<?php
/**
 * Percorso in alto ("Home › Chi siamo › Statuto") e voce di menu della sezione corrente. Segue il menu
 * principale, così rispecchia la struttura che vede chi naviga; fuori dal menu le pagine madri, per gli
 * articoli la pagina collegata alla loro categoria ("Categorie collegate").
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<int,WP_Post> Le voci del menu principale per ID.
 */
function vv_voci_menu() {
	static $voci = null;
	if ( null !== $voci ) {
		return $voci;
	}
	$voci  = array();
	$menu  = get_nav_menu_locations()['primary'] ?? 0;
	$tutte = $menu ? (array) wp_get_nav_menu_items( $menu ) : array();
	foreach ( $tutte as $voce ) {
		$voci[ (int) $voce->ID ] = $voce;
	}
	return $voci;
}

/**
 * @param string $tipo    post_type | taxonomy.
 * @param string $oggetto page | category.
 * @return array<int,array{0:string,1:string}>|null Coppie [titolo, indirizzo], null se non è nel menu.
 */
function vv_percorso_menu( $tipo, $oggetto, $oggetto_id ) {
	$voci = vv_voci_menu();
	foreach ( $voci as $voce ) {
		if ( $voce->type !== $tipo || $voce->object !== $oggetto || (int) $voce->object_id !== (int) $oggetto_id ) {
			continue;
		}
		$percorso = array();
		for ( $corrente = $voce; $corrente; $corrente = $voci[ (int) $corrente->menu_item_parent ] ?? null ) {
			array_unshift( $percorso, array( $corrente->title, $corrente->url ) );
			if ( count( $percorso ) > 6 ) {
				break; // un menu con un ciclo non blocca la pagina
			}
		}
		return $percorso;
	}
	return null;
}

/**
 * @return array<int,array{0:string,1:string}>
 */
function vv_percorso_pagina( $pagina_id ) {
	$percorso = vv_percorso_menu( 'post_type', 'page', $pagina_id );
	if ( null !== $percorso ) {
		return $percorso;
	}
	$percorso = array();
	foreach ( array_reverse( get_post_ancestors( $pagina_id ) ) as $madre ) {
		// Una madre non pubblicata resta senza link: darebbe 404 ai visitatori.
		$percorso[] = array( get_the_title( $madre ), 'publish' === get_post_status( $madre ) ? get_permalink( $madre ) : '' );
	}
	$percorso[] = array( get_the_title( $pagina_id ), get_permalink( $pagina_id ) );
	return $percorso;
}

/**
 * Dal campo "Categorie collegate" delle pagine; 0 se nessuna.
 */
function vv_pagina_della_categoria( $slug ) {
	static $pagine = null;
	if ( null === $pagine ) {
		$pagine    = array();
		$collegate = get_posts( array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => 'vv_categoria', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'no_found_rows'  => true,
		) );
		foreach ( $collegate as $pagina ) {
			foreach ( explode( ',', (string) get_post_meta( $pagina->ID, 'vv_categoria', true ) ) as $categoria ) {
				if ( $categoria && ! isset( $pagine[ $categoria ] ) ) {
					$pagine[ $categoria ] = $pagina->ID;
				}
			}
		}
	}
	return $pagine[ $slug ] ?? 0;
}

/**
 * Anche di una categoria madre: "Spettacoli e incontri" sta nella pagina di "Eventi". 0 se nessuna.
 */
function vv_pagina_del_termine( WP_Term $categoria ) {
	foreach ( array_merge( array( $categoria->term_id ), get_ancestors( $categoria->term_id, 'category', 'taxonomy' ) ) as $id ) {
		$termine = get_term( $id, 'category' );
		$pagina  = $termine && ! is_wp_error( $termine ) ? vv_pagina_della_categoria( $termine->slug ) : 0;
		if ( $pagina ) {
			return $pagina;
		}
	}
	return 0;
}

/**
 * Dal menu; altrimenti la pagina collegata (seguita dalla categoria, se la pagina è di una madre);
 * altrimenti la categoria sola.
 *
 * @return array<int,array{0:string,1:string}>
 */
function vv_percorso_categoria( WP_Term $categoria ) {
	$percorso = vv_percorso_menu( 'taxonomy', 'category', $categoria->term_id );
	if ( null !== $percorso ) {
		return $percorso;
	}
	$voce   = array( $categoria->name, get_category_link( $categoria ) );
	$pagina = vv_pagina_del_termine( $categoria );
	if ( ! $pagina ) {
		return array( $voce );
	}
	$percorso = vv_percorso_pagina( $pagina );
	if ( vv_pagina_della_categoria( $categoria->slug ) !== $pagina ) {
		$percorso[] = $voce;
	}
	return $percorso;
}

/**
 * Prima una categoria con pagina collegata (Soci → Chi siamo), poi una presente nel menu, poi la prima.
 *
 * @return array<int,array{0:string,1:string}>
 */
function vv_percorso_articolo( WP_Post $articolo ) {
	$categorie = vv_categorie_articolo( $articolo->ID );
	$sezione   = array();
	foreach ( $categorie as $categoria ) {
		$pagina = vv_pagina_del_termine( $categoria );
		if ( $pagina ) {
			$sezione = vv_percorso_pagina( $pagina );
			break;
		}
	}
	if ( ! $sezione ) {
		foreach ( $categorie as $categoria ) {
			$sezione = (array) vv_percorso_menu( 'taxonomy', 'category', $categoria->term_id );
			if ( $sezione ) {
				break;
			}
		}
	}
	if ( ! $sezione && $categorie ) {
		$sezione = array( array( $categorie[0]->name, get_category_link( $categorie[0] ) ) );
	}
	$sezione[] = array( get_the_title( $articolo ), get_permalink( $articolo ) );
	return $sezione;
}

/**
 * Calcolate una volta: servono al percorso e alla voce di menu della sezione corrente.
 *
 * @return array<int,array{0:string,1:string}> Dalla home all'ultima voce.
 */
function vv_voci_percorso() {
	static $voci = null;

	$voci ??= vv_costruisci_percorso();
	return $voci;
}

/**
 * @return array<int,array{0:string,1:string}>
 */
function vv_costruisci_percorso() {
	$dopo_home = match ( true ) {
		is_front_page()       => array(),
		is_page()             => vv_percorso_pagina( get_queried_object_id() ),
		is_singular( 'post' ) => vv_percorso_articolo( get_queried_object() ),
		is_category()         => vv_percorso_categoria( get_queried_object() ),
		is_search()           => array( array( sprintf( 'Risultati per “%s”', get_search_query() ), '' ) ),
		is_404()              => array( array( 'Pagina non trovata', '' ) ),
		is_archive()          => array( array( get_the_archive_title(), '' ) ),
		default               => array(),
	};
	if ( ! $dopo_home ) {
		return array();
	}
	// Niente doppioni consecutivi, come la voce di menu e la pagina con lo stesso indirizzo.
	$voci = array();
	foreach ( array_merge( array( array( 'Home', home_url( '/' ) ) ), $dopo_home ) as list( $titolo, $indirizzo ) ) {
		if ( ! $voci || end( $voci )[1] !== $indirizzo || '' === $indirizzo ) {
			$voci[] = array( vv_testo_semplice( $titolo ), $indirizzo );
		}
	}
	return $voci;
}

/**
 * WordPress segna da solo la pagina e le sue madri, non la sezione di un articolo: qui si accendono
 * tutte le voci del percorso (un corso accende "Cosa facciamo" e "Corsi e laboratori").
 */
function vv_sezione_corrente_menu( $classi, $voce ) {
	static $indirizzi = null;

	$indirizzi ??= array_map( 'untrailingslashit', array_filter( array_column( array_slice( vv_voci_percorso(), 1 ), 1 ) ) );
	if ( ! empty( $voce->url ) && in_array( untrailingslashit( $voce->url ), $indirizzi, true ) ) {
		$classi[] = 'vv-current';
	}
	return $classi;
}
add_filter( 'nav_menu_css_class', 'vv_sezione_corrente_menu', 10, 2 );

/**
 * Con i dati strutturati BreadcrumbList, se non li scrive già un plugin SEO.
 */
function vv_percorso_html() {
	$voci = vv_voci_percorso();
	if ( count( $voci ) < 2 ) {
		return '';
	}
	$ultima = count( $voci ) - 1;
	$html   = '<nav class="vv-breadcrumbs" aria-label="Percorso"><ol>';
	$schema = array();
	foreach ( $voci as $i => list( $titolo, $indirizzo ) ) {
		if ( $i === $ultima || ! $indirizzo ) {
			// Una voce senza link in mezzo (madre non pubblicata) non è la pagina corrente.
			$html .= '<li><span' . ( $i === $ultima ? ' aria-current="page"' : '' ) . '>' . esc_html( $titolo ) . '</span></li>';
		} else {
			$html .= '<li><a href="' . esc_url( $indirizzo ) . '">' . esc_html( $titolo ) . '</a></li>';
		}
		$schema[] = array_filter( array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $titolo,
			'item'     => $indirizzo,
		) );
	}
	$html .= '</ol></nav>';
	if ( vv_condivisione_plugin_seo() ) {
		return $html;
	}
	return $html . '<script type="application/ld+json">' . wp_json_encode( array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $schema,
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>';
}

function vv_stampa_percorso() {
	$html = vv_percorso_html();
	if ( $html ) {
		echo '<div class="vv-breadcrumbs-wrap col-md-12">' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'sydney_before_content', 'vv_stampa_percorso', 5 );
