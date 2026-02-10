=== Flitt payment gateway for WooCommerce ===
Contributors: flittpayments
Tags: payments, payment gateway, woocommerce, online payment, merchant, credit card, flitt
Requires at least: 3.5
Tested up to: 6.9
Requires PHP: 7.0
Stable tag: 4.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Short Description ==
The plugin for WooCommerce allows you to integrate flitt.com the online payment form on the Checkout page of your online store.

== Description ==
The plugin for WooCommerce allows you to integrate the online payment form on the Checkout page of your online store.

Flitt’s [WooCommerce Payment Gateway plugin](https://flitt.com/) for online stores, based on CMS Wordpress provides businesses with a single platform for quick and secure accepting payments on the site, and its customers with convenient ways to pay for goods and services of their interest. After connecting the system, your customers will be able to pay for purchases using bank cards, online banking, mobile payments.

The ability to pay in a convenient way increases customer loyalty, increases the frequency of purchases and helps entrepreneurs earn more. The Flitt platform has already been connected to more than 300 entrepreneurs — from small start-ups and niche stores to international companies with millions of turnover.

We already work in [5 countries](https://docs.flitt.com/supported-countries/), accept payments from any country, and support more than [8 currencies](https://docs.flitt.com/api/currencies/).


== Reasons to choose Flitt ==
* A wide range of payment methods: credit cards, local means of payment, Internet banks
* Support for recurring payments — regular debit from the client card for subscription services
* Holding system — freezing money on the client’s card for up to 25 days with the possibility of debit or refund in 1 click
* Tokenization — automatic filling in the details of the client card upon re-entry
* Roles system — the ability to create users with different access rights to the personal account
* Maximum security level: three levels of anti-fraud protection, SSL/TLS encryption, 3D Secure technology
* Detailed analytics on payments and invoices, the formation of customized reports in the user's personal account


== Supported payment methods ==

= Bank cards =
* Visa, MasterCard ([full list](https://docs.flitt.com/api/order-parameters/))

= Internet banking =
* Banks in 5 countries ([full list](https://docs.flitt.com/supported-countries/))


== Platform features and benefits ==

= Easy to get started =
Fast and friendly onboarding is one of the key advantages of Flitt. You just need to register in the personal account of the platform, enter payment information, sign an electronic agreement and undergo an express audit of the site by our specialists.

The procedure takes from 1 hour to 2 days. After that you can accept payments from the customers, create online invoices in your account and engage in business development.

= Payment security =
To ensure a high level of security and availability of the platform we placed it in a cloud service that meets the top ten security standards, has protection against DDoS attacks and ensures that there is no physical access by unauthorized persons and organizations to the data and equipment.

Every year we pass PCI DSS certification. We also developed and constantly update our own anti-fraud system consisting of three levels: barrier, analytical, post-operator. This means that the fraudsters have no chance.

= Customer care =
When they first pay, the clients enter the card data and selects their favourite method of payment, the platform will save them in encrypted form. All the following payments will be made for your customers in 1 click, without having to enter any data and fill in the fields. Such care removes unnecessary barriers to purchase, the customers do not have to look for a card or recall the CVV code.

For those who regularly buy goods or purchase subscription services in Flitt, you can set up periodic debiting of money from the card (account) once a week/month/year using the payment calendar. The client will not have to bother with repeated payments, and you will always receive money on time.

= Flexible payment options =
It so happens that the client paid the order and the goods were not in stock. The seller gets into an awkward situation. For such cases Flitt offers a holding mechanism — freezing money in a client’s account for a while. If there is no product, you can return the money back to the client in 1 click.

There are many cases when you need to check the client’s solvency or freeze a certain amount on his account. Often holding is used by car rental services, hotels, delivery services, taxi services.

= Mobile payments =
Flitt is fully optimized for use on smartphones, tablets, laptops, desktops, TVs. Your customers will be comfortable to make purchases from any device.

We also took care of the sellers, they can access the personal account via the web interface or a convenient mobile application for [Android](https://play.google.com/store/apps/details?id=com.cloudipsp.flittportal&hl=en) and [iOS](https://itunes.apple.com/ua/app/flitt-merchant-portal/id1273277350?l=en). From the application you can view the statistics, generate invoices, work with payments.

= Smart analytics =
We help the sellers get to better know their customers. The built-in analytics system allows real-time tracking of the status of all the payments, to see at what stage of the purchase each customer is.

The system also provides analytics on customers, showing in which ways payment is most often made, from which devices, countries and in what currency. The received data can be viewed in your Flitt account or converted into reports and saved to a computer.


== Installation instructions for the plugin ==

= 1. Module installation =

There are two ways to install the plugin:

1.    Download Flitt payment acceptance plugin for WooCommerce from the WordPress add-ons directory. Unpack this plugin into the /wp-content/plugins/ directory. After that activate it in the “Plugins” menu.
2.    Use the installation on the link replacing “site.com” with the address of your site: [site.com/wp-admin/plugin-install.php?tab=plugin-information&plugin=flitt-payment-gateway-for-woocommerce](http://site.com/wp-admin/plugin-install.php?tab=plugin-information&plugin=flitt-payment-gateway-for-woocommerce)


= 2. Module activation =

Go to the Wordpress control panel, find the Flitt payment module in the “Plugins” menu. Click on the “Activate”.

= 3. Settings =

To set up the payment acceptance plugin, do the following:

1.	Go to “WooCommerce” > Settings > Payments.
2. Go to the management of “Flitt” → Management. Let the plugin use this payment method: click “Enable”.
3. Enter the data you received from Flitt company. (can be found in your merchant's technical settings) You need to fill in two fields — Merchant ID and Merchant secretkey.
4. Choose how the payment will be displayed:
	a. Payment page within the site design
	b. Payment page in the personal account on the site
	c. Separate payment page on the side of Flitt
5. Select the Answer page — the page to which the user will be redirected after making the payment, the so-called “Thank you page”.
6. Set what order status should be returned after successful/unsuccessful payment.
7. Save the settings.

Done, now you can accept payments from the customers!

== Development / Source Code ==

This plugin ships production-ready, compiled JavaScript/CSS assets for performance and smaller package size.
In accordance with the WordPress.org plugin guidelines (human-readable code), the unminified source code
and build instructions are publicly available here:

* Source repository (unminified / development sources): https://github.com/flittpayments/checkout-vue/tree/latest/packages/checkout
https://github.com/jondavidjohn/payform/tree/master


== Undocumented use of a 3rd Party / external service ==

This plugin integrates with the external payment service **Flitt** (https://flitt.com/) to create and process payments.

When a customer places an order, the plugin connects to Flitt to:
* create a payment / checkout session;
* redirect the customer or display an embedded payment form hosted by Flitt;
* receive payment status updates (callbacks/webhooks) and update the WooCommerce order;
* fetch transaction information required to complete the payment flow.

Data sent to Flitt may include (as required for payment processing): order identifier, amount, currency, customer billing/shipping details, and other checkout metadata. The exact data depends on the merchant’s Flitt configuration and the checkout scenario.

This third-party service is required for the plugin to function because payment authorization and processing are handled by Flitt.
**Complaints** (https://flitt.com/wp-content/uploads/2025/06/%E1%83%9E%E1%83%A0%E1%83%94%E1%83%A2%E1%83%94%E1%83%9C%E1%83%96%E1%83%98%E1%83%94%E1%83%91%E1%83%98-02.06.2025.pdf). **privacy policy** (https://flitt.com/wp-content/uploads/2024/11/Privacy_Policy_flitt.pdf)

== Changelog ==
= 4.0.3 =
* First release
