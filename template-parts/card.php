<?php
/**
 * Card di articoli e pagine (griglie, caroselli, archivi).
 *
 * Argomenti: post (WP_Post), variante (evento|persona|stampa|pagina), accento (giallo|turchese|rosso), hx (h2|h3|h4).
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

$vv_post     = get_post( $args['post'] ?? null );
$vv_variante = $args['variante'] ?? 'evento';
$vv_accento  = $args['accento'] ?? 'giallo';
$vv_hx       = $args['hx'] ?? 'h3';

if ( ! $vv_post ) {
	return;
}

$vv_link    = get_permalink( $vv_post );
$vv_title   = get_the_title( $vv_post );
$vv_excerpt = has_excerpt( $vv_post ) ? get_the_excerpt( $vv_post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $vv_post->post_content ) ), array( 'persona' => 18, 'stampa' => 12 )[ $vv_variante ] ?? 22, '…' );
$vv_excerpt = vv_plain_letters( $vv_excerpt, false );
$vv_size    = 'persona' === $vv_variante ? 'vv-persona' : 'vv-card';
$vv_img     = get_the_post_thumbnail( $vv_post, $vv_size, array(
	'class'   => 'vv-card__img',
	'loading' => 'lazy',
	'alt'     => '',
) );

$vv_meta = array();
if ( 'post' === $vv_post->post_type && 'persona' !== $vv_variante ) {
	$vv_dates  = vv_event_dates( $vv_post );
	$vv_meta[] = sprintf( '<time datetime="%s">%s</time>', esc_attr( wp_date( 'Y-m-d', $vv_dates['start'] ) ), esc_html( $vv_dates['label'] ) );
	$vv_luogo  = get_post_meta( $vv_post->ID, 'vv_luogo', true );
	if ( $vv_luogo ) {
		$vv_meta[] = esc_html( $vv_luogo );
	}
}

$vv_badge = '';
if ( 'evento' === $vv_variante ) {
	$vv_cats = get_the_category( $vv_post->ID );
	if ( $vv_cats ) {
		$vv_badge = $vv_cats[0]->name;
	}
}

$vv_classes = array( 'vv-card', 'vv-card--' . $vv_variante, 'vv-accent--' . $vv_accento );
if ( ! $vv_img ) {
	$vv_classes[] = 'vv-card--no-img';
}
?>
<article class="<?php echo esc_attr( implode( ' ', $vv_classes ) ); ?>">
	<div class="vv-card__media">
		<?php if ( $vv_img ) : ?>
			<?php echo $vv_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php else : ?>
			<?php echo vv_svg( 'blob', 'vv-card__blob' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
		<?php if ( $vv_badge ) : ?>
			<span class="vv-badge"><?php echo esc_html( $vv_badge ); ?></span>
		<?php endif; ?>
	</div>
	<div class="vv-card__body">
		<?php if ( $vv_meta ) : ?>
			<p class="vv-card__meta"><?php echo implode( ' · ', $vv_meta ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php endif; ?>
		<<?php echo tag_escape( $vv_hx ); ?> class="vv-card__title">
			<a href="<?php echo esc_url( $vv_link ); ?>" class="vv-card__link"><?php echo esc_html( wp_strip_all_tags( $vv_title ) ); ?></a>
		</<?php echo tag_escape( $vv_hx ); ?>>
		<?php if ( $vv_excerpt ) : ?>
			<p class="<?php echo 'stampa' === $vv_variante ? 'vv-card__source' : 'vv-card__text'; ?>"><?php echo esc_html( $vv_excerpt ); ?></p>
		<?php endif; ?>
	</div>
</article>
