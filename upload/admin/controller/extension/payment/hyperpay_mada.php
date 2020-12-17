<?php

class ControllerExtensionPaymentHyperpayMada extends Controller
{
    private $error = array();

    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "hp_mada_saving_cards (
        id INT AUTO_INCREMENT,
         registration_id VARCHAR(255) NOT NULL,
         customer_id VARCHAR(255) NOT NULL,
         mode int (10) NOT NULL,
         PRIMARY KEY (id)
     )  ENGINE=INNODB;";

        $this->db->query($sql);
    }

    public function index()
    {

        $this->load->language('extension/payment/hyperpay_mada');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        $data['heading_title'] = $this->language->get('heading_title');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {

            //$this->model_setting_setting->editSetting('hyperpay_mada',  $this->request->post);
            $this->model_setting_setting->editSetting('payment_hyperpay_mada',  $this->request->post);
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
            'href' => $this->url->link('extension/payment/hyperpay_mada', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => ' :: '
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/hyperpay_mada', 'user_token=' . $this->session->data['user_token'], true),
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

        $data['action'] = $this->url->link('extension/payment/hyperpay_mada', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true);

        $data['entry_heading_title'] = $this->language->get('entry_heading_title');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['entry_connector'] = $this->language->get('entry_connector');


        $data['entry_testmode'] = $this->language->get('entry_testmode');

        $data['entry_testmode_off'] = $this->language->get('entry_testmode_off');
        $data['entry_testmode_on'] = $this->language->get('entry_testmode_on');

        $data['entry_trans_type'] = $this->language->get('entry_trans_type');
        $data['entry_all_trans_type'] = $this->get_hyperpay_mada_trans_type();

        $data['entry_trans_mode'] = $this->language->get('entry_trans_mode');
        $data['entry_all_trans_mode'] = $this->get_hyperpay_mada_trans_mode();

        $data['entry_base_currency'] = $this->language->get('entry_base_currency');
        $data['entry_all_currencies'] = $this->get_all_currencies();

        $data['entry_channel'] = $this->language->get('entry_channel');

        $data['entry_accesstoken'] = $this->language->get('entry_accesstoken');


        $data['entry_brands'] = $this->language->get('entry_brands');
        $data['entry_all_brands'] = $this->get_hyperpay_mada_payment_methods();

        $data['entry_payment_style'] = $this->language->get('Payment Style');
        $data['entry_all_payment_style'] = $this->get_hyperpay_mada_payment_style();

        $data['entry_mailerrors'] = str_replace('admin_email', $this->config->get('config_email'), $this->language->get('entry_mailerrors'));
        $data['entry_mailerrors_enable'] = $this->language->get('entry_mailerrors_enable');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');

        $data['entry_order_status'] = $this->language->get('entry_order_status');
        $data['entry_order_status_failed'] = $this->language->get('entry_order_status_failed');
        $data['entry_all_connector'] = $this->get_hyperpay_mada_connector();

        $data['tokenization_status'] = $this->language->get('tokenization_status');

        $data['tokenization_status_off'] = $this->language->get('tokenization_status_off');
        $data['tokenization_status_on'] = $this->language->get('tokenization_status_on');
        //-----------------------------------------------------------------------

        if (isset($this->request->post['payment_hyperpay_mada_status'])) {
            $data['payment_hyperpay_mada_status'] = $this->request->post['payment_hyperpay_mada_status'];
        } else {
            $data['payment_hyperpay_mada_status'] = $this->config->get('payment_hyperpay_mada_status');
        }

        if (isset($this->request->post['payment_hyperpay_mada_base_currency'])) {
            $data['payment_hyperpay_mada_base_currency'] = $this->request->post['payment_hyperpay_mada_base_currency'];
        } else {
            $data['payment_hyperpay_mada_base_currency'] = $this->config->get('payment_hyperpay_mada_base_currency');
        }

        if (isset($this->request->post['payment_hyperpay_mada_sort_order'])) {
            $data['payment_hyperpay_mada_sort_order'] = $this->request->post['payment_hyperpay_mada_sort_order'];
        } elseif($this->config->get('payment_hyperpay_mada_sort_order')) {
            $data['payment_hyperpay_mada_sort_order'] = $this->config->get('payment_hyperpay_mada_sort_order');
        }else{
            $data['payment_hyperpay_mada_sort_order'] = 1;
        }

        if (isset($this->request->post['payment_hyperpay_mada_testmode'])) {
            $data['payment_hyperpay_mada_testmode'] = $this->request->post['payment_hyperpay_mada_testmode'];
        } else {
            $data['payment_hyperpay_mada_testmode'] = $this->config->get('payment_hyperpay_mada_testmode');
        }

        if (isset($this->request->post['payment_hyperpay_mada_trans_type'])) {
            $data['payment_hyperpay_mada_trans_type'] = $this->request->post['payment_hyperpay_mada_trans_type'];
        } else {
            $data['payment_hyperpay_mada_trans_type'] = $this->config->get('payment_hyperpay_mada_trans_type');
        }

        if (isset($this->request->post['payment_hyperpay_mada_trans_mode'])) {
            $data['payment_hyperpay_mada_trans_mode'] = $this->request->post['payment_hyperpay_mada_trans_mode'];
        } else {
            $data['payment_hyperpay_mada_trans_mode'] = $this->config->get('payment_hyperpay_mada_trans_mode');
        }

        if (isset($this->request->post['payment_hyperpay_mada_heading_title'])) {
            $data['payment_hyperpay_mada_heading_title'] = $this->request->post['payment_hyperpay_mada_heading_title'];
        } else {
            $data['payment_hyperpay_mada_heading_title'] = $this->config->get('payment_hyperpay_mada_heading_title');
        }

        if (isset($this->request->post['payment_hyperpay_mada_channel'])) {
            $data['payment_hyperpay_mada_channel'] = $this->request->post['payment_hyperpay_mada_channel'];
        } else {
            $data['payment_hyperpay_mada_channel'] = $this->config->get('payment_hyperpay_mada_channel');
        }

        if (isset($this->request->post['payment_hyperpay_mada_accesstoken'])) {
            $data['payment_hyperpay_mada_accesstoken'] = $this->request->post['payment_hyperpay_mada_accesstoken'];
        } else {
            $data['payment_hyperpay_mada_accesstoken'] = $this->config->get('payment_hyperpay_mada_accesstoken');
        }
        $data['payment_hyperpay_mada_connector'] = $this->config->get('payment_hyperpay_mada_connector');
        if (isset($this->request->post['payment_hyperpay_mada_connector'])) {
            $data['payment_hyperpay_mada_connector'] = $this->request->post['payment_hyperpay_mada_connector'];
        }
        if (isset($this->request->post['payment_hyperpay_mada_brands'])) {
            $data['payment_hyperpay_mada_brands'] = $this->request->post['payment_hyperpay_mada_brands'];
        } else {
            $data['payment_hyperpay_mada_brands'] = $this->config->get('payment_hyperpay_mada_brands');
        }

        if (isset($this->request->post['payment_hyperpay_mada_payment_style'])) {
            $data['payment_hyperpay_mada_payment_style'] = $this->request->post['payment_hyperpay_mada_payment_style'];
        } else {
            $data['payment_hyperpay_mada_payment_style'] = $this->config->get('payment_hyperpay_mada_payment_style');
        }

        if (isset($this->request->post['payment_hyperpay_mada_mailerrors'])) {
            $data['payment_hyperpay_mada_mailerrors'] = $this->request->post['payment_hyperpay_mada_mailerrors'];
        } else {
            $data['payment_hyperpay_mada_mailerrors'] = $this->config->get('payment_hyperpay_mada_mailerrors');
        }

        if (isset($this->request->post['payment_hyperpay_mada_mailerrors_enable'])) {
            $data['payment_hyperpay_mada_mailerrors_enable'] = $this->request->post['payment_hyperpay_mada_mailerrors_enable'];
        } else {
            $data['payment_hyperpay_mada_mailerrors_enable'] = $this->config->get('payment_hyperpay_mada_mailerrors_enable');
        }

        $data['payment_hyperpay_mada_admin_email'] = $this->config->get('config_email');

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_hyperpay_mada_order_status_id'])) {
            $data['payment_hyperpay_mada_order_status_id'] = $this->request->post['payment_hyperpay_mada_order_status_id'];
        } else {
            $data['payment_hyperpay_mada_order_status_id'] = $this->config->get('payment_hyperpay_mada_order_status_id');
        }

        if (isset($this->request->post['payment_hyperpay_mada_order_status_failed_id'])) {
            $data['payment_hyperpay_mada_order_status_failed_id'] = $this->request->post['payment_hyperpay_mada_order_status_failed_id'];
        } else {
            $data['payment_hyperpay_mada_order_status_failed_id'] = $this->config->get('payment_hyperpay_mada_order_status_failed_id');
        }

        if (isset($this->request->post['payment_hyperpay_mada_tokenization_status'])) {
            $data['payment_hyperpay_mada_tokenization_status'] = $this->request->post['payment_hyperpay_mada_tokenization_status'];
        } else {
            $data['payment_hyperpay_mada_tokenization_status'] = $this->config->get('payment_hyperpay_mada_tokenization_status');
        }

        $data['text_missing'] = $this->language->get('text_missing');

        // hard code the title of the plugin according to mada branding guidlines
        if($this->config->get('config_language') == 'ar'){
          $data['payment_hyperpay_mada_heading_title'] = 'بطاقة مدى البنكية';
        }else{
          $data['payment_hyperpay_mada_heading_title'] = 'mada card';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/hyperpay_mada', $data));
    }

    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/hyperpay_mada')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }


        if (!$this->request->post['payment_hyperpay_mada_heading_title']) {
            $this->error['heading_title'] = $this->language->get('error_heading_title');
        }

        if (!$this->request->post['payment_hyperpay_mada_channel']) {
            $this->error['channel'] = $this->language->get('error_channel');
        }

        if (!$this->request->post['payment_hyperpay_mada_accesstoken']) {
            $this->error['access'] = $this->language->get('error_access');
        }

        if (!$this->request->post['payment_hyperpay_mada_connector']) {
            $this->error['connector'] = $this->language->get('error_access');
        }


        if (!$this->error) {
            return TRUE;
        } else {
            return FALSE;
        }
    }


    private function get_hyperpay_mada_payment_methods()
    {
        $hyperpay_mada_payments = array(
            'MADA' => 'Mada'
        );

        return $hyperpay_mada_payments;
    }


    private function get_hyperpay_mada_trans_mode()
    {
        $hyperpay_mada_trans_mode = array(
            'CONNECTOR_TEST' => 'CONNECTOR_TEST',
            'INTEGRATOR_TEST' => 'INTEGRATOR_TEST',
            'LIVE' => 'LIVE'
        );

        return $hyperpay_mada_trans_mode;
    }

    private function get_hyperpay_mada_trans_type()
    {
        $hyperpay_mada_trans_type = array(
            'DB' => 'Debit',
            'PA' => 'Pre-Authorization'
        );

        return $hyperpay_mada_trans_type;
    }

    private function get_hyperpay_mada_payment_style()
    {
        $hyperpay_mada_payment_style = array(
            'card' => 'Card',
            'plain' => 'Plain'
        );

        return $hyperpay_mada_payment_style;
    }

    private function get_hyperpay_mada_connector()
    {
        $hyperpay_mada_connector = array(
            'visa' => 'VISA ACP',
            'migs' => 'MIGS / MPGS'
        );
        return $hyperpay_mada_connector;
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
