<?php

class ModelExtensionPaymentHyperpayTabby extends Model
{

    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/hyperpay');

        $method_data = array(
            'code'       => 'hyperpay_tabby',
            'terms'      => '',
            'title'      => $this->config->get('payment_hyperpay_tabby_heading_title'),
            'sort_order' => $this->config->get('payment_hyperpay_tabby_sort_order'),
            'icon' => HTTPS_SERVER . 'image/catalog/hyperpay/TABBY.svg'
        );

        return $method_data;
    }

    /**
     * collect everything Tabby needs from the order and the store as plain arrays,
     * so buildParams() stays free of OpenCart calls
     */
    public function gatherContext($order_id)
    {
        $this->load->model('checkout/order');
        $this->load->model('catalog/product');
        $this->load->model('catalog/category');
        $this->load->model('tool/image');

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if (!$order_info) {
            return array();
        }

        $image_width = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width') ?: 500;
        $image_height = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height') ?: 500;

        $products = array();

        foreach ($this->model_checkout_order->getOrderProducts($order_id) as $order_product) {
            $product_info = $this->model_catalog_product->getProduct($order_product['product_id']);

            $type = '';

            foreach ($this->model_catalog_product->getCategories($order_product['product_id']) as $product_category) {
                $category_info = $this->model_catalog_category->getCategory($product_category['category_id']);

                if ($category_info) {
                    $type = $category_info['name'];
                    break;
                }
            }

            $image = $product_info && $product_info['image'] ? $this->model_tool_image->resize($product_info['image'], $image_width, $image_height) : '';

            $products[] = array(
                'product_id'  => $order_product['product_id'],
                'name'        => $order_product['name'],
                'model'       => $order_product['model'],
                'quantity'    => $order_product['quantity'],
                'price'       => $order_product['price'],
                'sku'         => $product_info ? $product_info['sku'] : '',
                'description' => $product_info ? $product_info['description'] : '',
                'url'         => str_replace('&amp;', '&', $this->url->link('product/product', 'product_id=' . (int)$order_product['product_id'], true)),
                'image'       => str_replace('&amp;', '&', (string)$image),
                'type'        => $type,
            );
        }

        $totals = array();

        foreach ($this->model_checkout_order->getOrderTotals($order_id) as $order_total) {
            $totals[] = array(
                'code'  => $order_total['code'],
                'value' => $order_total['value'],
            );
        }

        $email = $this->db->escape($order_info['email']);
        $exclude = (int)$order_id;

        $history = $this->db->query("SELECT `total`, `order_status_id`, `payment_code`, `date_added` FROM `" . DB_PREFIX . "order` WHERE `email` = '" . $email . "' AND `order_id` != '" . $exclude . "' AND `order_status_id` > '0' ORDER BY `date_added` DESC LIMIT 10")->rows;

        $paid_statuses = array_merge((array)$this->config->get('config_processing_status'), (array)$this->config->get('config_complete_status'));
        $paid_statuses = array_map('intval', $paid_statuses) ?: array(0);

        $paid_where = " FROM `" . DB_PREFIX . "order` WHERE `email` = '" . $email . "' AND `order_id` != '" . $exclude . "' AND `order_status_id` IN (" . implode(',', $paid_statuses) . ")";

        $first_paid = $this->db->query("SELECT `date_added`" . $paid_where . " ORDER BY `date_added` ASC LIMIT 1")->row;
        $last_paid = $this->db->query("SELECT `date_added`" . $paid_where . " ORDER BY `date_added` DESC LIMIT 1")->row;

        $registered = '';

        if ($order_info['customer_id']) {
            $customer = $this->db->query("SELECT `date_added` FROM `" . DB_PREFIX . "customer` WHERE `customer_id` = '" . (int)$order_info['customer_id'] . "'")->row;
            $registered = $customer ? $customer['date_added'] : '';
        }

        return array(
            'order'      => $order_info,
            'products'   => $products,
            'totals'     => $totals,
            'history'    => $history,
            'first_paid' => $first_paid ? $first_paid['date_added'] : '',
            'last_paid'  => $last_paid ? $last_paid['date_added'] : '',
            'registered' => $registered,
            'statuses'   => array(
                'complete'   => array_map('intval', (array)$this->config->get('config_complete_status')),
                'processing' => array_map('intval', (array)$this->config->get('config_processing_status')),
                'new'        => (int)$this->config->get('config_order_status_id'),
            ),
            'test'       => (bool)$this->config->get('payment_hyperpay_tabby_testmode'),
        );
    }

    /**
     * Tabby specific payment params, $convert must be the same currency conversion used for the amount
     */
    public function buildParams(array $ctx, $convert)
    {
        $order = $ctx['order'];

        $money = function ($value) use ($convert) {
            return number_format((float)call_user_func($convert, (float)$value), 2, '.', '');
        };

        $shipping = $this->sumTotals($ctx['totals'], array('shipping'));
        $tax = $this->sumTotals($ctx['totals'], array('tax'));
        $discount = abs($this->sumTotals($ctx['totals'], array('coupon', 'voucher', 'reward', 'credit', 'discount'), true));

        $has_shipping_address = !empty($order['shipping_address_1']) || !empty($order['shipping_city']);
        $address = $has_shipping_address ? 'shipping_' : 'payment_';

        $params = array(
            'customer.email'                  => $this->text($order['email']),
            'customer.givenName'              => $this->text($order['payment_firstname'] ?: $order['firstname']),
            'customer.surname'                => $this->text($order['payment_lastname'] ?: $order['lastname']),
            'customer.mobile'                 => $this->text($order['telephone']),
            'billing.street1'                 => $this->text($order['payment_address_1']),
            'billing.street2'                 => $this->text($order['payment_address_2']),
            'billing.city'                    => $this->text($order['payment_city']),
            'billing.state'                   => $this->text($order['payment_zone'] ?: $order['payment_city']),
            'billing.country'                 => $this->text($order['payment_iso_code_2']),
            'billing.postcode'                => $this->text($order['payment_postcode']),
            'shipping.street1'                => $this->text($order[$address . 'address_1']),
            'shipping.street2'                => $this->text($order[$address . 'address_2']),
            'shipping.city'                   => $this->text($order[$address . 'city']),
            'shipping.state'                  => $this->text($order[$address . 'zone'] ?: $order[$address . 'city']),
            'shipping.country'                => $this->text($order[$address . 'iso_code_2']),
            'shipping.postcode'               => $this->text($order[$address . 'postcode']),
            'shipping.cost'                   => $money($shipping),
            'taxAmount'                       => $money($tax),
            'locale'                          => substr((string)$order['language_code'], 0, 2),
            'customParameters[description]'   => 'Order #' . $order['order_id'],
            'customParameters[discount_amount]' => $money($discount),
            'customParameters[bill_number]'    => (string)$order['order_id'],
            'customParameters[plugin]'         => 'opencart',
        );

        if (!empty($ctx['test'])) {
            $params['customer.email'] = 'card.success@tabby.ai';
            $params['customer.mobile'] = '0500000001';
        }

        $subtotal = 0;

        foreach ($ctx['products'] as $product) {
            $subtotal += (float)$product['price'] * (int)$product['quantity'];
        }

        $line_discount = $subtotal > 0 ? min(max($discount / $subtotal * 100, 0), 100) : 0;

        foreach (array_values($ctx['products']) as $index => $product) {
            $description = $this->text(strip_tags($this->text($product['description'])));
            $description = $description !== '' ? $description : $this->text($product['name']);

            $sku = (string)$product['sku'] !== '' ? $product['sku'] : ((string)$product['model'] !== '' ? $product['model'] : $product['product_id']);

            $params["cart.items[$index].name"] = $this->text($product['name']);
            $params["cart.items[$index].price"] = $money($product['price']);
            $params["cart.items[$index].quantity"] = (string)max(1, (int)$product['quantity']);
            $params["cart.items[$index].discount"] = number_format($line_discount, 2, '.', '');
            $params["cart.items[$index].sku"] = $this->text($sku);
            $params["cart.items[$index].description"] = trim($this->limit($description, 255));
            $params["cart.items[$index].productUrl"] = (string)$product['url'];
            $params["cart.items[$index].imageUrl"] = (string)$product['image'];
            $params["cart.items[$index].type"] = $this->text($product['type']) !== '' ? $this->text($product['type']) : 'general';
        }

        $params = array_filter($params, function ($value) {
            return $value !== '';
        });

        return array_merge($params, $this->buyerHistory($ctx, $money), $this->attachment($ctx));
    }

    private function buyerHistory(array $ctx, $money)
    {
        $order = $ctx['order'];
        $registered = $ctx['registered'] !== '' ? $ctx['registered'] : $order['date_added'];

        $order_history = array();

        foreach ($ctx['history'] as $previous) {
            $order_history[] = array(
                'purchased_at'   => $this->isoDate($previous['date_added']),
                'amount'         => $money($previous['total']),
                'status'         => $this->mapOrderStatus((int)$previous['order_status_id'], $ctx['statuses']),
                'payment_method' => $previous['payment_code'] === 'cod' ? 'cod' : 'card',
            );
        }

        $completed_count = count(array_filter($order_history, function ($history) {
            return $history['status'] === 'complete';
        }));

        $buyer_history = array(
            'customParameters[buyer_history_registered_since]'             => $this->isoDate($registered),
            'customParameters[buyer_history_loyalty_level]'                => (string)$completed_count,
            'customParameters[buyer_history_wishlist_count]'               => '0',
            'customParameters[buyer_history_is_social_networks_connected]' => '0',
            'customParameters[buyer_history_is_phone_number_verified]'     => '0',
            'customParameters[buyer_history_is_email_verified]'            => '0',
        );

        // HyperPay rejects an empty order_history, so first-time buyers omit it
        if ($order_history) {
            $buyer_history['customParameters[order_history]'] = json_encode($order_history);
        }

        return $buyer_history;
    }

    private function attachment(array $ctx)
    {
        $order = $ctx['order'];

        $history = array(
            'unique_account_identifier' => $order['customer_id'] ? (string)$order['customer_id'] : (string)$order['email'],
            'paid_before_flag'          => $ctx['first_paid'] !== '',
        );

        if ($ctx['first_paid'] !== '') {
            $history['date_of_first_paid_purchase'] = gmdate('Y-m-d', strtotime($ctx['first_paid']));
            $history['date_of_last_paid_purchase'] = gmdate('Y-m-d', strtotime($ctx['last_paid']));
        }

        return array(
            'customParameters[attachment]' => json_encode(array(
                'body'         => array('payment_history_simple' => $history),
                'content_type' => 'application/vnd.tabby.v1+json',
            ), JSON_UNESCAPED_SLASHES),
        );
    }

    private function mapOrderStatus($status_id, array $statuses)
    {
        if (in_array($status_id, $statuses['complete'], true)) {
            return 'complete';
        }

        if (in_array($status_id, $statuses['processing'], true)) {
            return 'processing';
        }

        if ($status_id === $statuses['new']) {
            return 'new';
        }

        // OpenCart default order statuses: 11 Refunded, 12 Reversed, 7 Canceled, 8 Denied, 10 Failed, 14 Expired, 16 Voided
        if (in_array($status_id, array(11, 12), true)) {
            return 'refunded';
        }

        if (in_array($status_id, array(7, 8, 10, 14, 16), true)) {
            return 'canceled';
        }

        return 'unknown';
    }

    private function sumTotals(array $totals, array $codes, $negative_only = false)
    {
        $sum = 0;

        foreach ($totals as $total) {
            if (in_array($total['code'], $codes, true) && (!$negative_only || (float)$total['value'] < 0)) {
                $sum += (float)$total['value'];
            }
        }

        return $sum;
    }

    private function isoDate($date)
    {
        return gmdate('Y-m-d\TH:i:s\Z', strtotime($date));
    }

    private function text($value)
    {
        return trim(html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8'));
    }

    private function limit($value, $length)
    {
        return function_exists('mb_substr') ? mb_substr($value, 0, $length, 'UTF-8') : substr($value, 0, $length);
    }
}
