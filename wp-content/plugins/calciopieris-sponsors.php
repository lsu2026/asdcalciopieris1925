<?php
/**
 * Plugin Name: Calcio Pieris – Sponsor
 * Description: Gestisce l'elenco degli sponsor (nome, link, logo) da un pannello admin, con upload del logo dalla Libreria media. I dati alimentano il marquee della home e la pagina Sponsors tramite la funzione cp_get_sponsors().
 * Version: 1.4
 * Author: A.S.D. Calcio Pieris 1925
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CP_Sponsors {

	const OPT = 'cp_sponsors';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_cpsp_save', array( __CLASS__, 'handle_save' ) );
	}

	/** Sponsor iniziali (URL loghi calcolati dal tema attivo). */
	public static function defaults() {
		$d = get_template_directory_uri() . '/assets/sponsors/';
		return array(
			array( 'name' => 'Medicenter',         'url' => 'https://medicentercliniche.it/',   'logo' => $d . 'medicenter.webp',   'enabled' => 1 ),
			array( 'name' => 'Conit',              'url' => 'https://www.conit.org/it/',        'logo' => $d . 'conit.svg',         'enabled' => 1 ),
			array( 'name' => 'G.S.A. Interiors',   'url' => '',                                 'logo' => '',                       'enabled' => 1 ),
			array( 'name' => 'Ottica Russi',       'url' => 'https://otticarussi.it/',          'logo' => $d . 'ottica-russi.svg',  'enabled' => 1 ),
			array( 'name' => 'H2O Termotecnica',   'url' => 'https://h2otermotecnica.it/',      'logo' => $d . 'h2o.png',           'enabled' => 1 ),
			array( 'name' => 'BCC Venezia Giulia', 'url' => 'https://www.bccveneziagiulia.it/', 'logo' => $d . 'bcc.webp',          'enabled' => 1 ),
			array( 'name' => 'Eye Store',          'url' => 'https://www.eyesportshop.com/it/', 'logo' => $d . 'eyestore.jpg',      'enabled' => 1 ),
		);
	}

	/** Lista completa (per l'admin): opzione salvata o default, con chiave enabled normalizzata. */
	public static function get_list() {
		$l = get_option( self::OPT, null );
		if ( ! is_array( $l ) ) { $l = self::defaults(); }
		foreach ( $l as &$s ) {
			if ( ! isset( $s['enabled'] ) ) { $s['enabled'] = 1; }
			// il logo e' salvato con URL assoluto: lo riporto sull'host corrente,
			// altrimenti da cellulare (IP di LAN) o in produzione non si carica
			// in archivio il logo e' un percorso relativo: qui torna URL assoluto
			if ( ! empty( $s['logo'] ) && function_exists( 'cp_asset_url' ) ) {
				$s['logo'] = cp_asset_url( $s['logo'] );
			}
		}
		unset( $s );
		return $l;
	}

	/** Solo gli sponsor attivi (per il front-end). */
	public static function enabled_list() {
		$out = array();
		foreach ( self::get_list() as $s ) { if ( ! empty( $s['enabled'] ) ) { $out[] = $s; } }
		return $out;
	}

	/* ---------------- Admin ---------------- */

	public static function menu() {
		$hook = add_menu_page( 'Sponsor', 'Sponsor', 'manage_options', 'cp-sponsors', array( __CLASS__, 'page' ), 'dashicons-awards', 28 );
		add_action( 'admin_print_scripts-' . $hook, function () { wp_enqueue_media(); wp_enqueue_script( 'jquery-ui-sortable' ); } );
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non autorizzato.' ); }
		check_admin_referer( 'cpsp_save' );
		$out = array();
		if ( isset( $_POST['sp'] ) && is_array( $_POST['sp'] ) ) {
			foreach ( wp_unslash( $_POST['sp'] ) as $row ) {
				$name = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';
				$url  = isset( $row['url'] ) ? esc_url_raw( $row['url'] ) : '';
				$logo = isset( $row['logo'] ) ? esc_url_raw( $row['logo'] ) : '';
				// salvo relativo a wp-content, cosi' il dato non dipende dal dominio
				if ( function_exists( 'cp_asset_relative' ) ) { $logo = cp_asset_relative( $logo ); }
				$en   = empty( $row['enabled'] ) ? 0 : 1;
				if ( '' === $name && '' === $logo ) { continue; }
				$out[] = array( 'name' => $name, 'url' => $url, 'logo' => $logo, 'enabled' => $en );
			}
		}
		update_option( self::OPT, $out );
		wp_safe_redirect( add_query_arg( array( 'page' => 'cp-sponsors', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$list = self::get_list();
		?>
		<div class="wrap">
			<h1>Sponsor</h1>
			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Sponsor salvati.</p></div>
			<?php endif; ?>
			<p>Aggiungi, modifica o rimuovi gli sponsor. Compaiono nel <strong>marquee della home</strong> e nella <strong>pagina Sponsors</strong>. Il <strong>link</strong> è facoltativo; se non carichi un logo, viene mostrato il nome. <strong>Trascina</strong> le righe dall'icona a sinistra (⋮⋮) per cambiare l'<strong>ordine</strong>; deseleziona <strong>Attivo</strong> per nasconderne uno senza eliminarlo.</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cpsp_save">
				<?php wp_nonce_field( 'cpsp_save' ); ?>

				<div id="cpsp-rows">
					<?php foreach ( $list as $i => $sp ) { self::render_row( $i, $sp ); } ?>
				</div>
				<p><button type="button" class="button" id="cpsp-add">+ Aggiungi sponsor</button></p>
				<?php submit_button( 'Salva sponsor' ); ?>
			</form>
		</div>

		<template id="cpsp-tpl"><?php self::render_row( '__I__', array( 'name' => '', 'url' => '', 'logo' => '' ) ); ?></template>

		<style>
			.cpsp-row{display:flex;gap:16px;align-items:flex-start;background:#fff;border:1px solid #ccd0d4;border-radius:8px;padding:14px 16px;margin:0 0 14px}
			.cpsp-drag{flex:0 0 22px;align-self:center;cursor:grab;color:#999;font-size:20px;line-height:1;letter-spacing:-4px;user-select:none;text-align:center}
			.cpsp-drag:active{cursor:grabbing}
			.cpsp-placeholder{border:2px dashed #c3c4c7;border-radius:8px;background:#f6f7f7;margin:0 0 14px;height:120px}
			.cpsp-logo{width:170px;flex:0 0 170px;text-align:center}
			.cpsp-logo .cpsp-preview{max-width:100%;max-height:80px;object-fit:contain;border:1px solid #eee;border-radius:6px;background:#fff;padding:6px;display:block;margin:0 auto 8px}
			.cpsp-noimg{height:80px;display:flex;align-items:center;justify-content:center;color:#999;border:1px dashed #ccc;border-radius:6px;margin-bottom:8px;font-size:12px}
			.cpsp-fields{flex:1;display:flex;flex-direction:column;gap:8px}
			.cpsp-fields input[type=text],.cpsp-fields input[type=url]{width:100%}
			.cpsp-toggle{font-size:13px;color:#333}
			.cpsp-del{color:#b32d2e;background:none;border:none;cursor:pointer;font-size:13px;align-self:center}
			.cpsp-row.cpsp-off{opacity:.5}
			.cpsp-row.cpsp-off .cpsp-toggle{opacity:1;color:#b32d2e}
		</style>
		<script>
		(function(){
			var rows = document.getElementById('cpsp-rows');
			var tpl  = document.getElementById('cpsp-tpl');
			var frame = null;

			document.getElementById('cpsp-add').addEventListener('click', function(){
				var node = tpl.content.cloneNode(true);
				var uid = Date.now().toString(36) + Math.floor(Math.random()*1000).toString(36);
				var els = node.querySelectorAll('[name]');
				for ( var i=0;i<els.length;i++ ){ els[i].setAttribute('name', els[i].getAttribute('name').replace(/__I__/g, uid)); }
				rows.appendChild(node);
				if ( window.jQuery && window.jQuery('#cpsp-rows').sortable ) { window.jQuery('#cpsp-rows').sortable('refresh'); }
			});

			rows.addEventListener('change', function(e){
				if ( e.target.classList.contains('cpsp-enabled') ){
					var row = e.target.closest('.cpsp-row');
					if ( row ) row.classList.toggle('cpsp-off', ! e.target.checked);
				}
			});

			rows.addEventListener('click', function(e){
				var t = e.target;
				if ( t.classList.contains('cpsp-del') ){
					var row = t.closest('.cpsp-row'); if (row) row.parentNode.removeChild(row);
				} else if ( t.classList.contains('cpsp-pick') ){
					e.preventDefault();
					var row = t.closest('.cpsp-row');
					frame = wp.media({ title:'Seleziona il logo dello sponsor', button:{ text:'Usa questo logo' }, multiple:false });
					frame.on('select', function(){
						var att = frame.state().get('selection').first().toJSON();
						var url = att.url;
						row.querySelector('.cpsp-logo-input').value = url;
						var img = row.querySelector('.cpsp-preview'); img.src = url; img.style.display = 'block';
						var no = row.querySelector('.cpsp-noimg'); if (no) no.style.display = 'none';
					});
					frame.open();
				} else if ( t.classList.contains('cpsp-clear') ){
					e.preventDefault();
					var row = t.closest('.cpsp-row');
					row.querySelector('.cpsp-logo-input').value = '';
					var img = row.querySelector('.cpsp-preview'); img.src = ''; img.style.display = 'none';
					var no = row.querySelector('.cpsp-noimg'); if (no) no.style.display = 'flex';
				}
			});
		})();
		if ( window.jQuery ) {
			jQuery(function($){
				$('#cpsp-rows').sortable({ handle:'.cpsp-drag', placeholder:'cpsp-placeholder', forcePlaceholderSize:true, tolerance:'pointer', cursor:'grabbing', opacity:.9 });
			});
		}
		</script>
		<?php
	}

	private static function render_row( $i, $sp ) {
		$name = isset( $sp['name'] ) ? $sp['name'] : '';
		$url  = isset( $sp['url'] ) ? $sp['url'] : '';
		$logo = isset( $sp['logo'] ) ? $sp['logo'] : '';
		if ( '' !== $logo && function_exists( 'cp_asset_url' ) ) { $logo = cp_asset_url( $logo ); }
		$en   = ! isset( $sp['enabled'] ) || ! empty( $sp['enabled'] );
		$has  = ( '' !== $logo );
		?>
		<div class="cpsp-row<?php echo $en ? '' : ' cpsp-off'; ?>">
			<div class="cpsp-drag" title="Trascina per ordinare">&#8942;&#8942;</div>
			<div class="cpsp-logo">
				<img class="cpsp-preview" src="<?php echo esc_url( $logo ); ?>" alt="" style="<?php echo $has ? '' : 'display:none'; ?>">
				<div class="cpsp-noimg" style="<?php echo $has ? 'display:none' : ''; ?>">nessun logo</div>
				<input type="hidden" class="cpsp-logo-input" name="sp[<?php echo esc_attr( $i ); ?>][logo]" value="<?php echo esc_attr( $logo ); ?>">
				<button type="button" class="button button-small cpsp-pick">Scegli / Carica logo</button>
				<button type="button" class="button-link cpsp-clear" style="margin-top:6px">rimuovi logo</button>
			</div>
			<div class="cpsp-fields">
				<input type="text" name="sp[<?php echo esc_attr( $i ); ?>][name]" value="<?php echo esc_attr( $name ); ?>" placeholder="Nome sponsor">
				<input type="url" name="sp[<?php echo esc_attr( $i ); ?>][url]" value="<?php echo esc_attr( $url ); ?>" placeholder="https://sito-sponsor.it (facoltativo)">
				<label class="cpsp-toggle"><input type="checkbox" class="cpsp-enabled" name="sp[<?php echo esc_attr( $i ); ?>][enabled]" value="1" <?php checked( $en ); ?>> <strong>Attivo</strong> (mostrato sul sito)</label>
			</div>
			<button type="button" class="cpsp-del" title="Elimina sponsor">&times; Elimina</button>
		</div>
		<?php
	}
}

add_action( 'plugins_loaded', array( 'CP_Sponsors', 'init' ) );

if ( ! function_exists( 'cp_get_sponsors' ) ) {
	/** Elenco sponsor ATTIVI per il tema (nome, url, logo=URL completo). */
	function cp_get_sponsors() {
		return CP_Sponsors::enabled_list();
	}
}
