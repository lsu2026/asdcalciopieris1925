<?php
/**
 * Plugin Name: Calcio Pieris – Prima Squadra (Classifica e Partite)
 * Description: Gestione di stagioni, calendario partite e classifica della Prima Squadra, con area admin dedicata e shortcode [pieris_prima_squadra] per la visualizzazione (stagione corrente di default, con selettore delle stagioni passate).
 * Version: 1.1
 * Author: A.S.D. Calcio Pieris 1925
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CP_Prima_Squadra {

	const DB_VER = '1.2';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade' ) );
		add_shortcode( 'pieris_prima_squadra', array( __CLASS__, 'shortcode' ) );
		add_shortcode( 'pieris_classifica', array( __CLASS__, 'sc_classifica' ) );
		add_shortcode( 'pieris_calendario', array( __CLASS__, 'sc_calendario' ) );
		// contenuto di una singola stagione, chiesto dal carosello alla navigazione
		add_action( 'wp_ajax_cpps_season', array( __CLASS__, 'ajax_season' ) );
		add_action( 'wp_ajax_nopriv_cpps_season', array( __CLASS__, 'ajax_season' ) );
	}

	/* ============================ TABELLE ============================ */
	private static function t( $name ) { global $wpdb; return $wpdb->prefix . 'cp_' . $name; }

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$seasons = self::t( 'seasons' );
		$matches = self::t( 'matches' );
		$stand   = self::t( 'standings' );

		dbDelta( "CREATE TABLE $seasons (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			label VARCHAR(50) NOT NULL,
			categoria VARCHAR(80) NOT NULL DEFAULT '',
			girone VARCHAR(80) NOT NULL DEFAULT '',
			photo VARCHAR(255) NOT NULL DEFAULT '',
			is_current TINYINT(1) NOT NULL DEFAULT 0,
			sort INT NOT NULL DEFAULT 0,
			PRIMARY KEY (id)
		) $charset;" );

		dbDelta( "CREATE TABLE $matches (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			season_id BIGINT UNSIGNED NOT NULL,
			mdate DATETIME NULL,
			competition VARCHAR(120) NOT NULL DEFAULT '',
			home VARCHAR(120) NOT NULL DEFAULT '',
			away VARCHAR(120) NOT NULL DEFAULT '',
			home_goals INT NULL,
			away_goals INT NULL,
			PRIMARY KEY (id),
			KEY season_id (season_id)
		) $charset;" );

		dbDelta( "CREATE TABLE $stand (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			season_id BIGINT UNSIGNED NOT NULL,
			pos INT NOT NULL DEFAULT 0,
			team VARCHAR(120) NOT NULL DEFAULT '',
			pg INT NOT NULL DEFAULT 0,
			v INT NOT NULL DEFAULT 0,
			n INT NOT NULL DEFAULT 0,
			p INT NOT NULL DEFAULT 0,
			gf INT NOT NULL DEFAULT 0,
			gs INT NOT NULL DEFAULT 0,
			pts INT NOT NULL DEFAULT 0,
			ours TINYINT(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY season_id (season_id)
		) $charset;" );

		update_option( 'cpps_db_ver', self::DB_VER );
	}

	public static function maybe_upgrade() {
		if ( get_option( 'cpps_db_ver' ) !== self::DB_VER ) { self::install(); }
	}

	/* ============================ QUERY HELPER ============================ */
	public static function seasons() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . self::t( 'seasons' ) . ' ORDER BY sort DESC, id DESC' );
	}
	public static function current_season_id() {
		global $wpdb;
		$id = $wpdb->get_var( 'SELECT id FROM ' . self::t( 'seasons' ) . ' WHERE is_current=1 ORDER BY sort DESC, id DESC LIMIT 1' );
		if ( ! $id ) { $id = $wpdb->get_var( 'SELECT id FROM ' . self::t( 'seasons' ) . ' ORDER BY sort DESC, id DESC LIMIT 1' ); }
		return intval( $id );
	}
	public static function season( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'seasons' ) . ' WHERE id=%d', $id ) );
	}
	/** Testo "Categoria · Girone X" per una stagione. */
	public static function season_meta( $s ) {
		$parts = array();
		if ( ! empty( $s->categoria ) ) { $parts[] = $s->categoria; }
		if ( ! empty( $s->girone ) ) {
			$parts[] = ( false === stripos( $s->girone, 'girone' ) ) ? 'Girone ' . $s->girone : $s->girone;
		}
		return implode( ' · ', $parts );
	}
	public static function matches( $season_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'matches' ) . ' WHERE season_id=%d ORDER BY (mdate IS NULL), mdate ASC, id ASC', $season_id ) );
	}
	public static function standings( $season_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'standings' ) . ' WHERE season_id=%d ORDER BY pos ASC, pts DESC, (gf-gs) DESC, team ASC', $season_id ) );
	}

	/* ============================ CSV ============================ */
	/** Legge un CSV caricato e restituisce righe associative con header normalizzato. */
	private static function read_csv( $tmp ) {
		$content = @file_get_contents( $tmp );
		if ( false === $content ) { return array(); }
		$content = preg_replace( '/^\xEF\xBB\xBF/', '', $content );        // togli BOM
		if ( ! mb_check_encoding( $content, 'UTF-8' ) ) {                   // Excel a volte salva in ANSI
			$content = mb_convert_encoding( $content, 'UTF-8', 'Windows-1252' );
		}
		$content = str_replace( array( "\r\n", "\r" ), "\n", $content );
		$lines   = array_values( array_filter( explode( "\n", trim( $content ) ), function ( $l ) { return '' !== trim( $l ); } ) );
		if ( empty( $lines ) ) { return array(); }
		$delim  = ( substr_count( $lines[0], ';' ) > substr_count( $lines[0], ',' ) ) ? ';' : ',';
		$header = array_map( function ( $h ) { return strtolower( preg_replace( '/[^a-z0-9]/i', '', $h ) ); }, str_getcsv( array_shift( $lines ), $delim ) );
		$rows   = array();
		foreach ( $lines as $line ) {
			$cells = array_map( 'trim', str_getcsv( $line, $delim ) );
			$row   = array();
			foreach ( $header as $k => $name ) { $row[ $name ] = isset( $cells[ $k ] ) ? $cells[ $k ] : ''; }
			$rows[] = $row;
		}
		return $rows;
	}

	/** Primo valore non vuoto tra più possibili nomi di colonna. */
	private static function pick( $row, $keys, $def = '' ) {
		foreach ( (array) $keys as $k ) { if ( isset( $row[ $k ] ) && '' !== $row[ $k ] ) { return $row[ $k ]; } }
		return $def;
	}

	/** Interpreta date ISO (2025-09-14 15:30) o italiane (14/09/2025 15:30). */
	private static function parse_date( $s ) {
		$s = trim( (string) $s );
		if ( '' === $s ) { return null; }
		if ( preg_match( '/^\d{4}-\d{1,2}-\d{1,2}/', $s ) ) {
			$ts = strtotime( $s );
			return $ts ? gmdate( 'Y-m-d H:i:s', $ts ) : null;
		}
		if ( preg_match( '#^(\d{1,2})[/.\-](\d{1,2})[/.\-](\d{2,4})(?:[\sT]+(\d{1,2}):(\d{2}))?#', $s, $m ) ) {
			$d = intval( $m[1] ); $mo = intval( $m[2] ); $y = intval( $m[3] );
			if ( $y < 100 ) { $y += 2000; }
			$h = isset( $m[4] ) ? intval( $m[4] ) : 0; $min = isset( $m[5] ) ? intval( $m[5] ) : 0;
			return sprintf( '%04d-%02d-%02d %02d:%02d:00', $y, $mo, $d, $h, $min );
		}
		$ts = strtotime( $s );
		return $ts ? gmdate( 'Y-m-d H:i:s', $ts ) : null;
	}

	private static function import_matches_csv( $season, $tmp, $wipe ) {
		global $wpdb;
		$table = self::t( 'matches' );
		if ( $wipe ) { $wpdb->delete( $table, array( 'season_id' => $season ) ); }
		$n = 0;
		foreach ( self::read_csv( $tmp ) as $r ) {
			$home = self::pick( $r, array( 'casa', 'squadracasa', 'home' ) );
			$away = self::pick( $r, array( 'ospite', 'squadraospite', 'trasferta', 'away' ) );
			if ( '' === $home && '' === $away ) { continue; }
			$hg = self::pick( $r, array( 'golcasa', 'goalcasa', 'gfcasa', 'homegoals', 'reticasa' ), '' );
			$ag = self::pick( $r, array( 'golospite', 'goalospite', 'awaygoals', 'retiospite' ), '' );
			$wpdb->insert( $table, array(
				'season_id'   => $season,
				'mdate'       => self::parse_date( self::pick( $r, array( 'data', 'dataora', 'date', 'datetime' ) ) ),
				'competition' => sanitize_text_field( self::pick( $r, array( 'competizione', 'campionato', 'torneo' ) ) ),
				'home'        => sanitize_text_field( $home ),
				'away'        => sanitize_text_field( $away ),
				'home_goals'  => ( '' === $hg ) ? null : intval( $hg ),
				'away_goals'  => ( '' === $ag ) ? null : intval( $ag ),
			) );
			$n++;
		}
		return $n;
	}

	private static function import_standings_csv( $season, $tmp, $wipe ) {
		global $wpdb;
		$table = self::t( 'standings' );
		if ( $wipe ) { $wpdb->delete( $table, array( 'season_id' => $season ) ); }
		$n = 0; $auto = 0;
		foreach ( self::read_csv( $tmp ) as $r ) {
			$team = self::pick( $r, array( 'squadra', 'team' ) );
			if ( '' === $team ) { continue; }
			$auto++;
			$ours = self::pick( $r, array( 'noi', 'pieris' ), '' );
			$is_ours = ( in_array( strtolower( $ours ), array( '1', 'si', 'sì', 'x', 'true', 'yes' ), true ) || stripos( $team, 'pieris' ) !== false ) ? 1 : 0;
			$wpdb->insert( $table, array(
				'season_id' => $season,
				'pos'  => intval( self::pick( $r, array( 'pos', 'posizione' ), $auto ) ),
				'team' => sanitize_text_field( $team ),
				'pg'   => intval( self::pick( $r, array( 'pg', 'giocate', 'partite' ), 0 ) ),
				'v'    => intval( self::pick( $r, array( 'v', 'vinte' ), 0 ) ),
				'n'    => intval( self::pick( $r, array( 'n', 'pareggi', 'pareggiate' ), 0 ) ),
				'p'    => intval( self::pick( $r, array( 'p', 'perse' ), 0 ) ),
				'gf'   => intval( self::pick( $r, array( 'gf', 'golfatti', 'retifatte' ), 0 ) ),
				'gs'   => intval( self::pick( $r, array( 'gs', 'golsubiti', 'retisubite' ), 0 ) ),
				'pts'  => intval( self::pick( $r, array( 'pt', 'pts', 'punti' ), 0 ) ),
				'ours' => $is_ours,
			) );
			$n++;
		}
		return $n;
	}

	/* ============================ ADMIN MENU ============================ */
	public static function menu() {
		add_menu_page( 'Prima Squadra', 'Prima Squadra', 'manage_options', 'cpps_seasons', array( __CLASS__, 'page_seasons' ), 'dashicons-awards', 26 );
		add_submenu_page( 'cpps_seasons', 'Stagioni', 'Stagioni', 'manage_options', 'cpps_seasons', array( __CLASS__, 'page_seasons' ) );
		add_submenu_page( 'cpps_seasons', 'Partite', 'Partite', 'manage_options', 'cpps_matches', array( __CLASS__, 'page_matches' ) );
		add_submenu_page( 'cpps_seasons', 'Classifica', 'Classifica', 'manage_options', 'cpps_standings', array( __CLASS__, 'page_standings' ) );
		add_action( 'admin_enqueue_scripts', function ( $hook ) {
			if ( false !== strpos( $hook, 'cpps_seasons' ) ) { wp_enqueue_media(); }
		} );
	}

	private static function require_admin() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non autorizzato.' ); }
	}
	private static function notice( $m ) { echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $m ) . '</p></div>'; }

	private static function season_picker( $current, $base_page ) {
		$seasons = self::seasons();
		if ( empty( $seasons ) ) { return; }
		echo '<form method="get" style="margin:12px 0">';
		echo '<input type="hidden" name="page" value="' . esc_attr( $base_page ) . '">';
		echo '<label style="font-weight:600">Stagione: </label><select name="s" onchange="this.form.submit()">';
		foreach ( $seasons as $s ) {
			echo '<option value="' . intval( $s->id ) . '"' . selected( $current, $s->id, false ) . '>' . esc_html( $s->label ) . ( $s->is_current ? ' (corrente)' : '' ) . '</option>';
		}
		echo '</select></form>';
	}

	/* ---------------------------- STAGIONI ---------------------------- */
	public static function page_seasons() {
		self::require_admin();
		global $wpdb;
		$table = self::t( 'seasons' );

		if ( isset( $_POST['cpps_add_season'] ) && check_admin_referer( 'cpps_season' ) ) {
			$data = array(
				'label'     => sanitize_text_field( wp_unslash( $_POST['label'] ) ),
				'categoria' => sanitize_text_field( wp_unslash( $_POST['categoria'] ) ),
				'girone'    => sanitize_text_field( wp_unslash( $_POST['girone'] ) ),
				// salvo relativo a wp-content, cosi' il dato non dipende dal dominio
				'photo'     => isset( $_POST['photo'] ) && function_exists( 'cp_asset_relative' )
					? cp_asset_relative( esc_url_raw( wp_unslash( $_POST['photo'] ) ) )
					: ( isset( $_POST['photo'] ) ? esc_url_raw( wp_unslash( $_POST['photo'] ) ) : '' ),
				'sort'      => intval( $_POST['sort'] ),
			);
			if ( '' !== $data['label'] ) {
				$eid = intval( $_POST['edit_id'] );
				if ( $eid ) { $wpdb->update( $table, $data, array( 'id' => $eid ) ); self::notice( 'Stagione aggiornata.' ); }
				else { $wpdb->insert( $table, $data ); self::notice( 'Stagione aggiunta.' ); }
			}
		}
		if ( isset( $_GET['setcur'] ) && check_admin_referer( 'cpps_setcur' ) ) {
			$wpdb->query( "UPDATE $table SET is_current=0" );
			$wpdb->update( $table, array( 'is_current' => 1 ), array( 'id' => intval( $_GET['setcur'] ) ) );
			self::notice( 'Stagione corrente aggiornata.' );
		}
		if ( isset( $_GET['delseason'] ) && check_admin_referer( 'cpps_delseason' ) ) {
			$sid = intval( $_GET['delseason'] );
			$wpdb->delete( $table, array( 'id' => $sid ) );
			$wpdb->delete( self::t( 'matches' ), array( 'season_id' => $sid ) );
			$wpdb->delete( self::t( 'standings' ), array( 'season_id' => $sid ) );
			self::notice( 'Stagione (e relativi dati) eliminata.' );
		}

		$edit = null;
		if ( isset( $_GET['editseason'] ) ) { $edit = self::season( intval( $_GET['editseason'] ) ); }
		$seasons = self::seasons();
		?>
		<div class="wrap">
			<h1>Stagioni</h1>
			<p>Crea le stagioni (es. <code>2024/2025</code>) indicando <strong>categoria</strong> e <strong>girone</strong>. La stagione <strong>corrente</strong> è quella mostrata di default sul sito. Un numero d'ordine più alto compare prima.</p>
			<table class="widefat striped" style="max-width:860px">
				<thead><tr><th>Stagione</th><th>Categoria</th><th>Girone</th><th>Ordine</th><th>Corrente</th><th>Azioni</th></tr></thead>
				<tbody>
				<?php if ( empty( $seasons ) ) : ?>
					<tr><td colspan="6">Nessuna stagione. Aggiungine una qui sotto.</td></tr>
				<?php else : foreach ( $seasons as $s ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $s->label ); ?></strong></td>
						<td><?php echo esc_html( $s->categoria ); ?></td>
						<td><?php echo esc_html( $s->girone ); ?></td>
						<td><?php echo intval( $s->sort ); ?></td>
						<td><?php echo $s->is_current ? '✅' : ''; ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=cpps_seasons&editseason=' . $s->id ) ); ?>">Modifica</a> |
							<?php if ( ! $s->is_current ) : ?>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cpps_seasons&setcur=' . $s->id ), 'cpps_setcur' ) ); ?>">Rendi corrente</a> |
							<?php endif; ?>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cpps_seasons&delseason=' . $s->id ), 'cpps_delseason' ) ); ?>" style="color:#b32d2e" onclick="return confirm('Eliminare la stagione e tutti i suoi dati?')">Elimina</a>
						</td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>

			<h2 style="margin-top:24px"><?php echo $edit ? 'Modifica stagione' : 'Aggiungi stagione'; ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'cpps_season' ); ?>
				<input type="hidden" name="edit_id" value="<?php echo $edit ? intval( $edit->id ) : 0; ?>">
				<table class="form-table">
					<tr><th><label>Etichetta</label></th><td><input type="text" name="label" value="<?php echo $edit ? esc_attr( $edit->label ) : ''; ?>" placeholder="2024/2025" class="regular-text" required></td></tr>
					<tr><th><label>Categoria</label></th><td><input type="text" name="categoria" value="<?php echo $edit ? esc_attr( $edit->categoria ) : ''; ?>" placeholder="Prima Categoria" class="regular-text"></td></tr>
					<tr><th><label>Girone</label></th><td><input type="text" name="girone" value="<?php echo $edit ? esc_attr( $edit->girone ) : ''; ?>" placeholder="Girone B" class="regular-text"> <span class="description">puoi scrivere "B" o "Girone B"</span></td></tr>
					<tr><th><label>Ordine</label></th><td><input type="number" name="sort" value="<?php echo $edit ? intval( $edit->sort ) : count( $seasons ) + 1; ?>" class="small-text"> <span class="description">più alto = più recente</span></td></tr>
					<tr><th><label>Foto squadra</label></th><td>
						<?php $cur_photo = ( $edit && ! empty( $edit->photo ) ) ? $edit->photo : ''; ?>
						<input type="hidden" id="cpps_photo" name="photo" value="<?php echo esc_attr( $cur_photo ); ?>">
						<img id="cpps_photo_prev" src="<?php echo esc_url( function_exists( 'cp_asset_url' ) ? cp_asset_url( $cur_photo ) : $cur_photo ); ?>" alt="" style="max-width:280px;height:auto;border:1px solid #ddd;border-radius:6px;margin-bottom:8px;display:<?php echo $cur_photo ? 'block' : 'none'; ?>">
						<br>
						<button type="button" class="button" id="cpps_photo_pick">Scegli / Carica foto</button>
						<button type="button" class="button-link" id="cpps_photo_clear" style="margin-left:8px;color:#b32d2e">rimuovi</button>
						<p class="description">Mostrata nella pagina Prima Squadra, sopra la classifica della stagione.</p>
						<p style="color:#c0392b;font-weight:700;margin:4px 0 0">Caratteristiche consigliate: usa un JPEG compresso e ritaglia/ridimensiona la foto a 1200×800 px, per minimizzare il peso.</p>
					</td></tr>
				</table>
				<?php submit_button( $edit ? 'Salva modifiche' : 'Aggiungi stagione', 'primary', 'cpps_add_season' ); ?>
				<?php if ( $edit ) : ?><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=cpps_seasons' ) ); ?>">Annulla</a><?php endif; ?>
			</form>
		</div>
		<script>
		(function(){
			var frame, input=document.getElementById('cpps_photo'), prev=document.getElementById('cpps_photo_prev');
			var pick=document.getElementById('cpps_photo_pick'), clr=document.getElementById('cpps_photo_clear');
			if(pick){ pick.addEventListener('click', function(e){ e.preventDefault();
				if(!window.wp||!wp.media){ return; }
				frame = wp.media({ title:'Foto squadra', button:{text:'Usa questa foto'}, multiple:false });
				frame.on('select', function(){ var a=frame.state().get('selection').first().toJSON(); input.value=a.url; prev.src=a.url; prev.style.display='block'; });
				frame.open();
			}); }
			if(clr){ clr.addEventListener('click', function(e){ e.preventDefault(); input.value=''; prev.src=''; prev.style.display='none'; }); }
		})();
		</script>
		<?php
	}

	/* ---------------------------- PARTITE ---------------------------- */
	public static function page_matches() {
		self::require_admin();
		global $wpdb;
		$table = self::t( 'matches' );
		$season = isset( $_REQUEST['s'] ) ? intval( $_REQUEST['s'] ) : self::current_season_id();

		if ( isset( $_POST['cpps_save_match'] ) && check_admin_referer( 'cpps_match' ) ) {
			$data = array(
				'season_id'   => $season,
				'mdate'       => '' !== $_POST['mdate'] ? gmdate( 'Y-m-d H:i:s', strtotime( sanitize_text_field( wp_unslash( $_POST['mdate'] ) ) ) ) : null,
				'competition' => sanitize_text_field( wp_unslash( $_POST['competition'] ) ),
				'home'        => sanitize_text_field( wp_unslash( $_POST['home'] ) ),
				'away'        => sanitize_text_field( wp_unslash( $_POST['away'] ) ),
				'home_goals'  => ( '' === $_POST['home_goals'] ) ? null : intval( $_POST['home_goals'] ),
				'away_goals'  => ( '' === $_POST['away_goals'] ) ? null : intval( $_POST['away_goals'] ),
			);
			$eid = intval( $_POST['edit_id'] );
			if ( $eid ) { $wpdb->update( $table, $data, array( 'id' => $eid ) ); self::notice( 'Partita aggiornata.' ); }
			else { $wpdb->insert( $table, $data ); self::notice( 'Partita aggiunta.' ); }
		}
		if ( isset( $_GET['delmatch'] ) && check_admin_referer( 'cpps_delmatch' ) ) {
			$wpdb->delete( $table, array( 'id' => intval( $_GET['delmatch'] ) ) );
			self::notice( 'Partita eliminata.' );
		}
		if ( isset( $_POST['cpps_import_matches'] ) && check_admin_referer( 'cpps_import_m' ) && ! empty( $_FILES['csv']['tmp_name'] ) ) {
			$n = self::import_matches_csv( $season, $_FILES['csv']['tmp_name'], ! empty( $_POST['wipe'] ) );
			self::notice( "Importate {$n} partite dal file CSV." );
		}

		$edit = null;
		if ( isset( $_GET['editmatch'] ) ) { $edit = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id=%d", intval( $_GET['editmatch'] ) ) ); }
		$rows = $season ? self::matches( $season ) : array();
		?>
		<div class="wrap">
			<h1>Partite</h1>
			<?php if ( ! self::seasons() ) : ?>
				<div class="notice notice-warning"><p>Prima crea almeno una <a href="<?php echo esc_url( admin_url( 'admin.php?page=cpps_seasons' ) ); ?>">stagione</a>.</p></div>
			<?php else : ?>
			<?php self::season_picker( $season, 'cpps_matches' ); ?>
			<p class="description">Lascia i gol vuoti per una partita <em>programmata</em>; compilali quando la partita è stata giocata.</p>
			<table class="widefat striped">
				<thead><tr><th>Data</th><th>Competizione</th><th>Casa</th><th>Ospite</th><th>Risultato</th><th>Azioni</th></tr></thead>
				<tbody>
				<?php if ( empty( $rows ) ) : ?><tr><td colspan="6">Nessuna partita per questa stagione.</td></tr>
				<?php else : foreach ( $rows as $m ) : ?>
					<tr>
						<td><?php echo $m->mdate ? esc_html( date_i18n( 'j M Y H:i', strtotime( get_date_from_gmt( $m->mdate ) ) ) ) : '—'; ?></td>
						<td><?php echo esc_html( $m->competition ); ?></td>
						<td><?php echo esc_html( $m->home ); ?></td>
						<td><?php echo esc_html( $m->away ); ?></td>
						<td><?php echo ( null !== $m->home_goals && null !== $m->away_goals ) ? intval( $m->home_goals ) . ' - ' . intval( $m->away_goals ) : '<em>da giocare</em>'; ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=cpps_matches&s=' . $season . '&editmatch=' . $m->id ) ); ?>">Modifica</a> |
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cpps_matches&s=' . $season . '&delmatch=' . $m->id ), 'cpps_delmatch' ) ); ?>" style="color:#b32d2e" onclick="return confirm('Eliminare la partita?')">Elimina</a>
						</td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>

			<h2 style="margin-top:24px"><?php echo $edit ? 'Modifica partita' : 'Aggiungi partita'; ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'cpps_match' ); ?>
				<input type="hidden" name="edit_id" value="<?php echo $edit ? intval( $edit->id ) : 0; ?>">
				<table class="form-table">
					<tr><th><label>Data e ora</label></th><td><input type="datetime-local" name="mdate" value="<?php echo $edit && $edit->mdate ? esc_attr( date( 'Y-m-d\TH:i', strtotime( get_date_from_gmt( $edit->mdate ) ) ) ) : ''; ?>"></td></tr>
					<tr><th><label>Competizione</label></th><td><input type="text" name="competition" value="<?php echo $edit ? esc_attr( $edit->competition ) : ''; ?>" class="regular-text" placeholder="Campionato Prima Categoria"></td></tr>
					<tr><th><label>Squadra di casa</label></th><td><input type="text" name="home" value="<?php echo $edit ? esc_attr( $edit->home ) : 'Calcio Pieris'; ?>" class="regular-text" required></td></tr>
					<tr><th><label>Squadra ospite</label></th><td><input type="text" name="away" value="<?php echo $edit ? esc_attr( $edit->away ) : ''; ?>" class="regular-text" required></td></tr>
					<tr><th><label>Gol casa / ospite</label></th><td>
						<input type="number" name="home_goals" value="<?php echo $edit && null !== $edit->home_goals ? intval( $edit->home_goals ) : ''; ?>" class="small-text" min="0"> -
						<input type="number" name="away_goals" value="<?php echo $edit && null !== $edit->away_goals ? intval( $edit->away_goals ) : ''; ?>" class="small-text" min="0">
						<span class="description">(vuoti = da giocare)</span>
					</td></tr>
				</table>
				<?php submit_button( $edit ? 'Salva modifiche' : 'Aggiungi partita', 'primary', 'cpps_save_match' ); ?>
				<?php if ( $edit ) : ?><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=cpps_matches&s=' . $season ) ); ?>">Annulla</a><?php endif; ?>
			</form>

			<hr style="margin:28px 0">
			<h2>Importa partite da CSV</h2>
			<p class="description">Colonne accettate (con intestazione, separatore <code>,</code> o <code>;</code>): <code>data, competizione, casa, ospite, gol_casa, gol_ospite</code>. La <strong>data</strong> può essere <code>2025-09-14 15:30</code> o <code>14/09/2025 15:30</code>. Lascia i gol vuoti per le partite ancora da giocare.</p>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'cpps_import_m' ); ?>
				<input type="file" name="csv" accept=".csv,text/csv" required>
				<label style="margin-left:10px"><input type="checkbox" name="wipe" value="1"> svuota prima le partite di questa stagione</label>
				<?php submit_button( 'Importa CSV partite', 'secondary', 'cpps_import_matches', false ); ?>
			</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ---------------------------- CLASSIFICA ---------------------------- */
	public static function page_standings() {
		self::require_admin();
		global $wpdb;
		$table = self::t( 'standings' );
		$season = isset( $_REQUEST['s'] ) ? intval( $_REQUEST['s'] ) : self::current_season_id();

		if ( isset( $_POST['cpps_save_row'] ) && check_admin_referer( 'cpps_row' ) ) {
			$data = array(
				'season_id' => $season,
				'pos'  => intval( $_POST['pos'] ),
				'team' => sanitize_text_field( wp_unslash( $_POST['team'] ) ),
				'pg'   => intval( $_POST['pg'] ),
				'v'    => intval( $_POST['v'] ),
				'n'    => intval( $_POST['n'] ),
				'p'    => intval( $_POST['p'] ),
				'gf'   => intval( $_POST['gf'] ),
				'gs'   => intval( $_POST['gs'] ),
				'pts'  => intval( $_POST['pts'] ),
				'ours' => isset( $_POST['ours'] ) ? 1 : 0,
			);
			$eid = intval( $_POST['edit_id'] );
			if ( $eid ) { $wpdb->update( $table, $data, array( 'id' => $eid ) ); self::notice( 'Riga aggiornata.' ); }
			else { $wpdb->insert( $table, $data ); self::notice( 'Riga aggiunta.' ); }
		}
		if ( isset( $_GET['delrow'] ) && check_admin_referer( 'cpps_delrow' ) ) {
			$wpdb->delete( $table, array( 'id' => intval( $_GET['delrow'] ) ) );
			self::notice( 'Riga eliminata.' );
		}
		if ( isset( $_POST['cpps_import_rows'] ) && check_admin_referer( 'cpps_import_s' ) && ! empty( $_FILES['csv']['tmp_name'] ) ) {
			$n = self::import_standings_csv( $season, $_FILES['csv']['tmp_name'], ! empty( $_POST['wipe'] ) );
			self::notice( "Importate {$n} righe di classifica dal file CSV." );
		}

		$edit = null;
		if ( isset( $_GET['editrow'] ) ) { $edit = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id=%d", intval( $_GET['editrow'] ) ) ); }
		$rows = $season ? self::standings( $season ) : array();
		?>
		<div class="wrap">
			<h1>Classifica</h1>
			<?php if ( ! self::seasons() ) : ?>
				<div class="notice notice-warning"><p>Prima crea almeno una <a href="<?php echo esc_url( admin_url( 'admin.php?page=cpps_seasons' ) ); ?>">stagione</a>.</p></div>
			<?php else : ?>
			<?php self::season_picker( $season, 'cpps_standings' ); ?>
			<p class="description">Spunta "Noi" sulla riga del Calcio Pieris per evidenziarla sul sito.</p>
			<table class="widefat striped">
				<thead><tr><th>Pos</th><th>Squadra</th><th>PG</th><th>V</th><th>N</th><th>P</th><th>GF</th><th>GS</th><th>Pt</th><th>Noi</th><th>Azioni</th></tr></thead>
				<tbody>
				<?php if ( empty( $rows ) ) : ?><tr><td colspan="11">Nessuna riga per questa stagione.</td></tr>
				<?php else : foreach ( $rows as $r ) : ?>
					<tr>
						<td><?php echo intval( $r->pos ); ?></td><td><strong><?php echo esc_html( $r->team ); ?></strong></td>
						<td><?php echo intval( $r->pg ); ?></td><td><?php echo intval( $r->v ); ?></td><td><?php echo intval( $r->n ); ?></td><td><?php echo intval( $r->p ); ?></td>
						<td><?php echo intval( $r->gf ); ?></td><td><?php echo intval( $r->gs ); ?></td><td><strong><?php echo intval( $r->pts ); ?></strong></td>
						<td><?php echo $r->ours ? '⭐' : ''; ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=cpps_standings&s=' . $season . '&editrow=' . $r->id ) ); ?>">Modifica</a> |
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cpps_standings&s=' . $season . '&delrow=' . $r->id ), 'cpps_delrow' ) ); ?>" style="color:#b32d2e" onclick="return confirm('Eliminare la riga?')">Elimina</a>
						</td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>

			<h2 style="margin-top:24px"><?php echo $edit ? 'Modifica riga' : 'Aggiungi riga'; ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'cpps_row' ); ?>
				<input type="hidden" name="edit_id" value="<?php echo $edit ? intval( $edit->id ) : 0; ?>">
				<table class="form-table">
					<tr><th><label>Posizione</label></th><td><input type="number" name="pos" value="<?php echo $edit ? intval( $edit->pos ) : count( $rows ) + 1; ?>" class="small-text"></td></tr>
					<tr><th><label>Squadra</label></th><td><input type="text" name="team" value="<?php echo $edit ? esc_attr( $edit->team ) : ''; ?>" class="regular-text" required>
						<label style="margin-left:10px"><input type="checkbox" name="ours" <?php echo $edit && $edit->ours ? 'checked' : ''; ?>> è il Calcio Pieris (evidenzia)</label></td></tr>
					<tr><th><label>PG / V / N / P</label></th><td>
						PG <input type="number" name="pg" value="<?php echo $edit ? intval( $edit->pg ) : 0; ?>" class="small-text" min="0">
						V <input type="number" name="v" value="<?php echo $edit ? intval( $edit->v ) : 0; ?>" class="small-text" min="0">
						N <input type="number" name="n" value="<?php echo $edit ? intval( $edit->n ) : 0; ?>" class="small-text" min="0">
						P <input type="number" name="p" value="<?php echo $edit ? intval( $edit->p ) : 0; ?>" class="small-text" min="0">
					</td></tr>
					<tr><th><label>GF / GS / Punti</label></th><td>
						GF <input type="number" name="gf" value="<?php echo $edit ? intval( $edit->gf ) : 0; ?>" class="small-text" min="0">
						GS <input type="number" name="gs" value="<?php echo $edit ? intval( $edit->gs ) : 0; ?>" class="small-text" min="0">
						Punti <input type="number" name="pts" value="<?php echo $edit ? intval( $edit->pts ) : 0; ?>" class="small-text" min="0">
					</td></tr>
				</table>
				<?php submit_button( $edit ? 'Salva modifiche' : 'Aggiungi riga', 'primary', 'cpps_save_row' ); ?>
				<?php if ( $edit ) : ?><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=cpps_standings&s=' . $season ) ); ?>">Annulla</a><?php endif; ?>
			</form>

			<hr style="margin:28px 0">
			<h2>Importa classifica da CSV</h2>
			<p class="description">Colonne accettate (con intestazione, separatore <code>,</code> o <code>;</code>): <code>pos, squadra, pg, v, n, p, gf, gs, punti, noi</code>. La colonna <code>noi</code> (valori <code>si</code>/<code>1</code>) evidenzia la riga del Pieris; se manca, viene evidenziata automaticamente la squadra che contiene "Pieris". Se <code>pos</code> manca, viene usato l'ordine delle righe.</p>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'cpps_import_s' ); ?>
				<input type="file" name="csv" accept=".csv,text/csv" required>
				<label style="margin-left:10px"><input type="checkbox" name="wipe" value="1" checked> svuota prima la classifica di questa stagione</label>
				<?php submit_button( 'Importa CSV classifica', 'secondary', 'cpps_import_rows', false ); ?>
			</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ============================ FRONTEND ============================ */
	private static function css() {
		static $done = false;
		if ( $done ) { return ''; }
		$done = true;
		return '<style>
		.cp-ps{--g:var(--granata,#901913);--o:var(--oro,#d6aa63);margin:32px 0}
		.cp-section-title{text-align:center;color:var(--g);font-family:var(--font-display,inherit);font-size:1.7rem;margin:0 0 18px;letter-spacing:.6px;text-transform:uppercase}
		.cp-section-title::after{content:"";display:block;width:60px;height:3px;background:var(--o);margin:8px auto 0;border-radius:2px}
		.cp-ps__head{display:flex;align-items:center;justify-content:center;gap:18px;margin-bottom:22px}
		.cp-season-title{text-align:center;min-width:200px}
		.cp-season-kicker{display:block;font-size:.72rem;letter-spacing:2px;text-transform:uppercase;color:#999;font-weight:600}
		.cp-season-label{display:block;font-family:var(--font-display,inherit);font-size:1.9rem;font-weight:700;color:var(--g);line-height:1.1;letter-spacing:.5px}
		.cp-season-meta{display:block;font-size:.9rem;color:var(--g);font-weight:600;margin-top:3px}
		.cp-season-meta:empty{display:none}
		.cp-arrow{flex:0 0 auto;width:44px;height:44px;border-radius:50%;border:2px solid var(--g);background:#fff;color:var(--g);font-size:24px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;font-weight:700;transition:background .15s,color .15s,opacity .15s;box-shadow:0 1px 5px rgba(0,0,0,.15)}
		.cp-arrow:hover:not(:disabled){background:var(--g);color:#fff}
		.cp-arrow:disabled{opacity:.22;cursor:default;box-shadow:none}
		.cp-slides-viewport{overflow:hidden;transition:height .35s ease}
		.cp-slides-track{display:flex;align-items:flex-start;will-change:transform}
		.cp-slide{flex:0 0 100%;width:100%;box-sizing:border-box}
		.cp-ps h3{color:var(--g);border-bottom:2px solid var(--o);padding-bottom:6px;margin:0 0 14px}
		.cp-slide h3:not(:first-child){margin-top:28px}
		.cp-table{width:100%;border-collapse:collapse;font-size:.95rem;overflow:hidden;border-radius:10px;box-shadow:0 1px 6px rgba(0,0,0,.08)}
		.cp-table th{background:var(--g);color:#fff;font-weight:600;text-align:center;padding:10px 8px;font-family:var(--font-display,inherit);letter-spacing:.3px}
		.cp-table td{padding:9px 8px;text-align:center;border-bottom:1px solid #eee}
		.cp-table td.cp-team{text-align:left;font-weight:600}
		.cp-table tbody tr:nth-child(even){background:#faf7f2}
		.cp-table tr.cp-ours{background:var(--o)!important;color:#3a2500}
		.cp-table tr.cp-ours td{font-weight:700}
		.cp-cal{list-style:none;padding:0;margin:0;display:grid;gap:10px}
		.cp-cal li{display:grid;grid-template-columns:110px 1fr auto;gap:12px;align-items:center;background:#fff;border:1px solid #eee;border-left:4px solid var(--o);border-radius:8px;padding:10px 14px}
		.cp-cal .cp-when{font-size:.85rem;color:#666;line-height:1.25}
		.cp-cal .cp-teams{font-weight:600}
		.cp-cal .cp-teams .cp-hl{color:var(--g)}
		.cp-cal .cp-score{font-family:var(--font-display,inherit);font-size:1.25rem;font-weight:700;color:var(--g);white-space:nowrap}
		.cp-cal .cp-score.cp-todo{font-size:.9rem;font-weight:600;color:#888}
		.cp-empty{color:#777;font-style:italic}
		.cp-loading{display:flex;align-items:center;justify-content:center;gap:12px;min-height:220px;color:#777;font-style:italic}
		.cp-spinner{width:20px;height:20px;border:2px solid #ddd;border-top-color:#901913;border-radius:50%;animation:cp-spin .8s linear infinite;flex:0 0 auto}
		@keyframes cp-spin{to{transform:rotate(360deg)}}
		@media(prefers-reduced-motion:reduce){.cp-spinner{animation:none}}
		.cp-team-photo{margin:0 0 22px;text-align:center}
		.cp-team-photo img{max-width:460px;width:100%;height:auto;border-radius:12px;box-shadow:0 3px 14px rgba(0,0,0,.14)}
		@media(max-width:560px){.cp-cal li{grid-template-columns:1fr;text-align:left}.cp-table{font-size:.82rem}.cp-table th,.cp-table td{padding:7px 4px}}
		</style>';
	}

	/** Foto squadra della stagione (se impostata). */
	public static function render_photo( $s ) {
		if ( empty( $s->photo ) ) { return ''; }
		$src = function_exists( 'cp_asset_url' ) ? cp_asset_url( $s->photo ) : $s->photo;
		return '<figure class="cp-team-photo"><img src="' . esc_url( $src ) . '" alt="Squadra ' . esc_attr( $s->label ) . '" onload="window.dispatchEvent(new Event(\'resize\'))"></figure>';
	}

	private static function hl( $name ) {
		if ( stripos( $name, 'pieris' ) !== false ) { return '<span class="cp-hl">' . esc_html( $name ) . '</span>'; }
		return esc_html( $name );
	}

	private static function resolve_season() {
		$req = isset( $_GET['cp_season'] ) ? intval( $_GET['cp_season'] ) : 0;
		if ( $req && self::season( $req ) ) { return $req; }
		return self::current_season_id();
	}

	/** Script (una sola volta): carosello stagioni con sliding orizzontale. */
	private static function js() {
		static $done = false;
		if ( $done ) { return ''; }
		$done = true;

		$js = <<<'JS'
(function () {
	function wire(root) {
		var vp = root.querySelector('.cp-slides-viewport');
		var track = root.querySelector('[data-cp-track]');
		if (!vp || !track) { return; }

		var slides = track.children;
		var total = slides.length;
		var idx = parseInt(track.getAttribute('data-cp-index') || '0', 10);
		if (idx < 0 || idx >= total) { idx = 0; }

		var label = root.querySelector('[data-cp-label]');
		var meta = root.querySelector('[data-cp-meta]');
		var left = root.querySelector('.cp-arrow-left');
		var right = root.querySelector('.cp-arrow-right');
		var ajax = root.getAttribute('data-cp-ajax');
		var nonce = root.getAttribute('data-cp-nonce') || '';

		/* Il viewport ha overflow:hidden e altezza in pixel: se la misura viene presa
		   prima che caratteri e immagini siano pronti, il contenuto resta tagliato o la
		   sezione sembra sparita. Con ResizeObserver l'altezza segue la slide attiva
		   ogni volta che cambia; senza observer si lascia altezza automatica, che al
		   massimo lascia spazio vuoto ma non nasconde nulla. */
		var ro = ('ResizeObserver' in window) ? new ResizeObserver(function () { altezza(); }) : null;
		var osservata = null;

		function altezza() {
			var s = slides[idx];
			if (!s) { return; }
			vp.style.height = ro ? (s.offsetHeight + 'px') : '';
		}

		function posiziona(anim) {
			var w = vp.clientWidth;
			track.style.transition = anim ? 'transform .35s ease' : 'none';
			track.style.transform = 'translateX(-' + (idx * w) + 'px)';
			if (label && slides[idx]) { label.textContent = slides[idx].getAttribute('data-cp-season'); }
			if (meta && slides[idx]) { meta.textContent = slides[idx].getAttribute('data-cp-meta'); }
			if (left) { left.disabled = idx <= 0; }
			if (right) { right.disabled = idx >= total - 1; }
			if (ro) {
				if (osservata) { ro.unobserve(osservata); }
				osservata = slides[idx];
				if (osservata) { ro.observe(osservata); }
			}
			altezza();
		}

		function carica(i) {
			var s = slides[i];
			if (!s || !ajax) { return; }
			if (s.getAttribute('data-cp-loaded') === '1' || s.getAttribute('data-cp-loading') === '1') { return; }
			var id = s.getAttribute('data-cp-id');
			if (!id) { return; }

			s.setAttribute('data-cp-loading', '1');
			var u = ajax + (ajax.indexOf('?') > -1 ? '&' : '?') +
				'action=cpps_season&season=' + encodeURIComponent(id) +
				'&nonce=' + encodeURIComponent(nonce);

			fetch(u, { credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (j) {
					if (j && j.success && j.data && j.data.html) {
						s.innerHTML = j.data.html;
						s.setAttribute('data-cp-loaded', '1');
					} else {
						s.innerHTML = '<p class="cp-empty">Non e stato possibile caricare questa stagione.</p>';
					}
				})
				.catch(function () {
					s.innerHTML = '<p class="cp-empty">Non e stato possibile caricare questa stagione.</p>';
				})
				.then(function () {
					s.removeAttribute('data-cp-loading');
					if (i === idx) { altezza(); }
				});
		}

		function vai(n) {
			if (n < 0 || n >= total || n === idx) { return; }
			idx = n;
			carica(idx);
			posiziona(true);
		}

		if (left) { left.addEventListener('click', function () { vai(idx - 1); }); }
		if (right) { right.addEventListener('click', function () { vai(idx + 1); }); }
		window.addEventListener('resize', function () { posiziona(false); });
		window.addEventListener('load', function () { posiziona(false); });

		posiziona(false);
	}

	function init() {
		var nodi = document.querySelectorAll('.cp-ps');
		for (var i = 0; i < nodi.length; i++) { wire(nodi[i]); }
	}

	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); }
	else { init(); }
})();
JS;

		return '<script>' . $js . '</script>';
	}

	public static function render_classifica( $season_id ) {
		$rows = self::standings( $season_id );
		if ( empty( $rows ) ) { return '<p class="cp-empty">Classifica non ancora disponibile per questa stagione.</p>'; }
		$h  = '<h3>Classifica</h3><div style="overflow-x:auto"><table class="cp-table"><thead><tr>';
		$h .= '<th>#</th><th style="text-align:left">Squadra</th><th>PG</th><th>V</th><th>N</th><th>P</th><th>GF</th><th>GS</th><th>DR</th><th>Pt</th></tr></thead><tbody>';
		foreach ( $rows as $r ) {
			$dr = intval( $r->gf ) - intval( $r->gs );
			$h .= '<tr class="' . ( $r->ours ? 'cp-ours' : '' ) . '">';
			$h .= '<td>' . intval( $r->pos ) . '</td><td class="cp-team">' . esc_html( $r->team ) . '</td>';
			$h .= '<td>' . intval( $r->pg ) . '</td><td>' . intval( $r->v ) . '</td><td>' . intval( $r->n ) . '</td><td>' . intval( $r->p ) . '</td>';
			$h .= '<td>' . intval( $r->gf ) . '</td><td>' . intval( $r->gs ) . '</td><td>' . ( $dr > 0 ? '+' : '' ) . $dr . '</td><td><strong>' . intval( $r->pts ) . '</strong></td></tr>';
		}
		$h .= '</tbody></table></div>';
		return $h;
	}

	public static function render_calendario( $season_id ) {
		$rows = self::matches( $season_id );
		if ( empty( $rows ) ) { return '<p class="cp-empty">Nessuna partita inserita per questa stagione.</p>'; }
		$played = array(); $todo = array();
		foreach ( $rows as $m ) {
			if ( null !== $m->home_goals && null !== $m->away_goals ) { $played[] = $m; } else { $todo[] = $m; }
		}
		$out = '';
		if ( ! empty( $todo ) ) {
			$out .= '<h3>Prossime partite</h3><ul class="cp-cal">';
			foreach ( $todo as $m ) { $out .= self::match_li( $m, false ); }
			$out .= '</ul>';
		}
		if ( ! empty( $played ) ) {
			$played = array_reverse( $played ); // più recenti prima
			$out .= '<h3>Risultati</h3><ul class="cp-cal">';
			foreach ( $played as $m ) { $out .= self::match_li( $m, true ); }
			$out .= '</ul>';
		}
		return $out;
	}

	private static function match_li( $m, $played ) {
		$when = $m->mdate ? date_i18n( 'D j M', strtotime( get_date_from_gmt( $m->mdate ) ) ) . '<br>' . date_i18n( 'H:i', strtotime( get_date_from_gmt( $m->mdate ) ) ) : 'Data da definire';
		$comp = $m->competition ? '<div class="cp-when" style="margin-top:2px">' . esc_html( $m->competition ) . '</div>' : '';
		$teams = self::hl( $m->home ) . ' <span style="color:#bbb">vs</span> ' . self::hl( $m->away );
		if ( $played ) {
			$score = '<span class="cp-score">' . intval( $m->home_goals ) . ' - ' . intval( $m->away_goals ) . '</span>';
		} else {
			$score = '<span class="cp-score cp-todo">da giocare</span>';
		}
		return '<li><div class="cp-when">' . $when . '</div><div class="cp-teams">' . $teams . $comp . '</div><div>' . $score . '</div></li>';
	}

	/**
	 * Restituisce foto, classifica e calendario di una stagione.
	 *
	 * Serve al carosello per caricare le stagioni diverse da quella iniziale solo
	 * quando servono. Il nonce viene verificato ma non e' bloccante: i dati sono gli
	 * stessi gia' visibili in pagina, e cosi' la richiesta funziona anche se la pagina
	 * arriva da una cache con un nonce ormai scaduto.
	 */
	public static function ajax_season() {
		check_ajax_referer( 'cpps_season', 'nonce', false );

		$id = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : 0;
		$s  = $id ? self::season( $id ) : null;
		if ( ! $s ) {
			wp_send_json_error( array( 'message' => 'Stagione non trovata.' ), 404 );
		}

		wp_send_json_success( array(
			'html'  => self::render_photo( $s ) . self::render_classifica( $s->id ) . self::render_calendario( $s->id ),
			'label' => $s->label,
			'meta'  => self::season_meta( $s ),
		) );
	}

	public static function shortcode( $atts ) {
		$seasons = self::seasons();
		if ( empty( $seasons ) ) { return '<p class="cp-empty">Sezione in allestimento.</p>'; }

		$current = self::resolve_season();
		$ids     = array();
		foreach ( $seasons as $s ) { $ids[] = intval( $s->id ); }
		$start = array_search( intval( $current ), $ids, true );
		if ( false === $start ) { $start = 0; }
		$multi = count( $seasons ) > 1;

		$out  = self::css();
		$out .= '<div class="cp-ps" id="cp-classifica"'
			. ' data-cp-ajax="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '"'
			. ' data-cp-nonce="' . esc_attr( wp_create_nonce( 'cpps_season' ) ) . '">';
		$out .= '<h2 class="cp-section-title">Classifica e risultati</h2>';
		$out .= '<div class="cp-ps__head">';
		if ( $multi ) { $out .= '<button type="button" class="cp-arrow cp-arrow-left" aria-label="Stagione precedente">&#8249;</button>'; }
		$out .= '<div class="cp-season-title"><span class="cp-season-kicker">Stagione</span>';
		$out .= '<span class="cp-season-label" data-cp-label>' . esc_html( $seasons[ $start ]->label ) . '</span>';
		$out .= '<span class="cp-season-meta" data-cp-meta>' . esc_html( self::season_meta( $seasons[ $start ] ) ) . '</span></div>';
		if ( $multi ) { $out .= '<button type="button" class="cp-arrow cp-arrow-right" aria-label="Stagione successiva">&#8250;</button>'; }
		$out .= '</div>';

		$out .= '<div class="cp-slides-viewport"><div class="cp-slides-track" data-cp-track data-cp-index="' . intval( $start ) . '">';
		// Solo la stagione iniziale viene composta qui: le altre restano vuote e il
		// carosello le chiede via AJAX quando ci si sposta. Con 5 stagioni questo
		// evita di generare cinque classifiche e cinque calendari a ogni visita.
		foreach ( $seasons as $i => $s ) {
			$attiva = ( $i === $start );
			$out .= '<div class="cp-slide" data-cp-season="' . esc_attr( $s->label ) . '"'
				. ' data-cp-meta="' . esc_attr( self::season_meta( $s ) ) . '"'
				. ' data-cp-id="' . intval( $s->id ) . '"'
				. ( $attiva ? ' data-cp-loaded="1"' : '' ) . '>';
			if ( $attiva ) {
				$out .= self::render_photo( $s );
				$out .= self::render_classifica( $s->id );
				$out .= self::render_calendario( $s->id );
			} else {
				$out .= '<div class="cp-loading"><span class="cp-spinner" aria-hidden="true"></span>Caricamento della stagione&hellip;</div>';
			}
			$out .= '</div>';
		}
		$out .= '</div></div>';
		$out .= '</div>';
		$out .= self::js();
		return $out;
	}

	public static function sc_classifica( $atts ) {
		$season_id = self::resolve_season();
		return self::css() . '<div class="cp-ps">' . self::render_classifica( $season_id ) . '</div>';
	}
	public static function sc_calendario( $atts ) {
		$season_id = self::resolve_season();
		return self::css() . '<div class="cp-ps">' . self::render_calendario( $season_id ) . '</div>';
	}
}

CP_Prima_Squadra::init();
register_activation_hook( __FILE__, array( 'CP_Prima_Squadra', 'install' ) );
