<?php

namespace Reginaldoas\Nfse\Common;

use Exception;
use Reginaldoas\Nfse\Common\Pdf\Danfse;
use stdClass;

abstract class DanfeAbstract
{
    /**
     * XML autorizado da NFS-e (infNFSe).
     * @var string
     */
    protected $xml;

    /**
     * @var stdClass|null
     */
    public $std;

    /**
     * @param string|stdClass|null $xmlOrStd XML da NFS-e ou stdClass com propriedade xml
     * @throws Exception
     */
    public function __construct($xmlOrStd = null)
    {
        if ($xmlOrStd instanceof stdClass) {
            $this->std = $xmlOrStd;
            if (!empty($xmlOrStd->xml)) {
                $this->xml = (string) $xmlOrStd->xml;
            }
        } elseif (is_string($xmlOrStd) && $xmlOrStd !== '') {
            $this->xml = $xmlOrStd;
        }
    }

    /**
     * Define o XML da NFS-e a ser renderizado.
     * @param string $xml
     * @return $this
     */
    public function loadXml(string $xml)
    {
        $this->xml = $xml;
        return $this;
    }

    /**
     * Gera o conteúdo binário do PDF do DANFSe.
     * @param string|null $logo Caminho ou data-uri da logomarca (opcional)
     * @return string
     * @throws Exception
     */
    public function getPdfFile(?string $logo = null)
    {
        if (empty($this->xml)) {
            throw new Exception('XML da NFS-e é obrigatório para gerar o DANFSe.');
        }

        $danfse = new Danfse($this->xml);
        return $danfse->render($logo ?? '');
    }
}
