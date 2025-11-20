/**
 * Popup Loader Script
 *
 * Handles popup rendering and trigger logic on the frontend.
 *
 * @package ConvertLab
 * @since 1.0.0
 */

(function() {
	'use strict';

	// Check if convertlabPopup is available
	if (typeof convertlabPopup === 'undefined') {
		return;
	}

	const { apiUrl, nonce, popups } = convertlabPopup;

	/**
	 * Popup Manager Class
	 */
	class PopupManager {
		constructor() {
			this.popups = popups || [];
			this.shownPopups = new Set();
			this.init();
		}

		init() {
			if (this.popups.length === 0) {
				return;
			}

			// Process each popup
			this.popups.forEach(popup => {
				this.setupPopup(popup);
			});
		}

		setupPopup(popup) {
			const config = popup.config || {};
			const triggers = config.triggers || {};

			// Check if already shown
			if (triggers.show_once && this.shownPopups.has(popup.id)) {
				return;
			}

			// Setup triggers
			if (triggers.time_delay) {
				setTimeout(() => {
					this.showPopup(popup);
				}, triggers.time_delay * 1000);
			}

			if (triggers.scroll_percent) {
				this.setupScrollTrigger(popup, triggers.scroll_percent);
			}

			// Exit intent (mouse leaving viewport)
			this.setupExitIntent(popup);
		}

		setupScrollTrigger(popup, scrollPercent) {
			let triggered = false;

			const handleScroll = () => {
				if (triggered) return;

				const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
				const docHeight = document.documentElement.scrollHeight - window.innerHeight;
				const scrollPercentReached = (scrollTop / docHeight) * 100;

				if (scrollPercentReached >= scrollPercent) {
					triggered = true;
					this.showPopup(popup);
					window.removeEventListener('scroll', handleScroll);
				}
			};

			window.addEventListener('scroll', handleScroll, { passive: true });
		}

		setupExitIntent(popup) {
			let exitIntentTriggered = false;

			document.addEventListener('mouseout', (e) => {
				if (exitIntentTriggered) return;

				if (!e.toElement && !e.relatedTarget && e.clientY < 10) {
					exitIntentTriggered = true;
					this.showPopup(popup);
				}
			});
		}

		showPopup(popup) {
			// Check if already shown
			if (this.shownPopups.has(popup.id)) {
				return;
			}

			// Mark as shown
			this.shownPopups.add(popup.id);

			// Record impression
			this.recordEvent(popup.id, 'impression');

			// Render popup
			this.renderPopup(popup);
		}

		renderPopup(popup) {
			const container = document.getElementById('convertlab-popup-container');
			if (!container) return;

			const config = popup.config || {};
			const design = config.design || {};
			const fields = config.fields || [];
			const thankYou = config.thank_you || {};

			// Create popup HTML
			const popupHTML = `
				<div class="convertlab-popup-overlay" data-popup-id="${popup.id}">
					<div class="convertlab-popup" style="background-color: ${design.background_color || '#ffffff'};">
						<button class="convertlab-popup-close" aria-label="Close">&times;</button>
						${design.image ? `<div class="convertlab-popup-image"><img src="${design.image}" alt="" /></div>` : ''}
						<div class="convertlab-popup-content">
							${design.title ? `<h2 class="convertlab-popup-title">${this.escapeHtml(design.title)}</h2>` : ''}
							${design.text ? `<div class="convertlab-popup-text">${design.text}</div>` : ''}
							<form class="convertlab-popup-form" data-popup-id="${popup.id}">
								${fields.map(field => this.renderField(field)).join('')}
								<button type="submit" class="convertlab-popup-submit" style="background-color: ${design.button_color || '#0073aa'};">
									${this.escapeHtml(design.button_text || 'Submit')}
								</button>
							</form>
						</div>
					</div>
				</div>
			`;

			container.innerHTML = popupHTML;

			// Add event listeners
			this.attachPopupEvents(popup.id, thankYou);
		}

		renderField(field) {
			const required = field.required ? 'required' : '';
			const placeholder = field.placeholder || field.label || '';

			switch (field.type) {
				case 'email':
					return `
						<div class="convertlab-field">
							<label for="clb-field-${field.name}">${this.escapeHtml(field.label || 'Email')}</label>
							<input type="email" id="clb-field-${field.name}" name="${field.name}" placeholder="${this.escapeHtml(placeholder)}" ${required} />
						</div>
					`;
				case 'text':
				case 'name':
					return `
						<div class="convertlab-field">
							<label for="clb-field-${field.name}">${this.escapeHtml(field.label || 'Name')}</label>
							<input type="text" id="clb-field-${field.name}" name="${field.name}" placeholder="${this.escapeHtml(placeholder)}" ${required} />
						</div>
					`;
				case 'phone':
					return `
						<div class="convertlab-field">
							<label for="clb-field-${field.name}">${this.escapeHtml(field.label || 'Phone')}</label>
							<input type="tel" id="clb-field-${field.name}" name="${field.name}" placeholder="${this.escapeHtml(placeholder)}" ${required} />
						</div>
					`;
				default:
					return '';
			}
		}

		attachPopupEvents(popupId, thankYou) {
			const overlay = document.querySelector(`.convertlab-popup-overlay[data-popup-id="${popupId}"]`);
			if (!overlay) return;

			const closeBtn = overlay.querySelector('.convertlab-popup-close');
			const form = overlay.querySelector('.convertlab-popup-form');

			// Close button
			if (closeBtn) {
				closeBtn.addEventListener('click', () => this.closePopup(popupId));
			}

			// Close on overlay click
			overlay.addEventListener('click', (e) => {
				if (e.target === overlay) {
					this.closePopup(popupId);
				}
			});

			// Form submission
			if (form) {
				form.addEventListener('submit', (e) => {
					e.preventDefault();
					this.handleFormSubmit(popupId, form, thankYou);
				});
			}

			// Show popup with animation
			setTimeout(() => {
				overlay.classList.add('convertlab-popup-active');
			}, 10);
		}

		async handleFormSubmit(popupId, form, thankYou) {
			const formData = new FormData(form);
			const data = {};

			for (const [key, value] of formData.entries()) {
				data[key] = value;
			}

			const email = data.email || data.clb_email || '';
			const name = data.name || data.clb_name || '';
			const phone = data.phone || data.clb_phone || '';

			if (!email) {
				alert('Please enter your email address.');
				return;
			}

			// Disable form
			const submitBtn = form.querySelector('.convertlab-popup-submit');
			if (submitBtn) {
				submitBtn.disabled = true;
				submitBtn.textContent = 'Submitting...';
			}

			try {
				const response = await fetch(`${apiUrl}lead/submit`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonce,
					},
					body: JSON.stringify({
						popup_id: popupId,
						email: email,
						name: name,
						phone: phone,
						form_data: data,
					}),
				});

				const result = await response.json();

				if (result.success) {
					// Show thank you message
					if (thankYou.redirect) {
						window.location.href = thankYou.redirect;
					} else {
						this.showThankYou(popupId, thankYou.message || 'Thank you for subscribing!');
					}
				} else {
					alert(result.message || 'An error occurred. Please try again.');
					if (submitBtn) {
						submitBtn.disabled = false;
						submitBtn.textContent = form.querySelector('button[type="submit"]').dataset.originalText || 'Submit';
					}
				}
			} catch (error) {
				console.error('ConvertLab: Form submission error', error);
				alert('An error occurred. Please try again.');
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.textContent = form.querySelector('button[type="submit"]').dataset.originalText || 'Submit';
				}
			}
		}

		showThankYou(popupId, message) {
			const overlay = document.querySelector(`.convertlab-popup-overlay[data-popup-id="${popupId}"]`);
			if (!overlay) return;

			const popup = overlay.querySelector('.convertlab-popup');
			if (popup) {
				popup.innerHTML = `
					<div class="convertlab-popup-content">
						<div class="convertlab-thank-you">${message}</div>
					</div>
				`;

				setTimeout(() => {
					this.closePopup(popupId);
				}, 3000);
			}
		}

		closePopup(popupId) {
			const overlay = document.querySelector(`.convertlab-popup-overlay[data-popup-id="${popupId}"]`);
			if (overlay) {
				overlay.classList.remove('convertlab-popup-active');
				setTimeout(() => {
					overlay.remove();
				}, 300);
			}
		}

		async recordEvent(popupId, eventType) {
			try {
				await fetch(`${apiUrl}event`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonce,
					},
					body: JSON.stringify({
						popup_id: popupId,
						event_type: eventType,
					}),
				});
			} catch (error) {
				console.error('ConvertLab: Event recording error', error);
			}
		}

		escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => {
			new PopupManager();
		});
	} else {
		new PopupManager();
	}
})();

