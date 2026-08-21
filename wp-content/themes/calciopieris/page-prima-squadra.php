<?php
/**
 * Template della pagina "Prima Squadra" — contenuto pagina + sezione Classifica/Partite.
 * La sezione è generata dal plugin "Calcio Pieris – Prima Squadra" via shortcode.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
while ( have_posts() ) : the_post();
?>
<div class="page-hero">
	<div class="container">
		<div class="breadcrumb"><a style="color:var(--oro)" href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &rsaquo; <a style="color:var(--oro)" href="<?php echo esc_url( home_url( '/squadre/' ) ); ?>">Squadre</a> &rsaquo; Prima Squadra</div>
		<h1><?php the_title(); ?></h1>
	</div>
</div>

<div class="entry-content">
	<?php
	// Contenuto della pagina, rimuovendo la sezione "Vuoi giocare con noi?" (h2 + paragrafo).
	$cp_content = apply_filters( 'the_content', get_the_content() );
	$cp_content = preg_replace( '#<h2[^>]*>\s*Vuoi giocare con noi\?.*?</p>#is', '', $cp_content, 1 );
	$cp_content = preg_replace( '#<h2[^>]*>\s*Gare e allenamenti\s*</h2>.*?</p>#is', '', $cp_content, 1 );
		if ( function_exists( 'cp_staff_tecnico_html' ) ) {
			$cp_content = preg_replace( '#(<h2[^>]*>\s*Il nostro calcio\s*</h2>.*?</ul>)#is', '$1' . cp_staff_tecnico_html( 'prima' ), $cp_content, 1 );
		}
		echo $cp_content;

	// Sezione classifica + partite (stagione corrente di default, con selettore stagioni).
	if ( shortcode_exists( 'pieris_prima_squadra' ) ) {
		echo do_shortcode( '[pieris_prima_squadra]' );
	}
	?>
</div>

<?php
endwhile;
get_footer();
