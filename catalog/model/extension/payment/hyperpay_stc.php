<?php

class ModelExtensionPaymentHyperpayStc extends Model
{

    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/hyperpay');

        $method_data = array(
            'code'       => 'hyperpay_stc',
            'terms'      => '',
            'title'      => $this->config->get('payment_hyperpay_stc_heading_title'),
            'sort_order' => $this->config->get('payment_hyperpay_stc_sort_order')
        );

        return $method_data;
    }
}
