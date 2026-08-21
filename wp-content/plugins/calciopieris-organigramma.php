<?php
/**
 * Plugin Name: Calcio Pieris – Organigramma
 * Description: Gestisce l'organigramma della società (cariche sociali, consiglio direttivo, ecc.) da un pannello admin, con sezioni e righe ruolo/nome aggiungibili e rimovibili. Espone lo shortcode [pieris_organigramma].
 * Version: 1.1
 * Author: A.S.D. Calcio Pieris 1925
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CP_Organigramma {

	const OPT = 'cp_org';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_cporg_save', array( __CLASS__, 'handle_save' ) );
		add_shortcode( 'pieris_organigramma', array( __CLASS__, 'shortcode' ) );
	}

	/* ---------- Testi fissi (non modificabili da admin) ---------- */
	public static function intro_text() {
		return "La struttura dirigenziale dell'A.S.D. Calcio Pieris 1925 è composta da persone che dedicano tempo e passione alla società, con professionalità e spirito di famiglia.";
	}
	public static function note_text() {
		return 'Lo staff tecnico e i dirigenti accompagnatori delle squadre vengono definiti a inizio stagione e comunicati sui nostri canali ufficiali.';
	}

	/* ---------- Dati ---------- */

	/** Stato iniziale (creato all'attivazione). */
	public static function defaults() {
		return array(
			'intro'    => "La struttura dirigenziale dell'A.S.D. Calcio Pieris 1925 è composta da persone che dedicano tempo e passione alla società, con professionalità e spirito di famiglia.",
			'sections' => array(
				array(
					'title'   => 'Cariche sociali',
					'members' => array(
						array( 'role' => 'Presidente',      'name' => 'Massimo Wisniewski' ),
						array( 'role' => 'Vice-presidente', 'name' => 'Carica vacante' ),
						array( 'role' => 'Segretario',      'name' => 'Ylenia Tomasi' ),
						array( 'role' => 'Tesoriere',       'name' => 'Andrea De Campo' ),
					),
				),
				array(
					'title'   => 'Consiglio direttivo',
					'members' => array(
						array( 'role' => 'Consigliere', 'name' => 'Luisa Rodà' ),
						array( 'role' => 'Consigliere', 'name' => 'Gabriele Casula' ),
					),
				),
			),
			'note'     => 'Lo staff tecnico e i dirigenti accompagnatori delle squadre vengono definiti a inizio stagione e comunicati sui nostri canali ufficiali.',
		);
	}

	public static function get_data() {
		$d = get_option( self::OPT, null );
		if ( ! is_array( $d ) ) { $d = self::defaults(); }
		if ( ! isset( $d['intro'] ) )    { $d['intro'] = ''; }
		if ( ! isset( $d['note'] ) )     { $d['note'] = ''; }
		if ( ! isset( $d['sections'] ) || ! is_array( $d['sections'] ) ) { $d['sections'] = array(); }
		return $d;
	}

	public static function activate() {
		if ( null === get_option( self::OPT, null ) ) {
			add_option( self::OPT, self::defaults() );
		}
	}

	/* ---------- Admin ---------- */

	public static function menu() {
		add_menu_page(
			'Organigramma',
			'Organigramma',
			'manage_options',
			'cp-organigramma',
			array( __CLASS__, 'page' ),
			'dashicons-groups',
			26
		);
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non autorizzato.' ); }
		check_admin_referer( 'cporg_save' );

		$out = array( 'sections' => array() );

		if ( isset( $_POST['sections'] ) && is_array( $_POST['sections'] ) ) {
			foreach ( wp_unslash( $_POST['sections'] ) as $sec ) {
				$title   = isset( $sec['title'] ) ? sanitize_text_field( $sec['title'] ) : '';
				$members = array();
				if ( isset( $sec['members'] ) && is_array( $sec['members'] ) ) {
					foreach ( $sec['members'] as $m ) {
						$role = isset( $m['role'] ) ? sanitize_text_field( $m['role'] ) : '';
						$name = isset( $m['name'] ) ? sanitize_text_field( $m['name'] ) : '';
						if ( '' === $role && '' === $name ) { continue; }
						$members[] = array( 'role' => $role, 'name' => $name );
					}
				}
				if ( '' === $title && empty( $members ) ) { continue; }
				$out['sections'][] = array( 'title' => $title, 'members' => $members );
			}
		}

		update_option( self::OPT, $out );
		wp_safe_redirect( add_query_arg( array( 'page' => 'cp-organigramma', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$d = self::get_data();
		?>
		<div class="wrap">
			<h1>Organigramma</h1>
			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Organigramma salvato.</p></div>
			<?php endif; ?>

			<p>Modifica le sezioni e le righe (ruolo / nome). Le modifiche appaiono nella pagina che usa lo shortcode <code>[pieris_organigramma]</code>. Il testo introduttivo e la nota finale sono fissi.</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cporg_save">
				<?php wp_nonce_field( 'cporg_save' ); ?>

				<h2>Sezioni</h2>
				<div id="cporg-sections">
					<?php foreach ( $d['sections'] as $si => $sec ) : self::render_section( $si, $sec ); endforeach; ?>
				</div>
				<p><button type="button" class="button" id="cporg-add-section">+ Aggiungi sezione</button></p>

				<?php submit_button( 'Salva organigramma' ); ?>
			</form>
		</div>

		<!-- Template sezione (per JS) -->
		<template id="cporg-tpl-section">
			<?php self::render_section( '__S__', array( 'title' => '', 'members' => array( array( 'role' => '', 'name' => '' ) ) ) ); ?>
		</template>
		<!-- Template riga membro (per JS) -->
		<template id="cporg-tpl-member">
			<?php self::render_member( '__S__', '__M__', array( 'role' => '', 'name' => '' ) ); ?>
		</template>

		<style>
			.cporg-section{border:1px solid #ccd0d4;background:#fff;padding:12px 16px;margin:0 0 16px;border-radius:6px}
			.cporg-section-head{display:flex;gap:10px;align-items:center;margin-bottom:10px}
			.cporg-section-head input{flex:1;font-weight:600}
			.cporg-members{width:100%;border-collapse:collapse}
			.cporg-members th{text-align:left;padding:4px 6px;font-size:12px;color:#555}
			.cporg-members td{padding:4px 6px}
			.cporg-members input{width:100%}
			.cporg-del{color:#b32d2e;cursor:pointer;border:none;background:none;font-size:18px;line-height:1}
		</style>
		<script>
		(function(){
			var sectionsWrap = document.getElementById('cporg-sections');
			var tplSection = document.getElementById('cporg-tpl-section');
			var tplMember  = document.getElementById('cporg-tpl-member');

			function uid(){ return Date.now().toString(36) + Math.floor(Math.random()*1e4).toString(36); }

			// Rinomina i placeholder __S__/__M__ negli attributi name di un frammento
			function reindex(frag, sid, mid){
				var els = frag.querySelectorAll('[name]');
				for (var i=0;i<els.length;i++){
					var n = els[i].getAttribute('name');
					n = n.replace(/__S__/g, sid);
					if (mid !== undefined) n = n.replace(/__M__/g, mid);
					els[i].setAttribute('name', n);
				}
			}

			document.getElementById('cporg-add-section').addEventListener('click', function(){
				var sid = uid();
				var node = tplSection.content.cloneNode(true);
				// prima i membri col loro id, poi la sezione
				var mrows = node.querySelectorAll('.cporg-member');
				for (var i=0;i<mrows.length;i++){ reindex(mrows[i], sid, uid()); }
				reindex(node, sid);
				sectionsWrap.appendChild(node);
			});

			sectionsWrap.addEventListener('click', function(e){
				var t = e.target;
				if (t.classList.contains('cporg-del-section')){
					var sec = t.closest('.cporg-section');
					if (sec) sec.parentNode.removeChild(sec);
				} else if (t.classList.contains('cporg-add-member')){
					var sec = t.closest('.cporg-section');
					var sid = sec.getAttribute('data-sid');
					var tbody = sec.querySelector('.cporg-members tbody');
					var node = tplMember.content.cloneNode(true);
					reindex(node, sid, uid());
					tbody.appendChild(node);
				} else if (t.classList.contains('cporg-del-member')){
					var row = t.closest('.cporg-member');
					if (row) row.parentNode.removeChild(row);
				}
			});
		})();
		</script>
		<?php
	}

	private static function render_section( $si, $sec ) {
		$title   = isset( $sec['title'] ) ? $sec['title'] : '';
		$members = ( isset( $sec['members'] ) && is_array( $sec['members'] ) && $sec['members'] ) ? $sec['members'] : array( array( 'role' => '', 'name' => '' ) );
		?>
		<div class="cporg-section" data-sid="<?php echo esc_attr( $si ); ?>">
			<div class="cporg-section-head">
				<input type="text" name="sections[<?php echo esc_attr( $si ); ?>][title]" value="<?php echo esc_attr( $title ); ?>" placeholder="Titolo sezione (es. Cariche sociali)">
				<button type="button" class="button cporg-del-section" title="Elimina sezione">Elimina sezione</button>
			</div>
			<table class="cporg-members">
				<thead><tr><th style="width:45%">Ruolo</th><th style="width:45%">Nome</th><th></th></tr></thead>
				<tbody>
					<?php foreach ( $members as $mi => $m ) : self::render_member( $si, $mi, $m ); endforeach; ?>
				</tbody>
			</table>
			<p><button type="button" class="button button-small cporg-add-member">+ Aggiungi riga</button></p>
		</div>
		<?php
	}

	private static function render_member( $si, $mi, $m ) {
		$role = isset( $m['role'] ) ? $m['role'] : '';
		$name = isset( $m['name'] ) ? $m['name'] : '';
		?>
		<tr class="cporg-member">
			<td><input type="text" name="sections[<?php echo esc_attr( $si ); ?>][members][<?php echo esc_attr( $mi ); ?>][role]" value="<?php echo esc_attr( $role ); ?>" placeholder="Ruolo"></td>
			<td><input type="text" name="sections[<?php echo esc_attr( $si ); ?>][members][<?php echo esc_attr( $mi ); ?>][name]" value="<?php echo esc_attr( $name ); ?>" placeholder="Nome"></td>
			<td><button type="button" class="cporg-del cporg-del-member" title="Rimuovi">&times;</button></td>
		</tr>
		<?php
	}

	/* ---------- Front-end ---------- */

	public static function shortcode( $atts ) {
		$d = self::get_data();
		ob_start();

		echo '<p>' . esc_html( self::intro_text() ) . '</p>';

		foreach ( $d['sections'] as $sec ) {
			if ( empty( $sec['members'] ) && '' === trim( $sec['title'] ) ) { continue; }
			if ( '' !== trim( $sec['title'] ) ) {
				echo '<h2>' . esc_html( $sec['title'] ) . '</h2>';
			}
			echo '<table><thead><tr><th>Ruolo</th><th>Nome</th></tr></thead><tbody>';
			foreach ( $sec['members'] as $m ) {
				echo '<tr><td>' . esc_html( $m['role'] ) . '</td><td>' . esc_html( $m['name'] ) . '</td></tr>';
			}
			echo '</tbody></table>';
		}

		echo '<h2>Staff tecnico</h2>';
		echo '<p>' . esc_html( self::note_text() ) . '</p>';
		echo '<p class="cporg-staff-links">Consulta lo staff tecnico di ciascuna area: '
			. '<a href="' . esc_url( home_url( '/prima-squadra/#staff-tecnico' ) ) . '">Prima Squadra</a> &middot; '
			. '<a href="' . esc_url( home_url( '/settore-giovanile/#staff-tecnico' ) ) . '">Settore Giovanile</a>.</p>';

		return ob_get_clean();
	}
}

register_activation_hook( __FILE__, array( 'CP_Organigramma', 'activate' ) );
add_action( 'plugins_loaded', array( 'CP_Organigramma', 'init' ) );
