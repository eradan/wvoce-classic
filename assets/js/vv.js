/* VivaVoce: testata che si stringe, switcher del tema, pulsanti "Copia" (CF, P.IVA, IBAN), caroselli, menu mobile, moduli dei soci e dei corsi (ricompilati, codice fiscale, "Recupera i tuoi dati"). */
(function () {
	'use strict';

	// Testata: dopo aver fatto scorrere si stringe (header.css). Isteresi fra 80 e 10px: restringendosi
	// accorcia la testata, e con una soglia sola andrebbe avanti e indietro proprio lì.
	var scrolled = false;
	var syncHeader = function () {
		var y = window.scrollY || window.pageYOffset;
		if (!scrolled && y > 80) {
			scrolled = true;
			document.body.classList.add('vv-scrolled');
		} else if (scrolled && y < 10) {
			scrolled = false;
			document.body.classList.remove('vv-scrolled');
		}
	};
	window.addEventListener('scroll', syncHeader, { passive: true });
	syncHeader();

	// Switcher del tema accanto alla lente, in entrambe le testate: mostra il tema in uso e passa
	// al successivo (chiaro → scuro → auto). Lo stato lo tiene vvTema() (functions.php, in testa alla pagina).
	if (window.vvTema) {
		var root = document.documentElement;
		var ordine = ['chiaro', 'scuro', 'auto'];
		var temi = {
			chiaro: { nome: 'Tema chiaro', icona: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>' },
			scuro: { nome: 'Tema scuro', icona: '<path d="M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5z"/>' },
			auto: { nome: 'Tema automatico, come il dispositivo', icona: '<circle cx="12" cy="12" r="8"/><path d="M12 4a8 8 0 0 1 0 16z" fill="currentColor"/>' }
		};
		var pulsanti = [];
		var dopo = function (scelta) {
			return ordine[(ordine.indexOf(scelta) + 1) % ordine.length];
		};
		var syncTema = function () {
			var scelta = root.getAttribute('data-vv-scelta');
			var label = temi[scelta].nome + '. Passa a: ' + temi[dopo(scelta)].nome.toLowerCase();
			pulsanti.forEach(function (btn) {
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
			pulsanti.push(btn);
		});
		syncTema();
	}

	// Pulsanti "Copia". Se il browser nega gli appunti il valore resta selezionato, pronto per Ctrl+C.
	if (navigator.clipboard) {
		document.querySelectorAll('[data-vv-copy]').forEach(function (btn) {
			var label = btn.textContent;
			var avviso = function (testo) {
				btn.textContent = testo;
				btn.classList.add('is-done');
				setTimeout(function () {
					btn.textContent = label;
					btn.classList.remove('is-done');
				}, 1800);
			};
			btn.hidden = false;
			btn.addEventListener('click', function () {
				navigator.clipboard.writeText(btn.getAttribute('data-vv-copy')).then(function () {
					avviso('Copiato!');
				}, function () {
					var valore = btn.parentNode.querySelector('.vv-copy__value');
					if (valore && window.getSelection) {
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

	// Banner dei cookie: spazio in fondo alla pagina finché è aperto
	var cookieBar = document.getElementById('cookie-law-info-bar');
	if (cookieBar) {
		var syncCookieBar = function () {
			var open = getComputedStyle(cookieBar).display !== 'none' && cookieBar.offsetHeight > 0;
			document.body.classList.toggle('vv-cookie-open', open);
			document.body.style.setProperty('--vv-cookie-h', open ? cookieBar.offsetHeight + 'px' : '0px');
		};
		if (window.MutationObserver) {
			new MutationObserver(syncCookieBar).observe(cookieBar, { attributes: true, attributeFilter: ['style', 'class'] });
		}
		if (window.ResizeObserver) {
			new ResizeObserver(syncCookieBar).observe(cookieBar);
		}
		window.addEventListener('load', syncCookieBar);
		syncCookieBar();
	}

	// Pulsante "torna su" e badge di reCAPTCHA: quanto della fascia turchese è in vista, perché salgano
	// con lei (footer.css). Si aggiorna solo quando cambia, cioè solo in fondo alla pagina.
	var fascia = document.querySelector('.site-footer .site-info');
	if (fascia) {
		var sotto = -1, rafSotto = 0;
		var syncSotto = function () {
			rafSotto = 0;
			var h = Math.max(0, Math.round(window.innerHeight - fascia.getBoundingClientRect().top));
			if (h !== sotto) {
				sotto = h;
				document.documentElement.style.setProperty('--vv-sotto', h + 'px');
			}
		};
		var queueSotto = function () {
			if (!rafSotto) {
				rafSotto = requestAnimationFrame(syncSotto);
			}
		};
		window.addEventListener('scroll', queueSotto, { passive: true });
		window.addEventListener('resize', queueSotto);
		syncSotto();
	}

	// Il "torna su" di Sydney è un <a> senza href: né nome né tastiera
	var goTop = document.querySelector('.go-top');
	if (goTop) {
		goTop.setAttribute('role', 'button');
		goTop.setAttribute('tabindex', '0');
		goTop.setAttribute('aria-label', 'Torna in cima alla pagina');
		goTop.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				goTop.click();
			}
		});
	}

	// Menu mobile (pannello di Sydney): sfondo che chiude, Esc, frecce accessibili,
	// sottomenu della sezione corrente già aperto.
	var offcanvas = document.querySelector('.sydney-offcanvas-menu');
	if (offcanvas) {
		var closeMenu = function () {
			var close = offcanvas.querySelector('.mobile-menu-close');
			if (close && document.body.classList.contains('mobile-menu-visible')) {
				close.click();
			}
		};
		var backdrop = document.createElement('div');
		backdrop.className = 'vv-offcanvas-backdrop';
		backdrop.setAttribute('aria-hidden', 'true');
		backdrop.addEventListener('click', closeMenu);
		document.body.appendChild(backdrop);
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				closeMenu();
			}
		});

		offcanvas.querySelectorAll('li.menu-item-has-children').forEach(function (li) {
			var sub = li.querySelector(':scope > .sub-menu');
			var toggle = li.querySelector(':scope > .dropdown-symbol');
			var link = li.querySelector(':scope > a');
			if (!sub || !toggle) {
				return;
			}
			toggle.setAttribute('role', 'button');
			toggle.setAttribute('aria-label', 'Sottomenu ' + (link ? link.textContent.trim() : ''));
			var sync = function () {
				var open = sub.classList.contains('toggled');
				li.classList.toggle('vv-open', open);
				toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
				if (link) {
					link.setAttribute('aria-expanded', open ? 'true' : 'false');
				}
			};
			if (li.classList.contains('current-menu-ancestor') || li.classList.contains('current-menu-parent')) {
				sub.classList.add('toggled');
			}
			if (window.MutationObserver) {
				new MutationObserver(sync).observe(sub, { attributes: true, attributeFilter: ['class'] });
			}
			sync();
		});
	}

	// Caroselli: frecce precedente/successivo e contatore "1–3 di 18"
	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	document.querySelectorAll('[data-vv-carousel]').forEach(function (carousel) {
		var track = carousel.querySelector('.vv-grid--carosello');
		var cards = track ? track.children : [];
		if (!track || cards.length < 2) {
			return;
		}

		// Riga sotto il carosello: ‹ (pausa) contatore ›. Su schermo largo le frecce escono dalla riga e
		// stanno ai lati delle card (CSS); su telefono restano qui, dove non coprono le foto.
		var freccia = function (d) {
			return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="' + d + '"/></svg>';
		};
		var nav = document.createElement('div');
		nav.className = 'vv-carousel__nav';
		nav.innerHTML =
			'<div class="vv-carousel__bar">' +
			'<button type="button" class="vv-carousel__btn" data-dir="-1" aria-label="Contenuti precedenti">' + freccia('M15 5l-7 7 7 7') + '</button>' +
			'<span class="vv-carousel__count" aria-live="polite"></span>' +
			'<button type="button" class="vv-carousel__btn" data-dir="1" aria-label="Contenuti successivi">' + freccia('M9 5l7 7-7 7') + '</button>' +
			'</div>';
		carousel.insertBefore(nav, track);

		var count = nav.querySelector('.vv-carousel__count');
		var prev = nav.querySelector('[data-dir="-1"]');
		var next = nav.querySelector('[data-dir="1"]');

		function gap() {
			return parseFloat(getComputedStyle(track).columnGap) || 0;
		}

		function step() {
			return cards[0].getBoundingClientRect().width + gap();
		}

		// Card intere visibili (il padding del binario lascia spazio alle ombre, non conta)
		function visibleCards() {
			var cs = getComputedStyle(track);
			var inner = track.clientWidth - (parseFloat(cs.paddingLeft) || 0) - (parseFloat(cs.paddingRight) || 0);
			return Math.max(1, Math.round((inner + gap()) / step()));
		}

		function update() {
			var s = step();
			var visible = visibleCards();
			var first = Math.round(track.scrollLeft / s);
			var last = Math.min(cards.length, first + visible);
			prev.disabled = track.scrollLeft <= 2;
			next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
			count.textContent = (first + 1) + (last > first + 1 ? '–' + last : '') + ' di ' + cards.length;
			nav.hidden = prev.disabled && next.disabled;
		}

		nav.addEventListener('click', function (e) {
			var btn = e.target.closest('.vv-carousel__btn');
			if (!btn || btn.disabled) {
				return;
			}
			track.scrollBy({ left: Number(btn.getAttribute('data-dir')) * step() * visibleCards(), behavior: reduceMotion ? 'auto' : 'smooth' });
		});

		track.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
				e.preventDefault();
				track.scrollBy({ left: (e.key === 'ArrowRight' ? 1 : -1) * step(), behavior: reduceMotion ? 'auto' : 'smooth' });
			}
		});

		var raf = 0;
		track.addEventListener('scroll', function () {
			cancelAnimationFrame(raf);
			raf = requestAnimationFrame(update);
		}, { passive: true });
		window.addEventListener('resize', update);
		update();

		// Scorrimento automatico (data-vv-autoplay): una card ogni 5 secondi, in fondo si riparte.
		// Si ferma con il mouse sopra, il focus dentro, il dito sul binario, fuori schermo e a scheda
		// nascosta; il pulsante lo ferma del tutto. Con "riduci movimento" non parte.
		if (!carousel.hasAttribute('data-vv-autoplay') || reduceMotion) {
			return;
		}
		var ICON_PAUSE = '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><rect x="3" y="2" width="3.5" height="12" fill="currentColor"/><rect x="9.5" y="2" width="3.5" height="12" fill="currentColor"/></svg>';
		var ICON_PLAY = '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path d="M4 2l10 6-10 6z" fill="currentColor"/></svg>';
		var play = document.createElement('button');
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

		function running() {
			return !stopped && !hovering && !focused && !touching && visible && !document.hidden;
		}

		function tick() {
			if (!running() || track.scrollWidth <= track.clientWidth + 2) {
				return;
			}
			var atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
			track.scrollTo({ left: atEnd ? 0 : track.scrollLeft + step(), behavior: 'smooth' });
		}

		var shown = null;
		function sync() {
			clearInterval(timer);
			timer = running() ? setInterval(tick, 5000) : 0;
			// L'icona si ridisegna solo quando cambia: sostituirla fra pressione e rilascio del mouse
			// (il mouse che entra, il focus del clic) farebbe perdere il clic.
			if (shown !== stopped) {
				shown = stopped;
				play.innerHTML = stopped ? ICON_PLAY : ICON_PAUSE;
				play.setAttribute('aria-label', stopped ? 'Riprendi lo scorrimento automatico' : 'Ferma lo scorrimento automatico');
				play.setAttribute('aria-pressed', stopped ? 'true' : 'false');
			}
		}

		play.addEventListener('click', function () {
			stopped = !stopped;
			sync();
		});
		// Le frecce ripartono da capo con l'intervallo, così il salto automatico non arriva subito dopo.
		nav.addEventListener('click', function (e) {
			if (e.target.closest('[data-dir]')) {
				sync();
			}
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
		track.addEventListener('touchend', function () { touching = false; sync(); }, { passive: true });
		document.addEventListener('visibilitychange', sync);
		if ('IntersectionObserver' in window) {
			new IntersectionObserver(function (entries) {
				visible = entries[0].isIntersecting;
				sync();
			}, { threshold: 0.5 }).observe(track);
		}
		sync();
	});

	// Moduli Forminator dei soci e dei corsi (inc/forminator.php). Forminator può disegnare il modulo dopo
	// il caricamento della pagina: si riprova per qualche secondo.
	var quandoModulo = function (id, fatto) {
		var tentativi = 0;
		var cerca = function () {
			var form = id && document.getElementById('forminator-module-' + id);
			if (form) {
				fatto(form);
			} else if (++tentativi < 40) {
				setTimeout(cerca, 250);
			}
		};
		cerca();
	};

	// Modulo ricompilato: dati dell'iscrizione a socio nel modulo dei corsi, o dell'ultima iscrizione
	// nel modulo dei soci (link dell'email). Radio per valore dell'opzione, caselle spuntate; i valori
	// passano dagli eventi, così il plugin li vede.
	var datiRicompila = document.getElementById('vv-ricompila');
	var ricompila = null;
	if (datiRicompila) {
		try { ricompila = JSON.parse(datiRicompila.textContent); } catch (e) {}
	}
	if (ricompila) {
		quandoModulo(ricompila.form, function (form) {
			Object.keys(ricompila.campi).forEach(function (nome) {
				var valore = ricompila.campi[nome];
				form.querySelectorAll('[name="' + nome + '"]').forEach(function (input) {
					if (input.type === 'radio') {
						if (input.value === valore && !input.checked) {
							input.click();
						}
					} else if (!input.value && valore) {
						input.value = valore;
						input.dispatchEvent(new Event('input', { bubbles: true }));
						input.dispatchEvent(new Event('change', { bubbles: true }));
					}
				});
			});
			(ricompila.caselle || []).forEach(function (nome) {
				var casella = form.querySelector('input[type="checkbox"][name="' + nome + '"]');
				if (casella && !casella.checked) {
					casella.click();
				}
			});
			var avviso = document.createElement('p');
			avviso.className = 'vv-ricompila-avviso';
			avviso.setAttribute('role', 'status');
			avviso.textContent = ricompila.avviso;
			form.parentNode.insertBefore(avviso, form);
		});
	}

	var moduli = window.vvModuli;
	if (moduli) {
		// Codice fiscale: formato e carattere di controllo, come vv_codice_fiscale_valido().
		var cfValido = function (cf) {
			cf = cf.replace(/\s+/g, '').toUpperCase();
			if (!/^[A-Z]{6}[0-9LMNPQRSTUV]{2}[ABCDEHLMPRST][0-9LMNPQRSTUV]{2}[A-Z][0-9LMNPQRSTUV]{3}[A-Z]$/.test(cf)) {
				return false;
			}
			var dispari = [1, 0, 5, 7, 9, 13, 15, 17, 19, 21, 2, 4, 18, 20, 11, 3, 6, 8, 12, 14, 16, 10, 22, 25, 24, 23];
			var somma = 0;
			for (var i = 0; i < 15; i++) {
				var c = cf.charCodeAt(i);
				var n = c < 65 ? c - 48 : c - 65;
				somma += i % 2 === 0 ? dispari[n] : n;
			}
			return String.fromCharCode(65 + somma % 26) === cf.charAt(15);
		};
		var msgFormato = 'Controlla il codice fiscale: sono 16 lettere e numeri, come sulla tessera sanitaria.';

		// Compilazione automatica del browser: nome e cognome distinti, telefoni, e il luogo di nascita
		// lasciato fuori (il browser ci metterebbe la città di residenza).
		var completamento = {
			'name-1': 'given-name',
			'name-2': 'family-name',
			'email-1': 'email',
			'phone-1': 'tel',
			'phone-2': 'tel',
			'address-2-city': 'off',
			'address-2-state': 'off'
		};

		[moduli.soci, moduli.corsi].forEach(function (id) {
			quandoModulo(id, function (form) {
				Object.keys(completamento).forEach(function (nome) {
					var input = form.querySelector('input[name="' + nome + '"]');
					if (input) {
						input.setAttribute('autocomplete', completamento[nome]);
					}
				});

				var cf = form.querySelector('input[name="' + moduli.cf + '"]');
				var campo = cf && (cf.closest('.forminator-field') || cf.parentNode);
				if (!campo) {
					return;
				}
				// Errore di formato subito, all'uscita dal campo, invece che dopo l'invio
				var errore = document.createElement('p');
				errore.className = 'vv-cf-errore';
				errore.setAttribute('role', 'alert');
				errore.hidden = true;
				campo.appendChild(errore);
				cf.addEventListener('blur', function () {
					var sbagliato = cf.value.trim() !== '' && !cfValido(cf.value);
					errore.textContent = sbagliato ? msgFormato : '';
					errore.hidden = !sbagliato;
				});
				cf.addEventListener('input', function () {
					errore.hidden = true;
				});

				// "Recupera i tuoi dati": un link a destra sotto il codice fiscale, solo nel modulo dei soci e
				// non quando il modulo è già stato ricompilato dal link dell'email. L'esito va sotto la riga,
				// a tutta larghezza, per non allungare la colonna.
				if (id !== moduli.soci || (ricompila && ricompila.form === id)) {
					return;
				}
				var invito = document.createElement('p');
				invito.className = 'vv-recupera';
				invito.innerHTML = 'Già socio? <a href="#" role="button" class="vv-recupera__link">Recupera i tuoi dati</a>';
				campo.appendChild(invito);
				var riga = cf.closest('.forminator-row') || campo;
				var esito = document.createElement('p');
				esito.className = 'vv-recupera__esito';
				esito.setAttribute('role', 'status');
				esito.hidden = true;
				riga.parentNode.insertBefore(esito, riga.nextSibling);
				var link = invito.querySelector('a');
				var attesa = false;
				var mostra = function (testo) {
					esito.textContent = testo;
					esito.hidden = false;
				};
				link.addEventListener('click', function (e) {
					e.preventDefault();
					if (attesa) {
						return;
					}
					if (!cfValido(cf.value)) {
						mostra('Scrivi prima il tuo codice fiscale: sono 16 lettere e numeri, come sulla tessera sanitaria.');
						cf.focus();
						return;
					}
					attesa = true;
					link.setAttribute('aria-disabled', 'true');
					fetch(moduli.recupera, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({ cf: cf.value })
					}).then(function (r) {
						return r.json();
					}).then(function (d) {
						mostra(d.messaggio);
					}).catch(function () {
						mostra('Non è stato possibile inviare la richiesta: riprova fra poco, oppure compila il modulo per intero.');
					}).then(function () {
						attesa = false;
						link.removeAttribute('aria-disabled');
					});
				});
			});
		});
	}
})();
