<?php

namespace Reginaldoas\Nfse\Common;

use stdClass;

interface DpsInterface{
    public function render(stdClass $std = null);
    public function renderEvento(stdClass $std = null);
}