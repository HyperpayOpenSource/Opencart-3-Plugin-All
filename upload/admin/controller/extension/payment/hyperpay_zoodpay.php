
<?php

class ControllerExtensionPaymentHyperpayZoodPay extends Controller
{
    private $error = array();

    public function index()
    {

        $this->load->language('extension/payment/hyperpay_zoodpay');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        $data['heading_title'] = $this->language->get('heading_title');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {

            //$this->model_setting_setting->editSetting('hyperpay_zoodpay',  $this->request->post);
            $this->model_setting_setting->editSetting('payment_hyperpay_zoodpay',  $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');

            //$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true));
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => true
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment/hyperpay_zoodpay', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => ' :: '
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/hyperpay_zoodpay', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => ' :: '
        );

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['permission'])) {
            $data['error_permission'] = $this->error['permission'];
        } else {
            $data['error_permission'] = '';
        }

        if (isset($this->error['heading_title'])) {
            $data['error_heading_title'] = $this->error['heading_title'];
        } else {
            $data['error_heading_title'] = '';
        }

        if (isset($this->error['channel'])) {
            $data['error_channel'] = $this->error['channel'];
        } else {
            $data['error_channel'] = '';
        }


        //-------------------------------------------------------

        $data['text_edit'] = $this->language->get('text_edit');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['action'] = $this->url->link('extension/payment/hyperpay_zoodpay', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true);

        $data['entry_heading_title'] = $this->language->get('entry_heading_title');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');


        $data['entry_testmode'] = $this->language->get('entry_testmode');

        $data['entry_testmode_off'] = $this->language->get('entry_testmode_off');
        $data['entry_testmode_on'] = $this->language->get('entry_testmode_on');

        $data['entry_trans_type'] = $this->language->get('entry_trans_type');
        $data['entry_all_trans_type'] = $this->get_hyperpay_zoodpay_trans_type();


        $data['entry_base_currency'] = $this->language->get('entry_base_currency');
        $data['entry_all_currencies'] = $this->get_all_currencies();

        $data['entry_channel'] = $this->language->get('entry_channel');

        $data['entry_accesstoken'] = $this->language->get('entry_accesstoken');


        $data['service_code'] = $this->language->get('service_code');

        $data['entry_mailerrors'] = str_replace('admin_email', $this->config->get('config_email'), $this->language->get('entry_mailerrors'));
        $data['entry_mailerrors_enable'] = $this->language->get('entry_mailerrors_enable');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');

        $data['entry_order_status'] = $this->language->get('entry_order_status');
        $data['entry_order_status_failed'] = $this->language->get('entry_order_status_failed');

        //-----------------------------------------------------------------------

        if (isset($this->request->post['payment_hyperpay_zoodpay_status'])) {
            $data['payment_hyperpay_zoodpay_status'] = $this->request->post['payment_hyperpay_zoodpay_status'];
        } else {
            $data['payment_hyperpay_zoodpay_status'] = $this->config->get('payment_hyperpay_zoodpay_status');
        }

        if (isset($this->request->post['payment_hyperpay_zoodpay_base_currency'])) {
            $data['payment_hyperpay_zoodpay_base_currency'] = $this->request->post['payment_hyperpay_zoodpay_base_currency'];
        } else {
            $data['payment_hyperpay_zoodpay_base_currency'] = $this->config->get('payment_hyperpay_zoodpay_base_currency');
        }
      
        if (isset($this->request->post['payment_hyperpay_zoodpay_service_code'])) {
            $data['payment_hyperpay_zoodpay_service_code'] = $this->request->post['payment_hyperpay_zoodpay_service_code'];
        } else {
            $data['payment_hyperpay_zoodpay_service_code'] = $this->config->get('payment_hyperpay_zoodpay_service_code');
        }

        if (isset($this->request->post['payment_hyperpay_zoodpay_sort_order'])) {
            $data['payment_hyperpay_zoodpay_sort_order'] = $this->request->post['payment_hyperpay_zoodpay_sort_order'];
        } elseif($this->config->get('payment_hyperpay_zoodpay_sort_order')) {
            $data['payment_hyperpay_zoodpay_sort_order'] = $this->config->get('payment_hyperpay_zoodpay_sort_order');
        }else{
            $data['payment_hyperpay_zoodpay_sort_order'] = 4;
        }


        if (isset($this->request->post['payment_hyperpay_zoodpay_testmode'])) {
            $data['payment_hyperpay_zoodpay_testmode'] = $this->request->post['payment_hyperpay_zoodpay_testmode'];
        } else {
            $data['payment_hyperpay_zoodpay_testmode'] = $this->config->get('payment_hyperpay_zoodpay_testmode');
        }

        if (isset($this->request->post['payment_hyperpay_zoodpay_trans_type'])) {
            $data['payment_hyperpay_zoodpay_trans_type'] = $this->request->post['payment_hyperpay_zoodpay_trans_type'];
        } else {
            $data['payment_hyperpay_zoodpay_trans_type'] = $this->config->get('payment_hyperpay_zoodpay_trans_type');
        }


        if (isset($this->request->post['payment_hyperpay_zoodpay_heading_title'])) {
            $data['payment_hyperpay_zoodpay_heading_title'] = $this->request->post['payment_hyperpay_zoodpay_heading_title'];
        } else {
            $data['payment_hyperpay_zoodpay_heading_title'] = $this->config->get('payment_hyperpay_zoodpay_heading_title');
        }

        if (isset($this->request->post['payment_hyperpay_zoodpay_channel'])) {
            $data['payment_hyperpay_zoodpay_channel'] = $this->request->post['payment_hyperpay_zoodpay_channel'];
        } else {
            $data['payment_hyperpay_zoodpay_channel'] = $this->config->get('payment_hyperpay_zoodpay_channel');
        }

        if (isset($this->request->post['payment_hyperpay_zoodpay_accesstoken'])) {
            $data['payment_hyperpay_zoodpay_accesstoken'] = $this->request->post['payment_hyperpay_zoodpay_accesstoken'];
        } else {
            $data['payment_hyperpay_zoodpay_accesstoken'] = $this->config->get('payment_hyperpay_zoodpay_accesstoken');
        }

        if (isset($this->request->post['payment_hyperpay_zoodpay_brands'])) {
            $data['payment_hyperpay_zoodpay_brands'] = $this->request->post['payment_hyperpay_zoodpay_brands'];
        } else {
            $data['payment_hyperpay_zoodpay_brands'] = $this->config->get('payment_hyperpay_zoodpay_brands');
        }

        if (isset($this->request->post['payment_hyperpay_zoodpay_mailerrors'])) {
            $data['payment_hyperpay_zoodpay_mailerrors'] = $this->request->post['payment_hyperpay_zoodpay_mailerrors'];
        } else {
            $data['payment_hyperpay_zoodpay_mailerrors'] = $this->config->get('payment_hyperpay_zoodpay_mailerrors');
        }

        if (isset($this->request->post['payment_hyperpay_zoodpay_mailerrors_enable'])) {
            $data['payment_hyperpay_zoodpay_mailerrors_enable'] = $this->request->post['payment_hyperpay_zoodpay_mailerrors_enable'];
        } else {
            $data['payment_hyperpay_zoodpay_mailerrors_enable'] = $this->config->get('payment_hyperpay_zoodpay_mailerrors_enable');
        }

        $data['payment_hyperpay_zoodpay_admin_email'] = $this->config->get('config_email');

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_hyperpay_zoodpay_order_status_id'])) {
            $data['payment_hyperpay_zoodpay_order_status_id'] = $this->request->post['payment_hyperpay_zoodpay_order_status_id'];
        } else {
            $data['payment_hyperpay_zoodpay_order_status_id'] = $this->config->get('payment_hyperpay_zoodpay_order_status_id');
        }

        if (isset($this->request->post['payment_hyperpay_zoodpay_order_status_failed_id'])) {
            $data['payment_hyperpay_zoodpay_order_status_failed_id'] = $this->request->post['payment_hyperpay_zoodpay_order_status_failed_id'];
        } else {
            $data['payment_hyperpay_zoodpay_order_status_failed_id'] = $this->config->get('payment_hyperpay_zoodpay_order_status_failed_id');
        }



        $data['text_missing'] = $this->language->get('text_missing');


        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/hyperpay_zoodpay', $data));
    }

    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/hyperpay_zoodpay')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }


        if (!$this->request->post['payment_hyperpay_zoodpay_heading_title']) {
            $this->error['heading_title'] = $this->language->get('error_heading_title');
        }

        if (!$this->request->post['payment_hyperpay_zoodpay_channel']) {
            $this->error['channel'] = $this->language->get('error_channel');
        }

        if (!$this->request->post['payment_hyperpay_zoodpay_accesstoken']) {
            $this->error['access'] = $this->language->get('error_access');
        }




        if (!$this->error) {
            return TRUE;
        } else {
            return FALSE;
        }
    }



    private function get_hyperpay_zoodpay_trans_type()
    {
        $hyperpay_zoodpay_trans_type = array(
            'DB' => 'Debit',
            'PA' => 'Pre-Authorization'
        );

        return $hyperpay_zoodpay_trans_type;
    }



    private function get_all_currencies()
    {
        $this->load->model('localisation/currency');
        $currencyArray = [];
        $currencyArray = $this->model_localisation_currency->getCurrencies();
        $all = [];
        foreach ($currencyArray as $currency) {

            $all[$currency['code']] = $currency['code'];
        }
        return $all;
    }
}
