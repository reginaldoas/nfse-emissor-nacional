<?php

namespace Reginaldoas\Nfse\Common;

class Tools{
    public static function removeEspaco($str){
        return preg_replace('/\\s\\s+/', ' ',trim($str));
    }
    public static function increment($str,$qtde = 9,$sub = 0,$dir = "LEFT"){
        $string = self::removeEspaco($str);
        if($dir == "LEFT"){
            return substr(str_pad($string, $qtde, $sub, STR_PAD_LEFT),0,$qtde);
        }elseif($dir == "RIGHT"){
            return substr(str_pad($string, $qtde, $sub),0,$qtde);
        }elseif($dir == "LEFTRIGHT"){
            return substr(str_pad($string, $qtde, $sub, STR_PAD_BOTH),0,$qtde);
        }
    }
    public static function getCidadeIbge($codigo){
        $codigo_limpo = preg_replace('/[^0-9]/', '', $codigo);
        $url = "https://servicodados.ibge.gov.br/api/v1/localidades/municipios/" . $codigo_limpo;
        $response = file_get_contents($url);
        if ($response !== false) {
            $dados = json_decode($response, true);
            $nome_municipio = $dados['nome'];
            $sigla_estado = $dados['microrregiao']['mesorregiao']['UF']['sigla'];
            return array("nome" => $nome_municipio, "sigla" => $sigla_estado);
        } else {
            return array("nome" => $codigo, "sigla" => "");
        }
    }
}