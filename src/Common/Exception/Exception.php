<?php

namespace Reginaldoas\Nfse\Common\Exception;

use RuntimeException;

class Exception extends RuntimeException implements ExceptionInterface
{
    public static function unableToRead()
    {
        return new static('Falha ao ler certificado, ' . static::getOpenSSLError());
    }

    public static function unablePrivateKey()
    {
        return new static('Falha ao obter private key, ' . static::getOpenSSLError());
    }

    public static function unableCreateFile()
    {
        return new static('Falha ao criar o arquivo');
    }

    public static function unableSignature()
    {
        return new static('Falha ao assinar documento');
    }

    public static function unableFolder()
    {
        return new static('Falha ao tentar criar a pasta para certificados');
    }

    public static function isNotXml()
    {
        return new static('A string passada não é um XML');
    }

    public static function xmlErrors(array $errors)
    {
        $msg = '';
        foreach ($errors as $error) {
            $msg .= $error . "\n";
        }
        return new static('Este XML não é válido. ' . $msg);
    }

    public static function unableToLoadUri($message)
    {
        return new static('Falha ao tentar comunicar com servidor ' . $message);
    }

    protected static function getOpenSSLError()
    {
        $error = 'ocorreu o seguinte erro: ';
        while ($msg = openssl_error_string()) {
            $error .= "($msg)";
        }
        return $error;
    }

}