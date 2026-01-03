<?php

namespace Reginaldoas\Nfse\Common;

use stdClass;

class Dps extends DpsAbstract implements DpsInterface
{
    
    public function render(stdClass $std = null)
    {
        return $this->getDpsXml($std);
    }

    public function renderEvento(stdClass $std = null)
    {
        return $this->getEventoXml($std);
    }

}