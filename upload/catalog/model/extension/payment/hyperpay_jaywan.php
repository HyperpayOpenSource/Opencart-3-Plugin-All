<?php

class ModelExtensionPaymentHyperpayJaywan extends Model {

    public function getMethod($address, $total) {
        $this->load->language('extension/payment/hyperpay_jaywan');

        $method_data = array(
            'code'       => 'hyperpay_jaywan',
            'terms'      => '',
            'title'      => $this->config->get('payment_hyperpay_jaywan_heading_title'),
            'sort_order' => $this->config->get('payment_hyperpay_jaywan_sort_order'),
            'icon' => HTTPS_SERVER . 'image/catalog/hyperpay/JAYWAN.svg'
        );

        return $method_data;
    }

}
