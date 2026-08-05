<?php

namespace Reginaldoas\Nfse\Common;

class Danfe extends DanfeAbstract implements DanfeInterface
{
    /**
     * Renderiza o DANFSe e retorna o PDF binário.
     * @param string|null $logo Caminho ou data-uri da logomarca (opcional)
     * @return string
     */
    public function render(?string $logo = null)
    {
        return $this->getPdfFile($logo);
    }

    /**
     * Alias de render() para compatibilidade.
     * @param string|null $logo
     * @return string
     */
    public function renderDanfe(?string $logo = null)
    {
        return $this->render($logo);
    }
}
