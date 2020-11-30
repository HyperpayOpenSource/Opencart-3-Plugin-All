<?php

class ModelExtensionPaymentHyperpayApple extends Model {

    public function getMethod($address, $total) {
        $this->load->language('extension/payment/hyperpay');

        $method_data = array(
            'code'       => 'hyperpay_apple',
            'terms'      => '',
            'title'      => $this->config->get('payment_hyperpay_apple_heading_title'),
            'sort_order' => $this->config->get('payment_hyperpay_apple_sort_order')
        );

        return $method_data;        
    }

}

?>