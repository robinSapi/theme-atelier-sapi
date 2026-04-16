(function () {
	function init() {
		var container = document.querySelector('.single-post-content .schema-faq');
		if (!container) return;

		var sections = container.querySelectorAll('.schema-faq-section');
		if (!sections.length) return;

		sections.forEach(function (section) {
			var question = section.querySelector('.schema-faq-question');
			if (!question) return;

			question.setAttribute('role', 'button');
			question.setAttribute('tabindex', '0');
			question.setAttribute('aria-expanded', 'false');

			function toggle() {
				var isOpen = section.classList.contains('faq-open');

				sections.forEach(function (other) {
					if (other !== section && other.classList.contains('faq-open')) {
						other.classList.remove('faq-open');
						var q = other.querySelector('.schema-faq-question');
						if (q) q.setAttribute('aria-expanded', 'false');
					}
				});

				if (isOpen) {
					section.classList.remove('faq-open');
					question.setAttribute('aria-expanded', 'false');
				} else {
					section.classList.add('faq-open');
					question.setAttribute('aria-expanded', 'true');
				}
			}

			question.addEventListener('click', toggle);
			question.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					toggle();
				}
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
