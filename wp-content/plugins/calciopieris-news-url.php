<?php
/**
 * Plugin Name: Calcio Pieris – News da Facebook (Embed)
 * Description: Incolla il codice iframe di un post Facebook (post → ⋯ → Incorpora → iFrame): crea e pubblica subito una news che mostra il post così com'è, tramite l'iframe ufficiale (nessun token, nessuno script SDK). Include l'elenco delle news create con pulsante Rimuovi. Lo shortcode interno [pieris_fb_embed src="..."] viene scritto dal pannello nel contenuto della news.
 * Version: 3.4
 * Author: A.S.D. Calcio Pieris 1925
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CP_News_Embed {

	const META_SRC  = '_cpurl_src'; // URL/riferimento del post (deduplica)
	const META_FLAG = '_cpemb';     // segna le news create da questo plugin

	/** Diventa true quando in pagina c'e' almeno un embed: gli asset si stampano solo allora. */
	private static $needs_assets = false;

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_shortcode( 'pieris_fb_embed', array( __CLASS__, 'embed_shortcode' ) );
		add_action( 'wp_footer', array( __CLASS__, 'footer_assets' ) );
	}

	/* ---------------- Embed (iframe ufficiale Facebook) ---------------- */

	/**
	 * [pieris_fb_embed src="https://www.facebook.com/plugins/post.php?..." width="500" height="591"]
	 *
	 * Accetta soltanto l'indirizzo dell'iframe ufficiale di Facebook: e' il pannello
	 * "News da Facebook" a comporre lo shortcode e a scriverlo nel contenuto della news.
	 */
	public static function embed_shortcode( $atts ) {
		$a = shortcode_atts( array( 'src' => '', 'width' => '500', 'height' => '' ), $atts, 'pieris_fb_embed' );
		$w = preg_replace( '/[^0-9]/', '', (string) $a['width'] );
		if ( '' === $w ) { $w = '500'; }
		$src = trim( $a['src'] );
		if ( '' === $src ) { return ''; }
		$h = preg_replace( '/[^0-9]/', '', (string) $a['height'] );
		if ( '' === $h ) { $h = '591'; }
		self::$needs_assets = true;
		return '<div class="cp-fb-embed" data-w="' . esc_attr( $w ) . '" data-h="' . esc_attr( $h ) . '" style="max-width:' . esc_attr( $w ) . 'px;margin:16px auto">'
			. '<iframe src="' . esc_url( $src ) . '" width="' . esc_attr( $w ) . '" height="' . esc_attr( $h ) . '" '
			. 'style="border:none;overflow:hidden;max-width:100%" scrolling="no" frameborder="0" '
			. 'allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" loading="lazy"></iframe>'
			. '</div>';
	}

	/**
	 * CSS e JS per adattare l'embed alla larghezza disponibile.
	 *
	 * L'iframe conserva le dimensioni native (Facebook impagina il post a quella
	 * larghezza) e viene rimpicciolito con transform: scale(); il contenitore assume
	 * l'ingombro scalato, cosi' la pagina non scorre in orizzontale. Senza JavaScript
	 * resta il max-width:100% dell'iframe come ripiego.
	 */
	public static function footer_assets() {
		if ( ! self::$needs_assets ) { return; }
		?>
<style id="cp-fb-embed-fit">
.cp-fb-embed{overflow:hidden}
.cp-fb-embed iframe{display:block;border:0}
</style>
<script>
(function () {
	'use strict';

	/* Adatta un singolo embed allo spazio realmente disponibile. */
	function adatta(box) {
		var fr = box.querySelector('iframe');
		if (!fr) { return; }

		var w = parseInt(box.getAttribute('data-w'), 10) || 500;
		var h = parseInt(box.getAttribute('data-h'), 10) || 591;

		// misuro lo spazio disponibile senza contare l'ingombro imposto da noi
		var impostata = box.style.width;
		box.style.width = '';
		var avail = box.clientWidth;

		// slide del carosello nascosta (display:none): rimando, ci pensera'
		// il ResizeObserver quando tornera' visibile
		if (!avail) { box.style.width = impostata; return; }

		// gia' adattato a questa larghezza: evito lavoro inutile e rimbalzi dell'observer
		if (box.getAttribute('data-fit') === String(avail)) { box.style.width = impostata; return; }
		box.setAttribute('data-fit', String(avail));

		var s = (avail < w) ? avail / w : 1;
		fr.style.width = w + 'px';
		fr.style.height = h + 'px';
		fr.style.maxWidth = 'none';
		fr.style.transformOrigin = '0 0';
		fr.style.transform = (s < 1) ? 'scale(' + s + ')' : 'none';
		box.style.width = Math.floor(w * s) + 'px';
		box.style.height = Math.ceil(h * s) + 'px';
	}

	/* Il contenitore della slide cambia dimensione quando il carosello la mostra:
	   e' il momento giusto per ricalcolare. */
	var osservatore = ('ResizeObserver' in window) ? new ResizeObserver(function (voci) {
		for (var i = 0; i < voci.length; i++) {
			var box = voci[i].target.querySelector('.cp-fb-embed');
			if (box) { adatta(box); }
		}
	}) : null;

	function fit() {
		var boxes = document.querySelectorAll('.cp-fb-embed');
		for (var i = 0; i < boxes.length; i++) {
			var box = boxes[i];
			adatta(box);
			if (osservatore && box.parentNode && box.getAttribute('data-obs') !== '1') {
				box.setAttribute('data-obs', '1');
				osservatore.observe(box.parentNode);
			}
		}
	}

	/* Al ridimensionamento della finestra le larghezze cambiano per tutti:
	   azzero la memoria cosi' vengono ricalcolati. */
	function ricalcola() {
		var boxes = document.querySelectorAll('.cp-fb-embed');
		for (var i = 0; i < boxes.length; i++) { boxes[i].removeAttribute('data-fit'); }
		fit();
	}

	var t;
	function programma() { clearTimeout(t); t = setTimeout(ricalcola, 120); }

	if (document.readyState !== 'loading') { fit(); }
	else { document.addEventListener('DOMContentLoaded', fit); }
	window.addEventListener('load', fit);
	window.addEventListener('resize', programma);
	window.addEventListener('orientationchange', programma);
})();
</script>
		<?php
	}

	/* ---------------- Utility ---------------- */

	/** Estrae src, width, height e l'href originale dal codice iframe incollato. */
	private static function parse_iframe( $code ) {
		$out = array( 'src' => '', 'width' => '500', 'height' => '591', 'href' => '' );
		$code = html_entity_decode( $code, ENT_QUOTES, 'UTF-8' );
		if ( preg_match( '#<iframe[^>]*\ssrc=["\']([^"\']+)["\']#i', $code, $m ) ) {
			$out['src'] = trim( $m[1] );
		}
		if ( preg_match( '#\swidth=["\']?(\d+)#i', $code, $m ) )  { $out['width']  = $m[1]; }
		if ( preg_match( '#\sheight=["\']?(\d+)#i', $code, $m ) ) { $out['height'] = $m[1]; }
		// href originale del post (dentro il parametro href= del src)
		if ( $out['src'] && preg_match( '#[?&]href=([^&]+)#i', $out['src'], $m ) ) {
			$out['href'] = urldecode( $m[1] );
		}
		return $out;
	}

	private static function existing_by_src( $ref ) {
		if ( '' === $ref ) { return 0; }
		$q = get_posts( array(
			'post_type' => 'post', 'post_status' => 'any', 'numberposts' => 1,
			'fields' => 'ids', 'meta_key' => self::META_SRC, 'meta_value' => $ref,
		) );
		return $q ? (int) $q[0] : 0;
	}

	/* ---------------- Admin ---------------- */

	public static function menu() {
		add_menu_page(
			'News da Facebook', 'News da Facebook', 'edit_posts',
			'cp-news-embed', array( __CLASS__, 'page' ), 'dashicons-facebook', 27
		);
	}

	public static function page() {
		if ( ! current_user_can( 'edit_posts' ) ) { return; }
		$notice = '';

		// --- RIMUOVI (eliminazione definitiva) ---
		if ( isset( $_POST['cp_action'] ) && 'delete' === $_POST['cp_action'] && check_admin_referer( 'cpemb_delete' ) ) {
			$id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
			if ( $id && get_post( $id ) ) {
				wp_delete_post( $id, true ); // true = elimina definitivamente (bypassa il cestino)
				$notice = '<div class="notice notice-success"><p>News eliminata definitivamente.</p></div>';
			}
		}

		// --- CREA (pubblica subito) ---
		if ( isset( $_POST['cp_action'] ) && 'create' === $_POST['cp_action'] && check_admin_referer( 'cpemb_create' ) ) {
			$code  = isset( $_POST['iframe'] ) ? wp_unslash( $_POST['iframe'] ) : '';
			$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			$date  = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
			$p = self::parse_iframe( $code );

			if ( '' === $p['src'] || false === strpos( $p['src'], 'facebook.com/plugins/post' ) ) {
				$notice = '<div class="notice notice-error"><p>Non ho trovato un iframe valido di Facebook. Incolla il codice della scheda <strong>iFrame</strong> (deve contenere <code>facebook.com/plugins/post.php</code>).</p></div>';
			} else {
				$ref = $p['href'] ? $p['href'] : $p['src'];
				$dup = self::existing_by_src( $ref );
				if ( $dup ) {
					$notice = '<div class="notice notice-warning"><p>Esiste già una news con questo post: <a href="' . esc_url( get_edit_post_link( $dup ) ) . '">modificala</a>. Nessun duplicato creato.</p></div>';
				} else {
					// Titolo lasciato vuoto di default (nessuna generazione automatica).
					$body = '[pieris_fb_embed src="' . esc_url( $p['src'] ) . '" width="' . esc_attr( $p['width'] ) . '" height="' . esc_attr( $p['height'] ) . '"]';
					$postarr = array(
						'post_type' => 'post', 'post_status' => 'publish',
						'post_title' => $title, 'post_content' => $body,
					);
					if ( '' !== $date ) { $postarr['post_date'] = $date; }
					$pid = wp_insert_post( $postarr, true );
					if ( is_wp_error( $pid ) ) {
						$notice = '<div class="notice notice-error"><p>Errore: ' . esc_html( $pid->get_error_message() ) . '</p></div>';
					} else {
						update_post_meta( $pid, self::META_SRC, $ref );
						update_post_meta( $pid, self::META_FLAG, '1' );
						$notice = '<div class="notice notice-success"><p>News pubblicata. '
							. '<a href="' . esc_url( get_permalink( $pid ) ) . '" target="_blank">Vedi</a> &middot; '
							. '<a href="' . esc_url( get_edit_post_link( $pid ) ) . '">Modifica</a></p></div>';
					}
				}
			}
		}

		$items = get_posts( array(
			'post_type' => 'post', 'post_status' => array( 'publish', 'draft', 'future', 'pending', 'private' ),
			'numberposts' => 300, 'orderby' => 'date', 'order' => 'DESC',
		) );
		?>
		<div class="wrap">
			<h1>News da Facebook</h1>
			<?php echo $notice; // già sanificato ?>

			<p>Su Facebook apri il post &rarr; menù <strong>&#8943;</strong> in alto a destra &rarr; <strong>Incorpora</strong> &rarr; scheda <strong>iFrame</strong> &rarr; <strong>Copia codice</strong>. Poi incollalo qui sotto: la news viene <strong>pubblicata subito</strong>.</p>

			<form method="post">
				<?php wp_nonce_field( 'cpemb_create' ); ?>
				<input type="hidden" name="cp_action" value="create">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="cpemb_iframe">Codice iframe di Facebook</label></th>
						<td>
							<textarea id="cpemb_iframe" name="iframe" rows="4" class="large-text" required placeholder='<iframe src="https://www.facebook.com/plugins/post.php?href=..." width="500" height="591" ...></iframe>'></textarea>
							<p class="description">Incolla l'intero tag <code>&lt;iframe ...&gt;&lt;/iframe&gt;</code> che ti fornisce Facebook.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cpemb_title">Titolo della news</label></th>
						<td><input type="text" id="cpemb_title" name="title" class="large-text" placeholder="(facoltativo — lasciando vuoto la news non avrà titolo)"></td>
					</tr>
					<tr>
						<th scope="row"><label for="cpemb_date">Data</label></th>
						<td><input type="text" id="cpemb_date" name="date" class="regular-text" placeholder="AAAA-MM-GG HH:MM:SS (vuoto = adesso)"></td>
					</tr>
				</table>
				<?php submit_button( 'Pubblica news' ); ?>
			</form>

			<hr>
			<h2>Tutte le news</h2>
			<p class="description">Elenco di tutte le news del sito (anche quelle vecchie). Puoi eliminarle da qui.</p>
			<?php if ( empty( $items ) ) : ?>
				<p>Nessuna news presente.</p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:960px">
					<thead><tr><th>Titolo</th><th>Tipo</th><th>Data</th><th>Stato</th><th style="width:200px">Azioni</th></tr></thead>
					<tbody>
					<?php foreach ( $items as $it ) :
						$it_embed = ( '1' === get_post_meta( $it->ID, self::META_FLAG, true ) ) || has_shortcode( $it->post_content, 'pieris_fb_embed' );
					?>
						<tr>
							<td><strong><?php $t = get_the_title( $it ); echo '' === trim( $t ) ? '<em style="color:#888">(senza titolo)</em>' : esc_html( $t ); ?></strong></td>
							<td><?php echo $it_embed ? 'Embed FB' : 'Testo'; ?></td>
							<td><?php echo esc_html( get_the_date( 'j M Y H:i', $it ) ); ?></td>
							<td><?php echo esc_html( $it->post_status ); ?></td>
							<td>
								<a class="button button-small" href="<?php echo esc_url( get_permalink( $it ) ); ?>" target="_blank">Vedi</a>
								<a class="button button-small" href="<?php echo esc_url( get_edit_post_link( $it->ID ) ); ?>">Modifica</a>
								<form method="post" style="display:inline" onsubmit="return confirm('Eliminare definitivamente questa news? L\'operazione non è reversibile.');">
									<?php wp_nonce_field( 'cpemb_delete' ); ?>
									<input type="hidden" name="cp_action" value="delete">
									<input type="hidden" name="post_id" value="<?php echo intval( $it->ID ); ?>">
									<button type="submit" class="button button-small" style="color:#b32d2e">Elimina</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<hr>
			<h2>Note</h2>
			<ul style="list-style:disc;margin-left:20px">
				<li>Il post deve essere <strong>pubblico</strong>, altrimenti Facebook non lo mostra.</li>
				<li>Chi usa un <strong>ad-blocker</strong> che blocca Facebook potrebbe non vedere l'embed.</li>
				<li>Se il post viene <strong>eliminato</strong> su Facebook, l'embed sparisce anche dal sito.</li>
			</ul>
		</div>
		<?php
	}
}

add_action( 'plugins_loaded', array( 'CP_News_Embed', 'init' ) );
