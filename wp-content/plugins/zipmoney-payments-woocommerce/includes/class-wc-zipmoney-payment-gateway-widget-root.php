<?php
/**
 * Keeps the Zip configuration root ahead of everything else we print.
 *
 * The bundle takes the first element in the document carrying zm-merchant, zm-region or
 * zm-widget as its configuration. Anything of ours printed before the root is therefore
 * read as the root — and none of them carries the merchant key, so the bundle falls back
 * to the literal "default" merchant and serves the generic asset. The failure is silent:
 * a widget still appears, it just stops quoting the merchant's own terms.
 *
 * wp_body_open puts the root first on any theme that calls it — required of themes only
 * since WP 5.2, and themes in the wild still skip it. Rather than hunt for another hook,
 * every place that prints zm-* markup asks this object for the root first, so the order
 * holds by construction instead of by the theme's good behaviour.
 *
 * Pure: no WordPress, so the ordering rule itself is testable.
 */
class WC_Zipmoney_Payment_Gateway_Widget_Root {

	/**
	 * Whether the root still has to be printed. It is owed until something prints it.
	 *
	 * @var bool
	 */
	private $owed = true;

	/**
	 * The root markup, once — an empty string on every later call.
	 *
	 * Empty markup never settles the debt: a shop with Zip switched off builds no root,
	 * and swallowing the flag there would leave a later widget without one.
	 *
	 * @param mixed $markup The root element, as built for this request.
	 * @return string
	 */
	public function claim( $markup ) {
		$markup = is_scalar( $markup ) ? (string) $markup : '';

		if ( ! $this->owed || '' === $markup ) {
			return '';
		}

		$this->owed = false;

		return $markup;
	}

	/**
	 * $html with the root in front of it, when the root is still owed.
	 *
	 * Nothing to print means nothing to lead: a product with no price renders no widget,
	 * and claiming the root for that empty string would spend it on markup that never
	 * reaches the page.
	 *
	 * @param mixed $markup The root element, as built for this request.
	 * @param mixed $html   The markup about to be printed.
	 * @return string
	 */
	public function ahead_of( $markup, $html ) {
		$html = is_scalar( $html ) ? (string) $html : '';

		if ( '' === $html ) {
			return '';
		}

		return $this->claim( $markup ) . $html;
	}

	/**
	 * @return bool Whether the root still has to be printed.
	 */
	public function is_owed() {
		return $this->owed;
	}
}
