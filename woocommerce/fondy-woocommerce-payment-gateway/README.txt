=== Flitt — WooCommerce Payment Gateway ===
Contributors: flitt
Tags: payments, payment gateway, woocommerce, online payment, merchant, credit card, Flitt, apple pay, payment request, google pay
Requires at least: 3.5
Tested up to: 6.0.1
Requires PHP: 5.6
Stable tag: 3.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==
The plugin for WooCommerce allows you to integrate the online payment form on the Checkout page of your online store.

Flitt's WooCommerce Payment Gateway plugin for online stores, based on CMS Wordpress provides businesses with a single platform for quick and secure accepting payments on the site, and its customers with convenient ways to pay for goods and services of their interest. After connecting the system, your customers will be able to pay for purchases using bank cards, online banking, mobile payments.

== Installation instructions for the plugin ==

= 1. Module installation =

There are two ways to install the plugin:

1.    Download Flitt payment acceptance plugin for WooCommerce from the WordPress add-ons directory. Unpack this plugin into the /wp-content/plugins/ directory. After that activate it in the “Plugins” menu.
2.    Use the installation on the link replacing “site.com” with the address of your site: [site.com/wp-admin/plugin-install.php?tab=plugin-information&plugin=fondy-woocommerce-payment-gateway](http://site.com/wp-admin/plugin-install.php?tab=plugin-information&plugin=fondy-woocommerce-payment-gateway)


= 2. Module activation =

Go to the Wordpress control panel, find the Flitt payment module in the “Plugins” menu. Click on the “Activate”.

= 3. Settings =

To set up the payment acceptance plugin, do the following:

1.	Go to “WooCommerce” > Settings > Payments.
2. Go to the management of Flitt → Management. Let the plugin use this payment method: click “Enable”.
3. Enter the data you received from Fondy company. (can be found in your merchant's technical settings) You need to fill in two fields — Merchant ID and Merchant secretkey.
4. Choose how the payment will be displayed:
	a. Payment page within the site design
	b. Payment page in the personal account on the site
	c. Separate payment page on the side of Fondy
5. Select the Answer page — the page to which the user will be redirected after making the payment, the so-called “Thank you page”.
6. Set what order status should be returned after successful/unsuccessful payment.
7. Save the settings.

Done, now you can accept payments from the customers!


== Changelog ==
= 1.0.0 = 
* First release
= 1.0.1 =
add default success page
= 2.0 =
change to host-to-host
= 2.2 =
stability update
= 2.2.3 =
change payment complete status
= 2.3.0 =
some fix, duplicate update
= 2.4.0 =
Added v2 js Api
= 2.4.1 =
Added js Mask CCard
= 2.4.2 =
some fix php tags
= 2.4.3 =
Added Refund function
= 2.4.4 =
Fixed checkout card
= 2.4.5 =
Fix for php 5.3 <
= 2.5.2 =
Added instant redirect
= 2.5.3 =
Styles moved to merchant portal
= 2.5.6 =
Added order statuses to settings page
= 2.5.8 =
New logo and testing mode
= 2.6.3 =
Fixed default options
= 2.6.5 =
Added pre-orders
= 2.6.7 =
Added subscription
= 2.6.8 =
Refund fix
= 2.6.9 =
Added some API request error handler
= 3.0.0 =
Add Bank and Local payment methods
Add support checkout 2.0
Add some hooks and filters
Add MP transaction link
Add send reservation data (anti-fraud, ОФД, etc.)
Add better order status matching
Remove old unused settings
Fix callback url
Fix PHP 8 compatibility
= 3.0.1 =
Fix relative server_callback_url
= 3.0.2 =
Add send email renewal order payment info
Add more informative WP remote_post errors
= 3.0.3 =
Fixed handle POST form callback

== Upgrade Notice ==

= 1.0.0 =
Add pop-up mode
= 1.0.1 =
add default success page
= 2.0 =
change to host-to-host
= 2.2 =
stability update
= 2.2.2 =
add expired callback
= 2.2.3 =
change payment complete status
= 2.3.0 =
some fix, duplicate update
= 2.4.0 =
Added v2 js Api
= 2.4.1 =
Added js Mask CCard
= 2.4.2 =	
some fix php tags	
= 2.4.3 =	
Added Refund function
= 2.4.4 =
Fixed checkout card
= 2.4.6 =
Order notify update
= 2.4.7 =
Unification css containers
= 2.4.8 =
Stability update
= 2.4.9 =
Added multi currencies support(WMPL)
= 2.5.0 =
Added token caching
= 2.5.2 =
Added instant redirect
= 2.5.3 =
Styles moved to merchant portal
= 3.0.0 =
Up WooCommerce required minimum version to 3.0.
Add support checkout 2.0.