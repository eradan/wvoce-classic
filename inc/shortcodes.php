<?php
/**
 * Shortcode per le pagine e i widget del footer.
 *
 *   [vv_cards categoria="eventi" periodo="futuro"]       gli eventi non ancora finiti, dal più vicino (o una frase "niente in programma")
 *   [vv_cards categoria="eventi" periodo="dal-3-mesi" layout="carosello"]
 *                                        anche quelli finiti da meno di 3 mesi; senza nessuno, gli ultimi eventi
 *   [vv_cards categoria="eventi" periodo="dal-3-mesi" numero="9" layout="agenda" lotto="3"]
 *                                        righe compatte su telefono; "Mostra altri" apre 3 card alla volta
 *   [vv_cards categoria="soci" variante="persona" ordine="titolo"]
 *   [vv_cards categoria="rassegna-stampa" variante="citazione" layout="carosello"]   ritagli solo testo
 *                                        Un [vv_cards] non ripete gli articoli di quelli prima nella stessa pagina.
 *   [vv_sottopagine]                     card delle pagine figlie della pagina corrente
 *   [vv_sostienici]                      socio / donazione / 5×1000
 *   [vv_dati_fiscali]                    CF, P.IVA, IBAN con pulsante "copia" (mostra="iban" o "cf": uno solo)
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

function vv_sc_cards( $atts ) {
	$atts = shortcode_atts( array(
		'categoria' => '',
		'numero'    => 6,
		'variante'  => 'evento',
		'ordine'    => 'data',
		'layout'    => 'griglia',
		'periodo'   => '',
		'lotto'     => 0,
	), $atts, 'vv_cards' );

	static $gia_mostrati = array();

	$categorie = sanitize_text_field( $atts['categoria'] );
	$numero    = (int) $atts['numero'];
	$extra     = 'titolo' === $atts['ordine'] ? array( 'orderby' => 'title', 'order' => 'ASC' ) : array();
	if ( $gia_mostrati ) {
		$extra['post__not_in'] = $gia_mostrati;
	}

	if ( in_array( $atts['periodo'], array( 'futuro', 'dal-3-mesi' ), true ) ) {
		$articoli = vv_eventi_del_periodo( $categorie, $atts['periodo'], $numero, $extra );
		if ( ! $articoli && 'futuro' === $atts['periodo'] ) {
			return '<p class="vv-empty">Al momento non ci sono appuntamenti in programma: torna a trovarci presto!</p>';
		}
		if ( ! $articoli ) {
			$articoli = vv_eventi_del_periodo( $categorie, 'tutti', $numero, $extra );
		}
	} else {
		$articoli = vv_articoli( $categorie, $numero, $extra );
	}
	$gia_mostrati = array_merge( $gia_mostrati, wp_list_pluck( $articoli, 'ID' ) );

	return vv_griglia_card( $articoli, sanitize_key( $atts['variante'] ), array(
		'layout' => in_array( $atts['layout'], array( 'carosello', 'agenda' ), true ) ? $atts['layout'] : 'griglia',
		'lotto'  => max( 0, (int) $atts['lotto'] ),
	) );
}
add_shortcode( 'vv_cards', 'vv_sc_cards' );

function vv_sc_sottopagine() {
	$madre = (int) get_the_ID();
	// Fuori da una pagina la madre sarebbe 0: tutte le pagine di primo livello.
	return $madre ? vv_card_sottopagine( $madre ) : '';
}
add_shortcode( 'vv_sottopagine', 'vv_sc_sottopagine' );

/**
 * "Diventa socio" e "Fai una donazione" con titolo, riassunto e immagine delle pagine scelte nel
 * Customizer; il 5×1000, che una pagina non ce l'ha, con codice fiscale e immagine scelti lì.
 */
function vv_sc_sostienici() {
	$immagine = array(
		'class'   => 'vv-card__img',
		'loading' => 'lazy',
		'alt'     => '',
		'sizes'   => '(max-width: 991px) 100vw, 380px',
	);
	$pagina   = static function ( $quale, $accento, $azione ) use ( $immagine ) {
		$id = vv_pagina_id( $quale );
		return $id ? array(
			'accento' => $accento,
			'titolo'  => wp_strip_all_tags( get_the_title( $id ) ),
			'testo'   => has_excerpt( $id ) ? get_the_excerpt( $id ) : '',
			'link'    => get_permalink( $id ),
			'azione'  => $azione,
			'foto'    => get_the_post_thumbnail( $id, 'vv-card', $immagine ),
		) : null;
	};
	$cf       = vv_mod( 'vv_cf' );
	$riquadri = array_filter( array(
		$pagina( 'socio', 'giallo', 'Iscriviti' ),
		$pagina( 'donazione', 'turchese', 'Dona ora' ),
		$cf ? array(
			'accento' => 'rosso',
			'titolo'  => 'Il tuo 5×1000',
			'testo'   => 'Nella dichiarazione dei redditi scrivi il nostro codice fiscale:',
			'cf'      => $cf,
			'foto'    => wp_get_attachment_image( (int) vv_mod( 'vv_img_5x1000' ), 'vv-card', false, $immagine ),
		) : null,
	) );

	$html = '<div class="vv-grid vv-grid--3 vv-sostienici">';
	foreach ( $riquadri as $riquadro ) {
		$html .= '<div class="vv-card vv-card--cta vv-accent--' . esc_attr( $riquadro['accento'] ) . '">';
		$html .= $riquadro['foto'] ? '<div class="vv-card__media">' . $riquadro['foto'] . '</div>' : '';
		$html .= '<div class="vv-card__body"><h3 class="vv-card__title">' . esc_html( $riquadro['titolo'] ) . '</h3>';
		$html .= $riquadro['testo'] ? '<p class="vv-card__text">' . esc_html( $riquadro['testo'] ) . '</p>' : '';
		$html .= isset( $riquadro['cf'] )
			? vv_valore_copiabile( $riquadro['cf'], 'Copia il codice fiscale' )
			: '<a class="vv-button" href="' . esc_url( $riquadro['link'] ) . '">' . esc_html( $riquadro['azione'] ) . '</a>';
		$html .= '</div></div>';
	}
	return $html . '</div>';
}
add_shortcode( 'vv_sostienici', 'vv_sc_sostienici' );

/**
 * Il pulsante "Copia" compare solo con JavaScript.
 */
function vv_valore_copiabile( $valore, $etichetta ) {
	return sprintf(
		'<span class="vv-copy"><code class="vv-copy__value">%1$s</code><button type="button" class="vv-copy__btn" data-vv-copy="%1$s" aria-label="%2$s" hidden>Copia</button></span>',
		esc_attr( $valore ),
		esc_attr( $etichetta )
	);
}

function vv_sc_dati_fiscali( $atts = array() ) {
	$atts   = shortcode_atts( array( 'mostra' => 'cf,piva,iban' ), $atts, 'vv_dati_fiscali' );
	$mostra = array_map( 'trim', explode( ',', strtolower( (string) $atts['mostra'] ) ) );
	$dati   = array(
		'cf'   => array( 'CF (5×1000)', vv_mod( 'vv_cf' ), 'Copia il codice fiscale' ),
		'piva' => array( 'P.IVA', vv_mod( 'vv_piva' ), 'Copia la partita IVA' ),
		'iban' => array( 'IBAN', vv_mod( 'vv_iban' ), 'Copia l\'IBAN' ),
	);
	$elenco = '<dl class="vv-dati">';
	foreach ( $dati as $chiave => list( $nome, $valore, $etichetta ) ) {
		if ( $valore && in_array( $chiave, $mostra, true ) ) {
			$elenco .= '<dt>' . esc_html( $nome ) . '</dt><dd>' . vv_valore_copiabile( $valore, $etichetta ) . '</dd>';
		}
	}
	return $elenco . '</dl>';
}
add_shortcode( 'vv_dati_fiscali', 'vv_sc_dati_fiscali' );
