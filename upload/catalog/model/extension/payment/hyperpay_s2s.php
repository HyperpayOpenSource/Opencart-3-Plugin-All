<?php

class ModelExtensionPaymentHyperpayS2s extends Model
{
    const CONNECT_TIMEOUT = 15;

    const TIMEOUT = 60;

    public function baseUrl($test)
    {
        return $test ? 'https://eu-test.oppwa.com' : 'https://eu-prod.oppwa.com';
    }

    public function post($url, $token, array $params)
    {
        return $this->request($url, $token, http_build_query($params, '', '&'));
    }

    public function get($url, $token)
    {
        return $this->request($url, $token, null);
    }

    public function isSuccess($code)
    {
        return preg_match('/^(000\.000\.|000\.100\.1|000\.[36])/', (string)$code) === 1
            || preg_match('/^(000\.400\.0|000\.400\.100)/', (string)$code) === 1;
    }

    public function isPending($code)
    {
        return preg_match('/^(000\.200|800\.400\.5|100\.400\.500)/', (string)$code) === 1;
    }

    public function errorMessage(array $response)
    {
        $message = isset($response['result']['description']) ? (string)$response['result']['description'] : '';
        $details = array();

        if (isset($response['resultDetails']['ExtendedDescription'])) {
            $extended = $this->decodeExtended((string)$response['resultDetails']['ExtendedDescription']);

            if (isset($extended['details']) && is_array($extended['details'])) {
                foreach ($extended['details'] as $detail) {
                    if (!is_array($detail) || !isset($detail['error'])) {
                        continue;
                    }

                    $details[] = isset($detail['field']) && $detail['field'] !== ''
                        ? $detail['field'] . ': ' . $detail['error']
                        : (string)$detail['error'];
                }
            } elseif (isset($extended['message'])) {
                $details[] = (string)$extended['message'];
            }
        }

        if ($details) {
            $message = trim($message . ' ' . implode('; ', $details));
        }

        return $message;
    }

    public function redirectFields(array $redirect)
    {
        $fields = array();

        if (isset($redirect['parameters']) && is_array($redirect['parameters'])) {
            foreach ($redirect['parameters'] as $parameter) {
                if (isset($parameter['name'])) {
                    $fields[] = array(
                        'name'  => (string)$parameter['name'],
                        'value' => isset($parameter['value']) ? (string)$parameter['value'] : ''
                    );
                }
            }
        }

        $query = isset($redirect['url']) ? parse_url($redirect['url'], PHP_URL_QUERY) : '';

        if ($query) {
            foreach (explode('&', $query) as $pair) {
                if ($pair === '') {
                    continue;
                }

                $parts = explode('=', $pair, 2);

                $fields[] = array(
                    'name'  => urldecode($parts[0]),
                    'value' => isset($parts[1]) ? urldecode($parts[1]) : ''
                );
            }
        }

        return $fields;
    }

    public function baseParams(array $cfg, $orderId, $amount, $currency, $brand, $returnUrl)
    {
        $params = array(
            'entityId'                    => trim((string)$cfg['channel']),
            'amount'                      => number_format((float)$amount, 2, '.', ''),
            'currency'                    => $currency,
            'paymentType'                 => !empty($cfg['trans_type']) ? $cfg['trans_type'] : 'DB',
            'merchantTransactionId'       => $orderId . 'I' . mt_rand(100000, 999999),
            'paymentBrand'                => $brand,
            'shopperResultUrl'            => $returnUrl,
            'customParameters[branch_id]' => '1',
            'customParameters[teller_id]' => '1',
            'customParameters[device_id]' => '1',
        );

        if (!empty($cfg['test'])) {
            $params['testMode'] = 'EXTERNAL';
        }

        return $params;
    }

    private function decodeExtended($extended)
    {
        $decoded = json_decode($extended, true);

        if (!is_array($decoded)) {
            $decoded = json_decode(urldecode($extended), true);
        }

        return is_array($decoded) ? $decoded : array();
    }

    private function request($url, $token, $body)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $token
        ));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $responseData = curl_exec($ch);
        $error = curl_errno($ch) ? curl_error($ch) : '';
        curl_close($ch);

        if ($error !== '') {
            return array('result' => array('code' => '', 'description' => 'Connection error: ' . $error));
        }

        $response = json_decode((string)$responseData, true);

        if (!is_array($response)) {
            return array('result' => array('code' => '', 'description' => 'Invalid gateway response'));
        }

        return $response;
    }
}
