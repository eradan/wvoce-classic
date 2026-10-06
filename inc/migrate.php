<?php
/**
 * Eredità delle impostazioni del Customizer di Sydney.
 *
 * WordPress salva le impostazioni del Customizer (logo, colori, hero, footer…)
 * per tema: attivando il child senza copiarle il sito perderebbe logo e hero.
 * Finché la migrazione non è registrata, le impostazioni del child vengono
 * lette "sopra" quelle di Sydney (vale anche per l'Anteprima dal vivo);
 * all'attivazione il risultato viene salvato una volta per tutte.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/*
 * Eredità delle impostazioni del Customizer.
 *
 * WordPress salva le impostazioni del Customizer (logo, colori dell'header, hero, footer…)
 * tema per tema: un child attivato per la prima volta parte vuoto, e Sydney ci scrive
 * soltanto i suoi valori predefiniti. Finché la copia non è fatta, le impostazioni del child
 * vengono lette "sopra" quelle di un tema già configurato (vale anche per l'Anteprima dal vivo);
 * la copia viene poi salvata una volta per tutte, all'attivazione oppure alla prima visita
 * del pannello di amministrazione.
 *
 * Fonte: un altro child di Sydney già configurato (per esempio "VivaVoce" per "VivaVoce Classic"),
 * altrimenti Sydney stesso. "Già configurato" = ha un logo nelle impostazioni.
 */

/**
 * Nome dell'opzione che segna la copia fatta, per il tema attivo.
 */
function vv_migrated_option() {
	return 'vv_theme_mods_migrated_' . get_stylesheet();
}

/**
 * Impostazioni di un tema (array vuoto se assenti), lette senza i filtri di questo file.
 *
 * @param string $stylesheet Cartella del tema.
 */
function vv_raw_mods( $stylesheet ) {
	global $wpdb;
	$value = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", 'theme_mods_' . $stylesheet ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$value = $value ? maybe_unserialize( $value ) : array();
	return is_array( $value ) ? $value : array();
}

/**
 * Un tema è già configurato se ha un logo (un child appena attivato non lo ha).
 *
 * @param array $mods Impostazioni del tema.
 */
function vv_mods_configured( $mods ) {
	return ! empty( $mods['custom_logo'] ) || ! empty( $mods['site_logo'] );
}

/**
 * La copia per il tema attivo è già fatta?
 */
function vv_mods_migrated() {
	static $done = null;
	if ( null === $done ) {
		$done = (bool) get_option( vv_migrated_option() ) || vv_mods_configured( vv_raw_mods( get_stylesheet() ) );
	}
	return $done;
}

/**
 * Tema da cui copiare: il child di Sydney già configurato con più impostazioni, altrimenti Sydney.
 */
function vv_mods_source() {
	static $source = null;
	if ( null !== $source ) {
		return $source;
	}
	$current = get_stylesheet();
	$parent  = get_template();
	$best    = 0;
	$source  = $parent;
	foreach ( array_keys( wp_get_themes() ) as $slug ) {
		if ( $slug === $current || $slug === $parent || wp_get_theme( $slug )->get_template() !== $parent ) {
			continue;
		}
		$mods = vv_raw_mods( $slug );
		if ( vv_mods_configured( $mods ) && count( $mods ) > $best ) {
			$best   = count( $mods );
			$source = $slug;
		}
	}
	return $source;
}

/**
 * Impostazioni del child completate con quelle della fonte.
 * Vince la fonte: Sydney riempie un child nuovo di valori predefiniti (colori, footer…) che
 * altrimenti coprirebbero quelli veri. Del child restano solo le posizioni dei menu e il suo
 * CSS aggiuntivo (quello di Sydney è già riportato in style.css).
 *
 * @param mixed $child_mods Valore salvato per il child (false se assente).
 * @return array
 */
function vv_merge_parent_mods( $child_mods ) {
	$child_mods = is_array( $child_mods ) ? $child_mods : array();
	$source     = vv_raw_mods( vv_mods_source() );
	unset( $source['custom_css_post_id'], $source['sidebars_widgets'] );

	$merged = array_merge( $child_mods, $source );
	foreach ( array( 'nav_menu_locations', 'custom_css_post_id' ) as $key ) {
		if ( isset( $child_mods[ $key ] ) ) {
			$merged[ $key ] = $child_mods[ $key ];
		} else {
			unset( $merged[ $key ] );
		}
	}
	return $merged;
}

if ( ! vv_mods_migrated() ) {
	$vv_mods_option = 'theme_mods_' . get_stylesheet();
	add_filter( "option_{$vv_mods_option}", 'vv_merge_parent_mods' );
	add_filter( "default_option_{$vv_mods_option}", 'vv_merge_parent_mods' );
	unset( $vv_mods_option );
	// Sydney tiene in cache il CSS generato dalle impostazioni salvate: finché si leggono
	// quelle unite, va rigenerato ogni volta (dopo il salvataggio la cache si svuota da sola).
	add_filter( 'sydney_enable_css_cache', '__return_false' );
}

/**
 * Salva la copia e segna la migrazione (all'attivazione o alla prima visita dell'amministrazione).
 */
function vv_persist_parent_mods() {
	if ( vv_mods_migrated() || ! current_user_can( 'edit_theme_options' ) && ! doing_action( 'after_switch_theme' ) ) {
		return;
	}
	$option = 'theme_mods_' . get_stylesheet();
	$merged = vv_merge_parent_mods( vv_raw_mods( get_stylesheet() ) );
	// Senza i filtri di lettura: altrimenti update_option vedrebbe il valore già unito e non salverebbe.
	remove_filter( "option_{$option}", 'vv_merge_parent_mods' );
	remove_filter( "default_option_{$option}", 'vv_merge_parent_mods' );
	update_option( $option, $merged );
	if ( vv_mods_configured( vv_raw_mods( get_stylesheet() ) ) ) {
		update_option( vv_migrated_option(), gmdate( 'c' ) . ' da ' . vv_mods_source(), false );
	}
}
add_action( 'after_switch_theme', 'vv_persist_parent_mods' );
add_action( 'admin_init', 'vv_persist_parent_mods' );

/*
 * Header e footer "classici" di Sydney.
 *
 * Quando si passa da un tema che non ha "sydney" nel nome (per esempio questo child,
 * "VivaVoce"), Sydney accende da solo il costruttore header/footer
 * (sydney_hf_enable_to_new_users) e non offre un interruttore per spegnerlo.
 * Il costruttore usa un markup diverso: il CSS del child non lo trova e il footer
 * non mostra le aree widget. Il sito ha sempre usato header e footer classici, quindi
 * il costruttore resta spento qui, senza modificare l'opzione salvata.
 * Per usarlo davvero: define( 'VV_SYDNEY_BUILDER', true ); in wp-config.php.
 */
if ( ! defined( 'VV_SYDNEY_BUILDER' ) || ! VV_SYDNEY_BUILDER ) {
	add_filter(
		'option_sydney-modules',
		static function ( $modules ) {
			$modules               = is_array( $modules ) ? $modules : (array) $modules;
			$modules['hf-builder'] = false;
			return $modules;
		}
	);
}
