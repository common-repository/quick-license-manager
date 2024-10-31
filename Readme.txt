=== Quick License Manager - WooCommerce Plugin ===
Contributors: Soraco Technologies
Tags: quick license manager, software protection, ecommerce
Requires at least: 4.2
Tested up to: 6.3.1
Stable tag: 2.4.15
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrates QLM with WooCommerce

== Description ==

Integrates QLM with WooCommerce

== Installation ==

= Minimum Requirements =

* WordPress 4.2 or greater
* PHP version 5.4.24 or greater
* MySQL version 5.5 or greater
* WooCommerce 2.3.13 or greater

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don�t even need to leave your web browser. To do an automatic install of WooCommerce, log in to your WordPress admin panel, navigate to the Plugins menu and click Add New.

In the search field type �Quick License Manager� and click Search Plugins. Once you�ve found our plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking Install Now. After clicking that link you will be asked if you�re sure you want to install the plugin. Click yes and WordPress will automatically complete the installation.

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your webserver via your favourite FTP application.

1. Download the plugin file to your computer and unzip it
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation�s wp-content/plugins/ directory.
3. Activate the plugin from the Plugins menu within the WordPress admin.

== Frequently Asked Questions ==

== Changelog ==

= 2.4.15 - 09/25/2023 =
Automatically detect if WooCommerce Custom Product Add-ons is active.

= 2.4.14 - 09/25/2023 =
Minor fixes.

= 2.4.12 - 03/14/2023 =
The QLM Engine version can now be specified per product by setting the is_qlmversion custom field.

= 2.4.11 - 02/26/2023 =
Fixed benign warning messages displayed when saving the QLM Settings

= 2.4.10 - 01/16/2023 =
Fixed a regression issue in the last update that caused an exception when adding items to a cart without being logged in.

= 2.4.9 - 01/08/2023 =
Fixed a regression issue in the last update that caused an exception when accessing the QLM settings

= 2.4.8 - 01/06/2023 =
Added support for passing and setting the &is_userdata1 argument when placing an order.


= 2.4.7 - 10/17/2022 =
Tested compatibility with WooCommerce 7.0.0
Fixed an issue that caused an incompatibiliy with the WooCommerce Invoice plugin.

= 2.4.6 - 07/28/2022 =
Tested compatibility with WordPress 6.0.1
Configured some fields on the settings page as password fields to hide their content.

= 2.4.5 - 06/21/2021 =
Fixed an issue when processing an upgrade of a license.

= 2.4.4 - 05/18/2021 =
Fixed issue when there's a mix of QLM and non QLM products in a shopping cart, the License Key was not added as metadata to the order.
Added support for automatically creating a pending order for a subscription via a webhook that can be triggered by QLM v15 Webhooks Notifications. 
  This is useful for customers who use a Manual Renewal payment method. 
  When the pending order is created, the customer receives an invoice prior to the actual expiry of the subscription. 
  This gives customers enough lead time to make their payment prior to the actual expiry of the license.

= 2.3.2 - 04/23/2021 =
Fixed issue with QLM emails not being sent.

= 2.3.1 - 02/27/2021 =
Fixed date parsing issue when manually processing a subscription payment.
Added new option to enable order processing when the order status is completed, to be used only in very special cases.
If you have created a Maintenance Plan attribute to allow customers to opt-in/out of purchasing a maintenance plan, a new option allows you to specify the label that you used for the maintenance plan attribute. QLM uses this identifier to associate this attribute to the QLM Maintenance Plan feature.


= 2.3.0 - 02/17/2021 =
Added support for early renewals of subscriptions. To support early renewals, you must enable the new QLM setting "Next Payment Date based on schedule".

= 2.2.9 - 12/30/2020 =
Renamed plugin to: WooCommerce - Quick License Manager Integration
Fixed issue determining the expiry date when renewing a subscription following a change in the latest version of WooCommerce Subscriptions.

= 2.2.8 - 12/29/2020 =
Fixed issue determining the expiry date of a subscription following a change in the latest version of WooCommerce Subscriptions.

= 2.2.7 - 12/13/2020 =
For downloadable products, the license key was not included in the email because the Order Status is set to Completed before the license key is created.
To resolve this, you need to install the WooCommerce Order Status Control plugin and set the "Orders Auto-Complete" option to None.

= 2.2.6 - 11/07/2020 =
Fixed regression issue when renewing a subscription.

= 2.2.5 - 11/05/2020 =
Fixed regression issue when renewing a subscription.

= 2.2.4 - 11/04/2020 =
Fixed issue when number of items in order > 1.
Fixed issue when renewing the maintenance plan of a product and the quantity > 1.

= 2.2.3 - 10/29/2020 =
Fixed issue with QLM plugin setting the Order to Completed when no QLM items are in the order.

= 2.2.2 - 10/22/2020 =
Fixed issue if WooCommerce Subscriptions is not installed.

= 2.2.0 - 10/13/2020 =
Added support orders that contain subscription products with different durations.
Added support for downloadable products.
Added support for creating a dedicated log file. You can configure this option in Settings.
Added a global setting for enabling QLM emails. You can configure this option in Settings.

= 2.1.4 - 10/09/2020 =
Added support for revoking licenses when a subscription is cancelled. You must enable this option in Settings.
Updated support for revoking licenses when an order is cancelled. You can now enable this option in Settings.

= 2.1.3 - 10/06/2020 =
Fixed bug renewing a subscription.

= 2.1.2 - 10/05/2020 =
Fixed bug when renewing a subscription . The subscription status was not switching from On Hold to Active.
The QLM expiry date of a subscription is now set to the WooCommerce renewal date. There's no need to set the is_expduration custom field.

= 2.0.10 - 07/26/2020 =
Fixed bug when setting the user role upon purchase. The plugin was replacing the user role instead of adding a new user role.

= 2.0.8 - 5/5/2020 =
Changed monthly subscription to be 31 days instead of 30 days.
Validated compatibility with Wordpress 5.4 and WooCommerce 4.0.

= 2.0.7 - 2/7/2020 =
Added integration with 3rd party plugin WooCommerce Custom Product Addons to support product upgrades.
Added new settings to automatically change the user role once an order is completed.

= 2.0.6 - 12/25/2019 =
Removed debug messages.

= 2.0.5 - 12/18/2019 =
Added support for processing guest orders.
Added support for revoking a license when a subscription is cancelled.

= 2.0.4 - 12/14/2019 =
Fixed issue with WooCommerce Subscriptions whereby the subscription ID was not recorded in the QLM database.

= 2.0.3 - 11/14/2019 =
You can now specify a list of categories that QLM will process.

= 2.0.2 - 11/30/2018 =
Added support for specifying QLM features are variations
Added support for specifying QLM product properties are variations
Added support for specifying the QLM Maintenance Plan period as a variation
Added support for the following new arguments: is_maintduration, is_usemultipleactivationskey, is_affiliateid

= 1.9.14 - 08/03/2018 =
Added support for configuring a Maintenance Plan as a Subscription Product

= 1.9.13 - 08/17/2017 =
Added the ability to skip sending a QLM Email since WooCommerce can now send emails directly. To skip sending emails, set the is_send_mail custom field to false.

= 1.9.12 - 08/17/2017 =
Changed greeting in email from lastname/firstname to firstname/lastname

= 1.9.11 - 08/17/2017 =
Fixed default email subject template (pass 2).

= 1.9.10 - 08/17/2017 =
Fixed default email subject template

= 1.9.9 - 08/16/2017 =
Added support for customizing the subject of the confirmation email

= 1.9.8 - 08/16/2017 =
Minor regression issue in the previous update.

= 1.9.6 - 08/16/2017 =
Fixed issue when emailing orders for products of type variable.

= 1.9.5 - 06/04/2017 =
Fixed issue when emailing orders containing multiple products.

= 1.9.4 - 06/04/2017 =
Fixed issue with orders containing multiple products.

= 1.9.3 - 03/14/2017 =
Fixed regression issue for non subscription products - if the WooCommerce Subscriptions plugin was not installed, regular products did not work as expected.

= 1.9.2 - 12/30/2016 =
Added support for specifying is_additionalactivations.

= 1.9.1 - 12/30/2016 =
Fixed issue with simple ubscription products.
Added support for specifying is_licensemodel.

= 1.8.3 - 03/26/2016 =
Fixed issue with subscription duration not being set consistently.

= 1.8.2 - 03/06/2016 =
Fixed authentication issue.

= 1.8.1 - 02/18/2016 =
Added support for specifying is_features, is_expdate and is_expduration.

= 1.7.1 - 11/29/2015 =
Fixed Readme.txt, added icon for plugin directory.




