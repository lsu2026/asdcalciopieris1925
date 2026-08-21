<?php
/**
 * Plugin Name: Calcio Pieris – Staff Tecnico
 * Description: Gestisce lo staff tecnico (ruolo + nome) separatamente per Prima Squadra e Settore Giovanile, con righe aggiungibili/rimovibili e ordinabili via trascinamento. I dati alimentano le pagine tramite cp_get_staff().
 * Version: 1.0
 * Author: A.S.D. Calcio Pieris 1925
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CP_Staff {

	const OPT = 'cp_staff';

	public static function groups() {
		return array( 'prima' => 'Prima Squadra', 'giovanile' => 'Settore Giovanile' );
	}

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_cpstaff_save', array( __CLASS__, 'handle_save' ) );
	}

	/** Righe di default (uguali per i due gruppi finché non si modifica). */
	public static function default_rows() {
		return array(
			array( 'role' => 'Responsabile · Tecnico UEFA B',            'name' => 'Massimo Wisniewski' ),
			array( 'role' => 'Tecnico UEFA E · Educatrice',              'name' => 'Luisa Rodà' ),
			array( 'role' => 'Laureato Magistrale in Scienze Motorie',   'name' => 'Francesco Bradaschia' ),
			array( 'role' => 'Tecnico UEFA E',                           'name' => 'Francesca Bibalo' ),
			array( 'role' => 'Tecnico UEFA E',                           'name' => 'Michele Desogus' ),
		);
	}

	public static function get_group( $g ) {
		$all = get_option( self::OPT, null );
		if ( is_array( $all ) && isset( $all[ $g ] ) && is_array( $all[ $g ] ) ) {
			return $all[ $g ];
		}
		return self::default_rows();
	}

	/* ---------------- Admin ---------------- */

	public static function menu() {
		$hook = add_menu_page( 'Staff Tecnico', 'Staff Tecnico', 'manage_options', 'cp-staff', array( __CLASS__, 'page' ), 'dashicons-whistle', 29 );
		add_action( 'admin_print_scripts-' . $hook, function () { wp_enqueue_script( 'jquery-ui-sortable' ); } );
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non autorizzato.' ); }
		check_admin_referer( 'cpstaff_save' );
		$out = array();
		foreach ( array_keys( self::groups() ) as $g ) {
			$out[ $g ] = array();
			if ( isset( $_POST['staff'][ $g ] ) && is_array( $_POST['staff'][ $g ] ) ) {
				foreach ( wp_unslash( $_POST['staff'][ $g ] ) as $row ) {
					$role = isset( $row['role'] ) ? sanitize_text_field( $row['role'] ) : '';
					$name = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';
					if ( '' === $role && '' === $name ) { continue; }
					$out[ $g ][] = array( 'role' => $role, 'name' => $name );
				}
			}
		}
		update_option( self::OPT, $out );
		wp_safe_redirect( add_query_arg( array( 'page' => 'cp-staff', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		?>
		<div class="wrap">
			<h1>Staff Tecnico</h1>
			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Staff salvato.</p></div>
			<?php endif; ?>
			<p>Gestisci lo staff tecnico separatamente per le due sezioni. <strong>Trascina</strong> le righe dall'icona (⋮⋮) per ordinarle.</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cpstaff_save">
				<?php wp_nonce_field( 'cpstaff_save' ); ?>
				<?php foreach ( self::groups() as $g => $label ) : ?>
					<h2 style="margin-top:28px"><?php echo esc_html( $label ); ?></h2>
					<div class="cpstaff-rows" data-group="<?php echo esc_attr( $g ); ?>">
						<?php foreach ( self::get_group( $g ) as $i => $r ) { self::render_row( $g, $i, $r ); } ?>
					</div>
					<p><button type="button" class="button cpstaff-add" data-group="<?php echo esc_attr( $g ); ?>">+ Aggiungi persona</button></p>
				<?php endforeach; ?>
				<?php submit_button( 'Salva staff' ); ?>
			</form>
		</div>

		<template id="cpstaff-tpl"><?php self::render_row( '__G__', '__I__', array( 'role' => '', 'name' => '' ) ); ?></template>

		<style>
			.cpstaff-row{display:flex;gap:12px;align-items:center;background:#fff;border:1px solid #ccd0d4;border-radius:8px;padding:10px 14px;margin:0 0 10px;max-width:820px}
			.cpstaff-drag{cursor:grab;color:#999;font-size:18px;letter-spacing:-4px;user-select:none}
			.cpstaff-row input{flex:1}
			.cpstaff-del{color:#b32d2e;background:none;border:none;cursor:pointer;font-size:13px}
			.cpstaff-placeholder{border:2px dashed #c3c4c7;border-radius:8px;background:#f6f7f7;height:52px;margin:0 0 10px;max-width:820px}
		</style>
		<script>
		(function(){
			var tpl = document.getElementById('cpstaff-tpl');
			function addRow(group){
				var node = tpl.content.cloneNode(true);
				var uid = Date.now().toString(36) + Math.floor(Math.random()*1000).toString(36);
				var els = node.querySelectorAll('[name]');
				for ( var i=0;i<els.length;i++ ){ els[i].setAttribute('name', els[i].getAttribute('name').replace(/__G__/g, group).replace(/__I__/g, uid)); }
				var wrap = document.querySelector('.cpstaff-rows[data-group="'+group+'"]');
				wrap.appendChild(node);
				if ( window.jQuery ) { window.jQuery(wrap).sortable('refresh'); }
			}
			var adds = document.querySelectorAll('.cpstaff-add');
			for ( var i=0;i<adds.length;i++ ){ adds[i].addEventListener('click', function(){ addRow(this.getAttribute('data-group')); }); }
			document.addEventListener('click', function(e){
				if ( e.target.classList.contains('cpstaff-del') ){ var row=e.target.closest('.cpstaff-row'); if(row) row.parentNode.removeChild(row); }
			});
			if ( window.jQuery ) {
				jQuery(function($){ $('.cpstaff-rows').sortable({ handle:'.cpstaff-drag', placeholder:'cpstaff-placeholder', tolerance:'pointer', cursor:'grabbing' }); });
			}
		})();
		</script>
		<?php
	}

	private static function render_row( $g, $i, $r ) {
		$role = isset( $r['role'] ) ? $r['role'] : '';
		$name = isset( $r['name'] ) ? $r['name'] : '';
		?>
		<div class="cpstaff-row">
			<span class="cpstaff-drag" title="Trascina per ordinare">&#8942;&#8942;</span>
			<input type="text" name="staff[<?php echo esc_attr( $g ); ?>][<?php echo esc_attr( $i ); ?>][role]" value="<?php echo esc_attr( $role ); ?>" placeholder="Riferimenti (es. Tecnico UEFA E)">
			<input type="text" name="staff[<?php echo esc_attr( $g ); ?>][<?php echo esc_attr( $i ); ?>][name]" value="<?php echo esc_attr( $name ); ?>" placeholder="Nome">
			<button type="button" class="cpstaff-del" title="Rimuovi">&times;</button>
		</div>
		<?php
	}
}

add_action( 'plugins_loaded', array( 'CP_Staff', 'init' ) );

if ( ! function_exists( 'cp_get_staff' ) ) {
	/** Righe staff (role, name) per il gruppo 'prima' o 'giovanile'. */
	function cp_get_staff( $group ) {
		return CP_Staff::get_group( $group );
	}
}
