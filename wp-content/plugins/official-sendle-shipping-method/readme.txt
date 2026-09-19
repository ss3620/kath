=== Sendle Shipping Plugin ===
Contributors:       joovii
Plugin Name:        Sendle Shipping Plugin
Plugin URI:         https://joovii.com/shipping-method/sendle-wp.html
Tags:               shipping-delivery, woocommerce, joovii, sendle, tracking
Author URI:         https://joovii.com
Author:             Bivash Ranjan Munshi
Donate link:        https://joovii.com/woocommerce-plugin/wordpress-sendle-premium-plugin
Requires at least:  3.0
Tested up to:       6.9
Stable tag:         6.03
License:            GPLv2 or later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html


== Description ==
Sendle is an award-winning, 100% carbon neutral, door-to-door shipping carrier, designed to help small businesses thrive with simple, reliable, affordable shipping.

This app (tested and approved by Sendle) provides the basic connectivity between WooCommerce and Sendle. For the ultimate connectivity and features, please install the Premium Plugin. By integrating Sendle with your WooCommerce store, you get instant shipping quotes, order confirmation, and easy tracking updates.
The app provides:
* Automatic shipping quotes and outstanding Sendle service throughout the US
* Woocommerce Shipping Method Compatibility with WooCommerce Shipping Zones
* Parcel tracking
* Ability to connect orders to generate shipments in Sendle
* Easy order cancellation directly from the admin panel
* The option to generate Sendle shipments automatically on receipt of new orders
* Ability to add shipping quote markup (%) with admin access
* Default customer pickup instructions if required
* The option to add Additional Handling Fee
* Quotation for cart weight/volume > sendle's max weight/volume.
* Send tracking email to the customer.
* Update tracking email template.
* Separate satchel booking at the order details page for Australia.
* Split handling fee for tax option.

Additional info on what’s available on the app:
* Parcel Tracking shortcode [sendle_tracking]
* Backwards compatibility for methods existing before zones existed
* Receipt of tracking ID from Sendle and store on order details
* Real tracking information in the admin and customer account from Sendle API
* Parcel Tracking widget
* Ability to download label size [Letter or Cropped] from admin order details page
* Admin view of Sendle shipping details in admin order details page
* Admin can also set default [Sender Pickup instructions]
* Admin can set sender name, contact number, address, suburb, state, postcode
* Admin can set Show the rates in frontend [If not then the rates will not show in frontend but backend process will work for other shipping methods]
* Admin can set Post to Sendle API for the selected other shipping methods [Flat rate and Free Shipping]


== Installation ==
Get started
To use this app you need to sign up for an account with Sendle.
Shipping costs will be charged by Sendle and will vary based on the size/weight,  source/destination of the packages sent, and your account plan.
Sendle shipping rates can be found here: https://try.sendle.com/pricing
To sign up for a Sendle account, please visit: https://www.sendle.com/users/sign_up
Upon account creation, you will find a Sendle ID and API key on your dashboard. Use this information to connect the Sendle app to your Sendle account:
https://support.sendle.com/hc/en-au/articles/210798518I
For Wordpress installation, please visit: http://joovii.com/installation-instruction/wp

== Upgrade Notice ==
https://joovii.com/shipping-method/sendle-wp.html

== Disclosure ==
We are using https://api.sendle.com or https://sendle-sandbox.herokuapp.com url call to get shipping rate from sendle and post shipment to the sendle.
We are also using http://api.geonames.org url call to show zipcode-city suggestion in checkout.
We are also using http://plugins.joovii.com url call to update the joovii database to provide the best support.


== Screenshots ==
https://joovii.com/discuss/category/wordpress-extension/
== Changelog ==

* Version 1.0.1 :: Basic extension feature.
* Version 1.0.3 :: Added the following.
Added print label and pickup days delay.
Added "Book Shipment on" with "Order Submit" and "Shipment Submit".
Added shipping quote markup.
* Version 1.0.7 :: Added handling fees.
* Version 1.0.8 :: Rectified :: showing handling fees for no shipping.
* Version 1.0.9 :: Added the following
Added warning text for non supporting zones.
Show the rates in frontend [If not then the rates will not show in frontend but backend process will work for other shipping method].
Post to Sendle API for the selected shipping method [Flat rate and Free Shipping].
Enable Log
* Version 1.0.12 :: Added the following.
Show/hide "selected shipping method" box on clicking on the "Show the rates in frontend" checkbox in admin settings.
hide link[Track, Download Shipping Label,Cancel Sendle Order] from admin for cancelled order in admin order details page.
show "Sendle Order Status" in admin order details page.
* Version 1.0.15 :: Added the following.
Added international shipping option.
Automatically show international shipping rate from country dropdown of billing/shipping address.
Fixed some bugs reported in the last version.
* Version 1.0.17 :: Added the following.
Fixed some php warning.
Fixed some postcode conflict issue.
* Version 2.1.1 :: Added the following.
Fixed some conflict issue.
* Version 2.1.2 :: Added the following.
Remove licence textbox permanently from free version.
* Version 2.2.4 :: Added the following.
Added log table and a page for showing logs.
Added a page to validate sendle api key and id.
Added post to sendle for any shipping method.
Fixed some address/zipcode issue.
Added 3 new menu in admin under woocommerce menu for better navigation.
Added a page in admin to validate shipping with any param.
Fixed date_default_timezone for pickup date.
Change state_name from  state_code to sendle order.
Remove company from address line 2.
Set default title for shipping rate.
Added zipcode city autocomplete.
* Version 2.2.5 :: Added the following.
Enable/disable Address Match.
Fixed some incorrect postcode/suburb message.
* Version 2.2.6 :: Added the following.
Support for WP version 3.5
* Version 2.2.10 :: Added New Log
Added log for weight>25 or <=0 and Volume >0.1 or <=0.
* Version 2.2.13 :: Added New Log
Fixed some conflict between variation product weight and dimension.
* Version 2.2.14 ::
Fixed some bugs in widget.
* Version 2.2.15 ::
Fixed some confusion in setting text.
* Version 2.2.16 ::
Fixed some issue in postcode validation.
* Version 2.2.17 ::
Fixed some issue in tracking widget.
* Version 2.2.18 ::
* Version 2.2.19 ::
Filter log.
* Version 2.2.20 ::
Fixed some issues for custom shipping class.
* Version 2.2.21 ::
Removed some php warning to check customer address.
* Version 2.2.22 ::
Fixed some issue.
* Version 2.2.23 ::
Added shipping tax.
* Version 2.2.24 ::
Added Pickup Option[pickup or drop off].
* Version 2.2.25 ::
Added shipping zones observation to match address.
* Version 2.2.26 ::
Added some modification to shipping zones observation.
* Version 2.2.27 ::
Fixed some bug in the last version.
* Version 3.1.1 ::
Supports quoting and sending from Australia and USA locations.
Add details of sendle shipment in admin order details.
Add both cropped and a4/letter size download label in admin order details.
* Version 3.1.4 ::
Fixed some bug in admin shipment details page.
* Version 3.1.7 :: Update sendle api endpoint url
* Version 3.2.1 ::
Fixed all warning/notice/error in debug mode for wp-5.4
Fixed the access issue of shop manager account.
* Version 3.2.3 ::
Fixed some issue in city/zipcode autocomplete dropdown
Removed some php notice for debug mode
* Version 3.2.4 ::
Fixed some issue in admin order widget.
Fixed some issue in admin sendle shipemnt details page.
Fixed some css issue in city-zip autocomplete suggestion box loader.
* Version 3.2.7 ::
Quotation for cart weight > sendle max weight.
Change Order Status to Processing/Completed after shipment booking.
* Version 3.2.8 ::
Quotation for cart volume > sendle max volume.
* Version 3.3.1 ::
Send tracking email to the customer.
Update tracking email template.
Separate satchel booking at the order details page.
Split handling fee for tax option.
* Version 3.3.3 ::
Fixed the from_email of order tracking email.
* Version 4.1 ::
Fixed some security issues.
Fixed some php notice in debug mode.
* Version 4.2 ::
Added OPTIN option for merchant.
* Version 4.3 ::
Fixed some security issues.
* Version 4.4 ::
Tested up to woocommerce 5.0
Added new line or break to the email templates.
Remove PO BOX validation for US.
* Version 4.5 ::
Fixed some Satchel Booking issue.
* Version 4.6 ::
Fixed some issue in idempotencyKey.
* Version 4.7 ::
Added shop manager role to access this plugin.
Added a settings link in the plugin listing page to go to the settings page directly.
* Version 4.8 ::
Fixed some bug in cancel-shipment and user->roles function.
* Version 4.9 ::
Fixed a bug in order description field.
* Version 5.0 ::
Rearranged admin settings field.
Added Customer Reference to the sendle order from apply filter of ossm_filter_customer_reference.
Removed POBox validation code and added delivery address to the sendle api query string to get the rate.
* Version 5.1 ::
Enable/disable Satchel option for Both Booking OR Quotation.
Enable/disable warning text if product weight/volume > sendle max weight/volume.
* Version 5.2 ::
Fixed some issue in Satchel Booking.
* Version 5.3 ::
Fixed some php warning (conflict with woocommerce payment).
* Version 5.4 ::
Fixed some php warning in shipment booking.
* Version 5.5 ::
Fixed some php warning if product weight is 0.
* Version 5.5 ::
Fixed some internal code.
* Version 5.7 ::
Fixed the error when product weight or dimension is mission.
* Version 5.8 ::
Added Canada for pickup country.
* Version 5.9 ::
Fixed pickup_date_delay to skip weekend.
* Version 5.10 ::
Added HS_code in the settings for international shipping.
* Version 5.11 ::
Get HS_code from the product attribute   for international shipping.
* Version 5.12 ::
Fixed some bug.
* Version 5.13 ::
Added some tracking code.
* Version 5.14 ::
Added some more tracking code.
* Version 5.15 ::
Fixed some bug.
* Version 5.16 ::
Fixed some bug.
* Version 5.17 :
Tested up to wordpres version 6.4.2
* Version 5.18 :
Fixed some RXSS vulnerability issue.
* Version 5.19 :
tested upto wp latest version 6.4.3.
* Version 5.20 :
Fixed some security issue in displaying sendle log.
* Version 6.01 :
Added the premium plugin link.
https://wordpress.org/plugins/official-sendle-shipping-method
* Version 6.02 :
Sendle /quote endpoint is replaced with /products endpoint
* Version 6.03 :
Security Features Implemented

== Frequently Asked Questions ==
https://joovii.com/discuss/
= Where can I find my Sendle API key =
To obtain your Sendle API key, Please read the following article.
https://support.sendle.com/hc/en-us/articles/210798518-Sendle-API
= How to how to send tracking email to the customer =
There is an option in the admin sendle setting to send tracking email.
Just enable it, the system will send an email to the customer when an sendle shipment is booked.
You can also update email templates from admin.
= How to book satchel order  =
There is an option in the admin sendle setting to enable satchel booking.
You can see a link in the order details page in admin to to book satchel.
= How to validate your Sendle Api Id and Key
You have to go to the following path
admin -> side menu -> WooCommerce -> Validate Sendle
