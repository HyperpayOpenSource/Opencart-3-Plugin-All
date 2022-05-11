<?php

class ModelExtensionPaymentHyperpayZoodPay extends Model
{

    public function getMethod($address, $total)
    {

        $this->load->language('extension/payment/hyperpay');
        $status = false ;

        $testMode = $this->config->get('payment_hyperpay_zoodpay_testmode');
        if ($testMode == 0) {
            $zoodpayConnectorUrl = 'https://zoodpay.hyperpay.com/api/getTerms';
        } else {
            $zoodpayConnectorUrl = 'https://zoodpay-sandbox.hyperpay.com/api/getTerms';
        }

        $channel = $this->config->get('payment_hyperpay_zoodpay_channel');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $zoodpayConnectorUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['entity_id' => $channel]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
            print_r($responseData);
            return curl_error($ch);
        }
        curl_close($ch);

        $result = json_decode($responseData, true);


        if ($total <= $result['max_limit'] && $total >= $result['min_limit'])
            $status = true;

        if($status)
        $method_data = array(
            'code'       => 'hyperpay_zoodpay',
            'terms'      => "<b> Instalments of " . ($total / $result['instalments']) ."/mo </b>",
            'title'      => $this->config->get('payment_hyperpay_zoodpay_heading_title'),
            'sort_order' => $this->config->get('payment_hyperpay_zoodpay_sort_order')
        );

        return $method_data ?? [];
    }
}
