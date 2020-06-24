<?php

class ControllerExtensionPaymentHyperpayApple extends Controller
{

    public function index()
    {
        $this->language->load('extension/payment/hyperpay');
        $this->load->model('checkout/order');
        $data['button_confirm'] = $this->language->get('button_confirm');
        //--------------------------------------
        $testMode = $this->config->get('payment_hyperpay_apple_testmode');
        if ($testMode == 0) {
            $scriptURL = "https://oppwa.com/v1/paymentWidgets.js?checkoutId=";
            $url = "https://oppwa.com/v1/checkouts";
        } else {
            $scriptURL = "https://test.oppwa.com/v1/paymentWidgets.js?checkoutId=";
            $url = "https://test.oppwa.com/v1/checkouts";
        }

        // Amount
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $orderAmount = $order_info['total'];
        $orderid = $this->session->data['order_id'];


        $channel = $this->config->get('payment_hyperpay_apple_channel');
        $mode = $this->config->get('payment_hyperpay_apple_trans_mode');
        $token = $this->config->get('payment_hyperpay_apple_accesstoken');
        $type = $this->config->get('payment_hyperpay_apple_trans_type');
        $connector = $this->config->get('payment_hyperpay_apple_connector');
        $amount = number_format(round($orderAmount, 2), 2, '.', '');
        $currency = $this->config->get('payment_hyperpay_apple_base_currency');
        $transactionID = $orderid;
        $firstName = $order_info['payment_firstname'];
        $family = $order_info['payment_lastname'];
        $street = $order_info['payment_address_1'];
        $zip = $order_info['payment_postcode'];
        $city = $order_info['payment_city'];
        $state = $order_info['payment_zone'];
        $country = $order_info['payment_iso_code_2'];
        $email = $order_info['email'];
        $ip = $order_info['ip'];

        if (empty($state)) {
            $state = $city;
        }
        $lang = explode('-', $this->session->data['language']);
        $datacontent = "entityId=$channel" .
            "&amount=$amount" .
            "&currency=$currency" .
            "&paymentType=$type" .
            "&merchantTransactionId=$transactionID" .
            "&customer.email=$email";

        $firstNameBilling = preg_replace('/\s/', '', str_replace("&", "", $firstName));
        $surNameBilling = preg_replace('/\s/', '', str_replace("&", "", $family));
        $countryBilling = $country;
        $streetBilling = preg_replace('/\s/', '', str_replace("&", "", $street));
        $cityBilling = preg_replace('/\s/', '', str_replace("&", "", $city));
        if (!($connector == 'migs' && $this->isThisEnglishText($cityBilling) == false)) {
            $datacontent .= "&billing.city=" . $cityBilling;
        }

        if (!($connector == 'migs' && $this->isThisEnglishText($countryBilling) == false)) {
            $datacontent .= "&billing.country=" . $countryBilling;
        }

        if (!($connector == 'migs' && $this->isThisEnglishText($firstNameBilling) == false)) {
            $datacontent .= "&customer.givenName=" . $firstNameBilling;
        }

        if (!($connector == 'migs' && $this->isThisEnglishText($surNameBilling) == false)) {
            $datacontent .= "&customer.surname=" . $surNameBilling;
        }

        if (!($connector == 'migs' && $this->isThisEnglishText($streetBilling) == false)) {
            $datacontent .= "&billing.street1=" . $streetBilling;
            $datacontent .= "&billing.street2=" . $streetBilling;
        }

        if ($mode == 'CONNECTOR_TEST') {
            $datacontent .= "&testMode=EXTERNAL";
        }
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
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);


        $result = json_decode($responseData);
        //var_dump($result);exit;
        $token = '';

        if (isset($result->id)) {
            $token = $result->id;
        }

        $payment_brands = implode(' ', $this->config->get('payment_hyperpay_apple_brands'));
        //--------------------------------------
        $data['token'] = $token;
        $data['payment_brands'] = $payment_brands;
        $data['scriptURL'] = $scriptURL . $token;

        $data['formStyle'] = $this->config->get('payment_hyperpay_apple_payment_style');
        $data['language_code'] = $this->session->data['language'];

        $http = explode(':', $this->url->link('checkout/success'));
        $url = HTTP_SERVER;
        if ($http[0] == 'https') {
            $url = HTTPS_SERVER;
        }
        $data['postbackURL'] = $url . 'index.php?route=extension/payment/hyperpay_apple/callback';

        return $this->load->view('extension/payment/hyperpay_apple', $data);
    }

    public function callback()
    {
        if (isset($_GET['id'])) {
            $this->load->model('checkout/order');

            $token = $_GET["id"];

            $testMode = $this->config->get('payment_hyperpay_apple_testmode');

            if ($testMode == 0) {
                $url = "https://oppwa.com/v1/checkouts/$token/payment";
            } else {
                $url = "https://test.oppwa.com/v1/checkouts/$token/payment";
            }
            $url .= "?entityId=" . trim($this->config->get('payment_hyperpay_apple_channel'));
            $accesstoken = $this->config->get('payment_hyperpay_apple_accesstoken');
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
                    $this->model_checkout_order->addOrderHistory($orderid, $this->config->get('payment_hyperpay_apple_order_status_id'), "Trans Unique ID:$transUniqueID\n", TRUE);
                    $this->success();
                } else {
                    // Order is not approved.
                    $this->model_checkout_order->addOrderHistory($orderid, $this->config->get('payment_hyperpay_apple_order_status_failed_id'), '', TRUE);
                    $this->log->write("hyperpay_apple: Unauthorized Transaction. Transaction Failed. $failed_msg . Order Id: $orderid");
                    $this->session->data['payment_hyperpay_apple_error'] = $failed_msg;
                    $this->response->redirect($this->url->link('extension/payment/hyperpay_apple/fail', '', true));
                }
                exit;
            } else {
                if ($this->config->get('payment_hyperpay_apple_mailerrors') == 1) {
                    $message = "Hello,\n\nThis is your OpenCart site at " . $this->url->link('common/home') . ".\n\n";
                    $message .= "I've received this callback from hyperpay_apple, and I couldn't approve it.\n\n";
                    $message .= "This is the failed message that were sent from hyperpay_apple: $failed_msg.\n\n";

                    $message .= "\nYou can disable these notifications by changing the \"Enable error logging by email?\" setting within the hyperpay_apple merchant setup.";

                    $this->sendEmail($this->config->get('config_email'), 'hyperpay_apple callback failed!', $message);
                }

                //$this->model_checkout_order->confirm($orderid, $this->config->get('payment_hyperpay_apple_order_status_failed_id'), '', TRUE);
                $this->model_checkout_order->addOrderHistory($orderid, $this->config->get('payment_hyperpay_apple_order_status_failed_id'), '', TRUE);
                $this->log->write("hyperpay_apple: Unauthorized Transaction. Transaction Failed. $failed_msg. Order Id: $orderid");
                $this->response->redirect($this->url->link('extension/payment/hyperpay_apple/fail', '', true));
                print 'fff';
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

    public function fail()
    {
        $this->language->load('extension/payment/hyperpay');
        $data['heading_title'] = $this->config->get('payment_hyperpay_apple_heading_title');

        if (isset($this->session->data['payment_hyperpay_apple_error'])) {
            $data['general_error'] = $this->session->data['payment_hyperpay_apple_error'];
        } else {
            $data['general_error'] = $this->language->get('general_error');;
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
