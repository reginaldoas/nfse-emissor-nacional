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
}