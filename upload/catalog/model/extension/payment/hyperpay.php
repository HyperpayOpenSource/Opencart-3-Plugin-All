<?php

class ModelExtensionPaymentHyperpay extends Model {

    public function getMethod($address, $total) {
        $this->load->language('extension/payment/hyperpay');

        $method_data = array(
            'code'       => 'hyperpay',
            'terms'      => '',
            'title'      => $this->config->get('payment_hyperpay_heading_title'),
            'sort_order' => $this->config->get('payment_hyperpay_sort_order'),
            'icon' => HTTPS_SERVER . 'image/catalog/hyperpay/Hyperpay.png'
        );

        return $method_data;        
    }

}

?>