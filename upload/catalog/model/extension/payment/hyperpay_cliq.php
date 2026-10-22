<?php

class ModelExtensionPaymentHyperpayCliq extends Model
{

    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/hyperpay');

        $method_data = array(
            'code'       => 'hyperpay_cliq',
            'terms'      => '',
            'title'      => $this->config->get('payment_hyperpay_cliq_heading_title'),
            'sort_order' => $this->config->get('payment_hyperpay_cliq_sort_order'),
            'icon' => HTTPS_SERVER . 'image/catalog/hyperpay/CLIQ.svg'
        );

        return $method_data;
    }

    /**
     * the connector reads the CliQ payment page language from customParameters[locale]
     */
    public function buildParams($language_code)
    {
        return array(
            'customParameters[locale]' => strtolower(substr((string)$language_code, 0, 2)) === 'ar' ? 'ar' : 'en',
        );
    }
}
