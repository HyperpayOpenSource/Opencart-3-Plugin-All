<?php

class ModelExtensionPaymentHyperpayClickToPay extends Model
{

    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/hyperpay');

        $method_data = array(
            'code'       => 'hyperpay_click_to_pay',
            'terms'      => '',
            'title'      => $this->config->get('payment_hyperpay_click_to_pay_heading_title'),
            'sort_order' => $this->config->get('payment_hyperpay_click_to_pay_sort_order')
        );

        return $method_data;
    }
}
