<?php

namespace Reginaldoas\Nfse;

use Reginaldoas\Nfse\Common\Certificate;
use Reginaldoas\Nfse\Common\Sender;

abstract class NfseAbstract{
    
    /**
     * @var stdClass
     */
    public $std;
    /**
     * @var Certificate
     */
    public $cert;
    /**
     * @var string
     */
    public $cert_pass;
    /**
     * @var Sender
     */
    public Sender $sender;
    /**
     * @var boolean
     */
    public $production = false;

    public function __construct($std, $cert, $cert_pass, $production){
        $this->std = $std;
        $this->cert = $cert;
        $this->cert_pass = $cert_pass;
        $this->production = $production;
        $this->cert = new Certificate($this->cert,$this->cert_pass);
        $this->sender = new Sender($this->production,$this->cert);
    }

}