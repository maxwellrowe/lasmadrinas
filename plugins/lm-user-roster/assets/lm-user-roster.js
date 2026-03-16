// A-Z Index
document.addEventListener('click', function (e) {
	const link = e.target.closest('.a-z-item');
	if (!link) return;

	e.preventDefault();

	const targetId = link.dataset.letter;
	const target = document.getElementById(targetId);

	if (target) {
		target.scrollIntoView({
			behavior: 'smooth',
			block: 'start'
		});
	}
});

// Search Filter
document.addEventListener('DOMContentLoaded', function () {
	const searchInput = document.getElementById('search-roster');
	if (!searchInput) return;

	const cards = Array.from(document.querySelectorAll('.lm-user-roster__card'));

	searchInput.addEventListener('input', function () {
		const query = this.value.toLowerCase().trim();

		cards.forEach(card => {
			const text = card.textContent.toLowerCase();

			if (!query || text.includes(query)) {
				card.style.display = '';
			} else {
				card.style.display = 'none';
			}
		});
	});
});