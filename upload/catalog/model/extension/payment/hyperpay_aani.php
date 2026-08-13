<?php

class ModelExtensionPaymentHyperpayAani extends Model
{

    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/hyperpay');

        $method_data = array(
            'code'       => 'hyperpay_aani',
            'terms'      => '',
            'title'      => $this->config->get('payment_hyperpay_aani_heading_title'),
            'sort_order' => $this->config->get('payment_hyperpay_aani_sort_order'),
            'icon' => HTTPS_SERVER . 'image/catalog/hyperpay/AANI.svg'
        );

        return $method_data;
    }
}
