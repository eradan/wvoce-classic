/* VivaVoce: comportamenti del tema. I moduli delle iscrizioni sono del plugin wvoce-iscrizioni. */
(function () {
	'use strict';

	var root = document.documentElement;

	var perFotogramma = function (fn) {
		var richiesta = 0;
		return function () {
			if (!richiesta) {
				richiesta = requestAnimationFrame(function () {
					richiesta = 0;
					fn();
				});
			}
		};
	};

	var svg = function (viewBox, contenuto) {
		return '<svg viewBox="' + viewBox + '" aria-hidden="true" focusable="false">' + contenuto + '</svg>';
	};

	// Testata che si stringe: due soglie, perché restringendosi accorcia la pagina e con una sola
	// andrebbe avanti e indietro proprio lì.
	var ristretta = false;
	var aggiornaTestata = function () {
		if (!ristretta && scrollY > 80) {
			ristretta = true;
		} else if (ristretta && scrollY < 10) {
			ristretta = false;
		}
		document.body.classList.toggle('vv-scrolled', ristretta);
	};
	addEventListener('scroll', perFotogramma(aggiornaTestata), { passive: true });
	aggiornaTestata();

	// Switcher del tema accanto alla lente: senza la ricerca di Sydney nella testata non compare.
	if (window.vvTema) {
		var temi = {
			chiaro: { nome: 'Tema chiaro', icona: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>' },
			scuro: { nome: 'Tema scuro', icona: '<path d="M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5z"/>' },
			auto: { nome: 'Tema automatico, come il dispositivo', icona: '<circle cx="12" cy="12" r="8"/><path d="M12 4a8 8 0 0 1 0 16z" fill="currentColor"/>' },
			natale: { nome: 'Tema natalizio', icona: '<circle cx="12" cy="14.5" r="7"/><path d="M10.5 7.5v-2h3v2M12 5.5V3M5.4 12.5h13.2M5.6 17h12.8"/>' }
		};
		var scelte = window.vvTema.scelte;
		var pulsantiTema = [];
		var dopo = function (scelta) {
			return scelte[(scelte.indexOf(scelta) + 1) % scelte.length];
		};
		var mostraTema = function () {
			var scelta = root.getAttribute('data-vv-scelta');
			var etichetta = temi[scelta].nome + '. Passa a: ' + temi[dopo(scelta)].nome.toLowerCase();
			pulsantiTema.forEach(function (pulsante) {
				pulsante.innerHTML = svg('0 0 24 24', temi[scelta].icona);
				pulsante.setAttribute('aria-label', etichetta);
				pulsante.title = etichetta;
			});
		};
		document.querySelectorAll('.header-elements .header-search').forEach(function (ricerca) {
			var pulsante = document.createElement('button');
			pulsante.type = 'button';
			pulsante.className = 'vv-tema';
			pulsante.addEventListener('click', function () {
				window.vvTema.scegli(dopo(root.getAttribute('data-vv-scelta')));
				mostraTema();
			});
			ricerca.before(pulsante);
			pulsantiTema.push(pulsante);
		});
		mostraTema();
	}

	// Pulsanti "Copia": se il browser nega gli appunti il valore resta selezionato, pronto per Ctrl+C.
	// L'esito cambia anche l'etichetta accessibile, che per gli screen reader prevale sul testo.
	if (navigator.clipboard) {
		document.querySelectorAll('[data-vv-copy]').forEach(function (pulsante) {
			var testo = pulsante.textContent;
			var etichetta = pulsante.getAttribute('aria-label');
			var attesa = 0;
			var avvisa = function (esito) {
				clearTimeout(attesa);
				pulsante.textContent = esito;
				pulsante.setAttribute('aria-label', esito);
				pulsante.classList.add('is-done');
				attesa = setTimeout(function () {
					pulsante.textContent = testo;
					pulsante.setAttribute('aria-label', etichetta);
					pulsante.classList.remove('is-done');
				}, 1800);
			};
			pulsante.hidden = false;
			pulsante.addEventListener('click', function () {
				navigator.clipboard.writeText(pulsante.getAttribute('data-vv-copy')).then(function () {
					avvisa('Copiato!');
				}, function () {
					getSelection().selectAllChildren(pulsante.parentNode.querySelector('.vv-copy__value'));
					avvisa('Selezionato');
				});
			});
		});
	}

	var bannerCookie = document.getElementById('cookie-law-info-bar');
	if (bannerCookie) {
		var aggiornaCookie = function () {
			var aperto = getComputedStyle(bannerCookie).display !== 'none' && bannerCookie.offsetHeight > 0;
			document.body.classList.toggle('vv-cookie-open', aperto);
			root.style.setProperty('--vv-cookie-h', aperto ? bannerCookie.offsetHeight + 'px' : '0px');
		};
		new MutationObserver(aggiornaCookie).observe(bannerCookie, { attributes: true, attributeFilter: ['style', 'class'] });
		new ResizeObserver(aggiornaCookie).observe(bannerCookie);
		addEventListener('load', aggiornaCookie);
		aggiornaCookie();
	}

	// Quanto della fascia del copyright è in vista: "torna su" e badge di reCAPTCHA salgono con lei.
	var fascia = document.querySelector('.site-footer .site-info');
	if (fascia) {
		var inVista = -1;
		var aggiornaFascia = function () {
			var altezza = Math.max(0, Math.round(innerHeight - fascia.getBoundingClientRect().top));
			if (altezza !== inVista) {
				inVista = altezza;
				root.style.setProperty('--vv-sotto', altezza + 'px');
			}
		};
		var aggiornaFasciaPerFotogramma = perFotogramma(aggiornaFascia);
		addEventListener('scroll', aggiornaFasciaPerFotogramma, { passive: true });
		addEventListener('resize', aggiornaFasciaPerFotogramma);
		addEventListener('load', aggiornaFasciaPerFotogramma);
		aggiornaFascia();
	}

	// Il "torna su" di Sydney è un <a> senza href: senza nome, senza tastiera e senza spostare il focus.
	var tornaSu = document.querySelector('.go-top');
	var pagina = document.getElementById('page');
	if (tornaSu && pagina) {
		tornaSu.setAttribute('role', 'button');
		tornaSu.setAttribute('tabindex', '0');
		tornaSu.setAttribute('aria-label', 'Torna in cima alla pagina');
		tornaSu.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				tornaSu.click();
			}
		});
		tornaSu.addEventListener('click', function () {
			pagina.setAttribute('tabindex', '-1');
			pagina.focus({ preventScroll: true });
		});
	}

	// Menu mobile di Sydney: Esc lo chiude, il pulsante del sottomenu dice se è aperto, la sezione
	// corrente parte aperta. Invio e trappola del focus sono già di Sydney.
	var pannello = document.querySelector('.sydney-offcanvas-menu');
	if (pannello) {
		document.addEventListener('keydown', function (e) {
			var chiudi = pannello.querySelector('.mobile-menu-close');
			if (e.key === 'Escape' && chiudi && document.body.classList.contains('mobile-menu-visible')) {
				chiudi.click();
			}
		});
		pannello.querySelectorAll('li.menu-item-has-children').forEach(function (voce) {
			var sottomenu = voce.querySelector(':scope > .sub-menu');
			var apri = voce.querySelector(':scope > .dropdown-symbol');
			var link = voce.querySelector(':scope > a');
			if (!sottomenu || !apri) {
				return;
			}
			apri.setAttribute('role', 'button');
			apri.setAttribute('aria-label', 'Sottomenu ' + (link ? link.textContent.trim() : ''));
			var mostraStato = function () {
				apri.setAttribute('aria-expanded', String(sottomenu.classList.contains('toggled')));
			};
			if (voce.matches('.current-menu-ancestor, .current-menu-parent')) {
				sottomenu.classList.add('toggled');
			}
			new MutationObserver(mostraStato).observe(sottomenu, { attributes: true, attributeFilter: ['class'] });
			mostraStato();
		});
	}

	// Agenda a lotti: "Mostra altri" apre un lotto alla volta e porta il focus sulla prima card nuova.
	document.querySelectorAll('[data-vv-lotto]').forEach(function (griglia, n) {
		var lotto = parseInt(griglia.getAttribute('data-vv-lotto'), 10) || 0;
		var card = Array.from(griglia.children);
		if (lotto < 1 || card.length <= lotto) {
			return;
		}
		card.slice(lotto).forEach(function (c) {
			c.hidden = true;
		});
		griglia.id = griglia.id || 'vv-lotti-' + n;
		var riga = document.createElement('p');
		riga.className = 'vv-lotti';
		riga.innerHTML = '<button type="button" class="vv-button vv-button--ghost vv-lotti__altri" aria-controls="' + griglia.id + '">Mostra altri appuntamenti</button>';
		griglia.after(riga);
		riga.firstChild.addEventListener('click', function () {
			var nascoste = card.filter(function (c) { return c.hidden; });
			nascoste.slice(0, lotto).forEach(function (c) {
				c.hidden = false;
			});
			nascoste[0].querySelector('.vv-card__link')?.focus();
			if (nascoste.length <= lotto) {
				riga.remove();
			}
		});
	});

	var menoMovimento = matchMedia('(prefers-reduced-motion: reduce)');
	var comeScorrere = function () {
		return menoMovimento.matches ? 'auto' : 'smooth';
	};

	document.querySelectorAll('[data-vv-carousel]').forEach(function (carosello) {
		var binario = carosello.querySelector('.vv-grid--carosello');
		var card = binario ? binario.children : [];
		if (card.length < 2) {
			return;
		}

		var comandi = document.createElement('div');
		comandi.className = 'vv-carousel__nav';
		comandi.innerHTML =
			'<div class="vv-carousel__bar">' +
			'<button type="button" class="vv-carousel__btn" data-dir="-1" aria-label="Contenuti precedenti">' + svg('0 0 24 24', '<path d="M15 5l-7 7 7 7"/>') + '</button>' +
			'<span class="vv-carousel__count" aria-live="polite"></span>' +
			'<button type="button" class="vv-carousel__btn" data-dir="1" aria-label="Contenuti successivi">' + svg('0 0 24 24', '<path d="M9 5l7 7-7 7"/>') + '</button>' +
			'</div>';
		binario.before(comandi);

		var contatore = comandi.querySelector('.vv-carousel__count');
		var indietro = comandi.querySelector('[data-dir="-1"]');
		var avanti = comandi.querySelector('[data-dir="1"]');
		var spazio = function () {
			return parseFloat(getComputedStyle(binario).columnGap) || 0;
		};
		// Una card più lo spazio fra le card; 0 se il carosello è nascosto.
		var passo = function () {
			return card[0].getBoundingClientRect().width + spazio();
		};
		var cardInVista = function () {
			return Math.max(1, Math.round((binario.clientWidth + spazio()) / passo()));
		};

		var aggiorna = function () {
			if (!passo()) {
				return;
			}
			var prima = Math.round(binario.scrollLeft / passo());
			var ultima = Math.min(card.length, prima + cardInVista());
			indietro.disabled = binario.scrollLeft <= 2;
			avanti.disabled = binario.scrollLeft + binario.clientWidth >= binario.scrollWidth - 2;
			contatore.textContent = (prima + 1) + (ultima > prima + 1 ? '–' + ultima : '') + ' di ' + card.length;
			comandi.hidden = indietro.disabled && avanti.disabled;
		};
		binario.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
				e.preventDefault();
				binario.scrollBy({ left: (e.key === 'ArrowRight' ? 1 : -1) * passo(), behavior: comeScorrere() });
			}
		});
		binario.addEventListener('scroll', perFotogramma(aggiorna), { passive: true });
		addEventListener('resize', perFotogramma(aggiorna));
		aggiorna();

		comandi.addEventListener('click', function (e) {
			var freccia = e.target.closest('[data-dir]');
			if (freccia && !freccia.disabled) {
				binario.scrollBy({ left: Number(freccia.getAttribute('data-dir')) * passo() * cardInVista(), behavior: comeScorrere() });
			}
		});
	});

})();
