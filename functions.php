<?php
/**
 * VivaVoce: tema child di Sydney.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

define( 'VV_DIR', get_stylesheet_directory() );
define( 'VV_URI', get_stylesheet_directory_uri() );

require VV_DIR . '/inc/setup.php';
require VV_DIR . '/inc/contenuti.php';
require VV_DIR . '/inc/condivisione.php';
require VV_DIR . '/inc/dati-strutturati.php';
require VV_DIR . '/inc/email.php';
require VV_DIR . '/inc/sicurezza.php';
require VV_DIR . '/inc/customizer.php';
require VV_DIR . '/inc/meta.php';
require VV_DIR . '/inc/eventi.php';
require VV_DIR . '/inc/cards.php';
require VV_DIR . '/inc/shortcodes.php';
require VV_DIR . '/inc/home.php';
require VV_DIR . '/inc/archive.php';
require VV_DIR . '/inc/breadcrumbs.php';
require VV_DIR . '/inc/layout.php';
require VV_DIR . '/inc/single.php';
require VV_DIR . '/inc/page.php';
require VV_DIR . '/inc/redirects.php';

function vv_carica_stili_e_script() {
	$dipende_da = 'sydney-style-min';
	foreach ( array( 'tokens', 'base', 'layout', 'header', 'footer', 'components', 'home', 'pages', 'plugins', 'scuro' ) as $nome ) {
		$file = "/assets/css/{$nome}.css";
		wp_enqueue_style( "vv-{$nome}", VV_URI . $file, array( $dipende_da ), (string) filemtime( VV_DIR . $file ) );
		$dipende_da = "vv-{$nome}";
	}
	$palette = vv_pulisci_palette( vv_mod( 'vv_palette' ) );
	if ( $palette ) {
		$file = "/assets/css/palette/{$palette}.css";
		wp_enqueue_style( 'vv-palette', VV_URI . $file, array( $dipende_da ), (string) filemtime( VV_DIR . $file ) );
	}

	wp_enqueue_script( 'vv-main', VV_URI . '/assets/js/vv.js', array(), (string) filemtime( VV_DIR . '/assets/js/vv.js' ), array(
		'in_footer' => true,
		'strategy'  => 'defer',
	) );
}
add_action( 'wp_enqueue_scripts', 'vv_carica_stili_e_script', 20 );

/**
 * Poppins normale e grassetto subito, senza il passaggio dal font di sistema.
 */
function vv_precarica_font() {
	foreach ( array( 'poppins-400-latin', 'poppins-700-latin' ) as $font ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( VV_URI . "/assets/fonts/{$font}.woff2" ) );
	}
}
add_action( 'wp_head', 'vv_precarica_font', 1 );

/**
 * Tema scelto dal visitatore, applicato prima che la pagina si disegni (niente lampo bianco).
 * Con la palette Natale le scelte sono natale (scuro con neve), chiaro e scuro, salvate a parte:
 * finito il Natale torna la scelta di sempre.
 */
function vv_tema_script() {
	$natale = 'natale' === vv_pulisci_palette( vv_mod( 'vv_palette' ) );
	$temi   = $natale
		? array(
			'scelte' => array( 'natale', 'chiaro', 'scuro' ),
			'chiave' => 'vv-tema-natale',
		)
		: array(
			'scelte' => array( 'chiaro', 'scuro', 'auto' ),
			'chiave' => 'vv-tema',
		);
	?>
<script>
(function (d, temi) {
	var buio = matchMedia('(prefers-color-scheme: dark)');
	var applica = function (scelta) {
		if (temi.scelte.indexOf(scelta) < 0) {
			scelta = temi.scelte[0];
		}
		d.setAttribute('data-vv-scelta', scelta);
		d.setAttribute('data-vv-tema', scelta === 'chiaro' || (scelta === 'auto' && !buio.matches) ? 'chiaro' : 'scuro');
		d.toggleAttribute('data-vv-natale', scelta === 'natale');
		return scelta;
	};
	window.vvTema = {
		scelte: temi.scelte,
		scegli: function (scelta) {
			scelta = applica(scelta);
			try { localStorage.setItem(temi.chiave, scelta); } catch (e) {}
		}
	};
	var salvata = null;
	try { salvata = localStorage.getItem(temi.chiave); } catch (e) {}
	applica(salvata);
	buio.addEventListener('change', function () { applica(d.getAttribute('data-vv-scelta')); });
})(document.documentElement, <?php echo wp_json_encode( $temi ); ?>);
</script>
	<?php
}
add_action( 'wp_head', 'vv_tema_script', 0 );

function vv_stili_editor() {
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/tokens.css', 'assets/css/base.css', 'assets/css/components.css' ) );
}
add_action( 'after_setup_theme', 'vv_stili_editor', 20 );
