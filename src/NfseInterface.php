<?php

namespace Reginaldoas\Nfse;

interface NfseInterface{
    public function render();
    public function getDpsId();
    public function renderEvento();
    public function getEventoId();
    public function inclusaoNfse();
    public function consultaDanfe();
    public function consultaNfseChave();
    public function consultaDpsChave();
    public function consultaNfseEventos();
    public function cancelarNfse();
    public function nfseFile(string $response);
}