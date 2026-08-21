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
 * Dati dello store ufficiale del club.
 *
 * Lo store e' gestito da EYE Sports, che e' anche sponsor del club. Le due cose pero'
 * puntano ad indirizzi diversi e vanno tenute distinte:
 *  - la voce sponsor rimanda alla home dello shop (pagina Sponsors);
 *  - qui serve la pagina dedicata al kit granata, quella che interessa al tifoso.
 * Per questo l'indirizzo NON viene ereditato dallo sponsor, mentre lo stemma si:
 * cosi' il marchio resta allineato ovunque aggiornandolo in un punto solo.
 */
function cp_store_info() {
	$store = array(
		'name' => 'EYE Sports',
		'url'  => 'https://www.eyesportshop.com/it/450-asd-calcio-pieris-1925',
		'logo' => get_template_directory_uri() . '/assets/sponsors/eyestore.jpg',
	);
	if ( function_exists( 'cp_get_sponsors' ) ) {
		foreach ( cp_get_sponsors() as $sp ) {
			if ( ! empty( $sp['name'] ) && stripos( $sp['name'], 'eye' ) !== false ) {
				if ( ! empty( $sp['logo'] ) ) { $store['logo'] = $sp['logo']; }
				break;
			}
		}
	}
	return $store;
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
