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
require VV_DIR . '/inc/sicurezza.php';
require VV_DIR . '/inc/customizer.php';
require VV_DIR . '/inc/meta.php';
require VV_DIR . '/inc/cards.php';
require VV_DIR . '/inc/shortcodes.php';
require VV_DIR . '/inc/home.php';
require VV_DIR . '/inc/archive.php';
require VV_DIR . '/inc/breadcrumbs.php';
require VV_DIR . '/inc/single.php';
require VV_DIR . '/inc/page.php';
require VV_DIR . '/inc/redirects.php';
require VV_DIR . '/inc/forminator.php';
require VV_DIR . '/inc/sheets.php';
if ( is_admin() ) {
	require VV_DIR . '/inc/sheets-admin.php';
}

/**
 * Fogli di stile e script del tema.
 */
function vv_enqueue_assets() {
	$dep = 'sydney-style-min';
	foreach ( array( 'tokens', 'base', 'layout', 'header', 'footer', 'components', 'home', 'pages', 'plugins', 'scuro' ) as $name ) {
		$rel = "/assets/css/{$name}.css";
		wp_enqueue_style( "vv-{$name}", VV_URI . $rel, array( $dep ), (string) filemtime( VV_DIR . $rel ) );
		$dep = "vv-{$name}";
	}

	// style.css del child lo carica Sydney ("sydney-style") con una versione fissa: dopo un
	// aggiornamento i browser terrebbero la copia vecchia.
	$style = wp_styles()->query( 'sydney-style' );
	if ( $style && false !== strpos( (string) $style->src, get_stylesheet_directory_uri() ) ) {
		$style->ver = (string) filemtime( VV_DIR . '/style.css' );
	}

	wp_enqueue_script( 'vv-main', VV_URI . '/assets/js/vv.js', array(), (string) filemtime( VV_DIR . '/assets/js/vv.js' ), array(
		'in_footer' => true,
		'strategy'  => 'defer',
	) );
}
add_action( 'wp_enqueue_scripts', 'vv_enqueue_assets', 20 );

/**
 * Preload di Poppins normale e grassetto (latin): il testo compare subito nel carattere giusto,
 * senza il passaggio dal font di sistema. Gli altri pesi arrivano con il CSS.
 */
function vv_preload_fonts() {
	foreach ( array( 'poppins-400-latin', 'poppins-700-latin' ) as $font ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( VV_URI . "/assets/fonts/{$font}.woff2" ) );
	}
}
add_action( 'wp_head', 'vv_preload_fonts', 1 );

/**
 * Tema chiaro o scuro, prima che la pagina si disegni (niente lampo bianco): la scelta del visitatore
 * ("chiaro", "scuro" o "auto", salvata dallo switcher di vv.js) diventa data-vv-tema su <html>, che
 * accende la palette scura di tokens.css. "auto" segue il sistema, anche se cambia a pagina aperta.
 * Chi non ha mai scelto vede il tema predefinito. vvTema() la usa anche lo switcher.
 */
function vv_tema_script() {
	$predefinito = 'chiaro'; // Uno fra chiaro, scuro e auto.
	?>
<script>
(function (d) {
	var m = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
	window.vvTema = function (scelta) {
		if (scelta !== 'chiaro' && scelta !== 'scuro' && scelta !== 'auto') {
			scelta = '<?php echo esc_js( $predefinito ); ?>';
		}
		d.setAttribute('data-vv-scelta', scelta);
		d.setAttribute('data-vv-tema', scelta === 'scuro' || (scelta === 'auto' && m && m.matches) ? 'scuro' : 'chiaro');
		return scelta;
	};
	var salvata = null;
	try { salvata = localStorage.getItem('vv-tema'); } catch (e) {}
	window.vvTema(salvata);
	if (m && m.addEventListener) {
		m.addEventListener('change', function () { window.vvTema(d.getAttribute('data-vv-scelta')); });
	}
})(document.documentElement);
</script>
	<?php
}
add_action( 'wp_head', 'vv_tema_script', 0 );

/**
 * Stili nell'editor a blocchi, così gli articoli si scrivono con l'aspetto finale.
 */
function vv_editor_styles() {
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/tokens.css', 'assets/css/base.css', 'assets/css/components.css' ) );
}
add_action( 'after_setup_theme', 'vv_editor_styles', 20 );
