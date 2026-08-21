<?php
/**
 * Template della pagina "Squadre" — panoramica categorie.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$squadre = array(
	array( 'slug' => 'settore-giovanile', 'eta' => '5-10 anni', 'nome' => 'Settore Giovanile', 'desc' => 'Il nostro percorso formativo per i pi&ugrave; piccoli: Piccoli Amici, Primi Calci e Pulcini. Gioco, crescita e divertimento per muovere i primi passi nel calcio.', 'prima' => false ),
	array( 'slug' => 'prima-squadra', 'eta' => 'Seniores', 'nome' => 'Prima Squadra', 'desc' => 'Il punto d&rsquo;arrivo del percorso granata: la squadra che rappresenta Pieris nei campionati regionali FIGC-LND.', 'prima' => true ),
);
?>
<div class="page-hero">
	<div class="container">
		<div class="breadcrumb"><a style="color:var(--oro)" href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &rsaquo; Squadre</div>
		<h1>Squadre</h1>
	</div>
</div>

<?php
/* Logo Golee nei pulsanti di tesseramento. Cercato in assets/ come golee.{estensione}:
   finche' il file non c'e', i pulsanti restano solo testuali. Il logo sta dentro una
   pastiglia bianca, cosi' si legge sia sul pulsante pieno sia su quello con contorno,
   qualunque siano i colori del marchio. */
$cp_golee = '';
foreach ( array( 'svg', 'webp', 'png', 'jpg' ) as $cp_ext ) {
	if ( file_exists( get_template_directory() . '/assets/golee.' . $cp_ext ) ) {
		$cp_golee = get_template_directory_uri() . '/assets/golee.' . $cp_ext;
		break;
	}
}
$cp_golee_img = $cp_golee
	? '<span class="btn-logo" aria-hidden="true"><img src="' . esc_url( $cp_golee ) . '" alt=""></span>'
	: '';
?>
<div class="entry-content">
	<p>Al Calcio Pieris si gioca a ogni et&agrave;. Dai primi calci dei pi&ugrave; piccoli fino alla Prima Squadra, il nostro obiettivo &egrave; sempre lo stesso: <strong>far crescere le persone attraverso lo sport</strong>, con passione e rispetto.</p>
	<p>Il settore giovanile granata accompagna i bambini e le bambine passo dopo passo, rispettando i tempi di ciascuno e mettendo al centro il <strong>divertimento, il rispetto e lo spirito di squadra</strong>.</p>

	<h2>Gioca con noi</h2>
	<p>Le iscrizioni alla stagione sportiva sono aperte: se vuoi giocare per il Pieris o portare tuo figlio a provare, vieni a trovarci al campo negli orari di allenamento. <strong>Ti aspettiamo!</strong></p>
	<p>Per iscriverti puoi scegliere la strada che preferisci: <strong>online</strong>, con i moduli qui sotto, oppure <strong>di persona</strong>, <a href="<?php echo esc_url( home_url( '/contatti/' ) ); ?>">scrivendoci o parlando con noi al campo</a>. I moduli online passano da <strong>Golee</strong>, la piattaforma con cui seguiamo tesseramenti, visite mediche e comunicazioni ad atleti, famiglie e staff.</p>
	<p class="tesseramento-cta" style="display:flex;gap:14px;flex-wrap:wrap;margin-top:8px;">
		<a class="btn btn-granata" href="https://moduli.golee.it/asd-calcio-pieris-1925/tesseramento" target="_blank" rel="noopener"><?php echo $cp_golee_img; // gia' sanificato ?>Nuovo tesseramento</a>
		<a class="btn btn-outline" href="https://moduli.golee.it/asd-calcio-pieris-1925/tesseramento-riconferme" target="_blank" rel="noopener"><?php echo $cp_golee_img; // gia' sanificato ?>Rinnovo tesseramento</a>
	</p>

	<div class="squadre-grid">
		<?php foreach ( $squadre as $s ) : ?>
		<a class="card squadra-card<?php echo $s['prima'] ? ' squadra-card--prima' : ''; ?>" href="<?php echo esc_url( home_url( '/' . $s['slug'] . '/' ) ); ?>">
			<span class="squadra-eta"><?php echo esc_html( $s['eta'] ); ?></span>
			<h3><?php echo esc_html( $s['nome'] ); ?></h3>
			<p><?php echo wp_kses_post( $s['desc'] ); ?></p>
			<span class="squadra-link">Scopri di pi&ugrave; &rarr;</span>
		</a>
		<?php endforeach; ?>
	</div>
</div>

<?php get_footer();
