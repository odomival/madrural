(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		function createConfirmModal() {
			var existing = document.getElementById('madrural-confirm-modal');
			if (existing) {
				return existing;
			}

			var modal = document.createElement('div');
			modal.id = 'madrural-confirm-modal';
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
			var pendingForm = null;

			function closeModal() {
				modal.classList.remove('is-open');
				modal.setAttribute('aria-hidden', 'true');
				pendingForm = null;
			}

			okButton.addEventListener('click', function () {
				if (pendingForm) {
					pendingForm.submit();
				}
				closeModal();
			});

			Array.prototype.forEach.call(cancelButtons, function (button) {
				button.addEventListener('click', closeModal);
			});

			document.addEventListener('keydown', function (event) {
				if (event.key === 'Escape' && modal.classList.contains('is-open')) {
					closeModal();
				}
			});

			Array.prototype.forEach.call(forms, function (form) {
				form.addEventListener('submit', function (event) {
					event.preventDefault();
					pendingForm = form;
					messageNode.textContent = form.getAttribute('data-confirm-message') || '¿Confirmas esta acción?';
					modal.classList.add('is-open');
					modal.setAttribute('aria-hidden', 'false');
				});
			});
		}

		var wrapper = document.getElementById('madrural-auth-floating');
		var trigger = document.getElementById('madrural-auth-avatar');
		var passwordToggles = document.querySelectorAll('.madrural-auth-password-toggle[data-target]');
		if (!wrapper || !trigger) {
			wrapper = null;
			trigger = null;
		}

		if (wrapper && trigger) {
			trigger.addEventListener('click', function () {
				wrapper.classList.toggle('is-open');
			});

			document.addEventListener('click', function (event) {
				if (!wrapper.contains(event.target)) {
					wrapper.classList.remove('is-open');
				}
			});
		}

		if (passwordToggles.length) {
			Array.prototype.forEach.call(passwordToggles, function (toggle) {
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

		bindStyledConfirmations();
	});
})();
