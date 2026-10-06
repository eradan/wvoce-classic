<?php
/**
 * Template Name: Pagina hub (card delle sottopagine)
 *
 * Contenuto della pagina come introduzione, poi una card per ogni pagina figlia
 * (immagine in evidenza + estratto, ordine = "Ordine" negli attributi di pagina).
 * Se il testo contiene [vv_sottopagine] le card stanno lì e non vengono ripetute in fondo.
 *
 * @package VivaVoce
 */

get_header();

do_action( 'sydney_before_content' );

vv_page_with_extra( 'vv_hub_cards' );

do_action( 'sydney_after_content' );

do_action( 'sydney_get_sidebar' );

get_footer();
