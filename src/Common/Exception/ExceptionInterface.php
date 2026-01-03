<?php

namespace Reginaldoas\Nfse\Common\Exception;

interface ExceptionInterface{
    public static function unableToRead();
    public static function unablePrivateKey();
    public static function unableCreateFile();
    public static function unableSignature();
    public static function unableFolder();
    public static function isNotXml();
    public static function xmlErrors(array $errors);
    public static function unableToLoadUri($message);
}