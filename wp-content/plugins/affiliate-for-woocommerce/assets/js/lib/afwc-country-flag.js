/* phpcs:ignoreFile */
const CountryFlag = {
	getEmoji: function(countryCode) {
		if(!countryCode || typeof countryCode !== 'string') return '';
		return countryCode.toUpperCase().replace(/./g, char =>
			String.fromCodePoint(127397 + char.charCodeAt())
		);
	},
	setEmojis: function() {
		let elements = document.querySelectorAll('.afwc-visits-country:not(.afwc-flag-rendered)');
		if(!elements.length) return;

		elements.forEach(el => {
			let countryCode = el.dataset.country_code;
			let countryName = el.dataset.country_name;
			if(!countryCode && !countryName) return;

			let countryFlag = this.getEmoji(countryCode);
			el.textContent = countryFlag && countryFlag !== countryCode
				? countryFlag
				: (countryName || '');

			el.classList.add('afwc-flag-rendered');
		});
	}
};
