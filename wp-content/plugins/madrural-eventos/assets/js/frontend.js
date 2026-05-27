(function () {
	'use strict';

	var ajaxConfig = window.MADRURAL_AJAX_VIEWS || {};
	var isLoadingView = false;

	function normalizeUrl(url) {
		try {
			return new URL(url, window.location.href);
		} catch (error) {
			return null;
		}
	}

	function getProfileListLoadingTarget() {
		var table = document.querySelector('.madrural-auth-table');
		if (table) {
			return table;
		}

		return document.querySelector('.madrural-auth-card') || getMainContent();
	}

	function getMisEventosListLoadingTarget() {
		var table = document.querySelector('.madrural-mis-eventos');
		if (table) {
			return table;
		}

		return document.querySelector('.madrural-eventos-listado') || getMainContent();
	}

	function getPath(url) {
		var parsed = normalizeUrl(url);
		return parsed ? parsed.pathname.replace(/\/+$/, '') : '';
	}

	function isManagedPluginUrl(url) {
		var parsed = normalizeUrl(url);
		if (!parsed || parsed.origin !== window.location.origin) {
			return false;
		}

		var path = parsed.pathname.replace(/\/+$/, '');
		var managedPaths = [
			getPath(ajaxConfig.agendaUrl || ''),
			getPath(ajaxConfig.misUrl || ''),
			getPath(ajaxConfig.formUrl || ''),
			getPath(ajaxConfig.panelUrl || ''),
			getPath(ajaxConfig.loginUrl || '')
		];

		if (managedPaths.indexOf(path) !== -1) {
			return true;
		}

		if (parsed.searchParams.has('me_action') || parsed.searchParams.has('me_paged') || parsed.searchParams.has('ma_paged') || parsed.searchParams.has('ma_edit')) {
			return true;
		}

		return path.indexOf('/evento/') !== -1;
	}

	function getMainContent() {
		return document.querySelector('.madrural-plugin-content');
	}

	function getHeader() {
		return document.querySelector('.madrural-plugin-header');
	}

	function parseHtml(html) {
		return new window.DOMParser().parseFromString(html, 'text/html');
	}

	function setTargetLoading(target, active) {
		if (target && target.tagName && target.tagName.toLowerCase() === 'table') {
			target = target.parentElement || target;
		}

		if (!target) {
			return;
		}

		target.classList.add('madrural-ajax-target');

		var spinner = target.querySelector(':scope > .madrural-ajax-spinner-overlay');
		if (!spinner) {
			spinner = document.createElement('div');
			spinner.className = 'madrural-ajax-spinner-overlay';
			spinner.innerHTML = '<span class="madrural-ajax-spinner" aria-hidden="true"></span><span class="madrural-ajax-spinner-text">' + (ajaxConfig.loadingText || 'Cargando...') + '</span>';
			target.insertBefore(spinner, target.firstChild);
		}

		target.classList.toggle('is-ajax-loading', !!active);
	}

	function extractToastMessage(url) {
		var parsed = normalizeUrl(url);
		if (!parsed) {
			return ajaxConfig.savedToastText || 'Cambios guardados correctamente.';
		}

		var meNotice = parsed.searchParams.get('me_notice');
		var maNotice = parsed.searchParams.get('ma_notice');
		var map = {
			saved: 'Guardado correctamente.',
			deleted: 'Eliminado correctamente.',
			nonce: 'No se pudo validar la solicitud.',
			duplicate_name: 'El nombre ya existe, usa otro.',
			missing_name: 'El nombre es obligatorio.',
			missing_password: 'La contraseña es obligatoria para nuevos perfiles.',
			missing_territorio: 'Debes asignar un territorio al rol admin.',
			cannot_delete_current: 'No puedes eliminar el perfil con sesión activa.'
		};

		if (meNotice && map[meNotice]) {
			return map[meNotice];
		}
		if (maNotice && map[maNotice]) {
			return map[maNotice];
		}

		return ajaxConfig.savedToastText || 'Cambios guardados correctamente.';
	}

	function showToast(message) {
		if (!message) {
			return;
		}

		var toast = document.createElement('div');
		toast.className = 'madrural-ajax-toast is-visible';
		toast.textContent = message;
		document.body.appendChild(toast);

		window.setTimeout(function () {
			toast.classList.remove('is-visible');
			window.setTimeout(function () {
				if (toast.parentNode) {
					toast.parentNode.removeChild(toast);
				}
			}, 250);
		}, 3000);
	}

	function fetchHtml(url, options) {
		options = options || {};
		return window.fetch(url, {
			method: options.method || 'GET',
			credentials: 'same-origin',
			body: options.body || null,
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function (response) {
			if (!response.ok) {
				throw new Error('Request failed');
			}
			var finalUrl = response.url || url;
			return response.text().then(function (html) {
				return { html: html, url: finalUrl };
			});
		});
	}

	function replaceViewFromDocument(nextDocument) {
		var currentContent = getMainContent();
		var nextContent = nextDocument.querySelector('.madrural-plugin-content');
		if (!currentContent || !nextContent) {
			return false;
		}

		currentContent.replaceWith(nextContent);

		var currentHeader = getHeader();
		var nextHeader = nextDocument.querySelector('.madrural-plugin-header');
		if (currentHeader && nextHeader) {
			currentHeader.innerHTML = nextHeader.innerHTML;
		}

		if (nextDocument.title) {
			document.title = nextDocument.title;
		}

		return true;
	}

	function replaceIfFound(currentSelector, nextSelector, nextDocument) {
		var currentNode = document.querySelector(currentSelector);
		var nextNode = nextDocument.querySelector(nextSelector || currentSelector);
		if (!currentNode || !nextNode) {
			return false;
		}
		currentNode.replaceWith(nextNode);
		return true;
	}

	function replaceListSections(nextDocument) {
		var replaced = false;

		if (document.querySelector('.madrural-eventos-listado')) {
			replaced = replaceIfFound('.madrural-eventos-listado', '.madrural-eventos-listado', nextDocument) || replaced;
			replaced = replaceIfFound('.madrural-pagination', '.madrural-pagination', nextDocument) || replaced;
			return replaced;
		}

		if (document.querySelector('.madrural-mis-eventos')) {
			replaced = replaceIfFound('.madrural-mis-eventos', '.madrural-mis-eventos', nextDocument) || replaced;
			replaced = replaceIfFound('.madrural-pagination', '.madrural-pagination', nextDocument) || replaced;
			replaced = replaceIfFound('.madrural-eventos-notice', '.madrural-eventos-notice', nextDocument) || replaced;
			return replaced;
		}

		if (document.querySelector('.madrural-auth-table')) {
			replaced = replaceIfFound('.madrural-auth-table', '.madrural-auth-table', nextDocument) || replaced;
			replaced = replaceIfFound('.madrural-auth-pagination', '.madrural-auth-pagination', nextDocument) || replaced;
			replaced = replaceIfFound('.madrural-auth-notice', '.madrural-auth-notice', nextDocument) || replaced;
			return replaced;
		}

		return false;
	}

	function replaceProfilesListSection(nextDocument) {
		var replaced = false;
		replaced = replaceIfFound('.madrural-auth-table', '.madrural-auth-table', nextDocument) || replaced;
		replaced = replaceIfFound('.madrural-auth-pagination', '.madrural-auth-pagination', nextDocument) || replaced;
		replaced = replaceIfFound('.madrural-auth-notice', '.madrural-auth-notice', nextDocument) || replaced;
		return replaced;
	}

	function replaceProfileFormSection(nextDocument) {
		var replaced = false;
		replaced = replaceIfFound('.madrural-auth-profile-form', '.madrural-auth-profile-form', nextDocument) || replaced;
		replaced = replaceIfFound('.madrural-auth-notice', '.madrural-auth-notice', nextDocument) || replaced;
		replaced = replaceProfilesListSection(nextDocument) || replaced;
		return replaced;
	}

	function resetProfileFormToCreateMode() {
		var form = document.querySelector('.madrural-auth-profile-form');
		if (!form) {
			return;
		}

		var profileIdInput = form.querySelector('input[name="profile_id"]');
		if (profileIdInput) {
			profileIdInput.value = '0';
		}

		var nameInput = form.querySelector('input[name="name"]');
		if (nameInput) {
			nameInput.value = '';
		}

		var passwordInput = form.querySelector('input[name="password"]');
		if (passwordInput) {
			passwordInput.value = '';
			passwordInput.setAttribute('required', 'required');
		}

		var roleSelect = form.querySelector('select[name="role"]');
		if (roleSelect) {
			roleSelect.value = 'admin';
		}

		var territorioSelect = form.querySelector('select[name="territorios[]"]');
		if (territorioSelect && territorioSelect.options) {
			Array.prototype.forEach.call(territorioSelect.options, function (option) {
				option.selected = false;
			});
		}

		var title = form.closest('.madrural-auth-card') ? form.closest('.madrural-auth-card').querySelector('h2') : null;
		if (title) {
			title.textContent = 'Nuevo perfil';
		}
	}

	function replaceEventFormSection(nextDocument) {
		var replaced = false;
		replaced = replaceIfFound('.madrural-evento-formulario', '.madrural-evento-formulario', nextDocument) || replaced;
		replaced = replaceIfFound('.madrural-eventos-notice', '.madrural-eventos-notice', nextDocument) || replaced;
		return replaced;
	}

	function afterViewRender(url, pushState) {
		if (pushState) {
			window.history.pushState({ madruralAjaxView: true }, '', url);
		}
		initViewEnhancements();
		document.dispatchEvent(new CustomEvent('madrural:content-updated'));
	}

	function fetchAndRenderView(url, options) {
		if (isLoadingView) {
			return;
		}

		options = options || {};
		isLoadingView = true;
		setTargetLoading(getMainContent(), true);

		fetchHtml(url).then(function (result) {
			var nextDocument = parseHtml(result.html);
			if (!replaceViewFromDocument(nextDocument)) {
				window.location.href = url;
				return;
			}

			afterViewRender(url, !options.fromPopState);
			window.scrollTo({ top: 0, behavior: 'smooth' });
		}).catch(function () {
			window.location.href = url;
		}).finally(function () {
			isLoadingView = false;
			setTargetLoading(getMainContent(), false);
		});
	}

	function getListLoadingTarget() {
		return document.querySelector('.madrural-eventos-listado') || document.querySelector('.madrural-mis-eventos') || document.querySelector('.madrural-auth-table') || getMainContent();
	}

	function fetchAndRenderList(url, options) {
		options = options || {};
		var target = options.target || getListLoadingTarget();
		var isDeleteAction = !!options.isDeleteAction;
		var deleteType = options.deleteType || '';
		var toastMessage = options.toastMessage || '';
		setTargetLoading(target, true);

		fetchHtml(url).then(function (result) {
			setTargetLoading(target, false);
			var nextDocument = parseHtml(result.html);
			if (replaceListSections(nextDocument)) {
				afterViewRender(result.url, true);
				if (options.showToast || isDeleteAction) {
					showToast(toastMessage || extractToastMessage(result.url));
				}
				if (isDeleteAction) {
					return;
				}

				if (typeof options.onComplete === 'function') {
					options.onComplete();
				}
				return;
			}

			if (isDeleteAction) {
				if ('perfil' === deleteType) {
					replaceProfilesListSection(nextDocument);
				} else if ('evento' === deleteType) {
					replaceIfFound('.madrural-mis-eventos', '.madrural-mis-eventos', nextDocument);
					replaceIfFound('.madrural-pagination', '.madrural-pagination', nextDocument);
					replaceIfFound('.madrural-eventos-notice', '.madrural-eventos-notice', nextDocument);
				}

				afterViewRender(result.url, true);
				showToast(toastMessage || extractToastMessage(result.url));
				return;
			}

			if (typeof options.onComplete === 'function') {
				options.onComplete();
			}
			fetchAndRenderView(result.url);
		}).catch(function () {
			setTargetLoading(target, false);
			if (isDeleteAction) {
				showToast('No se pudo eliminar el elemento.');
				return;
			}

			if (typeof options.onComplete === 'function') {
				options.onComplete();
			}
			window.location.href = url;
		});
	}

	function submitEventFormAjax(form) {
		setTargetLoading(form, true);

		var action = form.getAttribute('action') || window.location.href;
		var method = (form.getAttribute('method') || 'post').toUpperCase();
		var formData = new window.FormData(form);

		fetchHtml(action, { method: method, body: formData }).then(function (result) {
			setTargetLoading(form, false);
			var misUrl = ajaxConfig.misUrl || result.url;
			fetchAndRenderView(misUrl);
			showToast(extractToastMessage(result.url));
			return;

			afterViewRender(result.url, true);
			showToast(extractToastMessage(result.url));
		}).catch(function () {
			setTargetLoading(form, false);
			form.submit();
		});
	}

	function submitProfileFormAjax(form) {
		var isDeleteForm = form.classList.contains('madrural-auth-confirm-form');
		var target = isDeleteForm ? getProfileListLoadingTarget() : (form.closest('.madrural-auth-card') || form);
		setTargetLoading(target, true);

		var action = form.getAttribute('action') || window.location.href;
		var method = (form.getAttribute('method') || 'post').toUpperCase();
		var formData = new window.FormData(form);

		fetchHtml(action, { method: method, body: formData }).then(function (result) {
			setTargetLoading(target, false);
			if (isDeleteForm) {
				resetProfileFormToCreateMode();
				fetchAndRenderList(result.url, {
					target: getProfileListLoadingTarget(),
					isDeleteAction: true,
					deleteType: 'perfil',
					toastMessage: 'elemento eliminado correctamente.'
				});
				return;
			}

			var nextDocument = parseHtml(result.html);
			var replaced = replaceProfileFormSection(nextDocument);
			if (!replaced) {
				fetchAndRenderView(result.url);
				return;
			}

			afterViewRender(result.url, true);
			showToast(extractToastMessage(result.url));
		}).catch(function () {
			setTargetLoading(target, false);
			form.submit();
		});
	}

	function shouldInterceptLink(link, event) {
		if (!link || event.defaultPrevented) {
			return false;
		}
		if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
			return false;
		}
		if ((link.target && '_self' !== link.target) || link.hasAttribute('download')) {
			return false;
		}

		var href = link.getAttribute('href') || '';
		if (!href || href.charAt(0) === '#') {
			return false;
		}

		return isManagedPluginUrl(link.href);
	}

	function buildGetFormUrl(form) {
		var action = normalizeUrl(form.getAttribute('action') || window.location.href);
		if (!action) {
			return window.location.href;
		}
		action.search = '';

		var data = new window.FormData(form);
		data.forEach(function (value, key) {
			if (value === null || value === undefined || String(value).trim() === '') {
				return;
			}
			action.searchParams.append(key, value);
		});

		return action.toString();
	}

	function initAjaxFlow() {
		document.addEventListener('click', function (event) {
			var link = event.target.closest('a');
			if (!shouldInterceptLink(link, event)) {
				return;
			}

			if (link.closest('.madrural-pagination') || link.closest('.madrural-auth-pagination')) {
				event.preventDefault();
				fetchAndRenderList(link.href);
				return;
			}

			event.preventDefault();
			fetchAndRenderView(link.href);
		});

		document.addEventListener('submit', function (event) {
			var form = event.target;
			if (!form || form.nodeName !== 'FORM') {
				return;
			}

			var method = (form.getAttribute('method') || 'get').toLowerCase();
			if ('get' === method && form.classList.contains('madrural-eventos-filtros')) {
				var submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
				if (submitButton) {
					submitButton.disabled = true;
				}

				event.preventDefault();
				fetchAndRenderList(buildGetFormUrl(form), {
					onComplete: function () {
						if (submitButton) {
							submitButton.disabled = false;
						}
					}
				});
				return;
			}

			if (form.classList.contains('madrural-evento-formulario')) {
				event.preventDefault();
				submitEventFormAjax(form);
				return;
			}

			if (form.classList.contains('madrural-auth-confirm-form')) {
				if ('1' !== form.dataset.confirmedDelete) {
					event.preventDefault();
					return;
				}

				form.dataset.confirmedDelete = '0';
				event.preventDefault();
				submitProfileFormAjax(form);
				return;
			}

			if (form.classList.contains('madrural-auth-profile-form')) {
				event.preventDefault();
				submitProfileFormAjax(form);
			}
		});

		window.addEventListener('popstate', function () {
			if (isManagedPluginUrl(window.location.href)) {
				fetchAndRenderView(window.location.href, { fromPopState: true });
			}
		});
	}

	function initCarousel(carousel) {
		var track = carousel.querySelector('.madrural-evento-carousel-track');
		if (!track || carousel.dataset.carouselInit === '1') {
			return;
		}
		carousel.dataset.carouselInit = '1';

		var slides = Array.prototype.slice.call(track.querySelectorAll('.madrural-evento-slide'));
		if (slides.length <= 1) {
			return;
		}

		var prevButton = carousel.querySelector('.madrural-carousel-prev');
		var nextButton = carousel.querySelector('.madrural-carousel-next');
		var dotsContainer = carousel.querySelector('.madrural-carousel-dots');
		var currentIndex = 0;
		var autoplayTimer = null;

		function goTo(index) {
			if (index < 0) {
				currentIndex = slides.length - 1;
			} else if (index >= slides.length) {
				currentIndex = 0;
			} else {
				currentIndex = index;
			}

			track.style.transform = 'translateX(-' + (currentIndex * 100) + '%)';
			if (dotsContainer) {
				var dots = dotsContainer.querySelectorAll('button');
				Array.prototype.forEach.call(dots, function (dot, dotIndex) {
					dot.classList.toggle('is-active', dotIndex === currentIndex);
				});
			}
		}

		if (dotsContainer) {
			slides.forEach(function (_, index) {
				var dot = document.createElement('button');
				dot.type = 'button';
				dot.setAttribute('aria-label', 'Ir a imagen ' + (index + 1));
				dot.addEventListener('click', function () {
					goTo(index);
				});
				dotsContainer.appendChild(dot);
			});
		}

		if (prevButton) {
			prevButton.addEventListener('click', function () {
				goTo(currentIndex - 1);
			});
		}
		if (nextButton) {
			nextButton.addEventListener('click', function () {
				goTo(currentIndex + 1);
			});
		}

		function startAutoplay() {
			if ('true' !== carousel.getAttribute('data-autoplay')) {
				return;
			}
			if (autoplayTimer) {
				window.clearInterval(autoplayTimer);
			}
			autoplayTimer = window.setInterval(function () {
				goTo(currentIndex + 1);
			}, 5000);
		}

		function stopAutoplay() {
			if (autoplayTimer) {
				window.clearInterval(autoplayTimer);
				autoplayTimer = null;
			}
		}

		carousel.addEventListener('mouseenter', stopAutoplay);
		carousel.addEventListener('mouseleave', startAutoplay);

		goTo(0);
		startAutoplay();
	}

	function initEventGalleryModal() {
		var modal = document.getElementById('madrural-evento-gallery-modal');
		if (!modal) {
			return;
		}

		var modalImg = modal.querySelector('.madrural-evento-gallery-modal-body img');
		var closeButton = modal.querySelector('.madrural-evento-gallery-modal-close');
		var triggers = document.querySelectorAll('.madrural-evento-gallery-trigger[data-full-src]');
		if (!modalImg || !triggers.length) {
			return;
		}

		function closeModal() {
			modal.classList.remove('is-open');
			modal.setAttribute('aria-hidden', 'true');
			modalImg.setAttribute('src', '');
		}

		Array.prototype.forEach.call(triggers, function (trigger) {
			if (trigger.dataset.bound === '1') {
				return;
			}
			trigger.dataset.bound = '1';
			trigger.addEventListener('click', function () {
				var fullSrc = trigger.getAttribute('data-full-src');
				if (!fullSrc) {
					return;
				}
				modalImg.setAttribute('src', fullSrc);
				modal.classList.add('is-open');
				modal.setAttribute('aria-hidden', 'false');
			});
		});

		if (closeButton && closeButton.dataset.bound !== '1') {
			closeButton.dataset.bound = '1';
			closeButton.addEventListener('click', closeModal);
		}
	}

	function initStyledConfirmLinks() {
		var links = document.querySelectorAll('a.madrural-eventos-confirm-link[data-confirm-message]');
		if (!links.length) {
			return;
		}

		var modal = document.getElementById('madrural-confirm-modal');
		if (!modal) {
			modal = document.createElement('div');
			modal.id = 'madrural-confirm-modal';
			modal.className = 'madrural-confirm-modal';
			modal.setAttribute('aria-hidden', 'true');
			modal.innerHTML = '' +
				'<div class="madrural-confirm-modal__backdrop" data-confirm-cancel></div>' +
				'<div class="madrural-confirm-modal__dialog" role="dialog" aria-modal="true" aria-live="polite">' +
					'<p class="madrural-confirm-modal__message"></p>' +
					'<div class="madrural-confirm-modal__actions">' +
						'<button type="button" class="madrural-btn" data-confirm-ok>Confirmar</button>' +
						'<button type="button" class="madrural-btn madrural-btn-secondary" data-confirm-cancel>Cancelar</button>' +
					'</div>' +
				'</div>';
			document.body.appendChild(modal);
		}

		var messageNode = modal.querySelector('.madrural-confirm-modal__message');
		var okButton = modal.querySelector('[data-confirm-ok]');
		var cancelButtons = modal.querySelectorAll('[data-confirm-cancel]');
		if (typeof modal._pendingUrl === 'undefined') {
			modal._pendingUrl = '';
		}

		function closeModal() {
			modal.classList.remove('is-open');
			modal.setAttribute('aria-hidden', 'true');
			modal._pendingUrl = '';
		}

		if (okButton && okButton.dataset.bound !== '1') {
			okButton.dataset.bound = '1';
			okButton.addEventListener('click', function () {
				if (modal._pendingUrl) {
					var shouldDoPartialDelete = modal._pendingUrl.indexOf('me_action=delete') !== -1;
					if (shouldDoPartialDelete) {
						fetchAndRenderList(modal._pendingUrl, {
							target: getMisEventosListLoadingTarget(),
							showToast: true,
							isDeleteAction: true,
							deleteType: 'evento',
							toastMessage: 'elemento eliminado correctamente.'
						});
					} else {
						fetchAndRenderView(modal._pendingUrl);
					}
				}
				closeModal();
			});
		}

		Array.prototype.forEach.call(cancelButtons, function (button) {
			if (button.dataset.bound === '1') {
				return;
			}
			button.dataset.bound = '1';
			button.addEventListener('click', closeModal);
		});

		Array.prototype.forEach.call(links, function (link) {
			if (link.dataset.bound === '1') {
				return;
			}
			link.dataset.bound = '1';
			link.addEventListener('click', function (event) {
				event.preventDefault();
				modal._pendingUrl = link.getAttribute('href') || '';
				messageNode.textContent = link.getAttribute('data-confirm-message') || '¿Confirmas esta acción?';
				modal.classList.add('is-open');
				modal.setAttribute('aria-hidden', 'false');
			});
		});
	}

	function initViewEnhancements() {
		var carousels = document.querySelectorAll('.js-madrural-carousel');
		Array.prototype.forEach.call(carousels, initCarousel);
		initEventGalleryModal();
		initStyledConfirmLinks();
	}

	document.addEventListener('DOMContentLoaded', function () {
		initAjaxFlow();
		initViewEnhancements();
	});
})();
