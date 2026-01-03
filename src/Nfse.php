<?php

namespace Reginaldoas\Nfse;

use Reginaldoas\Nfse\Common\Dps;
use Reginaldoas\Nfse\Common\Sender;
use Reginaldoas\Nfse\Common\Certificate;

class Nfse extends NfseAbstract implements NfseInterface
{

    public function inclusaoNfse()
    {
        $dps = new Dps($this->std);
        $content = $dps->render();

        $content = $this->cert->sign($content, 'infDPS', 'DPS');
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . $content;
        $gz = gzencode($content);
        $data = base64_encode($gz);
        file_put_contents(dirname(__DIR__) . "/certs/nfse.xml",$content);
        file_put_contents(dirname(__DIR__) . "/certs/arquivo.gzip",$gz);
        file_put_contents(dirname(__DIR__) . "/certs/encode.txt",$data);

        $data_encode = [
            'dpsXmlGZipB64' => $data
        ];
        $response = $this->sender->request('SefinNacional/nfse', json_encode($data_encode));
        return $response;
    }

    public function consultaDanfe()
    {
        $callback = 'danfse/' . $this->std->chave_acesso;
        $response = $this->sender->request($callback, null, "GET", 2);
        return $response;
    }

    public function consultaNfseChave()
    {
        $callback = 'SefinNacional/nfse/' . $this->std->chave_acesso;
        $response = $this->sender->request($callback, null, 'GET');
        return $response;
    }

    public function consultaDpsChave()
    {
        $callback = 'SefinNacional/dps/' . $this->std->key;
        $response = $this->sender->request($callback, null, 'GET');
        return $response;
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
        $response = $this->sender->request($callback, null, 'GET');
        return $response;
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
        $response = $this->sender->request($callback, json_encode($dados));
        return $response;
    }

    public function nfseFile(string $response)
    {
        $data_decode = base64_decode($response);
        $gz_decode = gzdecode($data_decode);
        return mb_convert_encoding($gz_decode, 'ISO-8859-1', 'UTF-8');
    }

}