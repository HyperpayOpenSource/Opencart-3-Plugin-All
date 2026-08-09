<?php

class ModelExtensionPaymentHyperpayGooglepay extends Model {

    public function getMethod($address, $total) {
        $this->load->language('extension/payment/hyperpay');

        $method_data = array(
            'code'       => 'hyperpay_googlepay',
            'terms'      => '',
            'title'      => $this->config->get('payment_hyperpay_googlepay_heading_title'),
            'sort_order' => $this->config->get('payment_hyperpay_googlepay_sort_order'),
            'icon' => HTTPS_SERVER . 'image/catalog/hyperpay/GOOGLEPAY.svg'
        );

        return $method_data;
    }

}
