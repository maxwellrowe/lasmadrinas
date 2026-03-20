### Plugin Details ###

Gravity Forms NMI Gateway (Basic)  
Contributors: WP Gateways  
Tags: Gravity Forms Network Merchants (NMI), Network Merchants (NMI), Network Merchants, payment gateway, Gravity Forms, Gravity Forms payment gateway, payment forms  
Plugin URI: https://wpgateways.com/products/nmi-gateway-gravity-forms/  
Requires at least: 4.0  
Tested up to: 6.9  
License: GPLv3  
License URI: https://www.gnu.org/licenses/gpl-3.0.html  

### Description ###

**Gravity Forms Network Merchants (NMI) Payment Gateway (Basic)** extends the functionality of Gravity Forms to accept payments from credit/debit cards using the Network Merchants (NMI) payment gateway. Since your customers will be entering credit cards directly on your store you should ensure that your checkout pages are protected by SSL.

### Features ###

1. Accept Credit Cards directly on your website by using the Network Merchants (NMI) payment gateway.
2. No redirecting your customer back and forth.
3. Very easy to install and configure. Ready in Minutes!
4. Safe and secure method to process credit cards using the Network Merchants (NMI) payment gateway.
5. Internally processes credit cards, safer, quicker, and more secure!

If you need any assistance with this or any of our other plugins, please visit our contact page:
https://wpgateways.com/support/

### Installation ###

Easy steps to install the plugin:

1. Upload this folder to the `/wp-content/plugins/` directory.
2. Activate the plugin (WordPress -> Plugins).
3. Go to the Gravity Forms settings page (WordPress -> Forms -> Settings).
4. Now click the "Network Merchants (NMI)" tab.
5. On this page you will find all of the configuration options for this payment gateway.
6. Enter the Network Merchants (NMI) account details (See below).

	**Gateway Username**: Enter your Gateway Username. See your merchant account rep for additional information.  
	**Gateway Password**: Enter your Gateway Password. See your merchant account rep for additional information.

7. Now go to the forms page (WordPress -> Forms) and create/edit a form.
8. Add a credit card, address and payment type field each if you don't have them already.
9. Now from under the "Form Settings" drop down menu above the form editor, select "Network Merchants (NMI)" and click on "Add New".
10. Select "Products and Services" as your transaction type and proceed to map all billing related fields with the ones in your form.
11. Click on "Update Settings". That's it!
12. Proceed to test your form by selecting "Test Mode" and entering Network Merchants (NMI) card details provided by the payment provider.

That's it! You are ready to accept credit cards with your Network Merchants (NMI) payment gateway now connected to Gravity Forms.

### Frequently Asked Questions ###

**Is SSL Required to use this plugin?**  
A valid SSL certificate is required to ensure your customer credit card details are safe and make your site PCI DSS compliant. This plugin does not store the customer credit card numbers or sensitive information on your website.

**Does the plugin support direct updates from the WP dashboard?**  
Yes. You can navigate to WordPress -> Tools -> Gravity Forms Network Merchants (NMI) License page and activate the license key you received with your order. Once that is done you will be able to directly update the plugin to the latest version from the WordPress dashboard itself.

### Changelog ###

**1.2.3**  
Added headers to license update API endpoint  
Added gateway icon  
Updated compatibility info to WordPress 6.9  

**1.2.2**  
Added filter for custom CSS  
Added minor improvements in code base  
Updated compatibility info to Gravity Forms 2.8 and WordPress 6.7  

**1.2.1**  
Added compatiblity for reCaptcha integration  
Updated compatibility info to WordPress 6.5  

**1.2.0**  
Added support for Collect.js Tokenization  
Updated compatibility info to WordPress 6.4  

**1.1.2**  
Added filter to modify billing info  
Fixed notices in updates file  
Updated compatibility info to WordPress 6.0  

**1.1.1**  
Made compatible with Gravity Forms 2.5 and WordPress 5.6  

**1.1.0**  
Removed Authorize.Net AIM code and implemented native NMI integration  
Added First Name, Last Name, Company billing fields  
Added Order ID, Description order information fields  
Added Customer Receipt option  

**1.0.3**  
Added lifetime variation IDs to the update file  

**1.0.2**  
Fixed PHP notices  
Better error handling  

**1.0.1**  
Integrated auto-update API  
Removed deprecated code  
Removed the word "testing" appearing on the plugins settings page  

**1.0.0**  
Initial release version  