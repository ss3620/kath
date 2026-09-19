/* phpcs:ignoreFile */
/**
 * Build referral link from user input
 *
 * @param {string} input - The URL or path to convert
 * @param {string} affiliateIdentifier - The affiliate ID to append
 * @param {Object} additionalParams - Additional query parameters to append (e.g., utm_campaign, utm_source)
 * @returns {{valid: boolean, url: string|null, reason: string|null}}
 */
const buildReferralLink = (input = '', affiliateIdentifier = '', additionalParams = {}) => {
	const {_x, sprintf} = wp.i18n;

	// Validate required parameters
	if(!input || !affiliateIdentifier) {
		return {
			valid: false,
			url: null,
			reason: !input ? _x('Input URL is required', 'error message while creating referral link', 'affiliate-for-woocommerce') : _x('Affiliate identifier is required', 'error message while creating referral link', 'affiliate-for-woocommerce')
		};
	}

	input = input.trim();
	input = input.replace(/([^:]\/)\/+/g, '$1');

	// Block dangerous protocols
	if(/^\s*(javascript|data|vbscript|file|blob|mailto):/i.test(input)) {
		return {
			valid: false,
			url: null,
			reason: _x('Invalid page link', 'error message while creating referral link', 'affiliate-for-woocommerce')
		};
	}

	const {homeURL, refParam, isPrettyLink} = afwcBuildRefUrlParams;

	const homeHost = new URL(homeURL).host.replace(/^www\./, '').toLowerCase();
	let urlObj;

	try {
		// Handle different input formats
		if(input.startsWith('http://') || input.startsWith('https://')) {
			urlObj = new URL(input); // Absolute URL
		} else if(input.startsWith('/')) {
			urlObj = new URL(input, homeURL); // Relative path
		} else {
			urlObj = new URL('https://' + input);
			if(urlObj.host.replace(/^www\./, '').toLowerCase() !== homeHost) {
				urlObj = new URL(input, homeURL);
			}
		}
	} catch(e) {
		return {
			valid: false,
			url: null,
			reason: _x('Invalid page link', 'error message while creating referral link', 'affiliate-for-woocommerce')
		};
	}

	// Validate that URL belongs to the same domain
	if(urlObj.host.replace(/^www\./, '').toLowerCase() !== homeHost) {
		return {
			valid: false,
			url: null,
			reason: _x('Invalid page link', 'error message while creating referral link', 'affiliate-for-woocommerce')
		};
	}

	// Handle query parameter based referral links
	if(isPrettyLink === 'no') {
		// Ensure trailing slash before query params
		if(!urlObj.pathname.endsWith('/')) {
			urlObj.pathname += '/';
		}

		// Remove existing ref param if present
		urlObj.searchParams.delete(refParam);

		// Add new ref param
		urlObj.searchParams.append(refParam, affiliateIdentifier);

		// Add additional parameters
		if(additionalParams && typeof additionalParams === 'object') {
			Object.entries(additionalParams).forEach(([key, value]) => {
				if(key && value !== undefined && value !== null) {
					// Remove existing param if present
					urlObj.searchParams.delete(key);
					// Add new param
					urlObj.searchParams.append(key, String(value));
				}
			});
		}

		return {
			valid: true,
			url: urlObj.toString(),
			reason: null
		};
	}

	// Handle pretty link format (/ref/ID/)
	if(isPrettyLink === 'yes') {
		let path = urlObj.pathname;

		// Remove existing pretty ref pattern (all occurrences)
		const prettyRegex = new RegExp(`\\/+${refParam}\\/[^\\/]+\\/?`, 'g');
		path = path.replace(prettyRegex, '');

		// Handle empty or root path
		if(!path || path === '') {
			path = '/';
		}

		// Ensure path starts with /
		if(!path.startsWith('/')) {
			path = '/' + path;
		}

		// Ensure path ends with /
		if(!path.endsWith('/')) {
			path += '/';
		}

		// Add the pretty ref link at the end
		path += `${refParam}/${affiliateIdentifier}/`;

		urlObj.pathname = path;

		// Add additional parameters as query strings
		if(additionalParams && typeof additionalParams === 'object') {
			Object.entries(additionalParams).forEach(([key, value]) => {
				if(key && value !== undefined && value !== null) {
					// Remove existing param if present
					urlObj.searchParams.delete(key);
					// Add new param
					urlObj.searchParams.append(key, String(value));
				}
			});
		}

		return {
			valid: true,
			url: urlObj.toString(),
			reason: null
		};
	}

	return {
		valid: false,
		url: null,
		reason: _x('Something went wrong. Please try again later.', 'error message while creating referral link', 'affiliate-for-woocommerce')
	};
};
