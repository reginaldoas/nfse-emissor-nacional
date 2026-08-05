<?php

namespace Reginaldoas\Nfse;

use Reginaldoas\Nfse\Common\Dps;
use Reginaldoas\Nfse\Common\Danfe;
use Reginaldoas\Nfse\Common\Sender;
use Reginaldoas\Nfse\Common\Certificate;

class Nfse extends NfseAbstract implements NfseInterface
{

    public function render(){
        $dps = new Dps($this->std);
        $content = $dps->render();
        $this->dps_id = $dps->getDpsId();
        return $content;
    }

    public function getDpsId(){
        return $this->dps_id;
    }

    public function renderEvento(){
        $dps = new Dps($this->std);
        $content = $dps->renderEvento();
        $this->event_id = $dps->getEventoId();
        return $content;
    }

    public function getEventoId(){
        return $this->event_id;
    }

    public function inclusaoNfse()
    {
        $dps = new Dps($this->std);
        $content = $dps->render();

        $content = $this->cert->sign($content, 'infDPS', 'DPS');
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . $content;
        $gz = gzencode($content);
        $data = base64_encode($gz);

        $data_encode = [
            'dpsXmlGZipB64' => $data
        ];
        $response = $this->sender->request('SefinNacional/nfse', json_encode($data_encode));
        return $response;
    }

    /*
    * Consulta o Danfe da NFSe
    * Serviço inativo desde 01/06/2026
    * @return string
    */
    public function consultaDanfe()
    {
        $callback = 'danfse/' . $this->std->chave_acesso;

        $verify_status = $this->sender->verifyStatus($callback);
        if($verify_status){
            // service danfse active
            $response = $this->sender->request($callback, null, "GET", 2);
            return $response;
        }else{
            // using scraping
            $response = $this->sender->scraptDanfe($this->std->chave_acesso);
            return $response;
        }

    }

    /*
    * Renderiza o Danfe da NFSe localmente (sem API).
    * Informe o XML autorizado em $std->xml.
    * @return string Conteúdo binário do PDF
    */
    public function renderDanfe(?string $logo = null)
    {
        $xml = $this->std->xml ?? null;
        if (empty($xml)) {
            throw new \Exception('Informe o XML da NFS-e em $std->xml para renderizar o DANFSe.');
        }
        $danfe = new Danfe($xml);
        return $danfe->render($logo);
    }

    /*
    * Consulta a NFSe pela chave de acesso
    * @return string
    */
    public function consultaNfseChave()
    {
        $callback = 'SefinNacional/nfse/' . $this->std->chave_acesso;
        return $this->sender->request($callback, null, 'GET');
    }

    public function consultaDpsChave()
    {
        $callback = 'SefinNacional/dps/' . $this->std->key;
        return $this->sender->request($callback, null, 'GET');
    }

    public function consultaNfseEventos()
    {
        $callback = 'SefinNacional/nfse/' . $this->std->chave_acesso . '/eventos';
        if ($this->std->event_type) {
            $callback .= '/' . $this->std->event_type;
        }
        if ($this->std->nsequence) {
            $callback .= '/' . $this->std->nsequence;
        }
        return $this->sender->request($callback, null, 'GET'); 
    }

    public function cancelarNfse()
    {
        $dps = new Dps($this->std);
        $content = $dps->renderEvento();
        
        $content = $this->cert->sign($content, 'infPedReg', 'pedRegEvento');
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . $content;
        $gz = gzencode($content);
        $data = base64_encode($gz);
        $dados = [
            'pedidoRegistroEventoXmlGZipB64' => $data
        ];

        $callback = 'SefinNacional/nfse/' . $this->std->infPedReg->chNFSe . '/eventos';
        return $this->sender->request($callback, json_encode($dados));
    }

    public function nfseFile(string $response)
    {
        $data_decode = base64_decode($response);
        $gz_decode = gzdecode($data_decode);
        return mb_convert_encoding($gz_decode, 'ISO-8859-1', 'UTF-8');
    }

}