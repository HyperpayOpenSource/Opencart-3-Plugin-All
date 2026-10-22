<?php

class ControllerExtensionPaymentHyperpayCliq extends Controller
{

    public function index()
    {
        $this->language->load('extension/payment/hyperpay_cliq');

        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['text_loading'] = $this->language->get('text_loading');
        $data['confirm_url'] = str_replace('&amp;', '&', $this->url->link('extension/payment/hyperpay_cliq/confirm', '', true));

        return $this->load->view('extension/payment/hyperpay_cliq', $data);
    }

    public function confirm()
    {
        $this->load->language('extension/payment/hyperpay');

        $json = array();

        $this->load->model('checkout/order');

        $order_id = isset($this->session->data['order_id']) ? (int)$this->session->data['order_id'] : 0;
        $order_info = $order_id ? $this->model_checkout_order->getOrder($order_id) : array();

        if (!$order_info || !isset($this->session->data['payment_method']['code']) || $this->session->data['payment_method']['code'] != 'hyperpay_cliq') {
            $json['error'] = $this->language->get('general_error');
        } else {
            $this->load->model('extension/payment/hyperpay_s2s');
            $this->load->model('extension/payment/hyperpay_cliq');

            $currency = $this->config->get('payment_hyperpay_cliq_base_currency');

            $test = (bool)$this->config->get('payment_hyperpay_cliq_testmode');

            $cfg = array(
                'channel'    => $this->config->get('payment_hyperpay_cliq_channel'),
                'trans_type' => $this->config->get('payment_hyperpay_cliq_trans_type'),
                'test'       => $test,
            );

            $return_url = str_replace('&amp;', '&', $this->url->link('extension/payment/hyperpay_cliq/callback', 'order_id=' . $order_id, true));

            $charged = $this->currency->convert($order_info['total'], $this->config->get('config_currency'), $currency);

            $params = array_merge(
                $this->model_extension_payment_hyperpay_s2s->baseParams($cfg, $order_id, $charged, $currency, 'CLIQ', $return_url),
                $this->model_extension_payment_hyperpay_cliq->buildParams($order_info['language_code'])
            );

            $url = $this->model_extension_payment_hyperpay_s2s->baseUrl($test) . '/v1/payments';
            $response = $this->model_extension_payment_hyperpay_s2s->post($url, $this->config->get('payment_hyperpay_cliq_accesstoken'), $params);

            $code = isset($response['result']['code']) ? $response['result']['code'] : '';

            if ($this->model_extension_payment_hyperpay_s2s->isPending($code) && !empty($response['redirect']['url'])) {
                $json['action'] = $response['redirect']['url'];
                $json['method'] = !empty($response['redirect']['method']) ? $response['redirect']['method'] : 'POST';
                $json['fields'] = $this->model_extension_payment_hyperpay_s2s->redirectFields($response['redirect']);
            } else {
                $failed_msg = $this->model_extension_payment_hyperpay_s2s->errorMessage($response);
                $this->log->write("Hyperpay CliQ: payment request rejected. Code: $code. $failed_msg Order Id: $order_id");
                $json['error'] = $failed_msg !== '' ? $failed_msg : $this->language->get('general_error');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function callback()
    {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/hyperpay_s2s');

        $order_id = isset($this->request->get['order_id']) ? (int)$this->request->get['order_id'] : 0;
        $resource_path = $this->resourcePath();

        if (!$order_id || $resource_path === '') {
            $this->log->write("Hyperpay CliQ: callback without a valid payment reference. Order Id: $order_id");
            $this->response->redirect($this->url->link('extension/payment/hyperpay_cliq/fail', '', true));
        }

        $test = (bool)$this->config->get('payment_hyperpay_cliq_testmode');
        $url = $this->model_extension_payment_hyperpay_s2s->baseUrl($test) . $resource_path . '?entityId=' . urlencode(trim($this->config->get('payment_hyperpay_cliq_channel')));
        $resultJson = $this->model_extension_payment_hyperpay_s2s->get($url, $this->config->get('payment_hyperpay_cliq_accesstoken'));

        $code = isset($resultJson['result']['code']) ? $resultJson['result']['code'] : '';
        $transUniqueID = isset($resultJson['id']) ? $resultJson['id'] : '';
        $merchant_transaction_id = isset($resultJson['merchantTransactionId']) ? (string)$resultJson['merchantTransactionId'] : '';
        $parts = explode('I', $merchant_transaction_id);
        $failed_msg = $this->model_extension_payment_hyperpay_s2s->errorMessage($resultJson);

        $order_info = ((int)$parts[0] === $order_id) ? $this->model_checkout_order->getOrder($order_id) : array();

        if ($order_info) {
            $success_status_id = $this->config->get('payment_hyperpay_cliq_order_status_id');

            if ($this->model_extension_payment_hyperpay_s2s->isSuccess($code)) {
                if ($order_info['order_status_id'] != $success_status_id) {
                    $this->model_checkout_order->addOrderHistory($order_id, $success_status_id, "Trans Unique ID:$transUniqueID\n", TRUE);
                }
                $this->success();
            } elseif ($this->model_extension_payment_hyperpay_s2s->isPending($code)) {
                $pending_status_id = $this->config->get('payment_hyperpay_cliq_order_status_pending_id') ?: 1;

                if ($order_info['order_status_id'] != $success_status_id) {
                    $this->model_checkout_order->addOrderHistory($order_id, $pending_status_id, "Awaiting gateway confirmation. Payment ID: $transUniqueID", TRUE);
                }
                $this->success();
            } else {
                if ($order_info['order_status_id'] != $success_status_id) {
                    $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_hyperpay_cliq_order_status_failed_id'), '', TRUE);
                }
                $this->log->write("Hyperpay: Unauthorized Transaction. Transaction Failed. Code: $code. $failed_msg . Order Id: $order_id");
                $this->session->data['payment_hyperpay_cliq_error'] = $failed_msg;
                $this->response->redirect($this->url->link('extension/payment/hyperpay_cliq/fail', '', true));
            }
        } else {
            if ($this->config->get('payment_hyperpay_cliq_mailerrors') == 1) {
                $message = "Hello,\n\nThis is your OpenCart site at " . $this->url->link('common/home') . ".\n\n";
                $message .= "I've received this callback from Hyperpay, and I couldn't approve it.\n\n";
                $message .= "This is the failed message that were sent from Hyperpay: $failed_msg.\n\n";

                $message .= "\nYou can disable these notifications by changing the \"Enable error logging by email?\" setting within the Hyperpay merchant setup.";

                $this->sendEmail($this->config->get('config_email'), 'Hyperpay callback failed!', $message);
            }

            $this->log->write("Hyperpay: Unauthorized Transaction. Transaction Failed. Code: $code. $failed_msg. Order Id: $order_id, merchantTransactionId: $merchant_transaction_id");
            $this->response->redirect($this->url->link('extension/payment/hyperpay_cliq/fail', '', true));
        }
    }

    private function resourcePath()
    {
        $source = isset($this->request->get['resourcePath']) || isset($this->request->get['id']) ? $this->request->get : $this->request->post;

        if (isset($source['resourcePath']) && preg_match('#^/v1/payments/[\w.-]+$#', $source['resourcePath'])) {
            return $source['resourcePath'];
        }

        if (isset($source['id']) && preg_match('#^[\w.-]+$#', $source['id'])) {
            return '/v1/payments/' . $source['id'];
        }

        return '';
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
        $this->language->load('extension/payment/hyperpay_cliq');
        $data['heading_title'] = $this->config->get('payment_hyperpay_cliq_heading_title');

        if (isset($this->session->data['payment_hyperpay_cliq_error'])) {
            $data['general_error'] = $this->session->data['payment_hyperpay_cliq_error'];
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
