<?php

namespace Reginaldoas\Nfse\Common;

interface CertificateInterface{
    public function convertCertToPem();
    public function sign($xml,$tag_name,$tag_digest);
}