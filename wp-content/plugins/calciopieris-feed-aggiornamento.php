<?php
/**
 * Plugin Name: Calcio Pieris – Aggiornamento feed Facebook
 * Description: Completa il processo programmato di Smash Balloon, che svuota solo la vecchia cache a transient e lascia intatta la tabella dove i feed tengono davvero i post. Senza questo, i post sul sito restano fermi.
 * Version: 1.0
 * Author: A.S.D. Calcio Pieris 1925
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * PERCHE' ESISTE QUESTO FILE
 *
 * Smash Balloon programma un processo, 'cff_cron_job', che chiama
 * cff_cron_clear_cache() -> cff_delete_cache(). Quella funzione cancella
 * soltanto le opzioni _transient_cff_*, cioe' la cache vecchia maniera.
 * I feed creati con il loro "feed builder" tengono invece i post nella tabella
 * wp_cff_feed_caches, che quel processo non tocca mai: l'unico punto del
 * plugin che la svuota e' clear_stored_caches(), richiamato solo dal pulsante
 * "Clear Cache" nel pannello.
 *
 * Risultato: senza che nessuno prema quel pulsante, i post sul sito non si
 * aggiornano. Verificato il 2026-09-03 in locale: invecchiando la cache di due
 * ore non partiva nessuna richiesta a Facebook, mentre svuotandola il feed
 * scaricava subito - e comparivano 4 post al posto dei 2 rimasti in cache.
 *
 * Qui non si programma un processo nuovo: ci si aggancia al loro, che gia'
 * gira, aggiungendo il passo che manca. Se un aggiornamento del plugin
 * sistemasse la cosa, questo codice non farebbe danno: svuotare una cache gia'
 * svuotata non costa niente.
 */
add_action( 'cff_cron_job', function () {
	if ( ! class_exists( '\CustomFacebookFeed\CFF_Cache' ) ) { return; }

	/* Svuota le righe della tabella dei feed marcate come aggiornabili dal
	   processo. Non cancella le righe: azzera il contenuto, e al primo
	   visitatore il feed lo riscarica da Facebook. */
	\CustomFacebookFeed\CFF_Cache::clear_all_builder();

	/* Traccia dell'ultimo giro, cosi' si puo' controllare dal database se il
	   processo sta davvero girando senza dover aspettare a vuoto. */
	update_option( 'cp_feed_ultimo_svuotamento', gmdate( 'Y-m-d H:i:s' ), false );
}, 20 );

if ( ! function_exists( 'cp_feed_ultimo_aggiornamento' ) ) {
	/**
	 * Quando il processo ha svuotato la cache l'ultima volta (ora locale),
	 * oppure stringa vuota se non e' mai girato.
	 */
	function cp_feed_ultimo_aggiornamento() {
		$utc = get_option( 'cp_feed_ultimo_svuotamento', '' );
		return $utc ? get_date_from_gmt( $utc ) : '';
	}
}
