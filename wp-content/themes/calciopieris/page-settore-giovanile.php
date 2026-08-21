<?php
/**
 * Template della pagina "Settore Giovanile" — riunisce tutte le categorie giovanili.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$categorie = array(
	array(
		'eta'  => '5-6 anni',
		'nome' => 'Piccoli Amici',
		'intro'=> '&Egrave; qui che tutto comincia. Con i Piccoli Amici i bambini e le bambine incontrano per la prima volta il pallone in un ambiente sereno, sicuro e pieno di entusiasmo, dove la parola d&rsquo;ordine &egrave; una sola: divertirsi.',
		'cosa' => array(
			'Giochi di movimento per sviluppare coordinazione ed equilibrio.',
			'Primo approccio al pallone attraverso il gioco, mai la competizione.',
			'Attivit&agrave; di gruppo per imparare a stare insieme e fare squadra.',
		),
	),
	array(
		'eta'  => '7-8 anni',
		'nome' => 'Primi Calci',
		'intro'=> 'Con i Primi Calci si comincia a giocare davvero. I bambini affinano la coordinazione, scoprono i primi fondamentali del calcio e vivono le loro prime partite: emozioni indimenticabili, sempre vissute con entusiasmo.',
		'cosa' => array(
			'Sviluppo delle capacit&agrave; motorie di base attraverso il gioco del calcio.',
			'Primi fondamentali: guida della palla, passaggio, tiro.',
			'Partite e piccoli tornei per imparare il gioco di squadra.',
		),
	),
	array(
		'eta'  => '9-10 anni',
		'nome' => 'Pulcini',
		'intro'=> 'Con i Pulcini il calcio prende forma. I ragazzi affinano la tecnica individuale, imparano a leggere il gioco e affrontano i primi tornei ufficiali: crescono cos&igrave; i calciatori granata di domani.',
		'cosa' => array(
			'Consolidamento dei fondamentali tecnici e primi principi tattici.',
			'Gioco di squadra e responsabilit&agrave; del proprio ruolo in campo.',
			'Partecipazione a campionati e tornei giovanili del territorio.',
		),
	),
);
?>
<div class="page-hero">
	<div class="container">
		<div class="breadcrumb"><a style="color:var(--oro)" href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &rsaquo; <a style="color:var(--oro)" href="<?php echo esc_url( home_url( '/squadre/' ) ); ?>">Squadre</a> &rsaquo; Settore Giovanile</div>
		<h1>Settore Giovanile</h1>
	</div>
</div>

<div class="entry-content">
	<p>Il settore giovanile &egrave; il cuore dell&rsquo;A.S.D. Calcio Pieris. Accompagniamo bambini e bambine passo dopo passo, dai primi calci fino alle soglie della Prima Squadra, rispettando i tempi di ciascuno e mettendo al centro il <strong>divertimento, il rispetto e lo spirito di squadra</strong>.</p>
	<p>Il nostro percorso formativo &egrave; suddiviso in tre categorie per fasce d&rsquo;et&agrave;, ognuna con obiettivi e attivit&agrave; pensati per la crescita dei pi&ugrave; piccoli.</p>

	<div class="giovanili-list">
		<?php foreach ( $categorie as $i => $c ) : ?>
		<section class="card giovanile-block">
			<div class="giovanile-head">
				<h2><?php echo esc_html( $c['nome'] ); ?></h2>
				<span class="squadra-eta"><?php echo esc_html( $c['eta'] ); ?></span>
			</div>
			<p><?php echo wp_kses_post( $c['intro'] ); ?></p>
			<h3>Cosa facciamo</h3>
			<ul>
				<?php foreach ( $c['cosa'] as $item ) : ?>
				<li><?php echo wp_kses_post( $item ); ?></li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php endforeach; ?>
	</div>

	<section class="prova-cta" id="vuoi-giocare">
			<h2>Vuoi iniziare a giocare?</h2>
			<p><strong>Vieni a provare un allenamento gratuito e senza impegno!</strong> Ti aspettiamo al campo sportivo di <strong>Via Anna Frank a Pieris</strong> <strong>ogni marted&igrave; e gioved&igrave;, dalle 17:00 alle 18:30</strong>. Porta scarpe da ginnastica (o da calcio) e tanta voglia di divertirti: i nostri istruttori ti accoglieranno e ti faranno entrare subito in gioco.</p>
			<p>Le iscrizioni sono aperte tutto l&rsquo;anno. Per informazioni scrivici dalla pagina <a href="<?php echo esc_url( home_url( '/contatti/' ) ); ?>">Contatti</a> o passa direttamente al campo negli orari di prova.</p>
			<a class="btn btn-granata" href="<?php echo esc_url( home_url( '/contatti/' ) ); ?>">Contattaci</a>
		</section>

		<?php echo cp_staff_tecnico_html( 'giovanile' ); ?>

		<h2>Cosa offriamo</h2>
	<p>Un percorso serio e completo, pensato per far crescere ogni ragazzo come persona e come atleta.</p>
	<div class="offriamo-grid">
		<?php
		$offriamo = array(
			array( 'Staff qualificato', 'Allenatori qualificati UEFA, in formazione continua.', '<path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>' ),
			array( 'Programmazione tecnica', 'Percorsi formativi strutturati e mirati per ogni et&agrave; e livello.', '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>' ),
			array( 'Attenzione al ragazzo', 'Ogni atleta &egrave; unico: lo accompagniamo nella sua crescita, con ascolto e rispetto.', '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>' ),
			array( 'Percorsi per et&agrave;', 'Allenamenti calibrati per le tre categorie della scuola calcio, per un percorso unico e completo.', '<path d="M4 20V4M4 4h11l-2 3 2 3H4"/>' ),
			array( 'Tornei ed esperienze', 'Partecipiamo a tornei ed eventi per crescere e confrontarsi.', '<path d="M6 9H4a2 2 0 0 1-2-2V5h4M18 9h2a2 2 0 0 0 2-2V5h-4M6 4h12v5a6 6 0 0 1-12 0zM9 20h6M12 15v5"/>' ),
			array( 'Campo e ambiente sicuro', 'Impianti curati e sicuri, dove sentirsi a casa.', '<path d="M12 2 4 5v6c0 5 3.5 8 8 11 4.5-3 8-6 8-11V5z"/><path d="m9 12 2 2 4-4"/>' ),
			array( 'Ambiente familiare', 'Societ&agrave; di volontariato puro, vicina alle famiglie e al territorio.', '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9v12h14V9"/><path d="M9 21v-6h6v6"/>' ),
		);
		foreach ( $offriamo as $o ) : ?>
		<div class="card offriamo-card">
			<span class="offriamo-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $o[2]; ?></svg></span>
			<div>
				<h4><?php echo esc_html( $o[0] ); ?></h4>
				<p><?php echo wp_kses_post( $o[1] ); ?></p>
			</div>
		</div>
		<?php endforeach; ?>
	</div>

	<h2>Le skills che alleniamo</h2>
	<p>Oltre alla tecnica, alleniamo le competenze che servono in campo e nella vita.</p>
	<div class="skills-grid">
		<?php
		$skills = array(
			array( 'Velocit&agrave; mentale', 'Leggere il gioco, anticipare le situazioni, prendere decisioni e scegliere rapidamente la soluzione migliore.', '<path d="M12 5a3 3 0 0 0-3 3 3 3 0 0 0-1 5.8V16a2 2 0 0 0 4 0M12 5a3 3 0 0 1 3 3 3 3 0 0 1 1 5.8V16a2 2 0 0 1-4 0M12 5V3"/>' ),
			array( 'Apprendimento del gioco', 'Progressioni Open Skills e situazioni di gioco guidate, per sviluppare comprensione, scelta e lettura del gioco.', '<path d="M2 5h7a3 3 0 0 1 3 3v11a2.5 2.5 0 0 0-2.5-2.5H2zM22 5h-7a3 3 0 0 0-3 3v11a2.5 2.5 0 0 1 2.5-2.5H22z"/>' ),
			array( 'Coordinazione e atletismo', 'Movimento, agilit&agrave;, equilibrio e resistenza per migliorare le prestazioni in campo.', '<circle cx="13" cy="4" r="2"/><path d="m8 21 2-5 3 2 1 3M10 16l-2-4 4-2 3 3 3 1M6 9l3-1"/>' ),
			array( 'Mentalit&agrave;', 'Determinazione, concentrazione e gestione delle emozioni per non mollare mai.', '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>' ),
			array( 'Comunicazione', 'Parlare, ascoltare, incoraggiarsi: si vince insieme.', '<path d="M8 10h8M8 14h5M21 12a8 8 0 0 1-11.5 7.2L3 21l1.8-6.5A8 8 0 1 1 21 12z"/>' ),
			array( 'Autostima', 'Ogni piccolo passo rende pi&ugrave; sicuri, dentro e fuori dal campo.', '<path d="m12 3 2.6 5.3 5.9.9-4.2 4.1 1 5.8-5.3-2.8-5.3 2.8 1-5.8-4.2-4.1 5.9-.9z"/>' ),
		);
		foreach ( $skills as $s ) : ?>
		<div class="card skill-card">
			<span class="skill-icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $s[2]; ?></svg></span>
			<h4><?php echo wp_kses_post( $s[0] ); ?></h4>
			<p><?php echo wp_kses_post( $s[1] ); ?></p>
		</div>
		<?php endforeach; ?>
	</div>

</div>

<?php get_footer();
