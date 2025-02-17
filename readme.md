# PayMaster for WordPress + WooCommerce

## Creating the plugin

1. Open *settings.json* and fill in the following fields:

    * base_service_url (PayMaster API base URL);
	* service_name (payment method short title);
	* service_description (payment method description);
	* send_receipt_data (1/0, only for paymaster.ru).

    Filling example:

    > "base_service_url": "https://psp.paymaster24.com",  
    > "service_name": "PayMaster (payments online)",
    > "service_description": "Payments by bank cards, electronic money and more",
    > "send_receipt_data": "0"

2. Zip the resulting *settings.json*, *paymaster.php* *classes* and *languages* folders. Name the archive file **paymaster.zip**.

## Installing the plugin

Please read the [user guide](user-guide.pdf).