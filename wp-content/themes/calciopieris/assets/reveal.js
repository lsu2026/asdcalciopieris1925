/**
 * Calcio Pieris 1925 — animazioni di comparsa allo scroll.
 *
 * Gli elementi ricevono la classe .reveal via JS (senza JS restano visibili) e
 * .is-visible quando entrano nello schermo. Le card fratelle sono sfalsate.
 *
 * Il controllo e' fatto a mano su scroll/resize invece che con IntersectionObserver:
 * la soglia percentuale dell'observer e' una frazione dell'AREA DELL'ELEMENTO, quindi
 * su una sezione piu' alta dello schermo puo' essere irraggiungibile e l'elemento
 * resta a opacity 0 per sempre (la sezione "Classifica e risultati", alta oltre
 * 5000px, spariva del tutto sui telefoni con schermo corto). Con un controllo
 * esplicito sulla posizione il comportamento e' lo stesso per tutti gli elementi,
 * indipendentemente dalla loro altezza.
 *
 * Sicurezza: qualunque cosa vada storta, dopo il caricamento tutto cio' che e' gia'
 * stato superato viene mostrato comunque. Nessun contenuto puo' restare invisibile.
 */
(function () {
	'use strict';

	if ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		return;
	}

	var selectors = [
		'.section-head',
		'.grid .card',
		'.info-strip .card',
		'.timeline-item',
		'.cta-band',
		'.store-band__media',
		'.store-band__text',
		'.kit-grid .kit-card',
		'.hero-badge',
		'.hero-copy',
		'.page-hero h1',
		'.entry-content > *'
	];

	/* Esclusi:
	   - i tag senza resa visiva che gli shortcode mettono in .entry-content;
	   - la sezione "Classifica e risultati": e' alta migliaia di pixel, farla
	     comparire in blocco non ha senso e ogni ritardo la fa sembrare mancante. */
	function daAnimare( el ) {
		if ( /^(STYLE|SCRIPT|LINK|TEMPLATE|META|NOSCRIPT)$/.test( el.tagName ) ) { return false; }
		if ( el.classList.contains( 'cp-ps' ) || el.querySelector( '.cp-slides-viewport' ) ) { return false; }
		return true;
	}

	var elements = Array.prototype.filter.call(
		document.querySelectorAll( selectors.join( ',' ) ),
		daAnimare
	);

	if ( ! elements.length ) { return; }

	// Sfalsa gli elementi che condividono lo stesso genitore (effetto cascata)
	var siblingIndex = new Map();
	elements.forEach( function ( el ) {
		var parent = el.parentNode;
		var i = siblingIndex.get( parent ) || 0;
		el.style.transitionDelay = Math.min( i * 90, 540 ) + 'ms';
		siblingIndex.set( parent, i + 1 );
		el.classList.add( 'reveal' );
	} );

	var restanti = elements.slice();
	var inCoda = false;

	function mostra( el ) {
		el.classList.add( 'is-visible' );
	}

	/** Mostra tutto cio' che ha il bordo superiore entro lo schermo (meno un margine). */
	function controlla() {
		inCoda = false;
		var limite = ( window.innerHeight || document.documentElement.clientHeight ) - 80;
		for ( var i = restanti.length - 1; i >= 0; i-- ) {
			var el = restanti[ i ];
			var r = el.getBoundingClientRect();
			// Volutamente NON si richiede che l'elemento sia ancora sullo schermo: se una
			// scorrimento veloce lo supera fra due controlli, deve comparire lo stesso.
			// Basta che il suo bordo superiore abbia passato la soglia.
			if ( r.top < limite ) {
				mostra( el );
				restanti.splice( i, 1 );
			}
		}
		if ( ! restanti.length ) { stacca(); }
	}

	function programma() {
		if ( inCoda ) { return; }
		inCoda = true;
		if ( window.requestAnimationFrame ) { window.requestAnimationFrame( controlla ); }
		// Rete di sicurezza: se il browser non produce fotogrammi (scheda in secondo
		// piano, risparmio energetico, alcuni ambienti automatizzati) il callback del
		// requestAnimationFrame puo' non essere mai eseguito e nulla comparirebbe piu.
		// controlla() e ripetibile senza effetti collaterali.
		setTimeout( controlla, 200 );
	}

	/* Battito di sicurezza: gli eventi di scorrimento possono mancare (posizione
	   gia' a fondo pagina, scatti programmatici, comportamenti particolari di alcuni
	   browser). Un controllo periodico garantisce che nulla resti invisibile; si spegne
	   da solo appena tutti gli elementi sono comparsi, quindi non pesa. */
	var battito = setInterval( controlla, 400 );

	function stacca() {
		clearInterval( battito );
		window.removeEventListener( 'scroll', programma );
		window.removeEventListener( 'resize', programma );
		window.removeEventListener( 'orientationchange', programma );
	}

	window.addEventListener( 'scroll', programma, { passive: true } );
	window.addEventListener( 'resize', programma );
	window.addEventListener( 'orientationchange', programma );
	window.addEventListener( 'load', function () {
		controlla();
		// la pagina puo' crescere dopo il load (immagini, embed): ricontrollo
		setTimeout( controlla, 400 );
		setTimeout( controlla, 1500 );
	} );

	controlla();
})();
