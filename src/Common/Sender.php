<?php

namespace Reginaldoas\Nfse\Common;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Reginaldoas\Nfse\Common\Certificate;
use Reginaldoas\Nfse\Common\Exception\Exception;

class Sender{

    const URL_SEFIN_HOMOLOGACAO = 'https://sefin.producaorestrita.nfse.gov.br/';
    const URL_SEFIN_PRODUCAO = 'https://sefin.nfse.gov.br/';
    const URL_ADN_HOMOLOGACAO = 'https://adn.producaorestrita.nfse.gov.br/';
    const URL_ADN_PRODUCAO = 'https://adn.nfse.gov.br/';
    private string $url_api;
    /**
     * @var Certificate
     */
    public $cert;
    /**
     * @var boolean
     */
    public $production = false;

    public function __construct($production, Certificate $cert){
        $this->production = $production;
        $this->cert = $cert;
    }

    public function request($callback, $data, $method = 'POST',$origin = 1)
    {

        $this->resolveUrl($origin);
        $url_api = $this->url_api . $callback;

        $msgSize = $data ? strlen($data) : 0;

        $headers = array(
            "Content-Type: application/json",
            "Content-length: " . $msgSize
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_api);
        curl_setopt($ch, CURLOPT_REFERER, $url_api);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if($method == 'POST'){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //certificate
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, "P12");
        curl_setopt($ch, CURLOPT_SSLCERT, $this->cert->getPathCert() . 'cert.pfx');
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->cert->getPass());
        
        #Envia a requisição e verifica o retorno
        $return = true;
        if (($result = curl_exec($ch)) === FALSE) {
            $return = false;
            $data_return = ['error' => true, 'result' => curl_error($ch),'pdf' => false];
        }else{
            $headsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            // $body = trim(substr($result, $headsize));
            $data_body = $contentType == 'application/pdf' ? $result : json_decode($result, true);
            $pdf = $contentType == 'application/pdf' ? true : false;
            $data_return = ['error' => false, 'result' => $data_body,'pdf' => $pdf];
        }
        
        curl_close($ch);

        return $data_return;

    }

    /*
    public function request($callback, $data, Certificate $cert, $origin = 1, $method = "POST", $debug = false)
    {

        $this->resolveUrl($origin);

        $msgSize = $data ? strlen($data) : 0;

        $options = array();
        $options['debug'] = $debug;
        $options['verify'] = false;
        $options['timeout'] = 300;
        $options['connect_timeout'] = 300;
        $options['allow_redirects'] = false;
        $options['http_errors'] = true;
        $options['headers'] = [
            'Content-Type' => 'application/json',
            'Content-length: ' . $msgSize,
        ];
        if(!empty($data)){
            $options['json'] = $data;
        }

        $client = new Client([
            'base_uri' => $this->url_api,
            'cert' => [$cert->getPathCert() . 'cert.pem', $cert->getPass()]
        ]);
        $promise = $client->requestAsync($method, $callback, $options);
        $promise->then(function (ResponseInterface $response) {
            $contentType = $response->getHeaderLine('Content-Type');
            $body = $contentType == "application/pdf" ? $response->getBody() : json_decode($response->getBody(),true);
            return ['error' => false, 'body' => $body];
        });

        try{
            return $promise->wait();
        }catch(RequestException $e){
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            return ['error' => true, 'body' => $responseBodyAsString];
        }

    }
    */

    private function resolveUrl(int $origin = 0)
    {
        switch ($origin) {
            case 1: // SEFIN
            default:
                $this->url_api = $this->production ? self::URL_SEFIN_PRODUCAO : self::URL_SEFIN_HOMOLOGACAO;
                break;
            case 2: // ADN
                $this->url_api = $this->production ? self::URL_ADN_PRODUCAO : self::URL_ADN_HOMOLOGACAO;
                break;
        }

    }

}