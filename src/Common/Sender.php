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

    public function verifyStatus($callback, $origin = 2) {

        $this->resolveUrl($origin);
        $url_api = $this->url_api . $callback;

        $ch = curl_init($url_api);
        
        curl_setopt($ch, CURLOPT_NOBODY, true); // Only headers
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        //certificate
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, "P12");
        curl_setopt($ch, CURLOPT_SSLCERT, $this->cert->getPathCert() . 'cert.pfx');
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->cert->getPass());
        curl_exec($ch);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode >= 200 && $httpCode < 300);
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
        if (($result = curl_exec($ch)) === FALSE) {
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

    private function scrapAuth()
    {

        $url_api = "https://www.nfse.gov.br/EmissorNacional/Certificado";

        $path = dirname(__DIR__).'/TESTES/novo_certificado.pfx';

        // $fp = fopen(dirname(__FILE__).'/errorlog.txt', 'w');

        $headers = array(
            "User-Agent: Sistema/v1.0"
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        // curl_setopt($ch, CURLOPT_VERBOSE, true);
        // curl_setopt($ch, CURLOPT_STDERR, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        //certificate
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, "P12");
        curl_setopt($ch, CURLOPT_SSLCERT, $this->cert->getPathCert() . 'cert.pfx');
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->cert->getPass());

        $result = curl_exec($ch);

        $headsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($result, 0, $headsize);
        $body = substr($result, $headsize);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $new_cookie = '';
        if($httpCode == 200 && !empty($header)){
            // Extract Cookies
            preg_match_all('/Set-Cookie:\s*([^;]*)/mi', $header, $matches);
            
            $list_cookies = [];
            foreach($matches[1] as $item) {
                $list_cookies[] = $item;
            }
            if(count($list_cookies) > 0){
                $new_cookie = implode("; ",$list_cookies);
            }

        }

        return $new_cookie;

    }

    private function scrapFile($chave_acesso,$cookie)
    {

        $url_api = "https://www.nfse.gov.br/EmissorNacional/Notas/Download/DANFSe/" . $chave_acesso;

        $headers = array(
            "Content-Type: application/json",
            "Cookie: {$cookie}"
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_api);
        curl_setopt($ch, CURLOPT_REFERER, $url_api);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (($result = curl_exec($ch)) === FALSE) {
            $data_return = '';
            $data_return = ['error' => true, 'result' => curl_error($ch),'pdf' => false];
        }else{
            $headsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $data_body = $contentType == 'application/pdf' ? $result : '';
            $pdf = $contentType == 'application/pdf' ? true : false;
            $data_return = ['error' => false, 'result' => $data_body,'pdf' => $pdf];
        }

        curl_close($ch);

        return $data_return;

    }

    public function scraptDanfe($chave_acesso)
    {
        $cookie = $this->scrapAuth();
        $file_content = '';
        if(!empty($cookie)){
            $file_content = $this->scrapFile($chave_acesso, $cookie);
        }
        return $file_content;
    }
    
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