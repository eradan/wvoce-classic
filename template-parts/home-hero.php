<?php
/**
 * Hero della home: testi e immagine da Customizer → VivaVoce → Hero della home.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

$immagine = vv_immagine_hero();
$link_2   = vv_mod( 'vv_hero_cta2_url' );
$pulsanti = array(
	array( vv_mod( 'vv_hero_cta1_text' ), vv_mod( 'vv_hero_cta1_url' ), 'vv-button' ),
	array( vv_mod( 'vv_hero_cta2_text' ), $link_2 ? $link_2 : vv_pagina_url( 'socio' ), 'vv-button vv-button--ghost' ),
);
// Livelli dell'illustrazione animata (tela 1900×1615, posizioni in home.css): larghezza e altezza.
$pezzi = array(
	'mano'       => array( 502, 702 ),
	'trattino-1' => array( 198, 152 ),
	'trattino-2' => array( 268, 45 ),
	'trattino-3' => array( 218, 120 ),
	'testa'      => array( 1034, 1203 ),
);
?>
<section class="vv-hero" aria-labelledby="vv-hero-title">
	<div class="vv-hero__inner">
		<div class="vv-hero__text">
			<h1 id="vv-hero-title" class="vv-hero__title screen-reader-text"><?php echo esc_html( vv_mod( 'vv_hero_title' ) ); ?></h1>
			<p class="vv-hero__actions">
				<?php foreach ( $pulsanti as list( $testo, $indirizzo, $classe ) ) : ?>
					<?php if ( $testo && $indirizzo ) : ?>
						<a class="<?php echo esc_attr( $classe ); ?>" href="<?php echo esc_url( $indirizzo ); ?>"><?php echo esc_html( $testo ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</p>
		</div>
		<?php if ( $immagine && vv_hero_animata() ) : ?>
			<div class="vv-hero__art vv-hero__art--animata" aria-hidden="true">
				<div class="vv-hero__tela">
					<?php foreach ( $pezzi as $nome => list( $larghezza, $altezza ) ) : ?>
						<img class="vv-hero__pezzo vv-hero__<?php echo esc_attr( $nome ); ?>" src="<?php echo esc_url( VV_URI . '/assets/img/hero/' . $nome . '.webp' ); ?>" width="<?php echo (int) $larghezza; ?>" height="<?php echo (int) $altezza; ?>" alt=""<?php echo in_array( $nome, array( 'testa', 'mano' ), true ) ? ' fetchpriority="high"' : ''; ?> decoding="async">
					<?php endforeach; ?>
				</div>
			</div>
		<?php elseif ( $immagine ) : ?>
			<div class="vv-hero__art">
				<?php
				if ( vv_id_immagine_hero() ) {
					// Dalla libreria dei media, con larghezza, altezza e srcset: la pagina non salta mentre carica.
					echo wp_get_attachment_image( vv_id_immagine_hero(), 'full', false, array( 'alt' => '', 'fetchpriority' => 'high', 'decoding' => 'async', 'loading' => false ) );
				} else {
					printf( '<img src="%s" alt="" fetchpriority="high" decoding="async">', esc_url( $immagine ) );
				}
				?>
			</div>
		<?php endif; ?>
	</div>
</section>
