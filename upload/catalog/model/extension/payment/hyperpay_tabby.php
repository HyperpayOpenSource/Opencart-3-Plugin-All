<?php

class ModelExtensionPaymentHyperpayTabby extends Model
{

    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/hyperpay');

        $method_data = array(
            'code'       => 'hyperpay_tabby',
            'terms'      => '',
            'title'      => $this->config->get('payment_hyperpay_tabby_heading_title'),
            'sort_order' => $this->config->get('payment_hyperpay_tabby_sort_order')
        );

        return $method_data;
    }
}
