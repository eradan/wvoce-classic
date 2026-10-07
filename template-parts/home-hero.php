<?php
/**
 * Hero della home. Testi e immagine: Customizer → VivaVoce → Hero della home.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

$vv_image = vv_hero_image_url();
$vv_ctas  = array(
	array( vv_mod( 'vv_hero_cta1_text' ), vv_mod( 'vv_hero_cta1_url' ), 'vv-button' ),
	array( vv_mod( 'vv_hero_cta2_text' ), vv_mod( 'vv_hero_cta2_url' ), 'vv-button vv-button--ghost' ),
);
?>
<section class="vv-hero" aria-labelledby="vv-hero-title">
	<div class="vv-hero__inner">
		<div class="vv-hero__text">
			<?php // Il titolo non si vede, come nel sito originale: resta per screen reader e motori di ricerca. ?>
			<h1 id="vv-hero-title" class="vv-hero__title"><?php echo esc_html( vv_mod( 'vv_hero_title' ) ); ?></h1>
			<p class="vv-hero__actions">
				<?php foreach ( $vv_ctas as list( $vv_label, $vv_url, $vv_class ) ) : ?>
					<?php if ( $vv_label && $vv_url ) : ?>
						<a class="<?php echo esc_attr( $vv_class ); ?>" href="<?php echo esc_url( $vv_url ); ?>"><?php echo esc_html( $vv_label ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</p>
		</div>
		<?php if ( $vv_image && vv_hero_animata() ) : ?>
			<?php
			// Livelli ritagliati dall'illustrazione (tela 1900×1615): posizioni e animazioni in home.css.
			$vv_pezzi = array(
				'mano'       => array( 502, 702 ),
				'trattino-1' => array( 198, 152 ),
				'trattino-2' => array( 268, 45 ),
				'trattino-3' => array( 218, 120 ),
				'testa'      => array( 1034, 1203 ),
			);
			?>
			<div class="vv-hero__art vv-hero__art--animata" aria-hidden="true">
				<div class="vv-hero__tela">
					<?php foreach ( $vv_pezzi as $vv_nome => list( $vv_w, $vv_h ) ) : ?>
						<img class="vv-hero__pezzo vv-hero__<?php echo esc_attr( $vv_nome ); ?>" src="<?php echo esc_url( VV_URI . '/assets/img/hero/' . $vv_nome . '.webp' ); ?>" width="<?php echo (int) $vv_w; ?>" height="<?php echo (int) $vv_h; ?>" alt=""<?php echo in_array( $vv_nome, array( 'testa', 'mano' ), true ) ? ' fetchpriority="high"' : ''; ?> decoding="async">
					<?php endforeach; ?>
				</div>
			</div>
		<?php elseif ( $vv_image ) : ?>
			<div class="vv-hero__art">
				<?php
				$vv_media = attachment_url_to_postid( $vv_image );
				if ( $vv_media ) {
					// Dalla libreria dei media: larghezza, altezza e srcset, la pagina non salta mentre carica.
					echo wp_get_attachment_image( $vv_media, 'full', false, array( 'alt' => '', 'fetchpriority' => 'high', 'decoding' => 'async', 'loading' => false ) );
				} else {
					printf( '<img src="%s" alt="" fetchpriority="high" decoding="async">', esc_url( $vv_image ) );
				}
				?>
			</div>
		<?php endif; ?>
	</div>
</section>
