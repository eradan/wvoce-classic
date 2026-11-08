<?php
/**
 * Template Name: Pagina hub (card delle sottopagine)
 *
 * Il testo della pagina, poi una card per ogni pagina figlia nell'ordine degli attributi di pagina;
 * con [vv_sottopagine] nel testo le card stanno lì.
 *
 * @package VivaVoce
 */

get_header();

do_action( 'sydney_before_content' );

vv_pagina_con_blocco_finale( 'vv_card_hub' );

do_action( 'sydney_after_content' );

do_action( 'sydney_get_sidebar' );

get_footer();
