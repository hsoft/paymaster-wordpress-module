<?php

class WC_PayMaster_Gateway extends WC_Payment_Gateway
{

    public function __construct()
    {
        $this->id = 'paymaster_gateway';
        $this->method_title = __('PayMaster for WooCommerce', 'paymaster');
        $this->method_description = __('The payment module for accepting payments via the PayMaster service', 'paymaster');
        $this->has_fields = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->settings['title'];
        $this->description = $this->settings['description'];
        
        // Save options
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this,'process_admin_options'));

        // PayMaster callback handler
        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'callback_handler'));
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'paymaster'),
                'type' => 'checkbox',
                'label' => __('Enabled', 'paymaster'),
                'default' => 'no'
            ),
            'base_service_address' => array(
                'title' => __('Base service address', 'paymaster'),
                'type' => 'text',
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'token' => array(
                'title' => __('Token', 'paymaster'),
                'type' => 'text',
                'description' => __('Token can be generated in the Merchant\'s Personal Account', 'paymaster'),
                'custom_attributes' => array(
                    'required' => 'required'
                ),
                'desc_tip' => true
            ),
            'merchant_id' => array(
                'title' => __('Merchant ID', 'paymaster'),
                'type' => 'text',
                'description' => __('Copy this value from the Merchant\'s Personal Account', 'paymaster'),
                'custom_attributes' => array(
                    'required' => 'required'
                ),
                'desc_tip' => true
            ),
            'title' => array(
                'title' => __('Payment service name', 'paymaster'),
                'type' => 'text',
                'description' => __('This title will be shown to the customer', 'paymaster'),
                'custom_attributes' => array(
                    'required' => 'required'
                ),
                'desc_tip' => true
            ),
            'description' => array(
                'title' => __('Payment service description', 'paymaster'),
                'type' => 'textarea',
                'description' => __('Description of payment method which user can see in you site', 'paymaster'),
                'desc_tip' => true
            ),
            'logging' => array(
                'title' => __('Logging', 'paymaster'),
                'type' => 'checkbox',
                'label' => __('Enabled', 'paymaster'),
                'default' => 'no'
            )
            
        );
        if ($this->get_option('send_receipt_data')) {
            
            $pm_vat_types = $this->get_pm_vat_types();
            $this->form_fields['default_vat_type'] = array(
                'title' => __('Default tax rate', 'paymaster'),
                'type' => 'select',
                'options' => $pm_vat_types,
                'description' => __('The default rate applies if another rate is not set on the product\'s page', 'paymaster'),
                'desc_tip' => true);
                
            $store_tax_rates = $this->get_store_tax_rates();
            foreach ( $store_tax_rates as $store_tax_rate ) {
                $this->form_fields['tax_rate_' . $store_tax_rate->tax_rate_id . '_vat_type'] = array(
                    'title' => __('VAT rate for', 'paymaster') . ' "' . $store_tax_rate->tax_rate_name . ' (' . (float)$store_tax_rate->tax_rate . '%)"',
                    'type' => 'select',
                    'options' => $pm_vat_types,
                    'description' => __('Match the rate in your store with the rate for the tax receipt', 'paymaster'),
                    'desc_tip' => true);
            }
            
            $payment_subjects = $this->get_payment_subjects();
            $this->form_fields['payment_subject'] = array(
                'title' => __('Payment subject', 'paymaster'),
                'type' => 'select',
                'options' => $payment_subjects);
            
            $payment_methods = $this->get_payment_methods();
            $this->form_fields['payment_method'] = array(
                'title' => __('Payment method', 'paymaster'),
                'type' => 'select',
                'options' => $payment_methods);
            
            $this->form_fields['payment_subject_for_shipping'] = array(
                'title' => __('Payment subject for shipping', 'paymaster'),
                'type' => 'select',
                'options' => $payment_subjects);
            
            $this->form_fields['payment_method_for_shipping'] = array(
                'title' => __('Payment method for shipping', 'paymaster'),
                'type' => 'select',
                'options' => $payment_methods);
        }
    }
    
    private function get_pm_vat_types()
    {
        return array(
            'vat_none' => __('None', 'paymaster'),
            'vat0' => __('VAT 0%', 'paymaster'),
            'vat10' => __('VAT 10%', 'paymaster'),
            'vat20' => __('VAT 20%', 'paymaster'),
            'vat110' => __('VAT formula 10/110', 'paymaster'),
            'vat120' => __('VAT formula 20/120', 'paymaster'));
    }
    
    private function get_store_tax_rates()
    {
        $all_tax_rates = [];
        $tax_classes = WC_Tax::get_tax_classes();
        if (!in_array( '', $tax_classes )) { // Make sure "Standard rate" (empty class name) is present.
            array_unshift( $tax_classes, '');
        }
        foreach ( $tax_classes as $tax_class ) {
            $taxes = WC_Tax::get_rates_for_tax_class( $tax_class );
            $all_tax_rates = array_merge( $all_tax_rates, $taxes );
        }
        return $all_tax_rates;
    }
    
    private function get_payment_subjects()
    {
        return array(
            'Commodity' => __('Commodity', 'paymaster'),
            'Excise' => __('Excise', 'paymaster'),
            'Job' => __('Job', 'paymaster'),
            'Service' => __('Service', 'paymaster'),
            'Gambling' => __('Gambling', 'paymaster'),
            'Lottery' => __('Lottery', 'paymaster'),
            'IntellectualActivity' => __('Intellectual activity', 'paymaster'),
            'Payment' => __('Payment', 'paymaster'),
            'AgentFee' => __('Agent fee', 'paymaster'),
            'PropertyRights' => __('Property rights', 'paymaster'),
            'NonOperatingIncome' => __('Non operating income', 'paymaster'),
            'InsurancePayment' => __('Insurance payment', 'paymaster'),
            'SalesTax' => __('Sales tax', 'paymaster'),
            'ResortFee' => __('Resort fee', 'paymaster'),
            'Other' => __('Other', 'paymaster')
        );
    }
    
    private function get_payment_methods()
    {
        return array(
            'FullPrepayment' => __('Full prepayment', 'paymaster'),
            'PartialPrepayment' => __('Partial prepayment', 'paymaster'),
            'Advance' => __('Advance', 'paymaster'),
            'FullPayment' => __('Full payment', 'paymaster'),
            'PartialPayment' => __('Partial payment', 'paymaster'),
            'Credit' => __('Credit', 'paymaster')
        );
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        $request_data = array(
            'merchantId' => $this->get_option('merchant_id'),
            'invoice' => array(
                'description' => __('Payment for order', 'paymaster') . ' ' . $order_id,
                'orderNo' => (string) $order_id
            ),
            'amount' => array(
                'value' => (float)$order->total,
                'currency' => $order->currency
            ),
            'protocol' => array(
                'callbackUrl' => get_site_url() . '/' . '?wc-api=' . strtolower(get_class($this)),
                'returnUrl' => get_site_url() . '/' . '?wc-api=' . strtolower(get_class($this)) . '&order_key=' . $order->order_key//$this->get_return_url($order)
            )
        );
        
        if ($this->get_option('send_receipt_data'))
            $request_data['receipt'] = $this->getReceipt($order);
        
        $response = $this->getResponse(__METHOD__, '/api/v2/invoices', $request_data);
        if (property_exists($response, 'url')) {
            WC()->session->set('payment_id', $response->paymentId);
            return array(
                'result' => 'success',
                'redirect' => $response->url
            );
        } else {
            wc_add_notice(__('Error connecting to PayMaster. Please try later or contact the site owner.', 'paymaster'), 'error');
            return;
        }
    }
    
    private function getReceipt($order): array
    {
        $receipt_items = array();
        
        $order_products = $order->get_items(array('line_item'));
        foreach ($order_products as $order_product) {
            $receipt_items[] = array(
                'name' => $order_product['name'],
                'quantity' => $order_product['quantity'],
                'price' => round(($order_product['total'] + $order_product['total_tax']) / $order_product['quantity'], 2),
                'vatType' => $this->getVatType($order_product['taxes']['total']),
                'paymentSubject' => $this->get_option('payment_subject'),
                'paymentMethod' => $this->get_option('payment_method')
            );
        }
        
        $shipping_items = $order->get_items(array('shipping'));
        if ($shipping_items) {
            foreach ($shipping_items as $shipping_item) {
                $receipt_items[] = array(
                    'name' => $shipping_item['name'],
                    'quantity' => 1,
                    'price' => round($shipping_item['total'] + $shipping_item['total_tax'], 2),
                    'vatType' => $this->getVatType($shipping_item['taxes']['total']),
                    'paymentSubject' => $this->get_option('payment_subject_for_shipping'),
                    'paymentMethod' => $this->get_option('payment_method_for_shipping')
                );
            }
        }
        
        return array(
            'client' => array('email' => $order->get_billing_email()),
            'items' => $receipt_items
        );
    }
    
    private function getVatType(array $total_taxes): string
    {
        $vat_type = $this->get_option('default_vat_type');
        if (sizeof($total_taxes) > 0) {
            $product_vat_type = $this->get_option('tax_rate_' . array_key_first($total_taxes) . '_vat_type');
            if ($product_vat_type)
                $vat_type = $product_vat_type;
        }
        return $vat_type;
    }

    public function callback_handler()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $content = file_get_contents('php://input');
            $this->addLogEntry('info', __METHOD__, 'PayMaster callback request', array('content' => $content));

            $request = json_decode($content);

            if ($request->status == 'Settled' && $request->merchantId == $this->get_option('merchant_id')) {

                $orderNo = $request->invoice->orderNo;
                $order = wc_get_order($orderNo);

                if (isset($order) &&
                    !$order->get_date_paid() &&
                    $request->amount->value == $order->total &&
                    $request->amount->currency == $order->currency) {

                    $this->checkAndComplete(__METHOD__, $order, $request->id);
                }
            }
        }
        else {
            if (isset($_GET['order_key'])) {
                $order_id = wc_get_order_id_by_order_key($_GET['order_key']);
                if ($order_id) {
                    $order = wc_get_order($order_id);
                    if ($order) {
                        if ($order->status == 'pending') {
                            $paymentId = WC()->session->get('payment_id');
                            if ($paymentId) {
                                $this->checkAndComplete(__METHOD__, $order, $paymentId);
                            }
                        }
                        if ($order->get_date_paid()) {
                            $this->toSuccessPage($order);
                        }
                        
                        wp_redirect($order->get_cancel_order_url());
                    }
                }
            }
        }
    }
    
    private function toSuccessPage($order)
    {
        WC()->session->__unset('payment_id');
        wp_redirect($this->get_return_url($order));
        exit();
    }
    
    private function checkAndComplete($method, $order, $payment_id): string
    {
        $status = 'Unknown';
        $response = $this->getResponse($method, '/api/v2/payments/' . $payment_id);
        if (isset($response)) {
            $status = $response->status;
            if ($response->status == 'Settled' &&
                $response->merchantId == $this->get_option('merchant_id') &&
                $response->invoice->orderNo == $order->id &&
                $response->amount->value == $order->total &&
                $response->amount->currency == $order->currency) {
                    $order->add_order_note(__('Order has been paid. PayMaster payment ID — ', 'paymaster') . $payment_id . '.');
                    $order->payment_complete();
                }
        }
        return $status;
    }

    private function getResponse($method, $relative_url, $request_data = null)
    {
        $pmtoken = $this->get_option('token');
        $options = array(
            'http' => array(
                'ignore_errors' => true,
                'method' => is_null($request_data) ? 'GET' : 'POST',
                'header' => "Authorization: Bearer $pmtoken\r\n" . "Content-Type: application/json\r\n" . "Accept: application/json\r\n",
                'content' => json_encode($request_data, JSON_UNESCAPED_SLASHES)
            )
        );

        if (! is_null($request_data))
            $this->addLogEntry('info', $method, 'request to [' . $relative_url . ']', array('content' => json_encode($request_data, JSON_UNESCAPED_SLASHES)));

        $context = stream_context_create($options);
        $result = file_get_contents($this->get_option('base_service_address') . $relative_url, false, $context);

        $this->addLogEntry('info', $method, 'response from [' . $relative_url . ']', array(
            'headers' => json_encode($http_response_header),
            'content' => $result
        ));

        return json_decode($result);
    }

    private function addLogEntry(string $level, string $method, string $comment, array $data)
    {
        if (array_key_exists('logging', $this->settings) && $this->settings['logging'] == 'yes') {
            $logger = wc_get_logger();
            $context = array(
                'source' => 'paymaster'
            );
            $str_data = (string) print_r($data, true);
            $logger->log($level, "$method - $comment: $str_data", $context);
        }
    }
}