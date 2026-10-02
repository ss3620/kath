===  WowRevenue Pro ===
Contributors: wpxpo, anik4e, jakirhasan
Tags: up-sell, cross-sell, product bundles, buy x get y, quantity discount
Requires at least: 6.8  
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.2.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

WowRevenue Pro is the premium extension of the WowRevenue plugin.

== Description ==

WowRevenue Pro is a comprehensive tool designed to quickly boost average order value. Packed with powerful features, it effectively drives revenue growth for WooCommerce stores. Built with both beginners and experienced users in mind, WowRevenue Pro offers a user-friendly experience for all skill levels.

== Changelog ==
= 2.2.2 – 27 September 2026 =
* Security: Added nonce verification to the cart total AJAX request.

= 2.2.1 – 21 September 2026 =
* Fix: Pro now checks Free/Pro compatibility before loading Pro campaign, REST, frontend, and asset code, and shows a clear notice when the required Free version is unavailable.
* Fix: Pro activation and update notices no longer call Pro initialization methods before they are available.

= 2.2.0 – 20 September 2026 =
* Improvement: Free plugin minimum version requirement updated to 2.3.0, matching the free plugin's new Pro-campaign routing filter.
* Fix: The free-plugin version check and auto-update, previously defined but never run, are now wired up so an outdated free plugin is actually detected and updated.
* New: If the free plugin can't be auto-updated to a compatible version, WowRevenue Pro now shows a persistent admin notice explaining the version mismatch, with links to download the latest free plugin or contact support.

= 2.1.4 – 19 July 2026 =
* Improvement: Free plugin minimum version requirement updated to 2.2.8 to prevent fatal errors when only Pro is updated.
* Fix: Free plugin auto-update now installs the new version in place instead of deleting first, so a failed download can no longer leave the site without the free plugin.
* Fix: Free plugin auto-update no longer interrupts admin pages on download errors.

= 2.1.3 – 07 July 2026 =
* Fix: Double Order validation strengthened for minimum quantity to claim offer.
* Fix: Mix Match extentable filter now calculates campaign products instead of all products in cart.

= 2.1.2 – 01 July 2026 =
* New: Mix Match Custom hooks for total quantity based discounts.
* Fix: Double Order Session Logic Improvements.

= 2.1.1 – 20 April 2026 =
* Fix: Compatibility issues with other WPXPO plugins.

= 2.1.0 – 19 April 2026 =
* Fix: Translation text domains updated.

= 2.0.8 – 30 Mar 2026 =
* Fix: Some Issue Fixed.

= 2.0.7 – 22th Mar 2026 =
* Fix: Some Issue Fixed.

= 2.0.6 – 02 February 2026 =
* New: Added a global base price filter to determine the price used before discount calculation for campaign types - Spending Goal, Frequently Bought Together, Mix & Match.
* Improvement: Discount calculation now supports both regular price and sale price as the base price through the new filter logic.
* Improvement: Display prices and save badges now respect the filtered base price across all supported campaigns.
* Fix: Improved consistency between frontend displayed prices and cart/checkout calculated prices when custom base pricing is applied via extensions.

= 2.0.5 – 28 January 2026 =
* New: Campaign items are now automatically removed when their required trigger products are removed from the cart (Frequently Bought Together and Mix & Match).
* New: Added support for Grouped Products in Mix & Match campaigns.
* Improvement: Improved Spending Goal behavior — free gift items are now removed automatically when the cart total drops below the goal amount.
* Improvement: Enhanced cart and mini-cart synchronization to ensure campaign items update consistently.
* Fix: Improved product handling to avoid conflicts by using a dedicated product context instead of modifying the global product object.
* Fix: Corrected discount eligibility checks when required products are removed from the cart.
* Fix: Resolved edge cases that could cause duplicate or inconsistent cart updates during campaign item removal.

= 2.0.4 – 03 December 2025 =
* Improvement: Better styling and alignment for drawer layouts — circular progress and text now properly centered.  
* Improvement: Cleaned up unused code for more stable and predictable output.  
* Improvement: Refreshed fragments/events for Spending Goal campaigns — gift item addition/removal now updates dynamically without full page refresh.
* Fix: Campaigns not showing when rendered via shortcode — resolved output issue to ensure correct display.  
* Fix: Added missing progress bar in drawer view and ensured full-width display for top/bottom site-wide positions.  
* Fix: Removed unnecessary output buffering that caused display inconsistencies.  
* Fix: Resolved hidden duplicate variable issue.  
* Fix: Corrected issue where Spending Goal free gift items were not receiving 0 price and could checkout at full price.

= 2.0.3 – 19 November 2025 =
* Fix: Corrected gift reward handling in Spending Goal campaigns.
* Fix: Resolved issues with Frequently Bought Together price updates and skip-add-to-cart behavior.
* Fix: Fixed Mix & Match reset and remove actions across all display modes (in-page, popup, floating).
* Fix: Fixed checkbox and tier styling issues, including conflicts with “Most Popular” tags.

= 2.0.2 – 16 November 2025 =
* Fix: Resolved tax calculation issue when using 'including' tax settings during gift claim from spending goal.
* Fix: Corrected incorrect AJAX cart total response for spending goal updates.
* Improvement: Enhanced gift automation by automatically adding gift products when all eligible items are selected.
* Improvement: Improved the positioning of the gift container (tooltip) based on screen size and the current gift step.

= 2.0.1 – 30 October 2025 =
* Improvement: Added comprehensive tax calculation support across all campaign types.
* Fix: Resolved responsive layout issues for Mix & Match tier sections inside the campaign builder.

= 2.0.0 – 20 October 2025 =
* New: Grid and List for better discount showcasing. 
* New: Builder restructure and redesign.
* New: Overall plugin performance optimization.
* New: Template system created and added for all campaigns.
* New: New discount styling system for discount campaigns. 
* New: Preset color palette added as style settings. 
* New: New templates can be created and saved for discount campaigns.
* New: Device-wise typography setup.
* New: Device-wise campaign appearance customization.
* New: Campaign visibility options added for different devices (Laptop, Tablet, Mobile).
* New: Campaign Grid option for product visibility. 
* New: Added Variable Product Support.
* New: Given Support for in-page, floating, and pop-up design dynamically.

= 1.1.8 – 30 September 2025 =
* Fix: Resolved an issue where the spending goal did not correctly include tax in the cart total.

= 1.1.7 – 30 July 2025 =
* Fix: Resolved Issue related to spending goal in both in-page and drawer views.

= 1.1.6 – 20 July 2025 =
* Improvement: Enhanced functionality of the License page.
* Fix: Resolved multiple offer issue in Spending Goal campaigns.
* Fix: Resolved issue with in-page warning for spending goals.

= 1.1.5 – 16 April 2025 =
* Fix: Spending Goal Cart Issue Fixed.

= 1.1.4 – 19 February 2025 =
* Update: Compatibility Added With WooCommerce Subscriptions, YITH WooCommerce Subscription, YayCurrency, FOX – Currency Switcher Professional for WooCommerce
* Fix: Spending Goal Right Slider Icon Color Not Working issue Fixed.

= 1.1.3 – 4 February 2025 =

* New: Spending Goal Campaign Added.
* Fix: Double Order Plus Not working on product variation issue fixed


= 1.1.2 – 27 January 2025 =

* Update: Improved content and messaging on the Campaign Page.
* Fix: Resolved issue with double order session persistence after cart update.
* Fix: Addressed validation issue for Mix and Match and Frequently Bought Together campaigns.
* Fix: Clear the selections for Mix and Match and Frequently Bought Together after adding to cart.
* Fix: Added compatibility for the Currency Switcher.
* Fix: Fixed issue with the "Add to Cart" animation not working on the Checkout button.
* Fix: Resolved issue where the Checkout button wasn't redirecting to the checkout page.

= 1.1.1 – 14 January 2025 =
* Update: Double Order Plus Campaign Added.

= 1.1.0 – 12 January 2025 =
* Update: Added new hooks to make extensibility for third party integreation.

= 1.0.4 – 4 December 2024 =
* Update: Translations Pot File Updated

= 1.0.3 – 6 November 2024 =
* New: Minicart/Sidecart Compatibility added

= 1.0.2 - 15 October 2024 =
* New: Campaign Multipage Support Added

= 1.0.1 - 30 September 2024 =
* Fix: Animated Add to cart not working on Bundle Discount.
* Fix: Fixed price not working on Buy X Get y.
* Fix: Resolved the server-dependent parse error in the Product Mix & Match Campaign.
* Fix: Resolved the trigger product auto required issue on Frequently Bought Together Campaign.
* Fix: Resolved the conflict between server timezone and end user timezone for the countdown timer.
* Fix: Automatically hide the countdown timer once it reaches zero.

= 1.0.0 - 26 September 2024 =
* New: Stable Release
