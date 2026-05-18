<?php

class ControllerExtensionPaymentHyperpayGooglepay extends Controller
{

    public function index()
    {
        $this->language->load('extension/payment/hyperpay_googlepay');
        $this->load->model('checkout/order');
        $data['button_confirm'] = $this->language->get('button_confirm');

        $testMode = $this->config->get('payment_hyperpay_googlepay_testmode');
        if ($testMode == 0) {
            $scriptURL = "https://oppwa.com/v1/paymentWidgets.js?checkoutId=";
            $url = "https://oppwa.com/v1/checkouts";
        } else {
            $scriptURL = "https://test.oppwa.com/v1/paymentWidgets.js?checkoutId=";
            $url = "https://test.oppwa.com/v1/checkouts";
        }

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $orderAmount = $order_info['total'];
        $orderid = $this->session->data['order_id'];

        $channel = $this->config->get('payment_hyperpay_googlepay_channel');
        $mode = $this->config->get('payment_hyperpay_googlepay_trans_mode');
        $accessToken = $this->config->get('payment_hyperpay_googlepay_accesstoken');
        $type = $this->config->get('payment_hyperpay_googlepay_trans_type');
        $connector = $this->config->get('payment_hyperpay_googlepay_connector');
        $currency = $this->config->get('payment_hyperpay_googlepay_base_currency');

        $amount = number_format($this->currency->convert($orderAmount, $this->config->get('config_currency'), $currency), 2, '.', '');
        $transactionID = $orderid;
        $firstName = $order_info['payment_firstname'];
        $family = $order_info['payment_lastname'];
        $street = $order_info['payment_address_1'];
        $city = $order_info['payment_city'];
        $country = $order_info['payment_iso_code_2'];
        $email = $order_info['email'];

        $datacontent = "entityId=$channel" .
            "&amount=$amount" .
            "&currency=$currency" .
            "&paymentType=$type" .
            "&merchantTransactionId=$transactionID" .
            "&customer.email=$email";

        $datacontent .= '&customParameters[branch_id]=1';
        $datacontent .= '&customParameters[teller_id]=1';
        $datacontent .= '&customParameters[device_id]=1';
        $datacontent .= '&customParameters[bill_number]=' . $transactionID;
        $datacontent .= '&customParameters[locale]=' . $this->session->data['language'];

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
            'Authorization:Bearer ' . $accessToken
        ));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $datacontent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);

        $result = json_decode($responseData);
        $checkoutId = '';

        if (isset($result->id)) {
            $checkoutId = $result->id;
        }

        $payment_brands = implode(' ', $this->config->get('payment_hyperpay_googlepay_brands'));

        $data['token'] = $checkoutId;
        $data['payment_brands'] = $payment_brands;
        $data['scriptURL'] = $scriptURL . $checkoutId;

        $data['formStyle'] = $this->config->get('payment_hyperpay_googlepay_payment_style');
        $data['language_code'] = $this->session->data['language'];

        $data['merchant_id'] = $this->config->get('payment_hyperpay_googlepay_merchant_id');
        $data['entityId'] = $this->config->get('payment_hyperpay_googlepay_channel');

        $http = explode(':', $this->url->link('checkout/success'));
        $url = HTTP_SERVER;
        if ($http[0] == 'https') {
            $url = HTTPS_SERVER;
        }
        $data['postbackURL'] = $url . 'index.php?route=extension/payment/hyperpay_googlepay/callback';

        return $this->load->view('extension/payment/hyperpay_googlepay', $data);
    }

    public function callback()
    {
        if (isset($_GET['id'])) {
            $this->load->model('checkout/order');

            $checkoutId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['id']);
            if (empty($checkoutId)) { exit; }

            $testMode = $this->config->get('payment_hyperpay_googlepay_testmode');

            if ($testMode == 0) {
                $url = "https://oppwa.com/v1/checkouts/$checkoutId/payment";
            } else {
                $url = "https://test.oppwa.com/v1/checkouts/$checkoutId/payment";
            }
            $url .= "?entityId=" . trim($this->config->get('payment_hyperpay_googlepay_channel'));
            $accesstoken = $this->config->get('payment_hyperpay_googlepay_accesstoken');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization:Bearer ' . $accesstoken
            ));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
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
                    if (($resultJson->paymentBrand ?? '') == 'SADAD') {
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
                    $this->model_checkout_order->addOrderHistory($orderid, $this->config->get('payment_hyperpay_googlepay_order_status_id'), "Trans Unique ID:$transUniqueID\n", TRUE);
                    $this->success();
                } else {
                    // Order is not approved.
                    $this->model_checkout_order->addOrderHistory($orderid, $this->config->get('payment_hyperpay_googlepay_order_status_failed_id'), '', TRUE);
                    $this->log->write("Hyperpay: Unauthorized Transaction. Transaction Failed. $failed_msg . Order Id: $orderid");
                    $this->session->data['payment_hyperpay_googlepay_error'] = $failed_msg;
                    $this->response->redirect($this->url->link('extension/payment/hyperpay_googlepay/fail', '', true));
                }
                exit;
            } else {
                if ($this->config->get('payment_hyperpay_googlepay_mailerrors') == 1) {
                    $message = "Hello,\n\nThis is your OpenCart site at " . $this->url->link('common/home') . ".\n\n";
                    $message .= "I've received this callback from Hyperpay, and I couldn't approve it.\n\n";
                    $message .= "This is the failed message that were sent from Hyperpay: $failed_msg.\n\n";

                    $message .= "\nYou can disable these notifications by changing the \"Enable error logging by email?\" setting within the Hyperpay merchant setup.";

                    $this->sendEmail($this->config->get('config_email'), 'Hyperpay callback failed!', $message);
                }

                $this->model_checkout_order->addOrderHistory($orderid, $this->config->get('payment_hyperpay_googlepay_order_status_failed_id'), '', TRUE);
                $this->log->write("Hyperpay: Unauthorized Transaction. Transaction Failed. $failed_msg. Order Id: $orderid");
                $this->response->redirect($this->url->link('extension/payment/hyperpay_googlepay/fail', '', true));
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
        $this->language->load('extension/payment/hyperpay_googlepay');
        $data['heading_title'] = $this->config->get('payment_hyperpay_googlepay_heading_title');

        if (isset($this->session->data['payment_hyperpay_googlepay_error'])) {
            $data['general_error'] = $this->session->data['payment_hyperpay_googlepay_error'];
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
