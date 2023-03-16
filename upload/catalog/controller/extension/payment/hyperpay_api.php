<?php
class ControllerExtensionPaymentHyperpayApi extends Controller
{

	public function index()
	{

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			return $this->response->setOutput("Method Not Allowed");
		}

		$method = $_GET['method'];
		$classname = "model_extension_payment_{$method}";

		$this->load->model('extension/payment/hyperpay_zoodpay');
		$this->load->model('checkout/order');

		if (!($this->$classname)) {
			http_response_code(404);
			return $this->response->setOutput("Class Not Found !");
		}

		$config = $this->$classname->get_config();


		$secret = $config->get("payment_{$method}_webhook_key");
		$initialization_vector = getallheaders()['X-Initialization-Vector'];
		$authentication_tag = getallheaders()['X-Authentication-Tag'];

		$key = hex2bin($secret);
		$iv = hex2bin($initialization_vector);
		$auth_tag = hex2bin($authentication_tag);
		$cipher_text = hex2bin(file_get_contents('php://input'));

		$result = openssl_decrypt($cipher_text, "aes-256-gcm", $key, OPENSSL_RAW_DATA, $iv, $auth_tag);
		$result = json_decode($result, true);


		if ($result['type'] == 'test') {
			http_response_code(200);
			return $this->response->setOutput("success");
		}

		if (!$result || !in_array($result['payload']['result']['code'], ['100.396.103', '000.000.000'])) {
			http_response_code(200);
			return $this->response->setOutput("you are not allowes to do that.");
		}

		$order_id =  $result['payload']['merchantTransactionId'];

		$order = $this->model_checkout_order->getOrder($order_id);

		if (!$order) {
			http_response_code(404);
			return $this->response->setOutput("order not found");
		}


		if ($order["payment_code"] == 'hyperpay_zoodpay' && $result['payload']['result']['code'] == '100.396.103') {
			$this->model_checkout_order->addOrderHistory($order_id, 1, "updated", TRUE);
			return $this->response->setOutput("updated");
		}

		if (!in_array($order['order_status_id'], ['0', "1"]) || $order["payment_code"] != $method) {
			http_response_code(401);
			return $this->response->setOutput("Sorry, you are not allowed to do that.");
		}

		$this->model_checkout_order->addOrderHistory($order_id, $config->get("payment_{$method}_order_status_id"), "Trans Unique ID: " . $result['payload']['id'], TRUE);

		$this->response->addHeader('Content-Type: application/json');
		return $this->response->setOutput("success");

	}

}
