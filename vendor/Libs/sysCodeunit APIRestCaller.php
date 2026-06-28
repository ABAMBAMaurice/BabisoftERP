<?php
    class APIRestCaller {
        private $baseUrl;
        private $headers;

        public function __construct($baseUrl, $headers = []) {
            $this->baseUrl = rtrim($baseUrl, '/');
            $this->headers = $headers;
        }

        public function get($endpoint, $params = []) {
            $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
            if (!empty($params)) {
                $url .= '/' . $params[0];
            }
            return $this->request('GET', $url);
        }

        public function post($endpoint, $data = []) {
            $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
            return $this->request('POST', $url, $data);
        }

        public function put($endpoint, $data = []) {
            $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
            return $this->request('PUT', $url, $data);
        }

        public function delete($endpoint, $data = []) {
            $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
            return $this->request('DELETE', $url, $data);
        }

        private function request($method, $url, $data = []) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

            $headers = [];
            foreach ($this->headers as $key => $value) {
                $headers[] = "$key: $value";
            }
            if (!empty($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }

            if (in_array($method, ['POST', 'PUT', 'DELETE']) && !empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new Exception("cURL error: $error");
            }

            curl_close($ch);

            return [
                'status' => $httpCode,
                'body' => json_decode($response, true)
            ];
        }
    }

?>