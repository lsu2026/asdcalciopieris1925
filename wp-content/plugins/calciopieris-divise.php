<?php
/**
 * Plugin Name: Calcio Pieris – Maglie
 * Description: Gestisce le foto delle tre maglie della prima squadra (prima, seconda, portiere) da un pannello admin, con upload dalla Libreria media. Le immagini alimentano la sezione "Le maglie della prima squadra" della pagina Store tramite la funzione cp_get_kits().
 * Version: 1.2
 * Author: A.S.D. Calcio Pieris 1925
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CP_Maglie {

	const OPT = 'cp_kits';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_cpdv_save', array( __CLASS__, 'handle_save' ) );
	}

	/**
	 * Le tre maglie gestite, con i testi di default del tema.
	 * Gli slug coincidono con i nomi dei file usati come ripiego in assets/kit/.
	 */
	public static function slots() {
		return array(
			'prima'    => array( 'name' => 'Prima maglia',    'desc' => 'La maglia di casa, nei colori granata del club.' ),
			'seconda'  => array( 'name' => 'Seconda maglia',  'desc' => 'La maglia utilizzata nelle gare in trasferta.' ),
			'portiere' => array( 'name' => 'Maglia portiere', 'desc' => 'La maglia di gara del portiere.' ),
		);
	}

	/**
	 * Elenco completo delle maglie: testi di default sovrascritti da quanto salvato.
	 * Ogni voce ha slug, name, desc, image (URL o stringa vuota) e image_id.
	 */
	public static function get_list() {
		$saved = get_option( self::OPT, array() );
		if ( ! is_array( $saved ) ) { $saved = array(); }

		$out = array();
		foreach ( self::slots() as $slug => $def ) {
			$row = isset( $saved[ $slug ] ) && is_array( $saved[ $slug ] ) ? $saved[ $slug ] : array();
			$desc = isset( $row['desc'] ) && '' !== trim( $row['desc'] ) ? $row['desc'] : $def['desc'];
			$out[] = array(
				'slug'     => $slug,
				'name'     => $def['name'],
				'desc'     => $desc,
				// in archivio la foto e' un percorso relativo: qui torna URL assoluto
				'image'    => isset( $row['image'] ) && function_exists( 'cp_asset_url' )
					? cp_asset_url( $row['image'] )
					: ( isset( $row['image'] ) ? $row['image'] : '' ),
				'image_id' => isset( $row['image_id'] ) ? intval( $row['image_id'] ) : 0,
			);
		}
		return $out;
	}

	/* ---------------- Admin ---------------- */

	public static function menu() {
		$hook = add_menu_page( 'Maglie', 'Maglie', 'manage_options', 'cp-maglie', array( __CLASS__, 'page' ), 'dashicons-images-alt2', 29 );
		add_action( 'admin_print_scripts-' . $hook, function () { wp_enqueue_media(); } );
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non autorizzato.' ); }
		check_admin_referer( 'cpdv_save' );

		$in  = isset( $_POST['kit'] ) ? wp_unslash( $_POST['kit'] ) : array();
		$out = array();
		foreach ( array_keys( self::slots() ) as $slug ) {
			$row = isset( $in[ $slug ] ) && is_array( $in[ $slug ] ) ? $in[ $slug ] : array();
			$out[ $slug ] = array(
				// salvo relativo a wp-content, cosi' il dato non dipende dal dominio
				'image'    => isset( $row['image'] ) && function_exists( 'cp_asset_relative' )
					? cp_asset_relative( esc_url_raw( $row['image'] ) )
					: ( isset( $row['image'] ) ? esc_url_raw( $row['image'] ) : '' ),
				'image_id' => isset( $row['image_id'] ) ? intval( $row['image_id'] ) : 0,
				'desc'     => isset( $row['desc'] ) ? sanitize_text_field( $row['desc'] ) : '',
			);
		}
		update_option( self::OPT, $out );
		wp_safe_redirect( add_query_arg( array( 'page' => 'cp-maglie', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$list  = self::get_list();
		$store = get_page_by_path( 'store' );
		?>
		<div class="wrap">
			<h1>Maglie</h1>
			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Maglie salvate.</p></div>
			<?php endif; ?>
			<p>
				Carica le foto delle tre maglie della prima squadra. Compaiono nella sezione <strong>&ldquo;Le maglie della prima squadra&rdquo;</strong> della
				<?php if ( $store ) : ?>
					<a href="<?php echo esc_url( get_permalink( $store->ID ) ); ?>" target="_blank">pagina Store</a>.
				<?php else : ?>
					pagina Store.
				<?php endif; ?>
				Finch&eacute; una foto non viene caricata, al suo posto resta un segnaposto.
				Le schede usano un ritaglio <strong>verticale 3:4</strong>: sono consigliate immagini in formato verticale
				(es. 900&times;1200 px) con la maglia centrata. La descrizione &egrave; facoltativa: se la lasci vuota viene usato il testo predefinito.
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cpdv_save">
				<?php wp_nonce_field( 'cpdv_save' ); ?>

				<div id="cpdv-rows">
					<?php foreach ( $list as $kit ) { self::render_card( $kit ); } ?>
				</div>

				<?php submit_button( 'Salva maglie' ); ?>
			</form>
		</div>

		<style>
			#cpdv-rows{display:flex;gap:20px;flex-wrap:wrap;margin-top:18px}
			.cpdv-card{background:#fff;border:1px solid #ccd0d4;border-radius:8px;padding:16px;width:260px}
			.cpdv-card h2{font-size:15px;margin:0 0 12px;padding:0}
			.cpdv-media{position:relative;aspect-ratio:3/4;background:#f6f7f7;border:1px dashed #c3c4c7;border-radius:6px;overflow:hidden;display:flex;align-items:center;justify-content:center;margin-bottom:10px}
			.cpdv-media img{width:100%;height:100%;object-fit:cover;display:block}
			.cpdv-noimg{color:#999;font-size:12px;text-align:center;padding:10px}
			.cpdv-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px}
			.cpdv-clear{color:#b32d2e;background:none;border:none;cursor:pointer;font-size:12px;padding:0}
			.cpdv-card label{display:block;font-size:12px;color:#555;margin-bottom:4px}
			.cpdv-card input[type=text]{width:100%}
		</style>
		<script>
		(function(){
			var rows = document.getElementById('cpdv-rows');
			var frame = null;

			rows.addEventListener('click', function(e){
				var t = e.target;
				var card = t.closest ? t.closest('.cpdv-card') : null;
				if ( ! card ) { return; }

				if ( t.classList.contains('cpdv-pick') ){
					e.preventDefault();
					frame = wp.media({ title:'Seleziona la foto della maglia', button:{ text:'Usa questa foto' }, library:{ type:'image' }, multiple:false });
					frame.on('select', function(){
						var att = frame.state().get('selection').first().toJSON();
						card.querySelector('.cpdv-url').value = att.url;
						card.querySelector('.cpdv-id').value  = att.id;
						var img = card.querySelector('.cpdv-preview');
						img.src = att.url; img.style.display = 'block';
						var no = card.querySelector('.cpdv-noimg'); if (no) no.style.display = 'none';
					});
					frame.open();
				} else if ( t.classList.contains('cpdv-clear') ){
					e.preventDefault();
					card.querySelector('.cpdv-url').value = '';
					card.querySelector('.cpdv-id').value  = '';
					var img = card.querySelector('.cpdv-preview'); img.src = ''; img.style.display = 'none';
					var no = card.querySelector('.cpdv-noimg'); if (no) no.style.display = 'block';
				}
			});
		})();
		</script>
		<?php
	}

	private static function render_card( $kit ) {
		$has = ( '' !== $kit['image'] );
		$s   = esc_attr( $kit['slug'] );
		?>
		<div class="cpdv-card">
			<h2><?php echo esc_html( $kit['name'] ); ?></h2>

			<div class="cpdv-media">
				<img class="cpdv-preview" src="<?php echo esc_url( $kit['image'] ); ?>" alt="" style="<?php echo $has ? '' : 'display:none'; ?>">
				<div class="cpdv-noimg" style="<?php echo $has ? 'display:none' : ''; ?>">nessuna foto<br>(verr&agrave; mostrato il segnaposto)</div>
			</div>

			<input type="hidden" class="cpdv-url" name="kit[<?php echo $s; ?>][image]" value="<?php echo esc_attr( $kit['image'] ); ?>">
			<input type="hidden" class="cpdv-id" name="kit[<?php echo $s; ?>][image_id]" value="<?php echo esc_attr( $kit['image_id'] ); ?>">

			<div class="cpdv-actions">
				<button type="button" class="button button-small cpdv-pick">Scegli / Carica foto</button>
				<button type="button" class="cpdv-clear">rimuovi</button>
			</div>

			<label for="cpdv-desc-<?php echo $s; ?>">Descrizione (facoltativa)</label>
			<input type="text" id="cpdv-desc-<?php echo $s; ?>" name="kit[<?php echo $s; ?>][desc]" value="<?php echo esc_attr( $kit['desc'] ); ?>">
		</div>
		<?php
	}
}

add_action( 'plugins_loaded', array( 'CP_Maglie', 'init' ) );

if ( ! function_exists( 'cp_get_kits' ) ) {
	/** Le tre maglie per il tema: slug, name, desc, image (URL completo o stringa vuota). */
	function cp_get_kits() {
		return CP_Maglie::get_list();
	}
}
