<?php
/**
 * Plugin Name: Calcio Pieris – Documenti
 * Description: Carica i documenti ufficiali della societa' dalla Libreria media e li rende scaricabili dalle pagine tramite lo shortcode [documenti area="..."]. Nasce per il Modello Organizzativo e il Codice di Condotta della pagina Safeguarding; per aggiungere altre caselle basta una riga in slots().
 * Version: 1.0
 * Author: A.S.D. Calcio Pieris 1925
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CP_Documenti {

	const OPT = 'cp_documenti';

	/** Estensioni ammesse. Documenti, non immagini e non archivi. */
	const ESTENSIONI = array( 'pdf', 'doc', 'docx', 'odt' );

	/**
	 * Le caselle disponibili.
	 *
	 * Sono dichiarate qui e non create dal pannello di proposito: un documento
	 * ufficiale non e' una riga qualsiasi in un elenco, ha un posto preciso in
	 * una pagina precisa. Cosi' chi carica sa cosa sta caricando, e il testo
	 * della pagina non puo' ritrovarsi ad annunciare un documento che nessuno
	 * ha previsto. Per aggiungerne una serve una riga qui, e il filtro
	 * 'cp_documenti_caselle' la rende comunque estendibile senza toccare il file.
	 */
	public static function slots() {
		return apply_filters( 'cp_documenti_caselle', array(
			'modello-organizzativo' => array(
				'area'  => 'safeguarding',
				'label' => 'Modello Organizzativo e di Controllo',
				'desc'  => 'Il modello organizzativo e di controllo dell&rsquo;attivit&agrave; sportiva adottato dalla Societ&agrave;.',
			),
			'codice-condotta' => array(
				'area'  => 'safeguarding',
				'label' => 'Codice di Condotta',
				'desc'  => 'Il codice di condotta che impegna tesserati, tecnici, dirigenti e accompagnatori.',
			),
		) );
	}

	/** Etichette delle aree, per i titoli nel pannello. */
	public static function aree() {
		return apply_filters( 'cp_documenti_aree', array( 'safeguarding' => 'Safeguarding' ) );
	}

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_cpdoc_save', array( __CLASS__, 'handle_save' ) );
		add_shortcode( 'documenti', array( __CLASS__, 'shortcode' ) );
	}

	/* ---------------- Dati ---------------- */

	public static function tutti() {
		$v = get_option( self::OPT, array() );
		return is_array( $v ) ? $v : array();
	}

	/**
	 * Il documento di una casella, oppure null.
	 *
	 * Restituisce null anche quando il documento risulta salvato ma il file non
	 * c'e' piu' sul disco. Non e' pignoleria: gli ambienti hanno database
	 * separati e i file si caricano su ciascuno, quindi puo' benissimo capitare
	 * che la produzione conosca il documento e non lo abbia ancora. Meglio non
	 * mostrare il bottone che mostrarne uno che porta a una pagina di errore.
	 */
	public static function get( $chiave ) {
		$tutti = self::tutti();
		if ( empty( $tutti[ $chiave ]['url'] ) ) { return null; }

		$doc = $tutti[ $chiave ];
		$percorso = self::percorso( $doc['url'] );
		if ( ! $percorso || ! is_readable( $percorso ) ) { return null; }

		/* peso letto dal file vero: quello salvato viene dall'ambiente in cui
		   il documento e' stato caricato la prima volta e potrebbe non valere qui */
		$doc['peso'] = filesize( $percorso );
		$doc['link'] = self::url( $doc['url'] );
		return $doc;
	}

	/** Documenti di un'area, nell'ordine in cui sono dichiarati in slots(). */
	public static function per_area( $area ) {
		$out = array();
		foreach ( self::slots() as $chiave => $casella ) {
			if ( $casella['area'] !== $area ) { continue; }
			$doc = self::get( $chiave );
			if ( $doc ) { $out[ $chiave ] = array_merge( $casella, $doc ); }
		}
		return $out;
	}

	/* ---------------- Percorsi ----------------
	 * Si salva il percorso RELATIVO a wp-content, non l'indirizzo completo: un
	 * indirizzo assoluto legherebbe il documento al dominio dell'ambiente in cui
	 * e' stato caricato, e altrove il bottone porterebbe al sito sbagliato. E' la
	 * stessa convenzione dei loghi degli sponsor. Le funzioni del tema fanno gia'
	 * questo lavoro; se non ci fossero, qui sotto c'e' lo stesso calcolo, cosi'
	 * il plugin non dipende dal tema attivo.
	 * ------------------------------------------------------------------------ */

	private static function relativo( $url ) {
		if ( function_exists( 'cp_asset_relative' ) ) { return cp_asset_relative( $url ); }
		$pos = strpos( (string) $url, '/wp-content/' );
		return ( false === $pos ) ? (string) $url : ltrim( substr( $url, $pos + strlen( '/wp-content/' ) ), '/' );
	}

	private static function url( $salvato ) {
		if ( function_exists( 'cp_asset_url' ) ) { return cp_asset_url( $salvato ); }
		return content_url( '/' . ltrim( (string) $salvato, '/' ) );
	}

	private static function percorso( $salvato ) {
		$rel = ltrim( (string) $salvato, '/' );
		if ( '' === $rel || false !== strpos( $rel, '..' ) ) { return ''; }
		return WP_CONTENT_DIR . '/' . $rel;
	}

	private static function estensione( $url ) {
		$p = wp_parse_url( $url, PHP_URL_PATH );
		return strtolower( pathinfo( (string) $p, PATHINFO_EXTENSION ) );
	}

	/* ---------------- Pannello ---------------- */

	public static function menu() {
		$hook = add_menu_page( 'Documenti', 'Documenti', 'manage_options', 'cp-documenti', array( __CLASS__, 'page' ), 'dashicons-media-text', 30 );
		add_action( 'admin_print_scripts-' . $hook, function () { wp_enqueue_media(); } );
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non autorizzato.' ); }
		check_admin_referer( 'cpdoc_save' );

		$vecchi = self::tutti();
		$out    = array();
		$scarti = array();

		$inviati = isset( $_POST['doc'] ) && is_array( $_POST['doc'] ) ? wp_unslash( $_POST['doc'] ) : array();

		foreach ( self::slots() as $chiave => $casella ) {
			$url = isset( $inviati[ $chiave ] ) ? esc_url_raw( trim( $inviati[ $chiave ] ) ) : '';
			if ( '' === $url ) { continue; }   // svuotato dal pannello: si rimuove

			/* Due controlli sul lato server, perche' il campo e' un testo nascosto
			   e la finestra della Libreria media non e' l'unico modo di riempirlo. */
			$est = self::estensione( $url );
			if ( ! in_array( $est, self::ESTENSIONI, true ) ) {
				$scarti[] = $casella['label'] . ' (tipo di file non ammesso: .' . $est . ')';
				if ( isset( $vecchi[ $chiave ] ) ) { $out[ $chiave ] = $vecchi[ $chiave ]; }
				continue;
			}
			$rel = self::relativo( $url );
			if ( 0 !== strpos( $rel, 'uploads/' ) ) {
				$scarti[] = $casella['label'] . ' (il file deve stare nella Libreria media)';
				if ( isset( $vecchi[ $chiave ] ) ) { $out[ $chiave ] = $vecchi[ $chiave ]; }
				continue;
			}

			$percorso = self::percorso( $rel );
			$cambiato = ! isset( $vecchi[ $chiave ]['url'] ) || $vecchi[ $chiave ]['url'] !== $rel;

			$out[ $chiave ] = array(
				'url'  => $rel,
				'nome' => basename( $rel ),
				'peso' => ( $percorso && is_readable( $percorso ) ) ? filesize( $percorso ) : 0,
				/* data in UTC, convertita solo al momento di mostrarla: salvare
				   l'ora locale come se fosse UTC e' l'errore che fa comparire
				   date sfasate di un'ora quando cambia l'ora legale */
				'data' => $cambiato ? gmdate( 'Y-m-d H:i:s' ) : ( isset( $vecchi[ $chiave ]['data'] ) ? $vecchi[ $chiave ]['data'] : gmdate( 'Y-m-d H:i:s' ) ),
			);
		}

		update_option( self::OPT, $out );

		$args = array( 'page' => 'cp-documenti', 'updated' => '1' );
		if ( $scarti ) { $args['scarti'] = rawurlencode( implode( ' | ', $scarti ) ); }
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$aree = self::aree();
		?>
		<div class="wrap">
			<h1>Documenti</h1>

			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Documenti salvati.</p></div>
			<?php endif; ?>
			<?php if ( ! empty( $_GET['scarti'] ) ) : ?>
				<div class="notice notice-error"><p><strong>Non salvati:</strong>
					<?php echo esc_html( rawurldecode( wp_unslash( $_GET['scarti'] ) ) ); ?></p></div>
			<?php endif; ?>

			<p>Carica qui i documenti ufficiali. Compaiono come bottoni di scarico nella pagina
			corrispondente del sito. Sono ammessi file <strong><?php echo esc_html( strtoupper( implode( ', ', self::ESTENSIONI ) ) ); ?></strong>.</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cpdoc_save">
				<?php wp_nonce_field( 'cpdoc_save' ); ?>

				<?php foreach ( $aree as $area => $etichetta ) : ?>
					<h2 style="margin-top:28px"><?php echo esc_html( $etichetta ); ?></h2>
					<?php
					foreach ( self::slots() as $chiave => $casella ) {
						if ( $casella['area'] === $area ) { self::render_riga( $chiave, $casella ); }
					}
					?>
				<?php endforeach; ?>

				<?php submit_button( 'Salva documenti' ); ?>
			</form>

			<p class="description" style="max-width:820px">
				I documenti vanno caricati <strong>in ogni ambiente</strong> (certificazione e produzione hanno
				archivi separati). Dove un documento non &egrave; ancora stato caricato, il bottone
				semplicemente non compare: non viene mai mostrato un collegamento che porta a un errore.
			</p>
		</div>

		<style>
			.cpdoc{background:#fff;border:1px solid #ccd0d4;border-radius:8px;padding:14px 18px;margin:0 0 12px;max-width:820px}
			.cpdoc h3{margin:0 0 4px;font-size:14px}
			.cpdoc .cpdoc-desc{color:#666;margin:0 0 10px}
			.cpdoc-file{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0 0 10px}
			.cpdoc-nome{font-family:Consolas,Monaco,monospace;background:#f6f7f7;border-radius:4px;padding:3px 8px}
			.cpdoc-vuoto{color:#999;font-style:italic}
		</style>

		<script>
		(function () {
			var frame = null;
			document.addEventListener('click', function (e) {
				var scegli = e.target.closest ? e.target.closest('.cpdoc-scegli') : null;
				var togli  = e.target.closest ? e.target.closest('.cpdoc-togli')  : null;

				if (togli) {
					e.preventDefault();
					var r = togli.closest('.cpdoc');
					r.querySelector('.cpdoc-input').value = '';
					r.querySelector('.cpdoc-file').innerHTML = '<span class="cpdoc-vuoto">nessun documento caricato</span>';
					return;
				}
				if (!scegli) { return; }
				e.preventDefault();

				var riga = scegli.closest('.cpdoc');
				/* una finestra nuova a ogni apertura: riusarla farebbe finire il
				   file scelto nella casella su cui si era cliccato la volta prima */
				frame = wp.media({
					title: 'Scegli il documento',
					button: { text: 'Usa questo documento' },
					library: { type: ['application/pdf', 'application/msword',
						'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
						'application/vnd.oasis.opendocument.text'] },
					multiple: false
				});
				frame.on('select', function () {
					var a = frame.state().get('selection').first().toJSON();
					riga.querySelector('.cpdoc-input').value = a.url;
					riga.querySelector('.cpdoc-file').innerHTML =
						'<span class="cpdoc-nome"></span> <span class="cpdoc-peso"></span>';
					riga.querySelector('.cpdoc-nome').textContent = a.filename;
					riga.querySelector('.cpdoc-peso').textContent = a.filesizeHumanReadable || '';
				});
				frame.open();
			});
		})();
		</script>
		<?php
	}

	private static function render_riga( $chiave, $casella ) {
		$tutti = self::tutti();
		$salvato = isset( $tutti[ $chiave ]['url'] ) ? $tutti[ $chiave ]['url'] : '';
		$doc = self::get( $chiave );   // null se il file non c'e' davvero
		?>
		<div class="cpdoc" data-chiave="<?php echo esc_attr( $chiave ); ?>">
			<h3><?php echo esc_html( $casella['label'] ); ?></h3>
			<p class="cpdoc-desc"><?php echo wp_kses_post( $casella['desc'] ); ?></p>

			<div class="cpdoc-file">
				<?php if ( $doc ) : ?>
					<span class="cpdoc-nome"><?php echo esc_html( $doc['nome'] ); ?></span>
					<span class="cpdoc-peso"><?php echo esc_html( size_format( $doc['peso'] ) ); ?></span>
					<a href="<?php echo esc_url( $doc['link'] ); ?>" target="_blank" rel="noopener">apri</a>
				<?php elseif ( $salvato ) : ?>
					<span class="cpdoc-vuoto">file mancante in questo ambiente (<?php echo esc_html( basename( $salvato ) ); ?>): ricaricalo</span>
				<?php else : ?>
					<span class="cpdoc-vuoto">nessun documento caricato</span>
				<?php endif; ?>
			</div>

			<input type="hidden" class="cpdoc-input" name="doc[<?php echo esc_attr( $chiave ); ?>]" value="<?php echo esc_attr( $salvato ? self::url( $salvato ) : '' ); ?>">
			<button type="button" class="button cpdoc-scegli"><?php echo $doc ? 'Sostituisci&hellip;' : 'Carica&hellip;'; ?></button>
			<button type="button" class="button-link cpdoc-togli" style="color:#b32d2e;margin-left:10px">Rimuovi</button>
		</div>
		<?php
	}

	/* ---------------- Sito ---------------- */

	/**
	 * [documenti area="safeguarding"]
	 *
	 * Se nessun documento e' stato caricato non stampa un elenco vuoto: lo dice.
	 * La frase che precede lo shortcode annuncia dei documenti disponibili, e
	 * lasciarla senza seguito farebbe sembrare la pagina rotta.
	 */
	public static function shortcode( $atts ) {
		$a = shortcode_atts( array( 'area' => '' ), $atts, 'documenti' );
		if ( '' === $a['area'] ) { return ''; }

		$docs = self::per_area( $a['area'] );
		$css  = self::css();

		if ( ! $docs ) {
			return $css . '<p class="cp-doc-attesa"><em>documenti in corso di pubblicazione</em></p>';
		}

		$out = $css . '<div class="cp-doc-lista">';
		foreach ( $docs as $d ) {
			$tipo = strtoupper( self::estensione( $d['url'] ) );
			$meta = $tipo . ' &middot; ' . size_format( $d['peso'] );
			if ( ! empty( $d['data'] ) ) {
				$meta .= ' &middot; aggiornato il ' . date_i18n( 'j F Y', strtotime( get_date_from_gmt( $d['data'] ) ) );
			}
			$out .= '<div class="cp-doc">'
				. '<a class="btn btn-granata" href="' . esc_url( $d['link'] ) . '" target="_blank" rel="noopener">'
				. esc_html( $d['label'] ) . '</a>'
				. '<span class="cp-doc__meta">' . $meta . '</span>'
				. '</div>';
		}
		return $out . '</div>';
	}

	/**
	 * Poche righe di stile, stampate una volta sola e solo se lo shortcode c'e'.
	 *
	 * Stanno qui e non nel foglio del tema per non far dipendere questo plugin da
	 * un rilascio del tema: i bottoni riusano .btn e .btn-granata, che il tema
	 * gia' definisce, e qui resta solo la disposizione.
	 */
	private static function css() {
		static $fatto = false;
		if ( $fatto ) { return ''; }
		$fatto = true;
		return '<style>'
			. '.cp-doc-lista{display:flex;flex-direction:column;gap:14px;margin:26px 0}'
			. '.cp-doc{display:flex;align-items:center;gap:14px;flex-wrap:wrap}'
			. '.cp-doc .btn{font-size:1rem;padding:11px 24px}'
			. '.cp-doc__meta{color:#6b6b6b;font-size:.9rem}'
			. '</style>';
	}
}

add_action( 'plugins_loaded', array( 'CP_Documenti', 'init' ) );

if ( ! function_exists( 'cp_get_documento' ) ) {
	/** Un documento (url, nome, peso, data, link) o null se non disponibile. */
	function cp_get_documento( $chiave ) {
		return CP_Documenti::get( $chiave );
	}
}

if ( ! function_exists( 'cp_get_documenti' ) ) {
	/** I documenti disponibili di un'area, nell'ordine dichiarato. */
	function cp_get_documenti( $area ) {
		return CP_Documenti::per_area( $area );
	}
}
