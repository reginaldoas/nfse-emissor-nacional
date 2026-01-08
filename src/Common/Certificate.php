<?php

namespace Reginaldoas\Nfse\Common;

use Reginaldoas\Nfse\Common\Exception\Exception;
use DOMDocument;
use DOMNode;
use DOMElement;
use stdClass;

class Certificate implements CertificateInterface{

    /**
     * @var string
     */
    private string $certificate = "";
    /**
     * @var string
     */
    private string $pass = "";
    /**
     * @var string
     */
    private $_pkey = null;
    /**
     * @var string
     */
    private $_cert = null;
    /**
     * @var string
     */
    private $_cert_pem = null;
    /**
     * @var string
     */
    private $_chain = null;
    /**
     * @var stdClass
     */
    private $_data_cert;
    /**
     * @var string
     */
    private $_resource = null;
    /**
     * @var string
     */
    private $_x509_certificate = null;
    /**
     * @var string
     */
    private $_certs_path = null;
    
    public function __construct($certificate,$pass,$path = "certs"){
        $this->certificate = $certificate;
        $this->pass = $pass;

        $path = dirname(dirname(__DIR__)) . '/' . $path . '/';
        $this->_certs_path = $path;
        $this->createFolder($path);
        $this->readPfx();
    }

    public function getDataCert(){
        return $this->_data_cert;
    }

    public function getPathCert(){
        return $this->_certs_path;
    }

    public function getPass(){
        return $this->pass;
    }

    private function readPfx(){

        $certs = array();
        if (!openssl_pkcs12_read($this->certificate, $certs, $this->pass)) {
            throw Exception::unableToRead();
        }
        $chain = '';
        if (!empty($certs['extracerts'])) {
            foreach ($certs['extracerts'] as $ec) {
                $chain .= $ec;
            }
        }
        
        $this->_pkey = $certs['pkey']; 
        $this->_cert = $certs['cert'];
        $this->_chain = $chain;
        
        $this->setX509Certificate();

        if (!$resource = openssl_pkey_get_private($this->_pkey)) {
            throw Exception::unablePrivateKey();
        }

        $this->read();

        if(!file_put_contents($this->_certs_path . "cert.pfx", $this->certificate)){
            throw Exception::unableCreateFile();
        }

        $this->convertCertToPem();

        $this->_resource = $resource;
        
    }

    private function setX509Certificate(){
        
        $ret = preg_replace('/-----.*[\n]?/', '', $this->_cert);
        $this->_x509_certificate = preg_replace('/[\n\r]/', '', $ret);

    }

    protected function read(){
        
        if (!$resource = openssl_x509_read($this->_cert)) {
            throw Exception::unableToRead();
        }
        $detail = openssl_x509_parse($resource, false);
        
        $this->_data_cert = new stdClass();

        $this->_data_cert->common_name = $detail['subject']['commonName'];
        if (isset($detail['subject']['emailAddress'])) {
            $this->_data_cert->email_address = $detail['subject']['emailAddress'];
        }
        if (isset($detail['issuer']['organizationalUnitName'])) {
            $this->_data_cert->csp_nname = is_array($detail['issuer']['organizationalUnitName']) ? implode(', ', $detail['issuer']['organizationalUnitName']) : $detail['issuer']['organizationalUnitName'];
        }
        $this->_data_cert->serial_number = $detail['serialNumber'];
        $dt_from = \DateTime::createFromFormat('ymdHis\Z', $detail['validFrom']);
        $this->_data_cert->valid_from = $dt_from->format('Y-m-d H:i:s');
        $dt_to = \DateTime::createFromFormat('ymdHis\Z', $detail['validTo']);
        $this->_data_cert->valid_to = $dt_to->format('Y-m-d H:i:s');
        if (isset($detail['name'])) {
            $arrayName = explode("/", $detail["name"]);
            $arrayName = array_reverse($arrayName);
            $arrayName = array_filter($arrayName);
            $name = implode(",", $arrayName);
            $this->_data_cert->subject_name_value = $name;
        }
    }

    public function isExpired(){
        return strtotime($this->_data_cert->valid_to) < strtotime(date('Y-m-d')) ? true : false;
    }

    private function signSignature($content,$algorithm){
        $encryptedData = '';
        if (!openssl_sign($content, $encryptedData, $this->_resource, $algorithm)) {
            throw Exception::unableSignature();
        }
        return $encryptedData;
    }

    public function convertCertToPem(){

        $certs = array();
        if (!openssl_pkcs12_read($this->certificate, $certs, $this->pass)) {
            throw Exception::unableToRead();
        }
        $chain = '';
        if (!empty($certs['extracerts'])) {
            foreach ($certs['extracerts'] as $ec) {
                $chain .= $ec;
            }
        }
        
        $this->_pkey = $certs['pkey']; 
        $this->_cert = $certs['cert'];
        $this->_chain = $chain;
        // this is the PEM FILE
        // We read the data provided by `openssl_pkcs12_read` and assemble the content of the PEM certificate.
        $contentPemCertificate = $certs["pkey"];
        $contentPemCertificate .= $certs["cert"];
        $contentPemCertificate .= ($certs["extracerts"][1] ?? "");
        $contentPemCertificate .= ($certs["extracerts"][0] ?? "");

        if(!file_put_contents($this->_certs_path . "cert.pem", $contentPemCertificate)){
            throw Exception::unableCreateFile();
        }

    }

    public function sign($xml,$tag_digest,$tag_name){
        
        $digestAlgorithm = 'sha1';

        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = true;

        #digest_value
        $dom->loadXML($xml);
        $node = $dom->getElementsByTagName($tag_digest)->item(0);
        $c14n = $node->C14N(false,false,null,null);
        $hashValue = hash($digestAlgorithm, $c14n, true);
        $digest_value = base64_encode($hashValue);
        
        #signature
        $nsDSIG = 'http://www.w3.org/2000/09/xmldsig#';
        $nsCannonMethod = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315'; //http://www.w3.org/TR/2001/REC-xml-c14n-20010315
        $mark = "Id";

        if ($digestAlgorithm == 'sha256') {
            $algorithm = OPENSSL_ALGO_SHA256;
            $nsSignatureMethod = 'http://www.w3.org/2001/04/xmldsig#rsa-sha256';
            $nsDigestMethod = 'http://www.w3.org/2000/09/xmldsig#sha256';
        }elseif ($digestAlgorithm == 'sha1') {
            $algorithm = OPENSSL_ALGO_SHA1;
            $nsSignatureMethod = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
            $nsDigestMethod = 'http://www.w3.org/2000/09/xmldsig#sha1';
        }

        $idSigned = trim($node->getAttribute($mark));
        $idSigned = !empty($idSigned) ? "#$idSigned" : "";

        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = true;
        $dom->loadXML($xml);

        
        $nsTransformMethod1 ='http://www.w3.org/2000/09/xmldsig#enveloped-signature';
        $nsTransformMethod2 = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315'; //http://www.w3.org/TR/2001/REC-xml-c14n-20010315
        
        $signatureNode = $dom->createElement('Signature');
        
        //insert signature in document
        $head = $dom->getElementsByTagName($tag_name)->item(0);

        if ($head->hasChildNodes()) {
            $head->insertBefore($signatureNode,$head->nextSibling);
        } else {
            $head->appendChild($signatureNode);
        }

        $signatureNode->setAttribute('xmlns', $nsDSIG);

        $signedInfoNode = $dom->createElement('SignedInfo');
        $signatureNode->appendChild($signedInfoNode);

        $canonicalNode = $dom->createElement('CanonicalizationMethod');
        $signedInfoNode->appendChild($canonicalNode);
        $canonicalNode->setAttribute('Algorithm', $nsCannonMethod);
        $signatureMethodNode = $dom->createElement('SignatureMethod');
        $signedInfoNode->appendChild($signatureMethodNode);
        $signatureMethodNode->setAttribute('Algorithm', $nsSignatureMethod);
        $referenceNode = $dom->createElement('Reference');
        $signedInfoNode->appendChild($referenceNode);
        
        $referenceNode->setAttribute('URI', $idSigned);
        $transformsNode = $dom->createElement('Transforms');
        $referenceNode->appendChild($transformsNode);
        $transfNode1 = $dom->createElement('Transform');
        $transformsNode->appendChild($transfNode1);
        $transfNode1->setAttribute('Algorithm', $nsTransformMethod1);
        $transfNode2 = $dom->createElement('Transform');
        $transformsNode->appendChild($transfNode2);
        $transfNode2->setAttribute('Algorithm', $nsTransformMethod2);
        $digestMethodNode = $dom->createElement('DigestMethod');
        $referenceNode->appendChild($digestMethodNode);
        $digestMethodNode->setAttribute('Algorithm', $nsDigestMethod);
        $digestValueNode = $dom->createElement('DigestValue', $digest_value);
        $referenceNode->appendChild($digestValueNode);
        
        $c14n = $signedInfoNode->C14N(false,false,null,null);
        $signature = $this->signSignature($c14n, $algorithm);
        $signatureValue = base64_encode($signature);
        $signatureValueNode = $dom->createElement('SignatureValue', $signatureValue);
        $signatureNode->appendChild($signatureValueNode);
        $keyInfoNode = $dom->createElement('KeyInfo');
        $signatureNode->appendChild($keyInfoNode);
        $x509DataNode = $dom->createElement('X509Data');
        $keyInfoNode->appendChild($x509DataNode);
        $x509CertificateNode = $dom->createElement('X509Certificate', $this->_x509_certificate);
        $x509DataNode->appendChild($x509CertificateNode);
        
        return $dom->saveXML($dom->documentElement);
        
    }

    private function createFolder($folder){
        if (!empty($folder)) {
            if (!is_dir($folder)) {
                if (!mkdir($folder, 0777, true) && !is_dir($folder)) {
                    throw Exception::unableFolder();
                }
            }
        }
    }

}