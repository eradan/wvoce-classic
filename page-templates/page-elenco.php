<?php
/**
 * Template Name: Pagina elenco (in programma + archivio)
 *
 * Il testo della pagina, poi gli articoli delle "Categorie collegate": in programma e archivio per anno.
 *
 * @package VivaVoce
 */

get_header();

do_action( 'sydney_before_content' );

vv_pagina_con_blocco_finale( static fn( $pagina_id ) => vv_elenco_html( (string) get_post_meta( $pagina_id, 'vv_categoria', true ) ) );

do_action( 'sydney_after_content' );

do_action( 'sydney_get_sidebar' );

get_footer();
