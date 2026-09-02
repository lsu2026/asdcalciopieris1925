<?php
/**
 * Plugin Name: Calcio Pieris – Staff e Safeguarding
 * Description: Gestisce le persone (ruolo + nome) di Prima Squadra, Settore Giovanile e Safeguarding, con righe aggiungibili/rimovibili e ordinabili via trascinamento. I dati alimentano le pagine tramite cp_get_staff() e, per il Safeguarding, lo shortcode [safeguarding].
 * Version: 1.2
 * Author: A.S.D. Calcio Pieris 1925
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CP_Staff {

	const OPT = 'cp_staff';

	/** Opzione della vecchia area separata, letta solo per travasarla. */
	const OPT_VECCHIA = 'cp_safeguarding';

	/**
	 * I tre gruppi, tutti sullo stesso piano.
	 *
	 * Il Safeguarding stava in un'area a parte con un campo solo. Averlo qui
	 * dentro come gli altri due significa un'anagrafica sola, un salvataggio
	 * solo e la possibilita' di elencare piu' persone con ruoli diversi:
	 * il Responsabile non e' per forza uno, e accanto a lui possono esserci
	 * altre figure.
	 */
	public static function groups() {
		return array(
			'prima'        => 'Prima Squadra',
			'giovanile'    => 'Settore Giovanile',
			'safeguarding' => 'Safeguarding',
		);
	}

	/** Nota esplicativa sotto il titolo di ogni gruppo, nel pannello. */
	public static function group_note( $g ) {
		if ( 'safeguarding' === $g ) {
			return 'Le persone indicate qui compaiono nella <strong>pagina Safeguarding</strong>. '
				. 'Usa il campo Riferimenti per il ruolo esatto, ad esempio '
				. '<em>Responsabile contro abusi, violenze e discriminazioni</em>.';
		}
		return '';
	}

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_cpstaff_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'travasa_vecchia_area' ), 20 );

		/* I nomi stanno nel database come dati, la pagina Safeguarding e' testo:
		   lo shortcode e' il ponte fra i due. Cosi' il testo resta modificabile
		   dall'editor e le persone si cambiano dal pannello, senza che chi le
		   aggiorna debba entrare nell'HTML della pagina. */
		add_shortcode( 'safeguarding', array( __CLASS__, 'sc_safeguarding' ) );
	}

	/**
	 * Travaso una tantum dalla vecchia area separata.
	 *
	 * La versione precedente teneva un nome solo nell'opzione cp_safeguarding.
	 * Qui viene spostato nel gruppo 'safeguarding' come prima riga, con il
	 * ruolo per esteso, e la vecchia opzione sparisce. Senza questo passaggio
	 * il nome gia' inserito si perderebbe in silenzio all'aggiornamento, che e'
	 * il modo peggiore di perdere un dato.
	 */
	public static function travasa_vecchia_area() {
		$vecchia = get_option( self::OPT_VECCHIA, null );
		if ( null === $vecchia ) { return; }   // niente da travasare

		if ( is_array( $vecchia ) && ! empty( $vecchia['nome'] ) ) {
			$tutti = get_option( self::OPT, array() );
			if ( ! is_array( $tutti ) ) { $tutti = array(); }
			if ( empty( $tutti['safeguarding'] ) ) {
				$tutti['safeguarding'] = array( array(
					'role' => 'Responsabile contro abusi, violenze e discriminazioni',
					'name' => $vecchia['nome'],
				) );
				update_option( self::OPT, $tutti );
			}
		}
		delete_option( self::OPT_VECCHIA );
	}

	/* ---------------- Safeguarding sul sito ---------------- */

	/**
	 * [safeguarding]  -> l'elenco delle persone, come tabella Ruolo / Nome.
	 *
	 * La forma e' la stessa dell'organigramma e dello staff tecnico: una
	 * tabella semplice dentro .entry-content, che il tema stila gia'. Nessuna
	 * classe nuova e nessun CSS aggiunto, cosi' le tre pagine restano coerenti
	 * anche il giorno che si cambia lo stile delle tabelle.
	 *
	 * Se non e' stato indicato nessuno NON stampa una tabella vuota: la frase
	 * che precede annuncia una nomina, e lasciarla senza seguito farebbe
	 * sembrare la pagina rotta. Dice invece che i nominativi non sono ancora
	 * pubblicati, che e' la cosa vera.
	 */
	public static function sc_safeguarding( $atts ) {
		$a = shortcode_atts( array( 'titolo' => '' ), $atts, 'safeguarding' );

		$righe = array();
		foreach ( self::get_group( 'safeguarding' ) as $r ) {
			$role = isset( $r['role'] ) ? trim( $r['role'] ) : '';
			$name = isset( $r['name'] ) ? trim( $r['name'] ) : '';
			if ( '' === $role && '' === $name ) { continue; }
			$righe[] = array( $role, $name );
		}

		if ( ! $righe ) {
			return '<p><em>nominativi in corso di pubblicazione</em></p>';
		}

		$h = '';
		if ( '' !== $a['titolo'] ) { $h .= '<h2>' . esc_html( $a['titolo'] ) . '</h2>'; }
		$h .= '<table><thead><tr><th>Ruolo</th><th>Nome</th></tr></thead><tbody>';
		foreach ( $righe as $r ) {
			$h .= '<tr><td>' . esc_html( $r[0] ) . '</td><td>' . esc_html( $r[1] ) . '</td></tr>';
		}
		return $h . '</tbody></table>';
	}

	/**
	 * Righe di default, usate finche' un gruppo non viene salvato.
	 *
	 * Valgono SOLO per i due gruppi tecnici. Per il Safeguarding il default e'
	 * vuoto: ereditare l'elenco degli allenatori vorrebbe dire pubblicare come
	 * responsabili contro abusi e violenze delle persone che non lo sono. Un
	 * elenco vuoto e' scomodo, quello sbagliato e' un danno.
	 */
	public static function default_rows( $g = 'prima' ) {
		if ( 'safeguarding' === $g ) { return array(); }
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
		return self::default_rows( $g );
	}

	/* ---------------- Admin ---------------- */

	public static function menu() {
		$hook = add_menu_page( 'Staff e Safeguarding', 'Staff e Safeguarding', 'manage_options', 'cp-staff', array( __CLASS__, 'page' ), 'dashicons-groups', 29 );
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
			<h1>Staff e Safeguarding</h1>
			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Salvato.</p></div>
			<?php endif; ?>
			<p>Gestisci le persone di ciascuna sezione. <strong>Trascina</strong> le righe dall'icona (⋮⋮) per ordinarle;
			il <strong>Riferimenti</strong> &egrave; il ruolo, il <strong>Nome</strong> la persona. Un unico
			<strong>Salva</strong> in fondo vale per tutte e tre le sezioni.</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cpstaff_save">
				<?php wp_nonce_field( 'cpstaff_save' ); ?>
				<?php foreach ( self::groups() as $g => $label ) : ?>
					<h2 style="margin-top:28px"><?php echo esc_html( $label ); ?></h2>
					<?php $nota = self::group_note( $g ); ?>
					<?php if ( '' !== $nota ) : ?>
						<p class="description" style="max-width:820px;margin:-6px 0 12px"><?php echo wp_kses_post( $nota ); ?></p>
					<?php endif; ?>
					<div class="cpstaff-rows" data-group="<?php echo esc_attr( $g ); ?>">
						<?php foreach ( self::get_group( $g ) as $i => $r ) { self::render_row( $g, $i, $r ); } ?>
					</div>
					<?php if ( ! self::get_group( $g ) ) : ?>
						<p class="cpstaff-vuoto" data-group="<?php echo esc_attr( $g ); ?>"><em>Nessuna persona indicata: sul sito compare &laquo;nominativi in corso di pubblicazione&raquo;.</em></p>
					<?php endif; ?>
					<p><button type="button" class="button cpstaff-add" data-group="<?php echo esc_attr( $g ); ?>">+ Aggiungi persona</button></p>
				<?php endforeach; ?>
				<?php submit_button( 'Salva' ); ?>
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
				/* l'avviso "nessuna persona" non ha piu' senso appena se ne aggiunge una */
				var vuoto = document.querySelector('.cpstaff-vuoto[data-group="'+group+'"]');
				if ( vuoto ) { vuoto.parentNode.removeChild(vuoto); }
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

if ( ! function_exists( 'cp_get_safeguarding' ) ) {
	/**
	 * Le persone del Safeguarding (role, name), array vuoto se non indicate.
	 *
	 * E' cp_get_staff('safeguarding') con un nome che si legge meglio dai
	 * template. Sostituisce cp_get_responsabile_safeguarding(), che tornava un
	 * nome solo: adesso le persone possono essere piu' di una.
	 */
	function cp_get_safeguarding() {
		return CP_Staff::get_group( 'safeguarding' );
	}
}
