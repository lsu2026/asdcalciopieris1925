/**
 * Banda sponsor: scorrimento orizzontale continuo (marquee) senza scatti.
 * Motore basato su requestAnimationFrame con offset frazionario e wrapping continuo:
 * non esiste il "reset" di fine keyframe, quindi nessun salto periodico.
 * Duplica i loghi quanto basta per riempire lo schermo e misura la distanza di loop
 * dalla posizione reale del 2° gruppo. Pausa su hover / tab in background.
 * Rispetta prefers-reduced-motion.
 */
(function () {
	'use strict';
	var marquee = document.querySelector('.sponsor-marquee');
	if (!marquee) return;
	var track = marquee.querySelector('.sponsor-track');
	if (!track) return;
	if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

	var original = track.innerHTML;
	var SPEED = 70;      // px al secondo
	var move = 0;        // periodo di ripetizione (px, frazionario)
	var offset = 0;
	var last = null;
	var raf = null;
	var paused = false;
	var lastVW = -1;

	function layout() {
		track.style.animation = 'none';           // disattiva l'eventuale animazione CSS
		track.style.transform = 'translate3d(0,0,0)';
		// IMPORTANTE: attiva subito nowrap (is-scrolling) così le misure sono su UNA riga,
		// altrimenti in flex-wrap gli item vanno a capo e offsetLeft/scrollWidth sono errati.
		track.classList.add('is-scrolling');
		track.style.willChange = 'transform';
		track.style.backfaceVisibility = 'hidden';

		track.innerHTML = original;
		var itemsPerSet = track.children.length;
		if (itemsPerSet === 0) return false;
		var setWidth = track.scrollWidth;
		if (setWidth <= 0) return false;

		var vw = marquee.clientWidth;
		lastVW = vw;
		var k = Math.max(1, Math.ceil((vw + 2) / setWidth));

		var groupHTML = '';
		for (var i = 0; i < k; i++) groupHTML += original;
		track.innerHTML = groupHTML + groupHTML;

		var kids = track.children;
		var second = kids[itemsPerSet * k];
		if (!second) return false;
		move = second.offsetLeft - kids[0].offsetLeft;   // distanza esatta (una riga)
		if (move <= 0) return false;

		offset = 0;
		return true;
	}

	function frame(ts) {
		raf = requestAnimationFrame(frame);
		if (paused) { last = ts; return; }
		if (last === null) { last = ts; return; }
		var dt = (ts - last) / 1000;
		last = ts;
		if (dt > 0.1) dt = 0.1;                 // evita balzi dopo tab in background
		offset += SPEED * dt;
		if (offset >= move) offset -= move * Math.floor(offset / move);
		track.style.transform = 'translate3d(' + (-offset) + 'px,0,0)';
	}

	function start() {
		if (!layout()) return;
		last = null;
		if (!raf) raf = requestAnimationFrame(frame);
	}

	function ready(cb) {
		var pending = [];
		var imgs = track.querySelectorAll('img');
		for (var i = 0; i < imgs.length; i++) {
			var img = imgs[i];
			if (!img.complete) {
				pending.push(new Promise(function (res) {
					img.addEventListener('load', res, { once: true });
					img.addEventListener('error', res, { once: true });
				}));
			}
		}
		if (document.fonts && document.fonts.ready) pending.push(document.fonts.ready);
		var done = false;
		var run = function () { if (!done) { done = true; cb(); } };
		if (pending.length && window.Promise) {
			Promise.all(pending).then(run);
			setTimeout(run, 3000);
		} else {
			run();
		}
	}

	ready(start);

	marquee.addEventListener('mouseenter', function () { paused = true; });
	marquee.addEventListener('mouseleave', function () { paused = false; last = null; });
	document.addEventListener('visibilitychange', function () { last = null; });

	var t;
	window.addEventListener('resize', function () {
		clearTimeout(t);
		t = setTimeout(function () { if (marquee.clientWidth !== lastVW) start(); }, 250);
	});
})();
