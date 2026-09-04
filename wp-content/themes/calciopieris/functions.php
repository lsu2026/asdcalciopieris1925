<?php
/**
 * Calcio Pieris 1925 — funzioni del tema.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function calciopieris_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array(
		'height'      => 160,
		'width'       => 160,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );

	register_nav_menus( array(
		'primary' => __( 'Menu principale', 'calciopieris' ),
		'footer'  => __( 'Menu footer', 'calciopieris' ),
	) );
}
add_action( 'after_setup_theme', 'calciopieris_setup' );

function calciopieris_scripts() {
	wp_enqueue_style(
		'calciopieris-fonts',
		'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=Barlow:wght@400;600&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'calciopieris-style', get_stylesheet_uri(), array( 'calciopieris-fonts' ), wp_get_theme()->get( 'Version' ) );
	wp_enqueue_script( 'calciopieris-reveal', get_template_directory_uri() . '/assets/reveal.js', array(), wp_get_theme()->get( 'Version' ), true );
	wp_enqueue_script( 'calciopieris-sponsors', get_template_directory_uri() . '/assets/sponsors.js', array(), wp_get_theme()->get( 'Version' ), true );
}
add_action( 'wp_enqueue_scripts', 'calciopieris_scripts' );

/**
 * Logo dello scudetto: media library (custom logo) con fallback al file del tema.
 */
function calciopieris_badge( $class = '' ) {
	$logo_id = get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		echo wp_get_attachment_image( $logo_id, 'full', false, array( 'class' => $class, 'alt' => get_bloginfo( 'name' ) ) );
	} else {
		printf(
			'<img src="%s" class="%s" alt="%s">',
			esc_url( get_template_directory_uri() . '/assets/scudetto-trasparente.png' ),
			esc_attr( $class ),
			esc_attr( get_bloginfo( 'name' ) )
		);
	}
}

/** Lunghezza estratto ridotta per le card news. */
add_filter( 'excerpt_length', function () { return 24; } );
add_filter( 'excerpt_more', function () { return '&hellip;'; } );

/**
 * Sezione "Staff tecnico" (ruolo + nome) per un gruppo: 'prima' o 'giovanile'.
 * Usa i dati del plugin "Staff Tecnico" se attivo, altrimenti un elenco di default.
 */
function cp_staff_tecnico_html( $group = 'prima' ) {
	$default = array(
		array( 'role' => 'Responsabile · Tecnico UEFA B',          'name' => 'Massimo Wisniewski' ),
		array( 'role' => 'Tecnico UEFA E · Educatrice',            'name' => 'Luisa Rodà' ),
		array( 'role' => 'Laureato Magistrale in Scienze Motorie', 'name' => 'Francesco Bradaschia' ),
		array( 'role' => 'Tecnico UEFA E',                         'name' => 'Francesca Bibalo' ),
		array( 'role' => 'Tecnico UEFA E',                         'name' => 'Michele Desogus' ),
	);
	$rows = function_exists( 'cp_get_staff' ) ? cp_get_staff( $group ) : $default;
	if ( empty( $rows ) ) { return ''; }
	$h  = '<section class="staff-tecnico" id="staff-tecnico"><h2>Staff tecnico</h2>';
	$h .= '<table><thead><tr><th>Riferimenti</th><th>Nome</th></tr></thead><tbody>';
	foreach ( $rows as $r ) {
		$role = isset( $r['role'] ) ? $r['role'] : '';
		$name = isset( $r['name'] ) ? $r['name'] : '';
		$h .= '<tr><td>' . esc_html( $role ) . '</td><td>' . esc_html( $name ) . '</td></tr>';
	}
	$h .= '</tbody></table></section>';
	return $h;
}


/**
 * Il feed Facebook di una sezione, gia' pronto da stampare, o stringa vuota.
 *
 * Si cerca per NOME e non per numero: gli identificativi dei feed nascono
 * diversi in ogni ambiente, come le voci di menu, e scriverli fissi
 * funzionerebbe solo in locale.
 *
 * Torna vuoto in tutti i casi in cui non c'e' niente di buono da mostrare:
 * plugin assente, feed non configurato, nessun post. Cosi' chi chiama puo'
 * ripiegare sulle news scritte a mano invece di lasciare un buco in pagina.
 */
function cp_feed_facebook( $nome ) {
	if ( ! shortcode_exists( 'custom-facebook-feed' ) ) { return ''; }

	global $wpdb;
	$tabella = $wpdb->prefix . 'cff_feeds';

	/* la tabella esiste solo se il plugin e' installato: chiederla senza
	   controllare stamperebbe un errore del database in cima alla pagina */
	static $c_e_la_tabella = null;
	if ( null === $c_e_la_tabella ) {
		$c_e_la_tabella = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabella ) ) === $tabella );
	}
	if ( ! $c_e_la_tabella ) { return ''; }

	$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$tabella` WHERE feed_name = %s LIMIT 1", $nome ) );
	if ( ! $id ) { return ''; }

	/* lo shortcode a volte stampa invece di restituire: si raccoglie entrambe */
	ob_start();
	$restituito = do_shortcode( '[custom-facebook-feed feed=' . (int) $id . ']' );
	$html = ob_get_clean() . $restituito;

	return ( false !== strpos( $html, 'cff-item' ) ) ? $html : '';
}

/**
 * Gli store ufficiali del club.
 *
 * Sono due e stanno sullo stesso piano: EYE Sports e Maglie4team.
 * L'ordine di questo elenco e' quello con cui compaiono sul sito.
 *
 * Ogni store porta due indirizzi distinti, e la differenza conta:
 *  - 'site' e' la home dello shop, dove si vede tutto il catalogo;
 *  - 'url'  e' la sezione dedicata al Calcio Pieris, cioe' quella che interessa
 *           davvero al tifoso, ed e' l'indirizzo dei pulsanti principali.
 * Per lo stesso motivo l'indirizzo NON viene mai ereditato dalla voce sponsor,
 * che rimanda alla home dello shop; il logo si', cosi' il marchio resta
 * allineato ovunque aggiornandolo in un punto solo.
 *
 * 'logo' puo' essere vuoto: in quel caso i template mostrano il nome scritto,
 * e l'immagine compare da sola appena lo store viene aggiunto fra gli sponsor
 * con il proprio logo (l'abbinamento avviene sul frammento in 'cerca').
 */
function cp_stores() {
	$stores = array(
		array(
			'name'  => 'EYE Sports',
			'url'   => 'https://www.eyesportshop.com/it/450-asd-calcio-pieris-1925',
			'site'  => 'https://www.eyesportshop.com/it/',
			'logo'  => get_template_directory_uri() . '/assets/sponsors/eyestore.jpg',
			'cerca' => 'eye',
			'desc'  => 'Punto di riferimento per il materiale tecnico e l&rsquo;abbigliamento del club: le stesse divise e gli stessi capi che la prima squadra e il settore giovanile indossano.',
		),
		array(
			'name'  => 'Maglie4team',
			'url'   => 'https://maglie4team.it/calcio/pieris-calcio/',
			'site'  => 'https://maglie4team.it',
			'logo'  => get_template_directory_uri() . '/assets/sponsors/maglie4team.png',
			'cerca' => 'maglie4',
			'desc'  => 'Borse e accessori del club: sacche, zaini, borsoni e tutto quello che serve per andare al campo. Sul sito c&rsquo;&egrave; una sezione dedicata al Calcio Pieris 1925.',
		),
	);

	/* Se lo store e' anche sponsor, il logo caricato dal pannello Sponsor ha la
	   precedenza su quello del tema: cosi' basta aggiornarlo in un posto solo.
	   Vale anche per gli store che qui non hanno ancora un logo. */
	if ( function_exists( 'cp_get_sponsors' ) ) {
		$sponsor = cp_get_sponsors();
		foreach ( $stores as $i => $s ) {
			foreach ( $sponsor as $sp ) {
				if ( ! empty( $sp['name'] ) && ! empty( $sp['logo'] )
					&& stripos( $sp['name'], $s['cerca'] ) !== false ) {
					$stores[ $i ]['logo'] = $sp['logo'];
					break;
				}
			}
		}
	}
	return $stores;
}

/**
 * Il primo store, per i punti del tema che ne mostrano uno solo.
 * Resta per compatibilita': cp_stores() e' la fonte completa.
 */
function cp_store_info() {
	$stores = cp_stores();
	return $stores[0];
}

/**
 * URL della foto di una maglia, cercata in assets/kit/ come {slug}.{estensione}.
 *
 * Ritorna stringa vuota se il file non c'e' ancora: in quel caso il template mostra un
 * segnaposto. Per pubblicare una foto basta copiare il file nella cartella con il nome
 * giusto (prima, seconda, portiere) - non serve toccare il codice.
 */
function cp_kit_image_url( $slug ) {
	$base = get_template_directory() . '/assets/kit/';
	foreach ( array( 'webp', 'jpg', 'jpeg', 'png' ) as $ext ) {
		if ( file_exists( $base . $slug . '.' . $ext ) ) {
			return get_template_directory_uri() . '/assets/kit/' . $slug . '.' . $ext;
		}
	}
	return '';
}


/**
 * Percorso relativo a wp-content, cioe' la forma in cui le immagini del sito vanno
 * SALVATE nel database.
 *
 * Un URL assoluto (http://dominio/.../wp-content/themes/...) legherebbe il dato al
 * dominio del momento: cambiando dominio o URL base le immagini non si vedrebbero piu'.
 * Salvando "themes/calciopieris/assets/sponsors/eyestore.jpg" il riferimento resta
 * valido ovunque. Gli indirizzi esterni non contengono /wp-content/ e tornano intatti.
 */
function cp_asset_relative( $url ) {
	if ( ! is_string( $url ) || '' === trim( $url ) ) { return ''; }
	$url = trim( $url );
	$pos = strpos( $url, '/wp-content/' );
	if ( false === $pos ) { return $url; }
	return ltrim( substr( $url, $pos + strlen( '/wp-content/' ) ), '/' );
}

/**
 * URL assoluto da usare in pagina, ricostruito a partire dal valore salvato.
 *
 * Accetta sia il percorso relativo sia vecchi valori assoluti rimasti nel database:
 * in entrambi i casi l'indirizzo viene riancorato a content_url(), che segue dominio
 * e URL base della richiesta in corso. Gli indirizzi esterni restano invariati.
 */
function cp_asset_url( $stored ) {
	if ( ! is_string( $stored ) || '' === trim( $stored ) ) { return ''; }
	$stored = trim( $stored );

	if ( ! preg_match( '#^(https?:)?//#i', $stored ) ) {
		return content_url( '/' . ltrim( $stored, '/' ) );   // gia' relativo
	}

	$pos = strpos( $stored, '/wp-content/' );
	if ( false === $pos ) { return $stored; }              // esterno: non lo tocco
	return content_url( substr( $stored, $pos + strlen( '/wp-content/' ) ) );
}
