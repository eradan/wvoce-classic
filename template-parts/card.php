<?php
/**
 * Card di un articolo o di una pagina.
 *
 * Argomenti: post, variante (evento|persona|stampa|citazione|pagina), accento (giallo|turchese|rosso),
 * titolo_livello (h2|h3|h4). La citazione è un ritaglio di giornale solo testo, senza immagine.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

$contenuto = get_post( $args['post'] ?? null );
if ( ! $contenuto ) {
	return;
}
$variante  = $args['variante'] ?? 'evento';
$livello   = $args['titolo_livello'] ?? 'h3';
$ritaglio  = in_array( $variante, array( 'stampa', 'citazione' ), true );
$citazione = 'citazione' === $variante;

if ( has_excerpt( $contenuto ) ) {
	$riassunto = get_the_excerpt( $contenuto );
} elseif ( post_password_required( $contenuto ) ) {
	$riassunto = '';
} else {
	$parole = match ( true ) {
		'persona' === $variante => 18,
		$ritaglio               => 12,
		default                 => 22,
	};
	$riassunto = vv_lettere_semplici( wp_trim_words( wp_strip_all_tags( strip_shortcodes( $contenuto->post_content ) ), $parole, '…' ), false );
}

$immagine = $citazione ? '' : get_the_post_thumbnail( $contenuto, 'persona' === $variante ? 'vv-persona' : 'vv-card', array(
	'class'   => 'vv-card__img',
	'loading' => 'lazy',
	'alt'     => '',
	'sizes'   => '(max-width: 599px) 85vw, (max-width: 991px) 50vw, 380px', // larghezze della card in components.css
) );

// Nei ritagli la data giusta è nella testata, dentro il riassunto: quella dell'articolo spesso non coincide.
$date = 'post' === $contenuto->post_type && 'persona' !== $variante && ! $ritaglio ? vv_date_articolo( $contenuto ) : null;
$riga = array();
if ( $date ) {
	$riga[] = sprintf( '<time datetime="%s">%s</time>', esc_attr( wp_date( 'Y-m-d', $date['inizio'] ) ), esc_html( $date['etichetta'] ) );
	$luogo  = get_post_meta( $contenuto->ID, 'vv_luogo', true );
	if ( $luogo ) {
		$riga[] = esc_html( $luogo );
	}
}

$evento    = 'evento' === $variante;
$foglietto = $evento && $date ? vv_foglietto_data( $date ) : null;
$fase      = $evento && $date && $date['con_date'] ? vv_fase_evento( $date, time() ) : '';
$categorie = $evento ? vv_categorie_articolo( $contenuto->ID ) : array();
$fasi      = array(
	'a-breve'  => 'A breve',
	'in-corso' => 'In corso',
	'concluso' => 'Concluso',
);

$classi = array( 'vv-card', 'vv-card--' . $variante, 'vv-accent--' . ( $args['accento'] ?? 'giallo' ) );
if ( ! $immagine && ! $citazione ) {
	$classi[] = 'vv-card--no-img';
}
?>
<article class="<?php echo esc_attr( implode( ' ', $classi ) ); ?>">
	<?php if ( ! $citazione ) : ?>
		<div class="vv-card__media">
			<?php echo $immagine; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( $foglietto ) : ?>
				<?php // Per gli screen reader la data intera è nella riga sotto il titolo. ?>
				<span class="vv-data-chip" aria-hidden="true"><span class="vv-data-chip__alto"><?php echo esc_html( $foglietto['alto'] ); ?></span><span class="vv-data-chip__basso"><?php echo esc_html( $foglietto['basso'] ); ?></span></span>
			<?php endif; ?>
			<?php if ( $categorie ) : ?>
				<span class="vv-badge"><?php echo esc_html( $categorie[0]->name ); ?></span>
			<?php endif; ?>
			<?php if ( $fase ) : ?>
				<span class="vv-fascetta vv-fascetta--<?php echo esc_attr( $fase ); ?>"><?php echo esc_html( $fasi[ $fase ] ); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<div class="vv-card__body">
		<?php if ( $riga ) : ?>
			<p class="vv-card__meta"><?php echo implode( ' · ', $riga ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php endif; ?>
		<<?php echo tag_escape( $livello ); ?> class="vv-card__title">
			<a href="<?php echo esc_url( get_permalink( $contenuto ) ); ?>" class="vv-card__link"><?php echo esc_html( sprintf( $citazione ? '«%s»' : '%s', wp_strip_all_tags( get_the_title( $contenuto ) ) ) ); ?></a>
		</<?php echo tag_escape( $livello ); ?>>
		<?php if ( $riassunto ) : ?>
			<p class="<?php echo $ritaglio ? 'vv-card__source' : 'vv-card__text'; ?>"><?php echo esc_html( $riassunto ); ?></p>
		<?php endif; ?>
	</div>
</article>
