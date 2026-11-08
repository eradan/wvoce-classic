<?php
/**
 * Date degli eventi: campi dell'articolo, etichette, foglietto, fase e ordine in calendario.
 * Niente query né markup: si prova con tests/run.php senza WordPress.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fino a un mese un evento è breve (laboratorio, rassegna): contano i giorni e, in corso, resta fra i
 * prossimi. Oltre è un corso di stagione.
 */
const VV_EVENTO_BREVE = 31 * DAY_IN_SECONDS;

/**
 * Una data inesistente o non AAAA-MM-GG conta come assente; una fine mancante o prima dell'inizio
 * diventa l'inizio.
 *
 * @return string[] array( inizio, fine ), AAAA-MM-GG o ''.
 */
function vv_date_evento( $inizio, $fine ) {
	$inizio = vv_pulisci_data( $inizio );
	$fine   = vv_pulisci_data( $fine );
	if ( $inizio && ( ! $fine || $fine < $inizio ) ) {
		$fine = $inizio;
	}
	return array( $inizio, $fine );
}

/**
 * Senza date dell'evento vale la data di pubblicazione. Un evento dura fino alla fine del suo ultimo giorno.
 *
 * @return array{inizio:int,fine:int,etichetta:string,con_date:bool}
 */
function vv_date_articolo( $articolo ) {
	$articolo = get_post( $articolo );
	$fuso     = wp_timezone();

	list( $inizio, $fine ) = vv_date_evento( get_post_meta( $articolo->ID, 'vv_data', true ), get_post_meta( $articolo->ID, 'vv_data_fine', true ) );

	if ( ! $inizio ) {
		$pubblicato = get_post_timestamp( $articolo );
		return array(
			'inizio'    => $pubblicato,
			'fine'      => $pubblicato,
			'etichetta' => wp_date( 'j F Y', $pubblicato ),
			'con_date'  => false,
		);
	}
	$dal = ( new DateTimeImmutable( $inizio, $fuso ) )->getTimestamp();
	$al  = ( new DateTimeImmutable( $fine . ' 23:59:59', $fuso ) )->getTimestamp();
	return array(
		'inizio'    => $dal,
		'fine'      => $al,
		'etichetta' => $fine !== $inizio ? vv_etichetta_periodo( $dal, $al ) : wp_date( 'j F Y', $dal ),
		'con_date'  => true,
	);
}

/**
 * Fino a un mese i giorni ("3–5 ottobre 2026", "28 settembre – 3 ottobre 2026"), oltre i mesi
 * ("ott–dic 2026", "ott 2026 – mag 2027").
 */
function vv_etichetta_periodo( $dal, $al ) {
	$stesso_anno = wp_date( 'Y', $dal ) === wp_date( 'Y', $al );
	if ( $al - $dal > VV_EVENTO_BREVE ) {
		return $stesso_anno
			? wp_date( 'M', $dal ) . '–' . wp_date( 'M Y', $al )
			: wp_date( 'M Y', $dal ) . ' – ' . wp_date( 'M Y', $al );
	}
	if ( $stesso_anno && wp_date( 'n', $dal ) === wp_date( 'n', $al ) ) {
		return wp_date( 'j', $dal ) . '–' . wp_date( 'j F Y', $al );
	}
	return wp_date( $stesso_anno ? 'j F' : 'j F Y', $dal ) . ' – ' . wp_date( 'j F Y', $al );
}

/**
 * Due righe brevi per il foglietto sulla foto: "13 / nov", "3–5 / ott", "28 set / 3 ott", per i corsi
 * "ott–dic / 2026" o "ott–giu / 26–27".
 *
 * @param array $date Risultato di vv_date_articolo().
 * @return array{alto:string,basso:string}|null Null senza le date dell'evento.
 */
function vv_foglietto_data( $date ) {
	if ( empty( $date['con_date'] ) ) {
		return null;
	}
	$dal = $date['inizio'];
	$al  = $date['fine'];
	if ( wp_date( 'Y-m-d', $dal ) === wp_date( 'Y-m-d', $al ) ) {
		return array( 'alto' => wp_date( 'j', $dal ), 'basso' => wp_date( 'M', $dal ) );
	}
	if ( $al - $dal > VV_EVENTO_BREVE ) {
		$anni = wp_date( 'Y', $dal ) === wp_date( 'Y', $al ) ? wp_date( 'Y', $dal ) : wp_date( 'y', $dal ) . '–' . wp_date( 'y', $al );
		return array( 'alto' => wp_date( 'M', $dal ) . '–' . wp_date( 'M', $al ), 'basso' => $anni );
	}
	if ( wp_date( 'Y-n', $dal ) === wp_date( 'Y-n', $al ) ) {
		return array( 'alto' => wp_date( 'j', $dal ) . '–' . wp_date( 'j', $al ), 'basso' => wp_date( 'M', $dal ) );
	}
	return array( 'alto' => wp_date( 'j M', $dal ), 'basso' => wp_date( 'j M', $al ) );
}

/**
 * @param array $date Risultato di vv_date_articolo().
 * @return string a-breve | in-corso | concluso.
 */
function vv_fase_evento( $date, $adesso ) {
	return match ( true ) {
		$date['fine'] < $adesso    => 'concluso',
		$date['inizio'] <= $adesso => 'in-corso',
		default                    => 'a-breve',
	};
}

/**
 * L'ordine di tutte le sezioni con gli eventi:
 * 1. quelli che devono cominciare e quelli brevi in corso, dal più vicino;
 * 2. i corsi lunghi già cominciati, dal più recente: restano visibili, ma un evento di domani non
 *    finisce dietro un corso partito una settimana fa;
 * 3. i conclusi, dal più recente.
 *
 * @param array $eventi array( contenuto, inizio, fine ) con i timestamp di vv_date_articolo().
 */
function vv_ordina_eventi( $eventi, $adesso ) {
	$prossimi = array();
	$lunghi   = array();
	$passati  = array();
	foreach ( $eventi as $evento ) {
		list( , $dal, $al ) = $evento;
		if ( $al < $adesso ) {
			$passati[] = $evento;
		} elseif ( $dal > $adesso || $al - $dal <= VV_EVENTO_BREVE ) {
			$prossimi[] = $evento;
		} else {
			$lunghi[] = $evento;
		}
	}
	// Un evento breve già cominciato conta da adesso: davanti a quelli che cominciano dopo.
	usort( $prossimi, static fn( $a, $b ) => array( max( $a[1], $adesso ), $a[2] ) <=> array( max( $b[1], $adesso ), $b[2] ) );
	usort( $lunghi, static fn( $a, $b ) => $b[1] <=> $a[1] );
	usort( $passati, static fn( $a, $b ) => array( $b[1], $b[2] ) <=> array( $a[1], $a[2] ) );
	return array_merge( $prossimi, $lunghi, $passati );
}
