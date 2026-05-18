<?php

class ModelExtensionPaymentHyperpayValu extends Model {

    public function getMethod($address, $total) {
        $this->load->language('extension/payment/hyperpay_valu');

        $method_data = array(
            'code'       => 'hyperpay_valu',
            'terms'      => '',
            'title'      => $this->config->get('payment_hyperpay_valu_heading_title'),
            'sort_order' => $this->config->get('payment_hyperpay_valu_sort_order'),
        );

        return $method_data;
    }

}
