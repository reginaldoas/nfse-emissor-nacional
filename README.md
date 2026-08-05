# NFSe Emissor Nacional

Pacote responsável pela integração com o Emissor Nacional para emissão de NFSe. (https://sefin.nfse.gov.br/).

Link para documentação original https://www.gov.br/nfse/pt-br/biblioteca/documentacao-tecnica.

## Emissão de Danfe
Devido a inativação do serviço em 01/06/2026 o danfe será gerado diretamente sem necessidade de integração com a Api.

```php
use Reginaldoas\Nfse\Common\Danfe;

$xml = file_get_contents('nfse-autorizada.xml');
$danfe = new Danfe($xml);
$pdf = $danfe->render(); // ou passe a logo: $danfe->render(__DIR__.'/logo.jpg');

file_put_contents('danfse.pdf', $pdf);
```

Exemplo completo em `examples/RenderDanfe.php`.

## Install

**Pacote desenvolvido para uso do [Composer](https://getcomposer.org/)**

```bash
composer require reginaldoas/nfse
```


## Requerimentos
- PHP 8.2+
- ext-zlib
- ext-openssl
- ext-dom
- ext-curl