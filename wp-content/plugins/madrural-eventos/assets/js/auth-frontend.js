(function () {
	'use strict';

	function createConfirmModal() {
		var existing = document.getElementById('madrural-auth-confirm-modal');
		if (existing) {
			return existing;
		}

		var modal = document.createElement('div');
		modal.id = 'madrural-auth-confirm-modal';
		modal.className = 'madrural-confirm-modal';
		modal.setAttribute('aria-hidden', 'true');
		modal.innerHTML = '' +
			'<div class="madrural-confirm-modal__backdrop" data-confirm-cancel></div>' +
			'<div class="madrural-confirm-modal__dialog" role="dialog" aria-modal="true" aria-live="polite">' +
				'<p class="madrural-confirm-modal__message"></p>' +
				'<div class="madrural-confirm-modal__actions">' +
					'<button type="button" class="madrural-auth-btn" data-confirm-ok>Confirmar</button>' +
					'<button type="button" class="madrural-auth-btn madrural-auth-btn-secondary" data-confirm-cancel>Cancelar</button>' +
				'</div>' +
			'</div>';

		document.body.appendChild(modal);
		return modal;
	}

	function bindStyledConfirmations() {
		var forms = document.querySelectorAll('form.madrural-auth-confirm-form[data-confirm-message]');
		if (!forms.length) {
			return;
		}

		var modal = createConfirmModal();
		var messageNode = modal.querySelector('.madrural-confirm-modal__message');
		var okButton = modal.querySelector('[data-confirm-ok]');
		var cancelButtons = modal.querySelectorAll('[data-confirm-cancel]');
		if (typeof modal._pendingForm === 'undefined') {
			modal._pendingForm = null;
		}

		function closeModal() {
			modal.classList.remove('is-open');
			modal.setAttribute('aria-hidden', 'true');
			modal._pendingForm = null;
		}

		if (okButton && okButton.dataset.bound !== '1') {
			okButton.dataset.bound = '1';
			okButton.addEventListener('click', function () {
				if (modal._pendingForm) {
					modal._pendingForm.dataset.confirmedDelete = '1';
					if (typeof modal._pendingForm.requestSubmit === 'function') {
						modal._pendingForm.requestSubmit();
					} else {
						modal._pendingForm.submit();
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

		if (document.body.dataset.madruralAuthConfirmEscBound !== '1') {
			document.body.dataset.madruralAuthConfirmEscBound = '1';
			document.addEventListener('keydown', function (event) {
				if (event.key === 'Escape' && modal.classList.contains('is-open')) {
					closeModal();
				}
			});
		}

		Array.prototype.forEach.call(forms, function (form) {
			if (form.dataset.bound === '1') {
				return;
			}
			form.dataset.bound = '1';
			form.addEventListener('submit', function (event) {
				if ('1' === form.dataset.confirmedDelete) {
					return;
				}

				event.preventDefault();
				modal._pendingForm = form;
				messageNode.textContent = form.getAttribute('data-confirm-message') || '¿Confirmas esta acción?';
				modal.classList.add('is-open');
				modal.setAttribute('aria-hidden', 'false');
			});
		});
	}

	function bindFloatingMenu() {
		var wrapper = document.getElementById('madrural-auth-floating');
		var trigger = document.getElementById('madrural-auth-avatar');
		if (!wrapper || !trigger) {
			return;
		}

		if (trigger.dataset.bound === '1') {
			return;
		}
		trigger.dataset.bound = '1';

		trigger.addEventListener('click', function () {
			wrapper.classList.toggle('is-open');
		});

		var menuLinks = wrapper.querySelectorAll('a');
		Array.prototype.forEach.call(menuLinks, function (link) {
			if (link.dataset.boundClose === '1') {
				return;
			}
			link.dataset.boundClose = '1';
			link.addEventListener('click', function () {
				wrapper.classList.remove('is-open');
			});
		});

		document.addEventListener('click', function (event) {
			if (!wrapper.contains(event.target)) {
				wrapper.classList.remove('is-open');
			}
		});
	}

	function bindPasswordToggles() {
		var passwordToggles = document.querySelectorAll('.madrural-auth-password-toggle[data-target]');
		if (!passwordToggles.length) {
			return;
		}

		Array.prototype.forEach.call(passwordToggles, function (toggle) {
			if (toggle.dataset.bound === '1') {
				return;
			}
			toggle.dataset.bound = '1';
			toggle.addEventListener('click', function () {
				var targetId = toggle.getAttribute('data-target');
				var input = targetId ? document.getElementById(targetId) : null;
				if (!input) {
					return;
				}

				var showPassword = input.type === 'password';
				input.type = showPassword ? 'text' : 'password';
				toggle.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
				toggle.setAttribute('aria-label', showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
				toggle.textContent = showPassword ? '🙈' : '👁';
			});
		});
	}

	function initAuthEnhancements() {
		bindFloatingMenu();
		bindPasswordToggles();
		bindStyledConfirmations();
	}

	document.addEventListener('DOMContentLoaded', initAuthEnhancements);
	document.addEventListener('madrural:content-updated', initAuthEnhancements);
})();
