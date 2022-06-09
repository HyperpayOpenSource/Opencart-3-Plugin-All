<?php

class ModelExtensionPaymentHyperpayMada extends Model {

    public function getMethod($address, $total) {
        $this->load->language('extension/payment/hyperpay_mada');

        $method_data = array(
            'code'       => 'hyperpay_mada',
            'terms'      => '',
            'title'      => $this->language->get('heading_title'),
            'sort_order' => $this->config->get('payment_hyperpay_mada_sort_order'),
            'icon' => HTTPS_SERVER . 'image/catalog/hyperpay/mada-logo.png'
        );

        return $method_data;
    }

}

?>
