<?php

class ModelExtensionPaymentHyperpayZoodPay extends Model
{

    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/hyperpay');

        $method_data = array(
            'code'       => 'hyperpay_zoodpay',
            'terms'      => '',
            'title'      => $this->config->get('payment_hyperpay_zoodpay_heading_title'),
            'sort_order' => $this->config->get('payment_hyperpay_zoodpay_sort_order')
        );

        return $method_data;
    }
}
