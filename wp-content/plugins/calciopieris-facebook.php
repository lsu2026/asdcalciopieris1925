<?php
/**
 * Plugin Name: Calcio Pieris – Post da Facebook
 * Description: Ci si collega con l'utenza Facebook che amministra la Pagina, si sceglie la Pagina e da quel momento il sito scarica da solo gli ultimi post, foto comprese, e li mostra con la stessa veste delle news. Nasce per sostituire Smash Balloon, che sull'hosting del sito non puo' girare.
 * Version: 1.0
 * Author: A.S.D. Calcio Pieris 1925
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Perche' questo plugin esiste.
 *
 * Smash Balloon gratuito non chiede al Graph API il campo delle immagini, quindi
 * le foto dei post non le scarica proprio; e la sua versione installabile porta
 * un file da 1,6 MB che l'hosting del sito cancella dopo il caricamento,
 * mandando tutto il sito in errore. Qui si fa la stessa cosa in poche centinaia
 * di righe, chiedendo pero' anche le immagini.
 *
 * DUE SCELTE CHE CONTANO.
 *
 * 1. Ci si collega con l'utenza Facebook, non incollando un token. Un token
 *    copiato a mano dura un'ora e scade nel momento peggiore; passando dal
 *    login si arriva a un token di Pagina che NON SCADE, e non c'e' una
 *    credenziale da tenere in giro su un foglietto.
 *
 * 2. Le immagini si scaricano sul sito. Gli indirizzi che Facebook restituisce
 *    puntano alla sua rete di distribuzione e SCADONO nel giro di qualche
 *    settimana: collegarli e basta vorrebbe dire ritrovarsi, un mese dopo, una
 *    pagina di riquadri vuoti. La copia locale invece resta, e si vede anche
 *    quando Facebook e' irraggiungibile.
 */
class CP_Facebook {

	/* Le credenziali stanno in opzioni TUTTE LORO, separate dalle impostazioni
	   normali: cosi' escluderle da un travaso fra ambienti e' una riga sola e
	   non c'e' modo di sbagliarsi. */
	const OPZ_APP   = 'cp_fb_app';   // identificativo e segreto dell'applicazione
	const OPZ_TOKEN = 'cp_fb_token'; // token della Pagina

	const OPZ_CONF     = 'cp_fb_configurazione';
	const OPZ_POST     = 'cp_fb_post';
	const OPZ_STATO    = 'cp_fb_stato';
	const OPZ_DIAGNOSI = 'cp_fb_diagnosi';

	const EVENTO   = 'cp_fb_aggiorna';
	const CARTELLA = 'pieris-facebook';

	/** Versione del Graph API con cui e' stato provato. */
	const API = 'v21.0';

	/**
	 * I permessi chiesti al momento del login.
	 *
	 * I primi due sono quelli che servono: elencare le Pagine e leggerne i post.
	 *
	 * Il terzo sembra di troppo e invece e' indispensabile. Quando il ruolo
	 * sulla Pagina non e' stato dato alla persona ma le arriva da un PORTFOLIO
	 * AZIENDALE - il Business Manager - la chiamata /me/accounts restituisce un
	 * elenco VUOTO senza business_management, pur essendo pages_show_list
	 * concesso e pur essendo quella persona amministratore pieno della Pagina.
	 * Da fuori sembra che i permessi manchino; in realta' manca il permesso di
	 * vedere le Pagine passando dall'azienda. Verificato sul campo il
	 * 2026-09-05: permessi concessi pages_show_list e pages_read_engagement,
	 * Pagine trovate zero.
	 */
	const PERMESSI = 'pages_show_list,pages_read_engagement,business_management';

	/** Oltre questa lunghezza la prima riga non fa piu' da titolo. */
	const TITOLO_MAX = 90;

	/** Oltre questa larghezza le foto vengono rimpicciolite. */
	const LARGHEZZA_MAX = 1200;

	/** Quante immagini al massimo scaricare in una sola passata. */
	const IMMAGINI_PER_GIRO = 6;

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_cp_fb_app', array( __CLASS__, 'salva_app' ) );
		add_action( 'admin_post_cp_fb_conf', array( __CLASS__, 'salva_conf' ) );
		add_action( 'admin_post_cp_fb_connetti', array( __CLASS__, 'connetti' ) );
		add_action( 'admin_post_cp_fb_scegli', array( __CLASS__, 'scegli_pagina' ) );
		add_action( 'admin_post_cp_fb_disconnetti', array( __CLASS__, 'disconnetti' ) );
		add_action( 'admin_post_cp_fb_aggiorna', array( __CLASS__, 'aggiorna_a_mano' ) );
		add_action( self::EVENTO, array( __CLASS__, 'scarica' ) );
		add_shortcode( 'post_facebook', array( __CLASS__, 'shortcode' ) );
		add_action( 'init', array( __CLASS__, 'programma' ) );
	}

	/* ===================== impostazioni e credenziali ===================== */

	public static function conf() {
		$c = get_option( self::OPZ_CONF, array() );
		return wp_parse_args( is_array( $c ) ? $c : array(), array(
			'pagina'      => '',
			'nome_pagina' => '',
			'quanti'      => 12,
			'in_home'     => 5,
		) );
	}

	public static function app() {
		$a = get_option( self::OPZ_APP, array() );
		return wp_parse_args( is_array( $a ) ? $a : array(), array(
			'id'            => '',
			'segreto'       => '',
			'configurazione' => '',
		) );
	}

	public static function token() {
		return (string) get_option( self::OPZ_TOKEN, '' );
	}

	public static function stato() {
		$s = get_option( self::OPZ_STATO, array() );
		return wp_parse_args( is_array( $s ) ? $s : array(), array(
			'ultimo_giro' => 0,
			'esito'       => '',
			'errore'      => '',
			'scaricati'   => 0,
			'immagini'    => 0,
		) );
	}

	/**
	 * L'indirizzo a cui Facebook rimanda dopo il login.
	 *
	 * Va ricopiato PAROLA PER PAROLA fra gli indirizzi ammessi
	 * dell'applicazione: Facebook lo confronta per intero, e un solo carattere
	 * diverso fa fallire il login con un messaggio che non lo dice.
	 */
	public static function ritorno() {
		return admin_url( 'admin.php?page=cp-facebook' );
	}

	public static function programma() {
		if ( ! wp_next_scheduled( self::EVENTO ) ) {
			wp_schedule_event( time() + 60, 'hourly', self::EVENTO );
		}
	}

	/* ===================== login con Facebook ===================== */

	/** Manda l'amministratore sulla pagina di login di Facebook. */
	public static function connetti() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non hai i permessi.' ); }
		check_admin_referer( 'cp_fb_connetti' );

		$app = self::app();
		if ( '' === $app['id'] || '' === $app['segreto'] ) {
			self::torna( 'errore', 'Prima servono identificativo e segreto dell&rsquo;applicazione Facebook.' );
		}

		/* Il parametro "state" torna indietro tale e quale: e' cosi' che si
		   riconosce che il ritorno viene davvero da un login partito da qui. */
		$stato = wp_create_nonce( 'cp_fb_ritorno' );
		set_transient( 'cp_fb_stato_login', $stato, 15 * MINUTE_IN_SECONDS );

		$parametri = array(
			'client_id'     => $app['id'],
			'redirect_uri'  => self::ritorno(),
			'state'         => $stato,
			'response_type' => 'code',
		);

		/* Meta ha DUE sistemi di login e le applicazioni finiscono sull'uno o
		   sull'altro a seconda del caso d'uso scelto quando le si crea.
		   Quello classico vuole i permessi in "scope"; quello "for Business"
		   vuole invece un ID configurazione, e lo scope lo ignora. Non c'e' modo
		   di indovinare da qui quale sia: se l'ID configurazione e' stato
		   compilato si usa quello, altrimenti si resta sul classico. */
		if ( '' !== $app['configurazione'] ) {
			$parametri['config_id']                      = $app['configurazione'];
			$parametri['override_default_response_type'] = 'true';
		} else {
			$parametri['scope'] = self::PERMESSI;
		}

		$url = 'https://www.facebook.com/' . self::API . '/dialog/oauth?' . http_build_query( $parametri );

		wp_redirect( $url ); // esterno: wp_safe_redirect lo rifiuterebbe
		exit;
	}

	/**
	 * Il ritorno da Facebook.
	 *
	 * Gira al caricamento della pagina del pannello, non su admin-post.php,
	 * perche' l'indirizzo di ritorno deve essere uno solo, semplice e sempre
	 * uguale: e' quello che si registra nell'applicazione.
	 */
	public static function ritorno_da_facebook() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }

		if ( isset( $_GET['error'] ) ) {
			$m = isset( $_GET['error_description'] ) ? sanitize_text_field( wp_unslash( $_GET['error_description'] ) ) : 'accesso negato';
			self::torna( 'errore', 'Facebook ha rifiutato il collegamento: ' . $m );
		}

		if ( empty( $_GET['code'] ) || empty( $_GET['state'] ) ) { return; }

		$atteso = get_transient( 'cp_fb_stato_login' );
		if ( ! $atteso || ! hash_equals( (string) $atteso, (string) wp_unslash( $_GET['state'] ) ) ) {
			self::torna( 'errore', 'Il ritorno da Facebook non corrisponde alla richiesta partita da qui: riprova.' );
		}
		delete_transient( 'cp_fb_stato_login' );

		$app = self::app();
		update_option( self::OPZ_DIAGNOSI, array( 'quando' => time(), 'passo' => 'ritorno', 'errore' => '', 'permessi' => array(), 'pagine' => -1 ), false );

		/* 1. il codice usa e getta diventa un token dell'utente, che pero' dura
		      un'ora scarsa */
		self::passo( 'scambio del codice' );
		$breve = self::chiedi( 'oauth/access_token', array(
			'client_id'     => $app['id'],
			'client_secret' => $app['segreto'],
			'redirect_uri'  => self::ritorno(),
			'code'          => sanitize_text_field( wp_unslash( $_GET['code'] ) ),
		) );
		if ( is_string( $breve ) ) { self::torna( 'errore', $breve ); }
		if ( empty( $breve['access_token'] ) ) { self::torna( 'errore', 'Facebook non ha restituito il token.' ); }

		/* 2. lo si scambia con uno lungo (due mesi). Serve perche' i token di
		      Pagina ricavati da un token utente LUNGO non scadono mai, mentre
		      quelli ricavati da uno breve scadono con lui. */
		$lungo = self::chiedi( 'oauth/access_token', array(
			'grant_type'        => 'fb_exchange_token',
			'client_id'         => $app['id'],
			'client_secret'     => $app['segreto'],
			'fb_exchange_token' => $breve['access_token'],
		) );
		$token_utente = ( ! is_string( $lungo ) && ! empty( $lungo['access_token'] ) )
			? $lungo['access_token']
			: $breve['access_token'];

		/* 2-bis. che cosa ci e' stato concesso DAVVERO.
		   Non serve al collegamento, serve a capirlo quando non riesce: se
		   l'elenco delle Pagine torna vuoto, la differenza fra "questa utenza
		   non amministra Pagine" e "il permesso di elencarle non e' stato dato"
		   sta tutta qui, e senza si tira a indovinare. */
		self::passo( 'permessi concessi' );
		$perm     = self::chiedi( 'me/permissions', array( 'access_token' => $token_utente ) );
		$concessi = array();
		if ( ! is_string( $perm ) && ! empty( $perm['data'] ) ) {
			foreach ( $perm['data'] as $p ) {
				if ( isset( $p['permission'] ) && isset( $p['status'] ) && 'granted' === $p['status'] ) {
					$concessi[] = $p['permission'];
				}
			}
		}
		self::passo( 'permessi concessi', array( 'permessi' => $concessi ) );

		/* 3. le Pagine che l'utenza amministra, ciascuna con il proprio token */
		self::passo( 'elenco delle Pagine' );
		$pagine = self::chiedi( 'me/accounts', array(
			'fields'       => 'id,name,access_token',
			'limit'        => 100,
			'access_token' => $token_utente,
		) );
		if ( is_string( $pagine ) ) { self::torna( 'errore', $pagine ); }
		self::passo( 'elenco delle Pagine', array( 'pagine' => isset( $pagine['data'] ) ? count( $pagine['data'] ) : 0 ) );

		if ( empty( $pagine['data'] ) ) {
			/* Il perche' cambia il rimedio, quindi lo si dice invece di dare un
			   messaggio buono per tutto. */
			if ( ! in_array( 'pages_show_list', $concessi, true ) ) {
				self::torna( 'errore', 'Facebook non ha concesso il permesso <code>pages_show_list</code>, quindi le Pagine non si possono nemmeno elencare. '
					. 'Succede quando l&rsquo;applicazione usa <em>Facebook Login for Business</em>: in quel caso i permessi non si chiedono per nome e '
					. 'va compilato il campo <strong>ID configurazione</strong> qui sopra. '
					. ( $concessi ? 'Concessi invece: ' . esc_html( implode( ', ', $concessi ) ) . '.' : 'Non e&rsquo; stato concesso nessun permesso.' ) );
			}
			if ( ! in_array( 'business_management', $concessi, true ) ) {
				self::torna( 'errore', 'Facebook dice che questa utenza non amministra nessuna Pagina, ma il permesso <code>business_management</code> non &egrave; stato concesso: '
					. 'quando il ruolo sulla Pagina arriva da un <strong>portfolio aziendale</strong>, senza quel permesso l&rsquo;elenco torna vuoto anche se sei amministratore pieno. '
					. 'Aggiungi <code>business_management</code> ai permessi dell&rsquo;applicazione su Facebook e riprova a collegarti.' );
			}
			self::torna( 'errore', 'Il permesso di elencare le Pagine c&rsquo;&egrave;, anche passando dall&rsquo;azienda, ma non risulta nessuna Pagina amministrata da questa utenza.' );
		}

		$elenco = array();
		foreach ( $pagine['data'] as $p ) {
			if ( empty( $p['id'] ) || empty( $p['access_token'] ) ) { continue; }
			$elenco[] = array(
				'id'    => (string) $p['id'],
				'nome'  => isset( $p['name'] ) ? (string) $p['name'] : $p['id'],
				'token' => (string) $p['access_token'],
			);
		}

		/* Una sola Pagina: si collega senza far scegliere, non c'e' scelta da
		   fare. Piu' di una: si mostra l'elenco. */
		if ( 1 === count( $elenco ) ) {
			self::collega( $elenco[0] );
			self::torna( 'ok', 'Collegato alla Pagina &laquo;' . esc_html( $elenco[0]['nome'] ) . '&raquo;.' );
		}

		set_transient( 'cp_fb_pagine', $elenco, 15 * MINUTE_IN_SECONDS );
		self::torna( 'scegli', '' );
	}

	/** Salva Pagina e token, e fa subito un primo scarico. */
	private static function collega( $pagina ) {
		$conf                = self::conf();
		$conf['pagina']      = preg_replace( '/[^0-9]/', '', $pagina['id'] );
		$conf['nome_pagina'] = sanitize_text_field( $pagina['nome'] );
		update_option( self::OPZ_CONF, $conf, false );
		update_option( self::OPZ_TOKEN, $pagina['token'], false );
		delete_transient( 'cp_fb_pagine' );
		self::scarica();
	}

	public static function scegli_pagina() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non hai i permessi.' ); }
		check_admin_referer( 'cp_fb_scegli' );

		$elenco = get_transient( 'cp_fb_pagine' );
		$scelta = isset( $_POST['pagina'] ) ? preg_replace( '/[^0-9]/', '', (string) wp_unslash( $_POST['pagina'] ) ) : '';
		if ( ! is_array( $elenco ) || '' === $scelta ) {
			self::torna( 'errore', 'La scelta e&rsquo; scaduta: rifai il collegamento.' );
		}

		foreach ( $elenco as $p ) {
			if ( $p['id'] === $scelta ) {
				self::collega( $p );
				self::torna( 'ok', 'Collegato alla Pagina &laquo;' . esc_html( $p['nome'] ) . '&raquo;.' );
			}
		}
		self::torna( 'errore', 'Quella Pagina non e&rsquo; nell&rsquo;elenco.' );
	}

	public static function disconnetti() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non hai i permessi.' ); }
		check_admin_referer( 'cp_fb_disconnetti' );

		delete_option( self::OPZ_TOKEN );
		$conf                = self::conf();
		$conf['pagina']      = '';
		$conf['nome_pagina'] = '';
		update_option( self::OPZ_CONF, $conf, false );
		delete_transient( 'cp_fb_pagine' );

		self::torna( 'ok', 'Collegamento tolto. I post gia&rsquo; scaricati restano.' );
	}

	/** Una chiamata al Graph API. Restituisce l'array, o il messaggio d'errore. */
	private static function chiedi( $percorso, $parametri ) {
		$url = 'https://graph.facebook.com/' . self::API . '/' . $percorso . '?' . http_build_query( $parametri );
		$r   = wp_remote_get( $url, array( 'timeout' => 25 ) );

		if ( is_wp_error( $r ) ) {
			return 'Non sono riuscito a contattare Facebook: ' . $r->get_error_message();
		}
		$dati = json_decode( wp_remote_retrieve_body( $r ), true );
		if ( isset( $dati['error']['message'] ) ) {
			return 'Facebook risponde: ' . $dati['error']['message'];
		}
		if ( ! is_array( $dati ) ) {
			return 'Risposta di Facebook non riconosciuta (http ' . wp_remote_retrieve_response_code( $r ) . ').';
		}
		return $dati;
	}

	/**
	 * Torna al pannello con un messaggio, senza lasciare il codice nell'indirizzo.
	 *
	 * Gli ERRORI si scrivono anche in un'opzione, non solo nel messaggio che
	 * dura un minuto: un collegamento che fallisce lo si scopre riaprendo il
	 * pannello mezz'ora dopo, e un messaggio gia' svanito lascia davanti a un
	 * "Nessuna Pagina collegata" che non spiega niente.
	 */
	private static function torna( $tipo, $messaggio ) {
		if ( 'errore' === $tipo ) {
			$d            = self::diagnosi();
			$d['quando']  = time();
			$d['errore']  = $messaggio;
			update_option( self::OPZ_DIAGNOSI, $d, false );
		} elseif ( 'ok' === $tipo ) {
			delete_option( self::OPZ_DIAGNOSI );
		}
		set_transient( 'cp_fb_messaggio', array( 'tipo' => $tipo, 'testo' => $messaggio ), 60 );
		wp_safe_redirect( admin_url( 'admin.php?page=cp-facebook' ) );
		exit;
	}

	/** Quello che si e' capito dell'ultimo tentativo di collegamento. */
	public static function diagnosi() {
		$d = get_option( self::OPZ_DIAGNOSI, array() );
		return wp_parse_args( is_array( $d ) ? $d : array(), array(
			'quando'   => 0,
			'passo'    => '',
			'errore'   => '',
			'permessi' => array(),
			'pagine'   => -1,
		) );
	}

	/** Segna a che punto si e' arrivati, per poterlo dire nel pannello. */
	private static function passo( $nome, $extra = array() ) {
		$d          = self::diagnosi();
		$d['passo'] = $nome;
		foreach ( $extra as $k => $v ) { $d[ $k ] = $v; }
		update_option( self::OPZ_DIAGNOSI, $d, false );
	}

	/* ===================== scarico dei post ===================== */

	/**
	 * Chiede a Facebook gli ultimi post e li mette da parte.
	 *
	 * Le immagini si scaricano DOPO aver salvato i testi, e non piu' di poche
	 * per volta: su un hosting gratuito il tempo di esecuzione e' contato, e
	 * meglio un giro che porta a casa i testi e sei foto di un giro che va in
	 * timeout e non salva niente. Le foto mancanti le prende il giro dopo.
	 */
	public static function scarica() {
		$conf  = self::conf();
		$token = self::token();

		if ( '' === $token || '' === $conf['pagina'] ) {
			return self::segna( 'Nessuna Pagina collegata.' );
		}

		$campi = 'id,message,story,created_time,permalink_url,full_picture,'
			. 'attachments{media_type,media{image{src}},subattachments{media{image{src}}}}';

		$dati = self::chiedi( $conf['pagina'] . '/posts', array(
			'limit'        => (int) $conf['quanti'],
			'fields'       => $campi,
			'access_token' => $token,
		) );
		if ( is_string( $dati ) ) { return self::segna( $dati ); }
		if ( ! isset( $dati['data'] ) || ! is_array( $dati['data'] ) ) {
			return self::segna( 'Facebook non ha restituito nessun elenco di post.' );
		}

		/* Le copie locali gia' fatte si tengono, cosi' i giri successivi non
		   riscaricano ogni volta le stesse foto. Si controlla pero' che il file
		   ci sia DAVVERO: quando i post viaggiano fra ambienti - un travaso del
		   database - arrivano i nomi delle foto ma non le foto, e senza questo
		   controllo il sito nuovo resterebbe convinto di averle gia'. */
		$cartella = trailingslashit( self::cartella()['via'] );
		$gia      = array();
		foreach ( self::post() as $v ) {
			if ( ! empty( $v['file'] ) && file_exists( $cartella . $v['file'] ) ) {
				$gia[ $v['id'] ] = $v['file'];
			}
		}

		$post = array();
		foreach ( $dati['data'] as $p ) {
			if ( empty( $p['id'] ) ) { continue; }

			$testo = '';
			if ( ! empty( $p['message'] ) ) {
				$testo = (string) $p['message'];
			} elseif ( ! empty( $p['story'] ) ) {
				/* i post senza testo scritto - una foto caricata e basta - hanno
				   solo la frase che Facebook compone da se': meglio di niente */
				$testo = (string) $p['story'];
			}

			list( $titolo, $sommario ) = self::dividi( $testo );

			$post[] = array(
				'id'       => (string) $p['id'],
				'titolo'   => $titolo,
				'sommario' => $sommario,
				'data'     => isset( $p['created_time'] ) ? strtotime( $p['created_time'] ) : 0,
				'link'     => isset( $p['permalink_url'] ) ? esc_url_raw( $p['permalink_url'] ) : '',
				'remota'   => self::immagine_remota( $p ),
				'file'     => isset( $gia[ $p['id'] ] ) ? $gia[ $p['id'] ] : '',
			);
		}

		update_option( self::OPZ_POST, $post, false );

		$nuove = 0;
		foreach ( $post as $i => $p ) {
			if ( $nuove >= self::IMMAGINI_PER_GIRO ) { break; }
			if ( '' === $p['remota'] || '' !== $p['file'] ) { continue; }
			$file = self::scarica_immagine( $p['remota'], $p['id'] );
			if ( $file ) {
				$post[ $i ]['file'] = $file;
				$nuove++;
			}
		}
		if ( $nuove ) { update_option( self::OPZ_POST, $post, false ); }

		self::fai_pulizia( $post );

		$con_foto = 0;
		foreach ( $post as $p ) { if ( ! empty( $p['file'] ) ) { $con_foto++; } }

		update_option( self::OPZ_STATO, array(
			'ultimo_giro' => time(),
			'esito'       => 'ok',
			'errore'      => '',
			'scaricati'   => count( $post ),
			'immagini'    => $con_foto,
		), false );

		return array( 'ok' => true, 'post' => count( $post ), 'immagini' => $con_foto, 'nuove' => $nuove );
	}

	/** Registra un errore senza buttare via i post gia' in casa. */
	private static function segna( $messaggio ) {
		$s                = self::stato();
		$s['ultimo_giro'] = time();
		$s['esito']       = 'errore';
		$s['errore']      = $messaggio;
		update_option( self::OPZ_STATO, $s, false );
		return array( 'ok' => false, 'errore' => $messaggio );
	}

	/**
	 * L'indirizzo della foto del post, cercata dove Facebook la mette davvero.
	 *
	 * full_picture e' la via breve; se manca si guarda nell'allegato, e negli
	 * allegati dentro l'allegato per i post con piu' foto: si prende la prima.
	 */
	private static function immagine_remota( $p ) {
		if ( ! empty( $p['full_picture'] ) ) { return esc_url_raw( $p['full_picture'] ); }

		$a = isset( $p['attachments']['data'][0] ) ? $p['attachments']['data'][0] : array();
		if ( ! empty( $a['media']['image']['src'] ) ) { return esc_url_raw( $a['media']['image']['src'] ); }
		if ( ! empty( $a['subattachments']['data'][0]['media']['image']['src'] ) ) {
			return esc_url_raw( $a['subattachments']['data'][0]['media']['image']['src'] );
		}
		return '';
	}

	/**
	 * Da un post di Facebook - che un titolo non ce l'ha - si ricavano un titolo
	 * e un sommario.
	 *
	 * Sui social la prima riga fa gia' da titolo: quasi sempre e' corta e dice
	 * di cosa si parla. Quando invece la prima riga e' un paragrafo intero si
	 * cerca dove finisce la prima frase, perche' tagliare li' da' un titolo che
	 * si legge, mentre tagliare a numero di parole ne da' uno mozzato.
	 *
	 * Il sommario e' SEMPRE quello che avanza dopo il titolo, mai il testo da
	 * capo: altrimenti la scheda direbbe due volte le stesse parole, una volta
	 * grande e una piccola.
	 */
	private static function dividi( $testo ) {
		$testo = trim( preg_replace( "/\r\n?/", "\n", (string) $testo ) );
		if ( '' === $testo ) { return array( 'Aggiornamento dalla Pagina', '' ); }

		$righe = array_values( array_filter( array_map( 'trim', explode( "\n", $testo ) ), 'strlen' ) );
		$prima = isset( $righe[0] ) ? $righe[0] : $testo;

		if ( mb_strlen( $prima ) <= self::TITOLO_MAX ) {
			$titolo    = $prima;
			$consumato = mb_strlen( $prima );
		} elseif ( preg_match( '/^(.{15,' . self::TITOLO_MAX . '}?)[:.!?]\s/us', $prima, $m ) ) {
			$titolo    = trim( $m[1] );
			$consumato = mb_strlen( $m[0] );
		} else {
			/* nessuna frase che finisca in tempo: si taglia alle parole, ma si
			   tiene il conto di quante se ne sono prese, per non ripeterle */
			$intero    = wp_trim_words( $prima, 10, '' );
			$titolo    = $intero . '…';
			$consumato = mb_strlen( $intero );
		}

		$resto = trim( mb_substr( $testo, $consumato ) );
		$resto = preg_replace( '/^[\s:.!?\x{2013}\x{2014}-]+/u', '', $resto );

		return array(
			wp_strip_all_tags( $titolo ),
			wp_strip_all_tags( wp_trim_words( $resto, 26, '…' ) ),
		);
	}

	/* ===================== immagini ===================== */

	public static function cartella() {
		$su = wp_upload_dir();
		return array(
			'via' => trailingslashit( $su['basedir'] ) . self::CARTELLA,
			'web' => trailingslashit( $su['baseurl'] ) . self::CARTELLA,
		);
	}

	/**
	 * Porta la foto sul sito e la rimpicciolisce.
	 *
	 * Il ridimensionamento non e' un vezzo: le foto di Facebook arrivano anche a
	 * 2000 pixel e qualche mega, e nelle schede si vedono larghe 340. Ridotte a
	 * 1200 pesano una frazione, la pagina si apre prima e si sta lontani dalla
	 * taglia di file che l'hosting gratuito guarda con sospetto.
	 *
	 * Se il ridimensionamento non e' possibile - libreria grafica assente - si
	 * tiene comunque l'originale: una foto pesante e' meglio di nessuna foto.
	 */
	private static function scarica_immagine( $url, $id ) {
		$dir = self::cartella();
		if ( ! wp_mkdir_p( $dir['via'] ) ) { return ''; }

		$nome = preg_replace( '/[^0-9_]/', '', $id ) . '.jpg';
		$via  = trailingslashit( $dir['via'] ) . $nome;

		$r = wp_remote_get( $url, array( 'timeout' => 30 ) );
		if ( is_wp_error( $r ) ) { return ''; }
		if ( 200 !== (int) wp_remote_retrieve_response_code( $r ) ) { return ''; }

		$tipo = (string) wp_remote_retrieve_header( $r, 'content-type' );
		if ( 0 !== strpos( $tipo, 'image/' ) ) { return ''; }

		$corpo = wp_remote_retrieve_body( $r );
		if ( strlen( $corpo ) < 500 || strlen( $corpo ) > 8 * 1024 * 1024 ) { return ''; }
		if ( ! file_put_contents( $via, $corpo ) ) { return ''; }

		/* controllo vero sul contenuto: l'intestazione dice quello che vuole */
		if ( ! @getimagesize( $via ) ) { @unlink( $via ); return ''; }

		$editore = wp_get_image_editor( $via );
		if ( ! is_wp_error( $editore ) ) {
			$editore->resize( self::LARGHEZZA_MAX, self::LARGHEZZA_MAX * 2, false );
			$editore->set_quality( 82 );
			$editore->save( $via );
		}

		return file_exists( $via ) ? $nome : '';
	}

	/** Toglie le copie locali dei post che non sono piu' nel feed. */
	private static function fai_pulizia( $post ) {
		$dir = self::cartella();
		if ( ! is_dir( $dir['via'] ) ) { return; }

		$vivi = array();
		foreach ( $post as $p ) { if ( ! empty( $p['file'] ) ) { $vivi[ $p['file'] ] = true; } }

		foreach ( (array) glob( trailingslashit( $dir['via'] ) . '*.jpg' ) as $f ) {
			if ( ! isset( $vivi[ basename( $f ) ] ) ) { @unlink( $f ); }
		}
	}

	/* ===================== come si vedono ===================== */

	public static function post() {
		$p = get_option( self::OPZ_POST, array() );
		return is_array( $p ) ? $p : array();
	}

	/**
	 * Le schede pronte da stampare, con lo stesso vestito delle news del sito.
	 *
	 * Si riusa la classe .news-card del tema invece di portarsi dietro un
	 * foglio di stile proprio: cosi' i post di Facebook e le news scritte a mano
	 * si somigliano davvero, e il carosello della home - che cerca .news-card -
	 * li prende in carico senza sapere da dove vengono.
	 */
	public static function schede( $quanti = 0 ) {
		$post = self::post();
		if ( ! $post ) { return ''; }
		if ( $quanti > 0 ) { $post = array_slice( $post, 0, (int) $quanti ); }

		$dir  = self::cartella();
		$html = '';

		foreach ( $post as $p ) {
			$link = ! empty( $p['link'] ) ? $p['link'] : '';
			$a    = $link ? ' href="' . esc_url( $link ) . '" target="_blank" rel="noopener"' : '';

			$html .= '<article class="card news-card news-card--facebook">';

			/* la foto si mostra solo se il file c'e' per davvero: meglio una
			   scheda di solo testo di un riquadro con l'immagine rotta */
			if ( ! empty( $p['file'] ) && file_exists( trailingslashit( $dir['via'] ) . $p['file'] ) ) {
				$src   = trailingslashit( $dir['web'] ) . $p['file'];
				$html .= $link ? '<a class="news-thumb"' . $a . '>' : '<span class="news-thumb">';
				$html .= '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $p['titolo'] ) . '" loading="lazy">';
				$html .= $link ? '</a>' : '</span>';
			}

			$html .= '<div class="news-body">';
			$html .= '<div class="news-meta">' . esc_html( self::data_scritta( $p['data'] ) ) . '</div>';
			$html .= '<h3>' . ( $link ? '<a' . $a . '>' : '' ) . esc_html( $p['titolo'] ) . ( $link ? '</a>' : '' ) . '</h3>';
			if ( ! empty( $p['sommario'] ) ) {
				$html .= '<p>' . esc_html( $p['sommario'] ) . '</p>';
			}
			if ( $link ) {
				$html .= '<a class="leggi"' . $a . '>Leggi su Facebook &rarr;</a>';
			}
			$html .= '</div></article>';
		}

		return $html;
	}

	/** La data come la scrive WordPress nella lingua del sito. */
	private static function data_scritta( $quando ) {
		return $quando ? wp_date( get_option( 'date_format' ), (int) $quando ) : '';
	}

	/** [post_facebook quanti="12"] */
	public static function shortcode( $atts ) {
		$a      = shortcode_atts( array( 'quanti' => 0 ), $atts, 'post_facebook' );
		$schede = self::schede( (int) $a['quanti'] );
		return $schede ? '<div class="news-embeds-home news-embeds-list">' . $schede . '</div>' : '';
	}

	/* ===================== pannello ===================== */

	public static function menu() {
		$hook = add_menu_page( 'Post da Facebook', 'Post da Facebook', 'manage_options', 'cp-facebook',
			array( __CLASS__, 'pagina' ), 'dashicons-facebook-alt', 31 );
		add_action( 'load-' . $hook, array( __CLASS__, 'ritorno_da_facebook' ) );
	}

	public static function salva_app() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non hai i permessi.' ); }
		check_admin_referer( 'cp_fb_app' );

		$app = self::app();
		$app['id'] = preg_replace( '/[^0-9]/', '', (string) wp_unslash( $_POST['app_id'] ) );

		/* Il segreto arriva vuoto quando non lo si vuole cambiare: uno gia'
		   funzionante non va perso solo perche' si e' corretto l'identificativo. */
		$segreto = trim( (string) wp_unslash( $_POST['app_segreto'] ) );
		if ( '' !== $segreto ) {
			$app['segreto'] = sanitize_text_field( $segreto );
		}

		$app['configurazione'] = isset( $_POST['app_configurazione'] )
			? preg_replace( '/[^0-9]/', '', (string) wp_unslash( $_POST['app_configurazione'] ) )
			: '';

		update_option( self::OPZ_APP, $app, false );
		self::torna( 'ok', 'Dati dell&rsquo;applicazione salvati.' );
	}

	public static function salva_conf() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non hai i permessi.' ); }
		check_admin_referer( 'cp_fb_conf' );

		$conf            = self::conf();
		$conf['quanti']  = max( 1, min( 50, (int) $_POST['quanti'] ) );
		$conf['in_home'] = max( 1, min( 20, (int) $_POST['in_home'] ) );
		update_option( self::OPZ_CONF, $conf, false );

		self::torna( 'ok', 'Impostazioni salvate.' );
	}

	public static function aggiorna_a_mano() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non hai i permessi.' ); }
		check_admin_referer( 'cp_fb_aggiorna' );

		$e = self::scarica();
		if ( empty( $e['ok'] ) ) {
			self::torna( 'errore', $e['errore'] );
		}
		self::torna( 'ok', sprintf( 'Presi %d post, %d con la foto.', $e['post'], $e['immagini'] ) );
	}

	private static function bottone( $azione, $etichetta, $classe = 'button' ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
		echo '<input type="hidden" name="action" value="' . esc_attr( $azione ) . '">';
		wp_nonce_field( $azione );
		echo '<button class="' . esc_attr( $classe ) . '">' . esc_html( $etichetta ) . '</button>';
		echo '</form>';
	}

	public static function pagina() {
		$conf     = self::conf();
		$app      = self::app();
		$stato    = self::stato();
		$post     = self::post();
		$collegato = ( '' !== self::token() && '' !== $conf['pagina'] );
		$prossimo = wp_next_scheduled( self::EVENTO );

		$messaggio = get_transient( 'cp_fb_messaggio' );
		delete_transient( 'cp_fb_messaggio' );
		$da_scegliere = get_transient( 'cp_fb_pagine' );
		?>
		<div class="wrap">
			<h1>Post da Facebook</h1>

			<?php if ( $messaggio && ! empty( $messaggio['testo'] ) ) : ?>
				<div class="notice notice-<?php echo 'errore' === $messaggio['tipo'] ? 'error' : 'success'; ?>">
					<p><?php echo wp_kses_post( $messaggio['testo'] ); ?></p>
				</div>
			<?php endif; ?>

			<p class="description" style="max-width:62em">
				Una volta collegata la Pagina, ogni ora il sito ne chiede gli ultimi post e
				ne scarica anche le foto, tenendone una copia qui. I post compaiono in home
				fra le news, con la stessa veste.
			</p>

			<h2>1. L&rsquo;applicazione Facebook</h2>
			<p class="description" style="max-width:62em">
				Facebook non fa entrare un sito senza sapere chi &egrave;: serve
				un&rsquo;applicazione, che si crea una volta sola su
				<a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener">developers.facebook.com</a>
				(tipo <em>Altro</em> &rarr; <em>Azienda</em>, poi si aggiunge il prodotto
				<em>Accesso Facebook</em>). Nelle sue impostazioni va incollato, fra gli
				indirizzi di reindirizzamento ammessi, <strong>esattamente</strong> questo:
			</p>
			<p><input type="text" class="large-text code" readonly onclick="this.select()"
				value="<?php echo esc_attr( self::ritorno() ); ?>"></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cp_fb_app">
				<?php wp_nonce_field( 'cp_fb_app' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="cpfb-appid">Identificativo applicazione</label></th>
						<td><input name="app_id" id="cpfb-appid" type="text" class="regular-text"
							value="<?php echo esc_attr( $app['id'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="cpfb-segreto">Chiave segreta</label></th>
						<td>
							<input name="app_segreto" id="cpfb-segreto" type="password" class="regular-text" autocomplete="off"
								placeholder="<?php echo $app['segreto'] ? 'gia&rsquo; impostata: lascia vuoto per non cambiarla' : 'incolla qui la chiave segreta'; ?>">
							<p class="description">Resta su questo sito e non viene mai mostrata.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cpfb-config">ID configurazione</label></th>
						<td>
							<input name="app_configurazione" id="cpfb-config" type="text" class="regular-text"
								value="<?php echo esc_attr( $app['configurazione'] ); ?>">
							<p class="description">
								<strong>Da lasciare vuoto</strong>, salvo un caso: se
								l&rsquo;applicazione usa <em>Facebook Login for Business</em>, i permessi
								non si chiedono per nome ma tramite una configurazione, e Facebook ne
								mostra l&rsquo;identificativo nelle impostazioni dell&rsquo;accesso.
								Compilalo solo se il collegamento fallisce dicendo che mancano i permessi.
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Salva i dati dell&rsquo;applicazione', 'secondary' ); ?>
			</form>

			<hr>

			<h2>2. Il collegamento alla Pagina</h2>
			<?php if ( is_array( $da_scegliere ) && $da_scegliere ) : ?>
				<p>Questa utenza amministra pi&ugrave; Pagine: scegli quella del club.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="cp_fb_scegli">
					<?php wp_nonce_field( 'cp_fb_scegli' ); ?>
					<?php foreach ( $da_scegliere as $p ) : ?>
						<p><label>
							<input type="radio" name="pagina" value="<?php echo esc_attr( $p['id'] ); ?>">
							<strong><?php echo esc_html( $p['nome'] ); ?></strong>
							<span class="description">(<?php echo esc_html( $p['id'] ); ?>)</span>
						</label></p>
					<?php endforeach; ?>
					<?php submit_button( 'Collega questa Pagina' ); ?>
				</form>
			<?php elseif ( $collegato ) : ?>
				<p>
					Collegato alla Pagina
					<strong><?php echo esc_html( $conf['nome_pagina'] ? $conf['nome_pagina'] : $conf['pagina'] ); ?></strong>
					<span class="description">(<?php echo esc_html( $conf['pagina'] ); ?>)</span>
				</p>
				<p>
					<?php self::bottone( 'cp_fb_aggiorna', 'Aggiorna adesso', 'button button-primary' ); ?>
					&nbsp;
					<?php self::bottone( 'cp_fb_disconnetti', 'Scollega la Pagina' ); ?>
				</p>
			<?php else : ?>
				<p>Nessuna Pagina collegata.</p>

				<?php
				/* Se l'ultimo tentativo e' andato male lo si dice QUI, dove si
				   sta guardando, e non in un avviso svanito da un pezzo. */
				$dg = self::diagnosi();
				if ( $dg['quando'] ) : ?>
				<div class="notice notice-error inline" style="margin:12px 0;padding:10px 14px">
					<p style="margin-top:0"><strong>L&rsquo;ultimo tentativo non &egrave; riuscito</strong>
						&mdash; <?php echo esc_html( wp_date( 'd/m/Y H:i', $dg['quando'] ) ); ?></p>
					<?php if ( $dg['errore'] ) : ?>
						<p><?php echo wp_kses_post( $dg['errore'] ); ?></p>
					<?php endif; ?>
					<p style="margin-bottom:0"><em>
						Si &egrave; fermato al passo: <strong><?php echo esc_html( $dg['passo'] ? $dg['passo'] : 'sconosciuto' ); ?></strong>.
						<?php if ( $dg['permessi'] ) : ?>
							Permessi concessi da Facebook: <code><?php echo esc_html( implode( ', ', $dg['permessi'] ) ); ?></code>.
						<?php elseif ( in_array( $dg['passo'], array( 'permessi concessi', 'elenco delle Pagine' ), true ) ) : ?>
							<?php /* lo si dice solo se a quel punto i permessi erano gia' stati chiesti:
							         prima non e' un'assenza, e' una domanda non ancora fatta */ ?>
							Facebook non ha concesso <strong>nessun</strong> permesso.
						<?php endif; ?>
						<?php if ( $dg['pagine'] >= 0 ) : ?>
							Pagine trovate: <strong><?php echo (int) $dg['pagine']; ?></strong>.
						<?php endif; ?>
					</em></p>
				</div>
				<?php endif; ?>
				<p>
					<?php if ( $app['id'] && $app['segreto'] ) : ?>
						<?php self::bottone( 'cp_fb_connetti', 'Connetti con Facebook', 'button button-primary' ); ?>
						<span class="description" style="margin-left:10px">
							Entra con l&rsquo;utenza che amministra la Pagina del club.
						</span>
					<?php else : ?>
						<em>Prima compila il passo 1.</em>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<hr>

			<h2>Come sta andando</h2>
			<table class="widefat striped" style="max-width:62em">
				<tbody>
				<tr><td style="width:17em"><strong>Ultimo aggiornamento</strong></td><td>
					<?php echo $stato['ultimo_giro'] ? esc_html( wp_date( 'd/m/Y H:i', $stato['ultimo_giro'] ) ) : 'mai'; ?>
				</td></tr>
				<tr><td><strong>Esito</strong></td><td>
					<?php if ( 'errore' === $stato['esito'] ) : ?>
						<span style="color:#b32d2e"><strong>errore</strong> &ndash; <?php echo esc_html( $stato['errore'] ); ?></span>
					<?php elseif ( 'ok' === $stato['esito'] ) : ?>
						<span style="color:#1a7f37">tutto bene</span>
					<?php else : ?>
						&mdash;
					<?php endif; ?>
				</td></tr>
				<tr><td><strong>Post in casa</strong></td><td><?php echo count( $post ); ?></td></tr>
				<tr><td><strong>Con foto scaricata</strong></td><td><?php echo (int) $stato['immagini']; ?></td></tr>
				<tr><td><strong>Prossimo giro</strong></td><td>
					<?php echo $prossimo ? esc_html( wp_date( 'd/m/Y H:i', $prossimo ) ) : 'non programmato'; ?>
				</td></tr>
				</tbody>
			</table>

			<h2>Impostazioni</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cp_fb_conf">
				<?php wp_nonce_field( 'cp_fb_conf' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="cpfb-quanti">Quanti post tenere</label></th>
						<td><input name="quanti" id="cpfb-quanti" type="number" min="1" max="50" value="<?php echo (int) $conf['quanti']; ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="cpfb-home">Quanti mostrarne in home</label></th>
						<td><input name="in_home" id="cpfb-home" type="number" min="1" max="20" value="<?php echo (int) $conf['in_home']; ?>"></td>
					</tr>
				</table>
				<?php submit_button( 'Salva', 'secondary' ); ?>
			</form>

			<?php if ( $post ) : ?>
			<h2>I post che sono in casa</h2>
			<table class="widefat striped">
				<thead><tr><th style="width:9em">Data</th><th>Titolo</th><th style="width:8em">Foto</th></tr></thead>
				<tbody>
				<?php foreach ( $post as $p ) : ?>
					<tr>
						<td><?php echo esc_html( $p['data'] ? wp_date( 'd/m/Y', $p['data'] ) : '—' ); ?></td>
						<td><?php echo esc_html( $p['titolo'] ); ?></td>
						<td><?php
							if ( ! empty( $p['file'] ) ) { echo 'scaricata'; }
							elseif ( ! empty( $p['remota'] ) ) { echo '<em>in attesa</em>'; }
							else { echo '—'; }
						?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ===================== accensione e spegnimento ===================== */

	public static function accendi() {
		if ( ! wp_next_scheduled( self::EVENTO ) ) {
			wp_schedule_event( time() + 60, 'hourly', self::EVENTO );
		}
	}

	public static function spegni() {
		$q = wp_next_scheduled( self::EVENTO );
		if ( $q ) { wp_unschedule_event( $q, self::EVENTO ); }
	}
}

CP_Facebook::init();
register_activation_hook( __FILE__, array( 'CP_Facebook', 'accendi' ) );
register_deactivation_hook( __FILE__, array( 'CP_Facebook', 'spegni' ) );

/**
 * Le schede dei post di Facebook, pronte da stampare nel tema.
 *
 * @param int $quanti 0 = tutti quelli in casa.
 * @return string HTML, stringa vuota se non c'e' niente da mostrare.
 */
function cp_post_facebook( $quanti = 0 ) {
	return class_exists( 'CP_Facebook' ) ? CP_Facebook::schede( (int) $quanti ) : '';
}

/** Quanti post vuole la home, secondo il pannello. */
function cp_post_facebook_in_home() {
	if ( ! class_exists( 'CP_Facebook' ) ) { return ''; }
	$c = CP_Facebook::conf();
	return CP_Facebook::schede( (int) $c['in_home'] );
}
