<?php

namespace MyLifeServer\core\model;

class HttpRequester
{
    private function curl_request(string $url, string $method, array $body = null, array $header = null): string
    {
        $curl = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 5, // TODO: 타임 아웃 변경 가능!
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        if ($body) {
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
        }
        if ($header) {
            $options[CURLOPT_HTTPHEADER] = $header;
        }
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function get_request(string $url): string
    {
        return $this->curl_request($url, 'GET');
    }

    public function post_request(string $url, array $body, array $header = ['Content-Type: application/json']): string
    {
        return $this->curl_request($url, 'POST', $body, $header);
    }

    public function put_request(string $url, array $body, array $header = ['Content-Type: application/json']): string
    {
        return $this->curl_request($url, 'PUT', $body, $header);
    }

    public function delete_request(string $url, array $body, array $header = ['Content-Type: application/json']): string
    {
        return $this->curl_request($url, 'DELETE', $body, $header);
    }
}
