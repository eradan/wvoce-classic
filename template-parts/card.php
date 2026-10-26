<?php
/**
 * Card di articoli e pagine (griglie, caroselli, archivi).
 *
 * Argomenti: post (WP_Post), variante (evento|persona|stampa|citazione|pagina), accento (giallo|turchese|rosso), hx (h2|h3|h4).
 * citazione: ritaglio di giornale solo testo (testata e data, poi il titolo), senza immagine.
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

$vv_link     = get_permalink( $vv_post );
$vv_title    = get_the_title( $vv_post );
$vv_ritaglio = in_array( $vv_variante, array( 'stampa', 'citazione' ), true );
$vv_excerpt  = has_excerpt( $vv_post ) ? get_the_excerpt( $vv_post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $vv_post->post_content ) ), array( 'persona' => 18 )[ $vv_variante ] ?? ( $vv_ritaglio ? 12 : 22 ), '…' );
$vv_excerpt  = vv_plain_letters( $vv_excerpt, false );
$vv_size     = 'persona' === $vv_variante ? 'vv-persona' : 'vv-card';
$vv_img      = 'citazione' === $vv_variante ? '' : get_the_post_thumbnail( $vv_post, $vv_size, array(
	'class'   => 'vv-card__img',
	'loading' => 'lazy',
	'alt'     => '',
	// Larghezza della card nelle griglie e nei caroselli (components.css): così il browser sceglie la miniatura giusta
	'sizes'   => '(max-width: 599px) 85vw, (max-width: 991px) 50vw, 380px',
) );

// Nei ritagli la data giusta è nella riga della testata: quella dell'articolo spesso non coincide.
$vv_meta  = array();
$vv_dates = null;
if ( 'post' === $vv_post->post_type && 'persona' !== $vv_variante && ! $vv_ritaglio ) {
	$vv_dates  = vv_event_dates( $vv_post );
	$vv_meta[] = sprintf( '<time datetime="%s">%s</time>', esc_attr( wp_date( 'Y-m-d', $vv_dates['start'] ) ), esc_html( $vv_dates['label'] ) );
	$vv_luogo  = get_post_meta( $vv_post->ID, 'vv_luogo', true );
	if ( $vv_luogo ) {
		$vv_meta[] = esc_html( $vv_luogo );
	}
}

// Eventi: data a foglietto sulla foto; quelli già finiti hanno la fascetta "Completato" sull'angolo.
$vv_badge    = '';
$vv_chip     = null;
$vv_concluso = false;
if ( 'evento' === $vv_variante ) {
	$vv_chip     = $vv_dates ? vv_date_chip( $vv_dates ) : null;
	$vv_concluso = $vv_dates && $vv_dates['has_meta'] && $vv_dates['end'] < time();
	$vv_cats     = vv_post_categories( $vv_post->ID );
	if ( $vv_cats ) {
		$vv_badge = $vv_cats[0]->name;
	}
}

$vv_classes = array( 'vv-card', 'vv-card--' . $vv_variante, 'vv-accent--' . $vv_accento );
if ( ! $vv_img && 'citazione' !== $vv_variante ) {
	$vv_classes[] = 'vv-card--no-img';
}
if ( $vv_concluso ) {
	$vv_classes[] = 'vv-card--concluso';
}
?>
<article class="<?php echo esc_attr( implode( ' ', $vv_classes ) ); ?>">
	<?php if ( 'citazione' !== $vv_variante ) : ?>
		<div class="vv-card__media">
			<?php echo $vv_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( $vv_chip ) : ?>
				<?php // La data intera, per gli screen reader, è nella riga sotto il titolo. ?>
				<span class="vv-data-chip" aria-hidden="true"><span class="vv-data-chip__alto"><?php echo esc_html( $vv_chip['alto'] ); ?></span><span class="vv-data-chip__basso"><?php echo esc_html( $vv_chip['basso'] ); ?></span></span>
			<?php endif; ?>
			<?php if ( $vv_badge ) : ?>
				<span class="vv-badge"><?php echo esc_html( $vv_badge ); ?></span>
			<?php endif; ?>
			<?php if ( $vv_concluso ) : ?>
				<span class="vv-fascetta">Completato</span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<div class="vv-card__body">
		<?php if ( $vv_meta ) : ?>
			<p class="vv-card__meta"><?php echo implode( ' · ', $vv_meta ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php endif; ?>
		<<?php echo tag_escape( $vv_hx ); ?> class="vv-card__title">
			<a href="<?php echo esc_url( $vv_link ); ?>" class="vv-card__link"><?php echo esc_html( sprintf( 'citazione' === $vv_variante ? '«%s»' : '%s', wp_strip_all_tags( $vv_title ) ) ); ?></a>
		</<?php echo tag_escape( $vv_hx ); ?>>
		<?php if ( $vv_excerpt ) : ?>
			<p class="<?php echo $vv_ritaglio ? 'vv-card__source' : 'vv-card__text'; ?>"><?php echo esc_html( $vv_excerpt ); ?></p>
		<?php endif; ?>
	</div>
</article>
