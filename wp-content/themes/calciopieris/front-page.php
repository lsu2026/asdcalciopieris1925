<?php
/**
 * Homepage istituzionale — A.S.D. Calcio Pieris 1925.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>

<section class="hero">
	<div class="container">
		<div class="hero-badge"><?php calciopieris_badge(); ?></div>
		<div class="hero-copy">
			<div class="kicker">Dal 1925 &middot; Pieris &middot; San Canzian d&rsquo;Isonzo</div>
			<h1>A.S.D. Calcio <span>Pieris 1925</span></h1>
			<p>Cento anni di storia nel cuore della Bisiacaria e un secolo nuovo tutto da giocare: un futuro da costruire insieme, come squadra, uniti dalla passione per il calcio che nasce dal sorriso.</p>
			<div class="hero-actions">
				<a class="btn btn-oro" href="<?php echo esc_url( home_url( '/settore-giovanile/#vuoi-giocare' ) ); ?>">Gioca con noi</a>
				<a class="btn btn-outline" style="border-color:var(--oro);color:var(--bianco)" href="<?php echo esc_url( home_url( '/la-nostra-storia/' ) ); ?>">La nostra storia</a>
			</div>
		</div>
	</div>
</section>

<section class="sponsor-marquee" aria-label="I nostri sponsor">
	<div class="sponsor-note">I nostri sponsor</div>
	<div class="sponsor-track">
		<?php
		$dir = get_template_directory_uri() . '/assets/sponsors/';
		$sponsors = function_exists( 'cp_get_sponsors' ) ? cp_get_sponsors() : array(
			array( 'name' => 'Medicenter',            'url' => 'https://medicentercliniche.it/',   'logo' => $dir . 'medicenter.webp' ),
			array( 'name' => 'Conit',                 'url' => 'https://www.conit.org/it/',        'logo' => $dir . 'conit.svg' ),
			array( 'name' => 'G.S.A. Interiors',      'url' => '',                                 'logo' => '' ),
			array( 'name' => 'Ottica Russi',          'url' => 'https://otticarussi.it/',          'logo' => $dir . 'ottica-russi.svg' ),
			array( 'name' => 'H2O Termotecnica',      'url' => 'https://h2otermotecnica.it/',      'logo' => $dir . 'h2o.png' ),
			array( 'name' => 'BCC Venezia Giulia',    'url' => 'https://www.bccveneziagiulia.it/', 'logo' => $dir . 'bcc.webp' ),
			array( 'name' => 'Eye Store',             'url' => 'https://www.eyesportshop.com/it/', 'logo' => $dir . 'eyestore.jpg' ),
		);
		foreach ( $sponsors as $sp ) :
			$logo = ! empty( $sp['logo'] ) ? $sp['logo'] : '';
			$inner = $logo
				? '<img class="sponsor-logo" src="' . esc_url( $logo ) . '" alt="' . esc_attr( $sp['name'] ) . '">'
				: '<span class="sponsor-name">' . esc_html( $sp['name'] ) . '</span>';
			if ( ! empty( $sp['url'] ) ) {
				echo '<a class="sponsor-item" href="' . esc_url( $sp['url'] ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( $sp['name'] ) . '">' . $inner . '</a>';
			} else {
				echo '<span class="sponsor-item" aria-label="' . esc_attr( $sp['name'] ) . '">' . $inner . '</span>';
			}
		endforeach;
		?>
	</div>
</section>

<section class="section">
	<div class="container">
		<div class="section-head">
			<div class="overline">La nostra identit&agrave;</div>
			<h2>I valori del Pieris</h2>
			<p>Quello in cui crediamo, dentro e fuori dal campo.</p>
		</div>
		<div class="grid grid-4">
			<div class="card valore-card">
				<div class="icona"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 11v8m0-8 4-7a2 2 0 0 1 3 2l-1 5h6a2 2 0 0 1 2 2.3l-1.4 6A2 2 0 0 1 17.6 21H7m0-10H4a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h3"/></svg></div>
				<h3>Rispetto</h3>
				<p>Per i compagni, gli avversari, gli arbitri e chi rende possibile ogni partita.</p>
			</div>
			<div class="card valore-card">
				<div class="icona"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
				<h3>Squadra</h3>
				<p>Nessuno vince da solo: il gruppo viene sempre prima del singolo.</p>
			</div>
			<div class="card valore-card">
				<div class="icona"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div>
				<h3>Impegno</h3>
				<p>Allenarsi con costanza e serietà, crescendo anche attraverso l&rsquo;errore.</p>
			</div>
			<div class="card valore-card">
				<div class="icona"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/></svg></div>
				<h3>Divertimento</h3>
				<p>Il calcio è prima di tutto un gioco: la passione nasce dal sorriso.</p>
			</div>
			<div class="card valore-card">
				<div class="icona"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M9 22V12h6v10"/></svg></div>
				<h3>Famiglia</h3>
				<p>Una società che accoglie atleti, genitori e tifosi come una grande famiglia.</p>
			</div>
			<div class="card valore-card">
				<div class="icona"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
				<h3>Territorio</h3>
				<p>Radicati a Pieris e nella Bisiacaria, al servizio della nostra comunit&agrave;.</p>
			</div>
			<div class="card valore-card">
				<div class="icona"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 15 8l7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z"/></svg></div>
				<h3>Professionalit&agrave;</h3>
				<p>Organizzazione seria e staff preparato, a ogni livello e per ogni et&agrave;.</p>
			</div>
			<div class="card valore-card">
				<div class="icona"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22c4-2 8-5.5 8-11V5l-8-3-8 3v6c0 5.5 4 9 8 11z"/></svg></div>
				<h3>Appartenenza</h3>
				<p>Indossare il granata significa portare con orgoglio cento anni di storia.</p>
			</div>
		</div>
	</div>
</section>

<section class="mission-band">
	<div class="container">
		<div class="mission-overline">La nostra Mission</div>
		<p class="mission-claim">Formare prima <span>persone</span>, poi giovani <span>calciatori</span>.</p>
		<p class="mission-sub">Un percorso di crescita fatto di rispetto, impegno e divertimento, dove ogni bambino &egrave; accompagnato passo dopo passo, dentro e fuori dal campo.</p>
		<div class="mission-slogan">
			<span>Gioca</span><i>&bull;</i><span>Impara</span><i>&bull;</i><span>Cresci</span><i>&bull;</i><span class="oro">Insieme!</span>
		</div>
		<div class="mission-actions">
			<a class="btn btn-oro" href="<?php echo esc_url( home_url( '/settore-giovanile/' ) ); ?>">Scopri il Settore Giovanile</a>
			<a class="btn btn-ghost" href="<?php echo esc_url( home_url( '/prima-squadra/' ) ); ?>">Scopri la Prima Squadra</a>
		</div>
	</div>
</section>

<section class="section">
	<div class="container">
		<div class="section-head">
			<div class="overline">Dove siamo e contatti</div>
			<h2>Vieni al campo</h2>
		</div>
		<div class="info-strip">
			<div class="card">
				<div class="icona"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
				<div><h4>La sede</h4><p>Via Anna Frank 3, Pieris<br>San Canzian d&rsquo;Isonzo (GO)</p></div>
			</div>
			<div class="card">
				<div class="icona"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
				<div><h4>Telefono</h4><p><a href="tel:+393346684760">+39 334 6684760</a></p></div>
			</div>
			<div class="card">
				<div class="icona"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg></div>
				<div><h4>Email</h4><p><a href="mailto:asdcalciopieris1925@gmail.com">asdcalciopieris1925@gmail.com</a></p></div>
			</div>
		</div>
		<div class="cta-band" style="margin-top:36px;">
			<div>
				<h2>Gioca per il <span>Pieris!</span></h2>
				<p>Le iscrizioni alla stagione sportiva sono aperte: vieni a trovarci al campo o scrivici per informazioni sui tesseramenti.</p>
			</div>
			<a class="btn btn-oro" href="<?php echo esc_url( home_url( '/settore-giovanile/#vuoi-giocare' ) ); ?>">Vieni a provare</a>
		</div>
	</div>
</section>

<section class="section section-alt" id="news">
	<div class="container">
		<div class="section-head">
			<div class="overline">Ultime notizie</div>
			<h2>News dal Pieris</h2>
		</div>
		<div class="news-dots" id="cp-news-dots" aria-hidden="true"></div>
		<div class="news-carousel">
		<button class="news-nav news-nav--prev" type="button" aria-label="Indietro">&lsaquo;</button>
		<div class="news-embeds-row" id="cp-news-row">
			<?php
			/* Prima si prova il feed di Facebook: e' quello che si aggiorna da
			   solo. Se non c'e' nulla da mostrare - plugin spento, Pagina non
			   collegata, nessun post - si ripiega sulle news scritte a mano,
			   cosi' la sezione non resta mai vuota. */
			$cp_feed_home = function_exists( 'cp_feed_facebook' ) ? cp_feed_facebook( 'Pieris - Home (ultimi 5)' ) : '';
			if ( $cp_feed_home ) {
				echo $cp_feed_home; // gia' ripulito dal plugin
			} else {
			$news = new WP_Query( array( 'posts_per_page' => 6, 'ignore_sticky_posts' => true ) );
			if ( $news->have_posts() ) :
				while ( $news->have_posts() ) : $news->the_post();
					$cp_content  = get_the_content();
					$cp_is_embed = ( '1' === get_post_meta( get_the_ID(), '_cpemb', true ) ) || has_shortcode( $cp_content, 'pieris_fb_embed' );
					if ( $cp_is_embed ) :
			?>
			<div class="news-embed-item"><?php echo do_shortcode( $cp_content ); ?></div>
			<?php else : ?>
			<article class="card news-card">
				<?php if ( has_post_thumbnail() ) : ?>
				<a class="news-thumb" href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium_large' ); ?></a>
				<?php endif; ?>
				<div class="news-body">
					<div class="news-meta"><?php echo esc_html( get_the_date() ); ?></div>
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
					<a class="leggi" href="<?php the_permalink(); ?>">Leggi tutto &rarr;</a>
				</div>
			</article>
			<?php endif; endwhile; wp_reset_postdata(); endif;
			} /* fine del ripiego sulle news scritte a mano */ ?>
			</div>
			<button class="news-nav news-nav--next" type="button" aria-label="Scorri avanti">&rsaquo;</button>
		</div><!-- /.news-carousel -->
		<script>
		(function(){
			var row = document.getElementById('cp-news-row');
			if ( ! row ) return;
			var prev = document.querySelector('.news-nav--prev');
			var next = document.querySelector('.news-nav--next');
			var dotsWrap = document.getElementById('cp-news-dots');
			/* .cff-item sono i post che arrivano da Facebook: il CSS appiattisce
			   i contenitori del plugin, quindi qui si comportano come gli altri */
			var items = Array.prototype.slice.call( row.querySelectorAll('.news-embed-item, .news-card, .cff-item') );
			if ( ! items.length ) return;
			var page = 0, perPage = 1, pages = 1, animating = false;

			function calcPerPage(){ return window.innerWidth >= 1040 ? 2 : 1; }

			function apply(){
				var start = page * perPage, end = start + perPage;
				for ( var i = 0; i < items.length; i++ ) {
					items[i].style.display = ( i >= start && i < end ) ? '' : 'none';
				}
			}

			function show( fade ){
				if ( fade ) {
					animating = true;
					row.style.opacity = '0';
					setTimeout(function(){ apply(); row.style.opacity = '1'; setTimeout(function(){ animating = false; }, 320); }, 300);
				} else {
					apply(); row.style.opacity = '1';
				}
			}

			function buildDots(){
				if ( ! dotsWrap ) return;
				dotsWrap.innerHTML = '';
				if ( pages <= 1 ) { dotsWrap.style.display = 'none'; return; }
				dotsWrap.style.display = '';
				for ( var i = 0; i < pages; i++ ) {
					var b = document.createElement('button');
					b.type = 'button'; b.className = 'news-dot';
					b.setAttribute('aria-label', 'Vai al gruppo ' + ( i + 1 ));
					(function( idx ){ b.addEventListener('click', function(){ gotoPage( idx ); }); })( i );
					dotsWrap.appendChild( b );
				}
			}

			function updateControls(){
				if ( dotsWrap ) {
					var ds = dotsWrap.children;
					for ( var i = 0; i < ds.length; i++ ) { ds[i].className = 'news-dot' + ( i === page ? ' is-active' : '' ); }
				}
				var multi = pages > 1;
				if ( prev ) { prev.hidden = ! multi; prev.disabled = ( page <= 0 ); }
				if ( next ) { next.hidden = ! multi; next.disabled = ( page >= pages - 1 ); }
			}

			function gotoPage( p ){
				if ( p < 0 ) p = 0; if ( p > pages - 1 ) p = pages - 1;
				if ( p === page || animating ) { updateControls(); return; }
				page = p; show( true ); updateControls();
			}

			function go( dir ){
				if ( animating ) return;
				var np = page + dir;
				if ( np < 0 || np > pages - 1 ) return;   // niente wrap: ai bordi non fa nulla
				page = np; show( true ); updateControls();
			}

			if ( prev ) prev.addEventListener('click', function(){ go( -1 ); });
			if ( next ) next.addEventListener('click', function(){ go( 1 ); });

			var rt;
			window.addEventListener('resize', function(){ clearTimeout( rt ); rt = setTimeout( layout, 200 ); });

			function layout(){
				perPage = calcPerPage();
				pages = Math.ceil( items.length / perPage );
				if ( page > pages - 1 ) page = pages - 1;
				if ( page < 0 ) page = 0;
				buildDots();
				show( false );
				updateControls();
			}

			layout();
		})();
		</script>
	</div>
</section>

<?php /* Store ufficiali: richiamo alla pagina Store. Sono due e stanno sullo stesso
   piano, quindi la fascia mostra una scheda per ciascuno invece di dare risalto
   a uno solo. L'elenco arriva da cp_stores() in functions.php. */ ?>
<?php $cp_stores = function_exists( 'cp_stores' ) ? cp_stores() : array(); ?>
<?php if ( ! empty( $cp_stores ) ) :
	$cp_nomi = wp_list_pluck( $cp_stores, 'name' );
	$cp_ultimo = array_pop( $cp_nomi );
	$cp_elenco = $cp_nomi ? implode( ', ', $cp_nomi ) . ' e ' . $cp_ultimo : $cp_ultimo;
	$cp_uno = ( 1 === count( $cp_stores ) );
	?>
<section class="store-band">
	<div class="container">
		<div class="store-band__inner">
			<div class="store-band__media">
				<span class="store-band__seal">Store<br><?php echo $cp_uno ? 'ufficiale' : 'ufficiali'; ?></span>
				<div class="store-band__cards<?php echo $cp_uno ? '' : ' store-band__cards--multi'; ?>">
					<?php foreach ( $cp_stores as $cp_s ) : ?>
						<a class="store-band__card" href="<?php echo esc_url( $cp_s['url'] ); ?>" target="_blank" rel="noopener">
							<?php if ( ! empty( $cp_s['logo'] ) ) : ?>
								<img src="<?php echo esc_url( $cp_s['logo'] ); ?>" alt="<?php echo esc_attr( $cp_s['name'] ); ?> &mdash; store ufficiale del Calcio Pieris 1925" loading="lazy">
							<?php else : ?>
								<span class="store-band__word"><?php echo esc_html( $cp_s['name'] ); ?></span>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="store-band__text">
				<div class="store-band__over"><?php echo esc_html( $cp_elenco ); ?></div>
				<h2>Indossa i colori <span>granata</span></h2>
				<p>Divise da gara, abbigliamento da allenamento, borse e accessori del club: <?php echo $cp_uno ? 'lo store ufficiale' : 'gli store ufficiali'; ?> dell&rsquo;A.S.D. Calcio Pieris 1925 <?php echo $cp_uno ? '&egrave;' : 'sono'; ?> <?php echo esc_html( $cp_elenco ); ?>. Ogni acquisto sostiene direttamente l&rsquo;attivit&agrave; granata.</p>
				<ul class="store-band__tags">
					<li>Divise da gara</li>
					<li>Allenamento</li>
					<li>Merchandising</li>
				</ul>
				<?php /* Un solo pulsante, verso la pagina Store. I collegamenti diretti ai
				   due shop non spariscono: le schede con i loghi qui accanto sono
				   gia' cliccabili e portano ciascuna alla propria sezione granata. */ ?>
				<div class="store-band__actions">
					<a class="btn btn-oro" href="<?php echo esc_url( home_url( '/store/' ) ); ?>">Scopri lo Store</a>
				</div>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>

<section class="section">
	<div class="container">
		<div class="section-head">
			<div class="overline">1925 &ndash; 2025</div>
			<h2>Un secolo di storia granata</h2>
			<p>Le tappe principali di cento anni di calcio a Pieris.</p>
		</div>
		<div class="timeline">
			<div class="timeline-item"><div class="anno">1925 &middot; La fondazione</div><p>Nasce il Calcio Pieris: il paese si stringe attorno alla sua squadra granata.</p></div>
			<div class="timeline-item"><div class="anno">1939-40 &middot; La Serie C</div><p>Secondo posto in Prima Divisione Venezia Giulia e promozione in Serie C.</p></div>
			<div class="timeline-item"><div class="anno">Anni &rsquo;40-&rsquo;50 &middot; L&rsquo;epoca d&rsquo;oro</div><p>Sei campionati consecutivi di Serie C. Da Pieris partono Fabio Capello, Giacomo Blason e Corrado Zorzin.</p></div>
			<div class="timeline-item"><div class="anno">1966-67 &middot; Campioni del Friuli</div><p>Il Pieris si laurea Campione Regionale FVG, rinunciando alla Serie D per mancanza di risorse.</p></div>
			<div class="timeline-item"><div class="anno">Oggi &middot; Il presente</div><p>La società prosegue il proprio cammino nei campionati dilettantistici regionali, custodendo una storia lunga un secolo.</p></div>
		</div>
		<p style="text-align:center;margin-top:20px;"><a class="btn btn-outline" href="<?php echo esc_url( home_url( '/la-nostra-storia/' ) ); ?>">Scopri tutta la storia</a></p>
	</div>
</section>

<section class="cinquexmille">
	<div class="container">
		<div class="cinquexmille-inner">
			<div class="cinquexmille-icon">
				<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/></svg>
			</div>
			<div class="cinquexmille-text">
				<div class="cinquexmille-over">Sostieni il tuo Pieris</div>
				<h2>Dona il tuo <span>5&times;1000</span></h2>
				<p>Non ti costa nulla e per noi vale tantissimo. Nella tua dichiarazione dei redditi firma nel riquadro dedicato alle associazioni sportive dilettantistiche e indica il nostro codice fiscale.</p>
			</div>
			<div class="cinquexmille-cf">
				<span>Codice Fiscale</span>
				<strong>01209310315</strong>
			</div>
		</div>
	</div>
</section>

<?php get_footer(); ?>
