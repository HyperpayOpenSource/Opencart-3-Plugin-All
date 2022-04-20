<?php

class ControllerExtensionPaymentHyperpayZoodPay extends Controller
{

    public function index()
    {
        $this->language->load('extension/payment/hyperpay_zoodpay');
        $this->load->model('checkout/order');
        $this->load->model('tool/image');
        $data['button_confirm'] = $this->language->get('button_confirm');
        //--------------------------------------


        // Amount
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $orderAmount = $order_info['total'];
        $orderid = $this->session->data['order_id'];
        $customer_mobile = $order_info['telephone'];

        if( substr( $customer_mobile,0 ,2) == '07'){
            $customer_mobile = substr_replace($customer_mobile, '962', 0, 1);
        }


        $channel = $this->config->get('payment_hyperpay_zoodpay_channel');
        $token = $this->config->get('payment_hyperpay_zoodpay_accesstoken');
        $type = $this->config->get('payment_hyperpay_zoodpay_trans_type');
        $amount = number_format($orderAmount * $order_info['currency_value'] ,2, '.', '');
        $currency = $this->config->get('payment_hyperpay_zoodpay_base_currency');
        $service_code = $this->config->get('payment_hyperpay_zoodpay_service_code');
        $transactionID = $orderid;
        $city = $order_info['payment_city'];

        $country = $order_info['payment_iso_code_2'];
        $postcode = $order_info['payment_postcode'];
        $payment_address = $order_info['payment_address_1'];


        $state = $order_info['payment_zone'];
        $email = $order_info['email'];
        $firstNameBilling = preg_replace('/\s/', '', str_replace("&", "", $order_info['payment_firstname']));
        $surNameBilling = preg_replace('/\s/', '', str_replace("&", "", $order_info['payment_lastname']));

        if (empty($state)) {
            $state = $city;
        }

        $datacontent = "entityId=$channel" .
            "&amount=$amount" .
            "&merchantTransactionId=$transactionID" .
            "&currency=$currency" .
            "&paymentType=$type" .
            "&customer.mobile=$customer_mobile". // here
            "&customer.givenName=$firstNameBilling".
            "&customer.surname=$surNameBilling".
            "&billing.postcode=$postcode" .
            "&shipping.postcode=$postcode" .
            "&shipping.street1=$payment_address" .
            "&billing.street1=$payment_address" .
            "&shipping.country=$country" .
            "&billing.country=$country" .
            "&customParameters[service_code]=$service_code" .
            "&customer.email=$email";

        foreach($this->cart->getProducts() as $key => $product){
           $datacontent .=
            "&cart.items[$key].name=$product[name]". // here
            "&cart.items[$key].price=". number_format($product['price']* $order_info['currency_value'], 2, '.', '').// here
            "&cart.items[$key].quantity=$product[quantity]";
            $categories[][][] =  $product['name'] ;
            
        }

        $datacontent .= "&customParameters[categories]=" . json_encode($categories) ;

        $testMode = $this->config->get('payment_hyperpay_zoodpay_testmode');
        if ($testMode == 0) {
            $scriptURL = "https://oppwa.com/v1/paymentWidgets.js?checkoutId=";
            $url = "https://oppwa.com/v1/checkouts";
            $zoodpayConnectorUrl = 'https://zoodpay.hyperpay.com/api/getTerms';
        } else {
            $scriptURL = "https://test.oppwa.com/v1/paymentWidgets.js?checkoutId=";
            $url = "https://test.oppwa.com/v1/checkouts";
            $datacontent .= "&testMode=EXTERNAL";
            $zoodpayConnectorUrl = 'https://zoodpay-sandbox.hyperpay.com/api/getTerms' ;
        }

        $datacontent .= '&customParameters[branch_id]=1';
        $datacontent .= '&customParameters[teller_id]=1';
        $datacontent .= '&customParameters[device_id]=1';
        $datacontent .= '&customParameters[bill_number]=' . $transactionID;
        $datacontent .= '&customParameters[locale]=' . $this->session->data['language'];


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization:Bearer ' . $token
        ));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $datacontent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        // print_r($responseData);
        // die;
        if (curl_errno($ch)) {
                    print_r($responseData);
            return curl_error($ch);
        }
        curl_close($ch);


        $result = json_decode($responseData);
        //var_dump($result);exit;
        $token = '';

        if (isset($result->id)) {
            $token = $result->id;
        }

        //--------------------------------------
        $data['token'] = $token;
        $data['scriptURL'] = $scriptURL . $token;



        $data['language_code'] = $this->session->data['language'];

        $http = explode(':', $this->url->link('checkout/success'));
        $url = HTTP_SERVER;
        if ($http[0] == 'https') {
            $url = HTTPS_SERVER;
        }
        $data['postbackURL'] = $url . 'index.php?route=extension/payment/hyperpay_zoodpay/callback';

        // get terms and conditions of zoodpay

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

        $result = json_decode($responseData , true);
        $result['amount'] = $amount ;
        $data['termsAndContitions'] =  $result ;


        return $this->load->view('extension/payment/hyperpay_zoodpay', $data);
    }

    public function callback()
    {
        if (isset($_GET['id'])) {
            $this->load->model('checkout/order');

            $token = $_GET["id"];

            $testMode = $this->config->get('payment_hyperpay_zoodpay_testmode');

            if ($testMode == 0) {
                $url = "https://oppwa.com/v1/checkouts/$token/payment";
            } else {
                $url = "https://test.oppwa.com/v1/checkouts/$token/payment";
            }
            $url .= "?entityId=" . trim($this->config->get('payment_hyperpay_zoodpay_channel'));
            $accesstoken = $this->config->get('payment_hyperpay_zoodpay_accesstoken');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization:Bearer ' . $accesstoken
            ));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $responseData = curl_exec($ch);
            if (curl_errno($ch)) {
                return curl_error($ch);
            }
            curl_close($ch);
            $resultJson = json_decode($responseData);

            $success = 0;
            $failed_msg = '';
            $orderid = '';


            switch ($resultJson->result->code) {
                case (preg_match('/^(000\.000\.|000\.100\.1|000\.[36])/', $resultJson->result->code) ? true : false):
                case (preg_match('/^(000\.400\.0|000\.400\.100)/', $resultJson->result->code) ? true : false):
                    $success = 1;
                    break;
                default:
                    if ($resultJson->paymentBrand == 'SADAD') {
                        if (isset($resultJson->resultDetails->ErrorMessage)) {
                            $failed_msg = $resultJson->resultDetails->Error;
                        } else {
                            $failed_msg = $resultJson->result->description;
                        }
                    } else {
                        $failed_msg = $resultJson->result->description;
                    }
            }
            $orderid = $resultJson->merchantTransactionId;



            $order_info = $this->model_checkout_order->getOrder($orderid);


            if ($order_info) {
                if ($success == 1) {
                    // Order is accepted.
                    $transUniqueID = $resultJson->id;
                    $this->model_checkout_order->addOrderHistory($orderid, $this->config->get('payment_hyperpay_zoodpay_order_status_id'), "Trans Unique ID:$transUniqueID\n", TRUE);
                    $this->success();
                } else {
                    // Order is not approved.
                    $this->model_checkout_order->addOrderHistory($orderid, $this->config->get('payment_hyperpay_zoodpay_order_status_failed_id'), '', TRUE);
                    $this->log->write("Hyperpay: Unauthorized Transaction. Transaction Failed. $failed_msg . Order Id: $orderid");
                    $this->session->data['payment_hyperpay_zoodpay_error'] = $failed_msg;
                    $this->session->data['payment_hyperpay_zoodpay_extended_error'] =   $resultJson->resultDetails->ExtendedDescription ?? '';
                    $this->response->redirect($this->url->link('extension/payment/hyperpay_zoodpay/fail', '', true));
                }
                exit;
            } else {
                if ($this->config->get('payment_hyperpay_zoodpay_mailerrors') == 1) {
                    $message = "Hello,\n\nThis is your OpenCart site at " . $this->url->link('common/home') . ".\n\n";
                    $message .= "I've received this callback from Hyperpay, and I couldn't approve it.\n\n";
                    $message .= "This is the failed message that were sent from Hyperpay: $failed_msg.\n\n";

                    $message .= "\nYou can disable these notifications by changing the \"Enable error logging by email?\" setting within the Hyperpay merchant setup.";

                    $this->sendEmail($this->config->get('config_email'), 'Hyperpay callback failed!', $message);
                }

                //$this->model_checkout_order->confirm($orderid, $this->config->get('payment_hyperpay_zoodpay_order_status_failed_id'), '', TRUE);
                $this->model_checkout_order->addOrderHistory($orderid, $this->config->get('payment_hyperpay_zoodpay_order_status_failed_id'), '', TRUE);
                $this->log->write("Hyperpay: Unauthorized Transaction. Transaction Failed. $failed_msg. Order Id: $orderid");
                $this->response->redirect($this->url->link('extension/payment/hyperpay_zoodpay/fail', '', true));
                exit;
            }
        }

        exit;
    }

    public function sendEmail($toEmail, $subject, $message)
    {
        $this->load->model('setting/store');

        $store_name = $this->config->get('config_name');

        $mail = new Mail();
        $mail->protocol = $this->config->get('config_mail_protocol');
        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->hostname = $this->config->get('config_smtp_host');
        $mail->username = $this->config->get('config_smtp_username');
        $mail->password = $this->config->get('config_smtp_password');
        $mail->port = $this->config->get('config_smtp_port');
        $mail->timeout = $this->config->get('config_smtp_timeout');
        $mail->setTo($toEmail);
        $mail->setFrom($this->config->get('config_email'));
        $mail->setSender($store_name);
        $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
        $mail->setText($message);
        $mail->send();
    }

    protected function success()
    {
        $this->response->redirect($this->url->link('checkout/success', '', true));
        exit;
    }

    public function isThisEnglishText($text)
    {
        return preg_match("/^[\w\s\.\-\,]*$/", $text);
    }

    private function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
     }

    public function fail()
    {
        $this->language->load('extension/payment/hyperpay_zoodpay');
        $data['heading_title'] = $this->config->get('payment_hyperpay_zoodpay_heading_title');

        if (isset($this->session->data['payment_hyperpay_zoodpay_error'])) {
            $data['general_error'] = $this->session->data['payment_hyperpay_zoodpay_error'];
        } else {
            $data['general_error'] = $this->language->get('general_error');;
        }
        if (isset($this->session->data['payment_hyperpay_zoodpay_extended_error'])) {

            $data['extended_error'] = $this->session->data['payment_hyperpay_zoodpay_extended_error'];
            if($this->isJson( $data['extended_error'])){
                $data['extended_error'] = json_decode( $data['extended_error'] , true);
                $msges = '';
                foreach($data['extended_error']['details'] ?? [] as $error){
                    $msges  .= $error['error'] . '<br>';
                }
                $msges .=( "<br>" . $data['extended_error']['message'] ?? '');

                $data['extended_error'] = $msges;

            };

        }
        $data['button_back'] = $this->language->get('button_back');
        $data['back'] = $this->url->link('common/home');


        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('extension/payment/hyperpay_fail', $data));
    }
}
