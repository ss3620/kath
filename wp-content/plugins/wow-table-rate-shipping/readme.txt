=== WowShipping - Weight Based Table Rate Shipping with Live Rates for UPS, USPS, DHL ===
Contributors: wpxpo
Tags: ups, table rate, table rate shipping, woocommerce table rate shipping, weight based shipping
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.33
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Flexible table rate shipping plugin with live rates to solve any complex shipping scenario using weight, cart, location, and 30+ conditions.

== Description ==

🥇 **Advanced Weight Based** Table Rate Shipping
🚚 360-Degree **Shipping Rate Solution** for WooCommerce
🏋️‍♂️ **Live Shipping Rates** from Popular Carriers
💕 A plugin by WPXPO that **empowers 60K+ businesses!**
📞 Dedicated support team with a **4.9/5** rating on [Trustpilot Rating](https://uk.trustpilot.com/review/wpxpo.com).

🔥 [WowShipping Pro](https://www.wpxpo.com/product/wowshipping#pricing) | 📃 [Documentation](https://www.wpxpo.com/docs/wowshipping/)

== Table Rate Shipping for WooCommerce ==

[youtube https://www.youtube.com/watch?v=OQ-b1tp4gYg ]

Set up table rate shipping by defining rules based on conditions like weight, quantity, cart value, or location. Create multiple rate tiers and apply different costs for each range, so shipping updates automatically based on the order. This gives you precise control over how shipping is calculated for every scenario.

== WowShipping Unlocks 30+ Table Rate Shipping Conditions: ==

✅ Table Rate Shipping
✅ Quantity-based Shipping
✅ Location-based Shipping
✅ Dimension-based Shipping
✅ Cart Subtotal-based Shipping
✅ Product-based Shipping
✅ Category-based Shipping
✅ Product Class-based Shipping
✅ User role-based Shipping
✅ Date & Time-based Shipping
➕  Much more!

== Key Features ==

* Set shipping costs using cart conditions, weight, products, customer role, location, time, and more
* Create flexible table rates with multiple tiers and up to 3 combined conditions
* Use fixed, incremental, or base + additional cost calculations based on cart, product, or order data
* Show real-time rates from carriers like DHL, FedEx, UPS, USPS, and Canada Post
* Calculate rates using packaging methods like single package or custom boxes
* Apply default weight and dimensions when product data is missing
* Offer conditional free shipping based on cart, product, location, or customer rules
* Show or hide shipping methods using advanced display rules
* Special Actions to control whether to continue, stop, or hide a shipping method
* Limit shipping methods visibility by user type (guest, logged-in, or role-based)
* Set Markup to increase or decrease carrier rates, with a backup price if rates fail
* Handling fees to add extra charges per order or per item as a fixed amount or percentage
* Configure shipping tax and control how it appears at checkout
* Add multiple shipping methods per zone using WooCommerce zones

== Weight-Based Shipping for WooCommerce ==

[youtube https://www.youtube.com/watch?v=1EUrp3HTzIo ]

Calculate shipping costs based on **cart weight or product weight**, ensuring accurate pricing for lightweight and heavy orders. Define weight ranges or use incremental logic to adjust costs automatically as weight increases.

Combine weight with other parameters like location, product category, or cart total to create advanced weight-based shipping logic for precise control.

**Examples:**

* $5 for 0-5kg, then $2 per additional kg
* 0-10kg = $8, but only for USA customers; others = $12
* Fitness category + weight 0-5kg → $6, above 5kg → $10
* Weight 0-3kg + cart total > $50 → Free shipping
* Weight 5-15kg + user role = Wholesale → $7 flat rate

== Shipping Live Rates for WooCommerce ==

[youtube https://www.youtube.com/watch?v=jdRTVCmr00w]

Show accurate shipping costs at checkout using real-time carrier rates instead of fixed pricing. WowShipping connects with popular carriers and calculates shipping based on weight, dimensions, destination, and selected services.

= Shipping Live Rates for UPS =

Display real-time UPS shipping rates by connecting your UPS account using API credentials. Choose from specific UPS services like Ground, Next Day Air, or Worldwide Express. 
Set default weight & dimensions, packaging strategies, rate markup, and fallback pricing to ensure accurate and reliable shipping calculations.

= Shipping Live Rates for USPS =

USPS integration allows you to fetch real-time shipping rates based on selected services such as First Class or Ground Advantage. 
Choose commercial or retail pricing, fetch delivery time from the API, and add extra services like tracking to improve shipping accuracy and transparency for customers.

= Shipping Live Rates for FedEx =

Connect your FedEx account to fetch live shipping prices at checkout based on selected services and package details. 
Choose services like International Priority, First Overnight, Ground, or Home Delivery. Set up special services such as delivery appointment, COD, and shipment insurance, along with other features such as pickup type, duties payment, and account-based rates.

= Shipping Live Rates for DHL Express =

Connect with your DHL Express account to retrieve live shipping rates at checkout based on selected services and shipment details. 
Choose products like Domestic Express, Express Worldwide, Economy Select, or Same Day, with options for different delivery times. It supports optional shipment insurance, giving you control over pricing and delivery coverage.

= Shipping Live Rates for Australia Post =

Australia Post integration uses an auth key to connect your account. You can choose services like Parcel Post, Express Post, and International Courier or Express services, allowing flexible delivery choices with accurate pricing at checkout.

= Shipping Live Rates for Canada Post =

Integrate easily with Canada Post using your customer account details. You can choose services like Regular Parcel, Expedited, or Priority. 

Enable extra options such as signature, coverage, COD, proof of age, and delivery instructions, along with settings for marking items to be unpackaged or sent through mailing tubes.

= Shipping Live Rates for Sendle =

Sendle integration uses your Sendle ID and API key to fetch real-time rates. You can choose options like Saver, Preferred, and 2-3 Day Guaranteed services, with both pickup and drop-off choices. 

**Note:** All of the integrations come with two modes: live and sandbox mode, helping you test the setup before showing real rates at checkout.

== Conditional Free Shipping ==

WowShipping lets you offer free shipping when specific conditions are met, giving you full control instead of applying it to every order. 

Choose from 37+ conditional parameters, including: Cart: quantity, total, weight, volume, coupons; Product: category, tags, price, quantity; Customer: user role, email, order history, and much more.

Add up to 3 conditions per rule, and all conditions follow AND logic, meaning every condition must be true for free shipping to apply.

**Example:**

* Free shipping only when: Cart total > $100 & Country = USA
* Free shipping only when: Product category = Fitness, Cart weight between 0–5 kg & User role = VIP

== Fixed and Incremental Table Rates ==

WowShipping gives you three ways to define shipping cost: Fixed Rate, Incremental Rate, and Base Cost + Additional Charge. Each option is built to match different pricing needs using parameters like cart quantity, weight, product price, and more.

= Fixed Rates =

Apply a fixed shipping cost when meeting a specific condition. This works best when the shipping price does not change for that particular condition, making pricing predictable.

**Example:**

* $20 shipping for all orders in the USA
* $12 shipping for all products in the “Furniture” category
* $15 shipping for orders with a weight between 10 and 20 kg

= Incremental Rates =

Shipping cost increases step by step as the order grows. You choose what it depends on (like weight, quantity, or price), and the system adds cost repeatedly as that value increases.

**Example:**

* $2 for every 1 kg → 3.5 kg = $7 shipping
* $3 for every 2 items → 5 items = $9 shipping

= Fixed + Incremental Rates =

Combine a base cost with additional charges after a defined limit. This allows you to set a minimum shipping fee and then scale pricing for larger or heavier orders.

**Example:**

* $10 for up to 5 kg, then $2 for every additional 1 kg → 8 kg = $16 shipping
* $20 for first 5 items, then $2 for every extra 2 items → 9 items = $24 shipping

== Special Actions ==

Use Special Actions to control what happens after a rule is matched. You can decide whether to continue checking more rules, stop with the current cost, or hide the shipping method.

* **Pass** → Continue to the next rule
* **Stop** → Use the calculated cost so far and stop checking further rules
* **Deny** → Hide the shipping method completely and stop processing

**For example:**

* A store charges $5 for the first 5kg and uses Pass, so the next tier adds $2 per extra kg.
* If a customer’s country is not supported, the rule uses Deny to completely hide that shipping method.

== Shipping Rates Calculations ==

WowShipping lets you control how the final shipping cost is calculated when multiple rate tiers are involved. 

You can choose from different calculation methods:

* **Sum** → Add costs from all tiers
* **Highest** → Apply the highest cost among tiers
* **Lowest** → Apply the lowest cost
* **First** → Use the first applicable tier
* **Last** → Use the last applicable tier

**Examples:**

* Weight rule ($5) + location rule ($3) → **Sum = $8**
* Multiple matched tiers ($5, $8, $10)  → **Highest = $10**

== Advanced Display Rules ==

Utilize the Advanced Display Rules to have extra control when a shipping method is shown at checkout. Make use of the available parameters such as cart quantity, cart total, subtotal, weight, coupons, product, category, tags, user role, and more – to create a custom display rule. 

* **Example 1:** Show Free Shipping only if cart total > $100 and country = USA
* **Example 2:** Show Express Delivery only if weight < 5kg and city = New York

This ensures the shipping methods appear at checkout, only when all the display conditions are met.

== Solve Any Complex Shipping Scenario with WowShipping Today! ==

WowShipping provides a powerful, no-code shipping logic builder that allows you to stack up multiple conditions using 30+ conditions using advanced AND/OR logic – letting you solve any complex shipping scenarios:

**🧩 Mixed Quantity Rules (Category + Product)**

* Apply tiered pricing for a category (e.g., clothing)
* Override with special pricing for specific product combinations
* **Example:** Lower rate when “T-shirt + Hoodie” are purchased together

**🌍 Country + Weight-Based Charges**

* Set a fixed base cost per country
* Add variable cost per kg for accurate international shipping
* **Example:** Canada → $12 + $1.5/kg, UK → $14 + $2/kg

**⚖️ Price + Weight Combined Logic**

* Reward high-value orders with lower rates
* Charge more for heavy, low-margin products
* **Example:** 
           Cart value ≥ $200 and weight ≤ 5 kg → $5 shipping; 
           Cart value < $200 or weight > 5 kg → $12 shipping

**👤 User Role + Cart + Category Conditions**

* Create exclusive rules for specific customer roles
* Combine with cart value and product category
* **Example:** Free shipping for B2B users on $1000+ furniture orders

**📍 City + Day-Based Pricing**

* Set different rates for specific cities or regions
* Apply different pricing for weekdays vs weekends
* **Example:** Higher delivery cost for weekend orders outside the city 

**Want to read more about these use cases in detail? Check out our [use cases documentation!](https://wpxpo.com/docs/wowshipping/shipping-scenarios/)**

== Installation ==

= Minimum Requirements =

* WordPress 6.4 or greater
* PHP version 7.4 or greater
* MySQL version 5.6 or greater

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t even need to leave your web browser. 

1. Go to your WordPress Plugin installation menu (Dashboard > Plugins > Add New)
2. In the search field type "WowShipping" and press enter.
3. "Install Now" and then click "Active"

= Manual installation =

For Manual installation, you download our product from WordPress directory uploading it to your web-server via your FTP or CPanel application.

1. Download the plugin and unzip it
2. Using an FTP program or CPanel upload the unzipped plugin folder to your WordPress installation’s wp-content/plugins/ directory.
3. Activate the plugin from the Plugins menu (Dashboard > Plugins > Installed Plugins) within the WordPress admin.

= Upgrading =

Automatic update working great for this plugin. If you customize the plugin make sure to store a backup before update.

Sometimes because of the permalink issue during the plugin update, just go to WordPress > Settings > Permalinks and hit the ‘Save’ button.

== Frequently Asked Questions ==

= Does WowShipping support multiple conditions per rate? =
Yes. You can add up to 3 condition rows for a single rate and combine them with display conditions at the table level.

= Can I use shipping classes? =
Yes. You can set different rates for each shipping class.

= Does it support live carrier rates? =
Yes, via WowShipping Pro for supported carriers.

= Can I offer conditional free shipping? =
Yes. Configure minimum order amount, location, products, or user roles to trigger free shipping.

== Screenshots ==

1. Table Rate Shipping
2. Weight-Based Shipping
3. 30+ Table Rate Shipping Conditions
4. Display Multiple Shipping Methods
5. Conditional Free Shipping
6. Live Carrier Rates

== Other Wow Plugins by WPXPO ==

We are excited that you are considering using WowShipping! Be sure to explore our other amazing plugins as well:

📄 **[WowInvoice](https://wordpress.org/plugins/wow-pdf-invoices-packing-slips)**: The all-in-one PDF invoices & packing slips plugin for WooCommerce for generating professional order documents without manual effort.

➕ **[WowAddons 🔥](https://wordpress.org/plugins/product-addons/)**: The ultimate product addons plugin for WooCommerce with custom fields and extra product options.

🧲 **[WowOptin](https://wordpress.org/plugins/optin/)**: The next-generation opt-in and pop-up builder with Canva-style design freedom and advanced audience targeting.

💝 **[WowStore](https://wordpress.org/plugins/product-blocks/)**: A complete WooCommerce builder for building professional, high-converting eCommerce stores.

💸 **[WowRevenue](https://wordpress.org/plugins/revenue/)**: A collection of WooCommerce product bundles and discount campaigns to boost revenue by increasing AOV.

📦 **[WholesaleX](https://wordpress.org/plugins/wholesalex/)**: The best Wholesale plugin for WooCommerce with Dynamic Pricing & Discount Rules and Bulk Order Form.

📃 **[PostX](https://wordpress.org/plugins/ultimate-post/)**: The best Gutenberg blocks plugin to build professional News, Magazine and Blogging websites effortlessly.

= Author =
Developed by [WPXPO](https://www.wpxpo.com). [Contribute to WowShipping on Bitbucket](https://bitbucket.org/wpstabon/table-rate-shipping/) and join the party.

= Privacy & Data Usage =

This plugin uses [IPInfo](https://ipinfo.io/) to determine the user’s country only for analytics purposes. The request is made only after the user gives consent (e.g., admin notice banner, plugin deactivation survey). Only the country code is logged for analytics.

== Changelog ==

= 1.1.33 - 27 Aug 2026 =
* Fix: single box packing algorithm improvement.
* Fix: Performance improvements.

= 1.1.32 - 24 Aug 2026 =
* Fix: guide tour popup not working.

= 1.1.31 - 18 Aug 2026 =
* New: Custom box size on order meta.

= 1.1.30 - 10 Aug 2026 =
* Fix: Added missing capability check to the plugin install AJAX handler.
* Fix: USPS custom boxes dimension/weight conversion was not working correctly.

= 1.1.29 - 22 Jul 2026 =
* Fix: Small bug fixes.

= 1.1.28 - 20 Jul 2026 =
* Fix: Code optimization.

= 1.1.27 - 09 Jul 2026 =
* Fix: Code optimization.

[View the changelogs for older versions.](https://bitbucket.org/wpstabon/table-rate-shipping/src/release/changelog.txt)