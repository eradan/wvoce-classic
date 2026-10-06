<?php
/**
 * Template Name: Pagina elenco (in programma + archivio)
 *
 * Contenuto della pagina come introduzione, poi gli articoli delle categorie indicate
 * nel riquadro "VivaVoce: Pagina elenco": prima quelli in programma, poi l'archivio per anno.
 *
 * @package VivaVoce
 */

get_header();

do_action( 'sydney_before_content' );

vv_page_with_extra(
	static function ( $page_id ) {
		return vv_render_elenco( (string) get_post_meta( $page_id, 'vv_categoria', true ) );
	}
);

do_action( 'sydney_after_content' );

do_action( 'sydney_get_sidebar' );

get_footer();
