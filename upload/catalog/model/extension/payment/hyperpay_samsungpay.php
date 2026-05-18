<?php

class ModelExtensionPaymentHyperpaySamsungpay extends Model {

    public function getMethod($address, $total) {
        $this->load->language('extension/payment/hyperpay');

        $method_data = array(
            'code'       => 'hyperpay_samsungpay',
            'terms'      => '',
            'title'      => $this->config->get('payment_hyperpay_samsungpay_heading_title'),
            'sort_order' => $this->config->get('payment_hyperpay_samsungpay_sort_order'),
        );

        return $method_data;
    }

}
