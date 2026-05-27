(function () {
	'use strict';

	function initCarousel(carousel) {
		var track = carousel.querySelector('.madrural-evento-carousel-track');
		if (!track) {
			return;
		}

		var slides = Array.prototype.slice.call(track.querySelectorAll('.madrural-evento-slide'));
		if (slides.length <= 1) {
			return;
		}

		var prevButton = carousel.querySelector('.madrural-carousel-prev');
		var nextButton = carousel.querySelector('.madrural-carousel-next');
		var dotsContainer = carousel.querySelector('.madrural-carousel-dots');
		var currentIndex = 0;
		var autoplayTimer = null;
		var autoplayDelay = 5000;

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
			stopAutoplay();
			autoplayTimer = window.setInterval(function () {
				goTo(currentIndex + 1);
			}, autoplayDelay);
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

		if (closeButton) {
			closeButton.addEventListener('click', closeModal);
		}

		modal.addEventListener('click', function (event) {
			if (event.target === modal) {
				closeModal();
			}
		});

		document.addEventListener('keydown', function (event) {
			if ('Escape' === event.key && modal.classList.contains('is-open')) {
				closeModal();
			}
		});
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
		var pendingUrl = '';

		function closeModal() {
			modal.classList.remove('is-open');
			modal.setAttribute('aria-hidden', 'true');
			pendingUrl = '';
		}

		okButton.addEventListener('click', function () {
			if (pendingUrl) {
				window.location.href = pendingUrl;
			}
			closeModal();
		});

		Array.prototype.forEach.call(cancelButtons, function (button) {
			button.addEventListener('click', closeModal);
		});

		document.addEventListener('keydown', function (event) {
			if ('Escape' === event.key && modal.classList.contains('is-open')) {
				closeModal();
			}
		});

		Array.prototype.forEach.call(links, function (link) {
			link.addEventListener('click', function (event) {
				event.preventDefault();
				pendingUrl = link.getAttribute('href') || '';
				messageNode.textContent = link.getAttribute('data-confirm-message') || '¿Confirmas esta acción?';
				modal.classList.add('is-open');
				modal.setAttribute('aria-hidden', 'false');
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		var carousels = document.querySelectorAll('.js-madrural-carousel');
		Array.prototype.forEach.call(carousels, initCarousel);
		initEventGalleryModal();
		initStyledConfirmLinks();
	});
})();
