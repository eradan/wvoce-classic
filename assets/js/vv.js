/*
 * VivaVoce: testata che si stringe, switcher del tema, pulsanti "Copia" (CF, P.IVA, IBAN), spazio per il
 * banner dei cookie, "torna su" che sale con la fascia del footer, menu mobile, agenda a lotti, caroselli.
 * I moduli delle iscrizioni sono del plugin wvoce-iscrizioni.
 */
(function () {
	'use strict';

	var root = document.documentElement;

	// Una chiamata per fotogramma al massimo, per scroll e resize.
	var perFrame = function (fn) {
		var raf = 0;
		return function () {
			if (!raf) {
				raf = requestAnimationFrame(function () {
					raf = 0;
					fn();
				});
			}
		};
	};

	// Testata: dopo aver fatto scorrere si stringe (header.css). Isteresi fra 80 e 10px: restringendosi
	// accorcia la testata, e con una soglia sola andrebbe avanti e indietro proprio lì.
	var ristretta = false;
	var syncTestata = function () {
		var y = window.scrollY;
		if (!ristretta && y > 80) {
			ristretta = true;
			document.body.classList.add('vv-scrolled');
		} else if (ristretta && y < 10) {
			ristretta = false;
			document.body.classList.remove('vv-scrolled');
		}
	};
	window.addEventListener('scroll', perFrame(syncTestata), { passive: true });
	syncTestata();

	// Switcher del tema accanto alla lente, in entrambe le testate: mostra il tema in uso e passa
	// al successivo (chiaro → scuro → auto). Lo stato lo tiene vvTema() (functions.php, in testa alla pagina).
	// Dipende dal markup di Sydney: senza la ricerca nella testata (.header-search) lo switcher non c'è.
	if (window.vvTema) {
		var ordine = ['chiaro', 'scuro', 'auto'];
		var temi = {
			chiaro: { nome: 'Tema chiaro', icona: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>' },
			scuro: { nome: 'Tema scuro', icona: '<path d="M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5z"/>' },
			auto: { nome: 'Tema automatico, come il dispositivo', icona: '<circle cx="12" cy="12" r="8"/><path d="M12 4a8 8 0 0 1 0 16z" fill="currentColor"/>' }
		};
		var pulsantiTema = [];
		var dopo = function (scelta) {
			return ordine[(ordine.indexOf(scelta) + 1) % ordine.length];
		};
		var syncTema = function () {
			var scelta = root.getAttribute('data-vv-scelta');
			var label = temi[scelta].nome + '. Passa a: ' + temi[dopo(scelta)].nome.toLowerCase();
			pulsantiTema.forEach(function (btn) {
				btn.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' + temi[scelta].icona + '</svg>';
				btn.setAttribute('aria-label', label);
				btn.title = label;
			});
		};
		document.querySelectorAll('.header-elements .header-search').forEach(function (search) {
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'vv-tema';
			btn.addEventListener('click', function () {
				var scelta = window.vvTema(dopo(root.getAttribute('data-vv-scelta')));
				try {
					localStorage.setItem('vv-tema', scelta);
				} catch (e) {}
				syncTema();
			});
			search.parentNode.insertBefore(btn, search);
			pulsantiTema.push(btn);
		});
		syncTema();
	}

	// Pulsanti "Copia". Se il browser nega gli appunti il valore resta selezionato, pronto per Ctrl+C.
	// L'esito si legge anche con gli screen reader: cambia l'etichetta accessibile, non solo il testo.
	if (navigator.clipboard) {
		document.querySelectorAll('[data-vv-copy]').forEach(function (btn) {
			var testo = btn.textContent;
			var etichetta = btn.getAttribute('aria-label');
			var timer = 0;
			var avviso = function (esito) {
				clearTimeout(timer);
				btn.textContent = esito;
				btn.setAttribute('aria-label', esito);
				btn.classList.add('is-done');
				timer = setTimeout(function () {
					btn.textContent = testo;
					btn.setAttribute('aria-label', etichetta);
					btn.classList.remove('is-done');
				}, 1800);
			};
			btn.hidden = false;
			btn.addEventListener('click', function () {
				navigator.clipboard.writeText(btn.getAttribute('data-vv-copy')).then(function () {
					avviso('Copiato!');
				}, function () {
					var valore = btn.parentNode.querySelector('.vv-copy__value');
					if (valore) {
						var range = document.createRange();
						range.selectNodeContents(valore);
						window.getSelection().removeAllRanges();
						window.getSelection().addRange(range);
					}
					avviso('Selezionato');
				});
			});
		});
	}

	// Banner dei cookie: spazio in fondo alla pagina finché è aperto (plugins.css)
	var bannerCookie = document.getElementById('cookie-law-info-bar');
	if (bannerCookie) {
		var syncCookie = function () {
			var aperto = getComputedStyle(bannerCookie).display !== 'none' && bannerCookie.offsetHeight > 0;
			document.body.classList.toggle('vv-cookie-open', aperto);
			root.style.setProperty('--vv-cookie-h', aperto ? bannerCookie.offsetHeight + 'px' : '0px');
		};
		new MutationObserver(syncCookie).observe(bannerCookie, { attributes: true, attributeFilter: ['style', 'class'] });
		if (window.ResizeObserver) {
			new ResizeObserver(syncCookie).observe(bannerCookie);
		}
		window.addEventListener('load', syncCookie);
		syncCookie();
	}

	// Pulsante "torna su" e badge di reCAPTCHA: quanto della fascia turchese è in vista, perché salgano
	// con lei (footer.css). Si aggiorna solo quando cambia, cioè solo in fondo alla pagina.
	var fascia = document.querySelector('.site-footer .site-info');
	if (fascia) {
		var sotto = -1;
		var syncSotto = function () {
			var h = Math.max(0, Math.round(window.innerHeight - fascia.getBoundingClientRect().top));
			if (h !== sotto) {
				sotto = h;
				root.style.setProperty('--vv-sotto', h + 'px');
			}
		};
		var syncSottoPerFrame = perFrame(syncSotto);
		window.addEventListener('scroll', syncSottoPerFrame, { passive: true });
		window.addEventListener('resize', syncSottoPerFrame);
		// Le immagini caricate dopo allungano la pagina anche senza scroll.
		window.addEventListener('load', syncSottoPerFrame);
		syncSotto();
	}

	// Il "torna su" di Sydney è un <a> senza href: né nome né tastiera. Dopo il clic il focus va in
	// cima alla pagina, dove la pagina è tornata (Sydney fa solo lo scorrimento).
	var tornaSu = document.querySelector('.go-top');
	var pagina = document.getElementById('page');
	if (tornaSu) {
		tornaSu.setAttribute('role', 'button');
		tornaSu.setAttribute('tabindex', '0');
		tornaSu.setAttribute('aria-label', 'Torna in cima alla pagina');
		tornaSu.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				tornaSu.click();
			}
		});
		if (pagina) {
			tornaSu.addEventListener('click', function () {
				pagina.setAttribute('tabindex', '-1');
				pagina.focus({ preventScroll: true });
			});
		}
	}

	// Menu mobile (pannello di Sydney): Esc chiude, il pulsante del sottomenu dice se è aperto, il
	// sottomenu della sezione corrente è già aperto. Tastiera (Invio) e trappola del focus sono di Sydney.
	var pannello = document.querySelector('.sydney-offcanvas-menu');
	if (pannello) {
		document.addEventListener('keydown', function (e) {
			var chiudi = pannello.querySelector('.mobile-menu-close');
			if (e.key === 'Escape' && chiudi && document.body.classList.contains('mobile-menu-visible')) {
				chiudi.click();
			}
		});

		pannello.querySelectorAll('li.menu-item-has-children').forEach(function (li) {
			var sub = li.querySelector(':scope > .sub-menu');
			var toggle = li.querySelector(':scope > .dropdown-symbol');
			var link = li.querySelector(':scope > a');
			if (!sub || !toggle) {
				return;
			}
			toggle.setAttribute('role', 'button');
			toggle.setAttribute('aria-label', 'Sottomenu ' + (link ? link.textContent.trim() : ''));
			var sync = function () {
				toggle.setAttribute('aria-expanded', sub.classList.contains('toggled') ? 'true' : 'false');
			};
			if (li.classList.contains('current-menu-ancestor') || li.classList.contains('current-menu-parent')) {
				sub.classList.add('toggled');
			}
			new MutationObserver(sync).observe(sub, { attributes: true, attributeFilter: ['class'] });
			sync();
		});
	}

	// Agenda a lotti (data-vv-lotto="3"): si vedono le prime card, "Mostra altri" apre le successive un lotto
	// alla volta e porta il focus sulla prima appena comparsa; finite le card, il pulsante sparisce.
	document.querySelectorAll('[data-vv-lotto]').forEach(function (grid, n) {
		var lotto = parseInt(grid.getAttribute('data-vv-lotto'), 10) || 0;
		var cards = Array.prototype.slice.call(grid.children);
		if (lotto < 1 || cards.length <= lotto) {
			return;
		}
		cards.slice(lotto).forEach(function (card) {
			card.hidden = true;
		});
		grid.id = grid.id || 'vv-lotti-' + n;
		var riga = document.createElement('p');
		riga.className = 'vv-lotti';
		riga.innerHTML = '<button type="button" class="vv-button vv-button--ghost vv-lotti__altri" aria-controls="' + grid.id + '">Mostra altri appuntamenti</button>';
		grid.parentNode.insertBefore(riga, grid.nextSibling);

		riga.firstChild.addEventListener('click', function () {
			var nascoste = cards.filter(function (card) { return card.hidden; });
			nascoste.slice(0, lotto).forEach(function (card) {
				card.hidden = false;
			});
			var link = nascoste[0].querySelector('.vv-card__link');
			if (link) {
				link.focus();
			}
			if (nascoste.length <= lotto) {
				riga.remove();
			}
		});
	});

	// Caroselli: frecce precedente/successivo e contatore "1–3 di 18"
	var menoMovimento = window.matchMedia('(prefers-reduced-motion: reduce)');
	var svg = function (viewBox, contenuto) {
		return '<svg viewBox="' + viewBox + '" aria-hidden="true" focusable="false">' + contenuto + '</svg>';
	};

	document.querySelectorAll('[data-vv-carousel]').forEach(function (carousel) {
		var track = carousel.querySelector('.vv-grid--carosello');
		var cards = track ? track.children : [];
		if (!track || cards.length < 2) {
			return;
		}

		// Riga sotto il carosello: ‹ (pausa) contatore ›. Su schermo largo le frecce escono dalla riga e
		// stanno ai lati delle card (CSS); su telefono restano qui, dove non coprono le foto.
		var nav = document.createElement('div');
		nav.className = 'vv-carousel__nav';
		nav.innerHTML =
			'<div class="vv-carousel__bar">' +
			'<button type="button" class="vv-carousel__btn" data-dir="-1" aria-label="Contenuti precedenti">' + svg('0 0 24 24', '<path d="M15 5l-7 7 7 7"/>') + '</button>' +
			'<span class="vv-carousel__count" aria-live="polite"></span>' +
			'<button type="button" class="vv-carousel__btn" data-dir="1" aria-label="Contenuti successivi">' + svg('0 0 24 24', '<path d="M9 5l7 7-7 7"/>') + '</button>' +
			'</div>';
		carousel.insertBefore(nav, track);

		var count = nav.querySelector('.vv-carousel__count');
		var prev = nav.querySelector('[data-dir="-1"]');
		var next = nav.querySelector('[data-dir="1"]');
		var comportamento = function () {
			return menoMovimento.matches ? 'auto' : 'smooth';
		};

		function gap() {
			return parseFloat(getComputedStyle(track).columnGap) || 0;
		}

		// Larghezza di una card più lo spazio fra le card; 0 se il carosello è nascosto.
		function step() {
			return cards[0].getBoundingClientRect().width + gap();
		}

		// Card intere visibili
		function visibleCards() {
			return Math.max(1, Math.round((track.clientWidth + gap()) / step()));
		}

		function update() {
			var s = step();
			if (!s) {
				return;
			}
			var first = Math.round(track.scrollLeft / s);
			var last = Math.min(cards.length, first + visibleCards());
			prev.disabled = track.scrollLeft <= 2;
			next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
			count.textContent = (first + 1) + (last > first + 1 ? '–' + last : '') + ' di ' + cards.length;
			nav.hidden = prev.disabled && next.disabled;
		}

		track.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
				e.preventDefault();
				track.scrollBy({ left: (e.key === 'ArrowRight' ? 1 : -1) * step(), behavior: comportamento() });
			}
		});
		track.addEventListener('scroll', perFrame(update), { passive: true });
		window.addEventListener('resize', perFrame(update));
		update();

		// Scorrimento automatico (data-vv-autoplay): una card ogni 5 secondi, in fondo si riparte.
		// Si ferma con il mouse sopra, il focus dentro, il dito sul binario, fuori schermo e a scheda
		// nascosta; il pulsante lo ferma del tutto. Con "riduci movimento" non parte.
		var play = null;
		var sync = function () {};
		if (carousel.hasAttribute('data-vv-autoplay') && !menoMovimento.matches) {
			play = document.createElement('button');
			play.type = 'button';
			play.className = 'vv-carousel__btn vv-carousel__play';
			// Pausa prima del contatore
			count.parentNode.insertBefore(play, count);

			var stopped = false;
			var hovering = false;
			var focused = false;
			var touching = false;
			var visible = !('IntersectionObserver' in window);
			var timer = 0;
			var shown = null;

			var running = function () {
				return !stopped && !hovering && !focused && !touching && visible && !document.hidden;
			};
			var tick = function () {
				if (!running() || track.scrollWidth <= track.clientWidth + 2) {
					return;
				}
				var atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
				track.scrollTo({ left: atEnd ? 0 : track.scrollLeft + step(), behavior: 'smooth' });
			};
			sync = function () {
				clearInterval(timer);
				timer = running() ? setInterval(tick, 5000) : 0;
				// Mentre scorre da solo il contatore non si annuncia a ogni card.
				count.setAttribute('aria-live', running() ? 'off' : 'polite');
				// L'icona si ridisegna solo quando cambia: sostituirla fra pressione e rilascio del mouse
				// (il mouse che entra, il focus del clic) farebbe perdere il clic.
				if (shown !== stopped) {
					shown = stopped;
					play.innerHTML = stopped
						? svg('0 0 16 16', '<path d="M4 2l10 6-10 6z" fill="currentColor"/>')
						: svg('0 0 16 16', '<rect x="3" y="2" width="3.5" height="12" fill="currentColor"/><rect x="9.5" y="2" width="3.5" height="12" fill="currentColor"/>');
					play.setAttribute('aria-label', stopped ? 'Riprendi lo scorrimento automatico' : 'Ferma lo scorrimento automatico');
				}
			};
			var touchFine = function () { touching = false; sync(); };

			play.addEventListener('click', function () {
				stopped = !stopped;
				sync();
			});
			carousel.addEventListener('mouseenter', function () { hovering = true; sync(); });
			carousel.addEventListener('mouseleave', function () { hovering = false; sync(); });
			// Il focus ferma lo scorrimento, tranne quello sul pulsante di pausa (dopo "riprendi" deve ripartire)
			carousel.addEventListener('focusin', function (e) { focused = e.target !== play; sync(); });
			carousel.addEventListener('focusout', function (e) {
				focused = carousel.contains(e.relatedTarget) && e.relatedTarget !== play;
				sync();
			});
			track.addEventListener('touchstart', function () { touching = true; sync(); }, { passive: true });
			track.addEventListener('touchend', touchFine, { passive: true });
			track.addEventListener('touchcancel', touchFine, { passive: true });
			document.addEventListener('visibilitychange', sync);
			if ('IntersectionObserver' in window) {
				new IntersectionObserver(function (entries) {
					visible = entries[0].isIntersecting;
					sync();
				}, { threshold: 0.5 }).observe(track);
			}
			sync();
		}

		// Frecce: una schermata di card; con l'autoplay l'intervallo riparte da capo, così il salto
		// automatico non arriva subito dopo.
		nav.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-dir]');
			if (!btn || btn.disabled) {
				return;
			}
			track.scrollBy({ left: Number(btn.getAttribute('data-dir')) * step() * visibleCards(), behavior: comportamento() });
			sync();
		});
	});
})();
