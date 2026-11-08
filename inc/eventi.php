<?php
/**
 * Date degli eventi: lettura dai campi dell'articolo, etichette, foglietto, fase e ordine in
 * calendario. Niente query né markup: si prova con tests/run.php senza WordPress.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Durata massima di un evento "breve" (laboratorio, rassegna di qualche giorno): fino a qui contano
 * i giorni nelle etichette e l'evento in corso resta fra i prossimi; oltre è un corso di stagione.
 */
const VV_EVENTO_BREVE = 31 * DAY_IN_SECONDS;

/**
 * Inizio e fine di un evento come scritti nei campi, ripuliti: una data che non esiste o non è
 * AAAA-MM-GG (salvata prima dei controlli o scritta a mano nel database) conta come assente, e una
 * fine mancante o scritta prima dell'inizio (errore di battitura) diventa l'inizio: un giorno solo.
 *
 * @param string $inizio Campo vv_data.
 * @param string $fine   Campo vv_data_fine.
 * @return string[] array( inizio, fine ), AAAA-MM-GG o ''.
 */
function vv_date_evento( $inizio, $fine ) {
	$inizio = vv_sanitize_date( $inizio );
	$fine   = vv_sanitize_date( $fine );
	if ( $inizio && ( ! $fine || $fine < $inizio ) ) {
		$fine = $inizio;
	}
	return array( $inizio, $fine );
}

/**
 * Date di un articolo-evento come timestamp (inizio/fine) più l'etichetta leggibile.
 *
 * @param WP_Post|int $post Articolo.
 * @return array{start:int,end:int,label:string,has_meta:bool}
 */
function vv_event_dates( $post ) {
	$post = get_post( $post );
	$tz   = wp_timezone();
	// Date già ripulite: con una data inesistente DateTimeImmutable fermerebbe la pagina con un errore.
	list( $start, $end ) = vv_date_evento( get_post_meta( $post->ID, 'vv_data', true ), get_post_meta( $post->ID, 'vv_data_fine', true ) );

	$has_meta = (bool) $start;
	$start_ts = $start ? ( new DateTimeImmutable( $start, $tz ) )->getTimestamp() : get_post_timestamp( $post );
	// Un evento resta "in programma" fino alla fine del suo ultimo giorno.
	$last_day = $end ? $end : $start;
	$end_ts   = $last_day ? ( new DateTimeImmutable( $last_day . ' 23:59:59', $tz ) )->getTimestamp() : $start_ts;

	$label = $has_meta && $end !== $start ? vv_date_range_label( $start_ts, $end_ts ) : wp_date( 'j F Y', $start_ts );

	return array(
		'start'    => $start_ts,
		'end'      => $end_ts,
		'label'    => $label,
		'has_meta' => $has_meta,
	);
}

/**
 * Etichetta di un evento su più giorni. Fino a un mese i giorni contano ("3–5 ottobre 2026",
 * "28 settembre – 3 ottobre 2026"); oltre, per i corsi che durano una stagione, bastano i mesi
 * ("ott–dic 2026", "ott 2026 – mag 2027").
 *
 * @param int $start_ts Inizio.
 * @param int $end_ts   Fine.
 */
function vv_date_range_label( $start_ts, $end_ts ) {
	$same_year = wp_date( 'Y', $start_ts ) === wp_date( 'Y', $end_ts );
	if ( $end_ts - $start_ts <= VV_EVENTO_BREVE ) {
		if ( $same_year && wp_date( 'n', $start_ts ) === wp_date( 'n', $end_ts ) ) {
			return wp_date( 'j', $start_ts ) . '–' . wp_date( 'j F Y', $end_ts );
		}
		return wp_date( $same_year ? 'j F' : 'j F Y', $start_ts ) . ' – ' . wp_date( 'j F Y', $end_ts );
	}
	return $same_year
		? wp_date( 'M', $start_ts ) . '–' . wp_date( 'M Y', $end_ts )
		: wp_date( 'M Y', $start_ts ) . ' – ' . wp_date( 'M Y', $end_ts );
}

/**
 * Data a foglietto di calendario sulla foto della card: due righe brevi. Un giorno "13 / nov",
 * più giorni nello stesso mese "3–5 / ott", a cavallo di due mesi "28 set / 3 ott"; oltre un
 * mese, per i corsi, i mesi e l'anno: "ott–dic / 2026", "ott–giu / 26–27".
 *
 * @param array $dates Risultato di vv_event_dates().
 * @return array{alto:string,basso:string}|null Null senza le date dell'evento.
 */
function vv_date_chip( $dates ) {
	if ( empty( $dates['has_meta'] ) ) {
		return null;
	}
	$start = $dates['start'];
	$end   = $dates['end'];
	if ( wp_date( 'Y-m-d', $start ) === wp_date( 'Y-m-d', $end ) ) {
		return array( 'alto' => wp_date( 'j', $start ), 'basso' => wp_date( 'M', $start ) );
	}
	if ( $end - $start <= VV_EVENTO_BREVE ) {
		return wp_date( 'Y-n', $start ) === wp_date( 'Y-n', $end )
			? array( 'alto' => wp_date( 'j', $start ) . '–' . wp_date( 'j', $end ), 'basso' => wp_date( 'M', $start ) )
			: array( 'alto' => wp_date( 'j M', $start ), 'basso' => wp_date( 'j M', $end ) );
	}
	$anni = wp_date( 'Y', $start ) === wp_date( 'Y', $end ) ? wp_date( 'Y', $start ) : wp_date( 'y', $start ) . '–' . wp_date( 'y', $end );
	return array( 'alto' => wp_date( 'M', $start ) . '–' . wp_date( 'M', $end ), 'basso' => $anni );
}

/**
 * Fase di un evento per la fascetta della card: "a-breve" prima del primo giorno, "in-corso"
 * dall'inizio del primo giorno alla fine dell'ultimo (senza orario, per un evento di un giorno vale
 * tutto il giorno), "concluso" dopo.
 *
 * @param array $dates Risultato di vv_event_dates().
 * @param int   $now   Adesso.
 * @return string a-breve | in-corso | concluso.
 */
function vv_fase_evento( $dates, $now ) {
	return match ( true ) {
		$dates['end'] < $now    => 'concluso',
		$dates['start'] <= $now => 'in-corso',
		default                 => 'a-breve',
	};
}

/**
 * Ordine degli eventi in calendario:
 * 1. gli appuntamenti da non perdere: quelli che devono ancora cominciare e quelli brevi (fino a un
 *    mese) in corso, dal più vicino;
 * 2. corsi e rassegne lunghi già cominciati, dal più recente inizio: restano visibili, ma un evento
 *    di domani non finisce dietro un corso partito una settimana fa;
 * 3. quelli già finiti, dal più recente inizio, come nell'archivio per anno.
 * È l'ordine di tutte le sezioni con gli eventi: home, "Prossimi appuntamenti", Pagine elenco.
 *
 * @param array $eventi array( contenuto, inizio, fine ) con i timestamp di vv_event_dates().
 * @param int   $now    Adesso.
 * @return array Gli stessi elementi, ordinati.
 */
function vv_ordina_eventi( $eventi, $now ) {
	$prossimi = array();
	$in_corso = array();
	$passati  = array();
	foreach ( $eventi as $e ) {
		list( , $start, $end ) = $e;
		if ( $end < $now ) {
			$passati[] = $e;
		} elseif ( $start > $now || $end - $start <= VV_EVENTO_BREVE ) {
			$prossimi[] = $e;
		} else {
			$in_corso[] = $e;
		}
	}
	// Un evento breve già cominciato conta da adesso: davanti a quelli che cominciano dopo.
	usort( $prossimi, static fn( $a, $b ) => array( max( $a[1], $now ), $a[2] ) <=> array( max( $b[1], $now ), $b[2] ) );
	usort( $in_corso, static fn( $a, $b ) => $b[1] <=> $a[1] );
	usort( $passati, static fn( $a, $b ) => array( $b[1], $b[2] ) <=> array( $a[1], $a[2] ) );
	return array_merge( $prossimi, $in_corso, $passati );
}
