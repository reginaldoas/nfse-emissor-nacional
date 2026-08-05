<?php

namespace Reginaldoas\Nfse\Common\Pdf;

use FPDF;

/**
 * Extensão do FPDF com utilitários para o DANFSe.
 */
class Pdf extends FPDF
{
    /** @var float */
    private $angle = 0.0;

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4')
    {
        parent::__construct($orientation, $unit, $format);
    }

    public function SetFont($family, $style = '', $size = 0)
    {
        if (strtolower((string) $family) === 'arial') {
            $family = 'Helvetica';
        }
        parent::SetFont($family, $style, $size);
    }

    public function getPdf(): string
    {
        return $this->Output('S');
    }

    public function open(): void
    {
        // Compatibilidade com API legada; FPDF abre automaticamente no AddPage.
    }

    public function rotate($angle, $x = -1, $y = -1)
    {
        if ($x == -1) {
            $x = $this->x;
        }
        if ($y == -1) {
            $y = $this->y;
        }
        if (isset($this->angle) && $this->angle != 0) {
            $this->_out('Q');
        }
        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI/180;
            $c = cos($angle);
            $s = sin($angle);
            $cx =$x*$this->k;
            $cy = ($this->h-$y)*$this->k;
            $this->_out(
                sprintf(
                    'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
                    $c,
                    $s,
                    -$s,
                    $c,
                    $cx,
                    $cy,
                    -$cx,
                    -$cy
                )
            );
        }
    }

    public function roundedRect($x, $y, $w, $h, $r, $corners = '1234', $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if ($style == 'F') {
            $op = 'f';
        } elseif ($style == 'FD' || $style == 'DF') {
            $op = 'B';
        } else {
            $op = 'S';
        }
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x+$r)*$k, ($hp-$y)*$k));
        $xc = $x+$w-$r;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k, ($hp-$y)*$k));
        if (strpos($corners, '2')===false) {
            $this->_out(sprintf('%.2F %.2F l', ($x+$w)*$k, ($hp-$y)*$k));
        } else {
            $this->arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        }
        $xc = $x+$w-$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l', ($x+$w)*$k, ($hp-$yc)*$k));
        if (strpos($corners, '3')===false) {
            $this->_out(sprintf('%.2F %.2F l', ($x+$w)*$k, ($hp-($y+$h))*$k));
        } else {
            $this->arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        }
        $xc = $x+$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k, ($hp-($y+$h))*$k));
        if (strpos($corners, '4')===false) {
            $this->_out(sprintf('%.2F %.2F l', ($x)*$k, ($hp-($y+$h))*$k));
        } else {
            $this->arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        }
        $xc = $x+$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', ($x)*$k, ($hp-$yc)*$k));
        if (strpos($corners, '1')===false) {
            $this->_out(sprintf('%.2F %.2F l', ($x)*$k, ($hp-$y)*$k));
            $this->_out(sprintf('%.2F %.2F l', ($x+$r)*$k, ($hp-$y)*$k));
        } else {
            $this->arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        }
        $this->_out($op);
    }

    private function arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(
            sprintf(
                '%.2F %.2F %.2F %.2F %.2F %.2F c ',
                $x1 * $this->k,
                ($h - $y1) * $this->k,
                $x2 * $this->k,
                ($h - $y2) * $this->k,
                $x3 * $this->k,
                ($h - $y3) * $this->k
            )
        );
    }

    public function dashedHLine($x, $y, $w, $h, $n)
    {
        $this->setDrawColor(110);
        $this->setLineWidth($h);
        $wDash = ($w/$n)/2;
        for ($i=$x; $i<=$x+$w; $i += $wDash+$wDash) {
            for ($j=$i; $j<= ($i+$wDash); $j++) {
                if ($j <= ($x+$w-1)) {
                    $this->line($j, $y, $j+1, $y);
                }
            }
        }
        $this->setDrawColor(0);
    }

    public function wordWrap(&$text, $maxwidth)
    {
        $text = trim($text);
        if ($text === '') {
            return 0;
        }
        $space = $this->getStringWidth(' ');
        $lines = explode("\n", $text);
        $text = '';
        $count = 0;
        foreach ($lines as $line) {
            $words = preg_split('/ +/', $line);
            $width = 0;
            foreach ($words as $word) {
                $wordwidth = $this->getStringWidth($word);
                if ($wordwidth > $maxwidth) {
                    // Word is too long, we cut it
                    for ($i=0; $i < strlen($word); $i++) {
                        $wordwidth = $this->getStringWidth(substr($word, $i, 1));
                        if ($width + $wordwidth <= $maxwidth) {
                            $width += $wordwidth;
                            $text .= substr($word, $i, 1);
                        } else {
                            $width = $wordwidth;
                            $text = rtrim($text)."\n".substr($word, $i, 1);
                            $count++;
                        }
                    }
                } elseif ($width + $wordwidth <= $maxwidth) {
                    $width += $wordwidth + $space;
                    $text .= $word . ' ';
                } else {
                    $width = $wordwidth + $space;
                    $text = rtrim($text) . "\n" . $word . ' ';
                    $count++;
                }
            }
            $text = rtrim($text)."\n";
            $count++;
        }
        $text = rtrim($text);
        return $count;
    }

    public function textBox(
        $x,
        $y,
        $w,
        $h,
        $text = '',
        $aFont = array('font' => 'Times', 'size' => 8, 'style' => ''),
        $vAlign = 'T',
        $hAlign = 'L',
        $border = true,
        $link = '',
        $force = true,
        $hmax = 0,
        $vOffSet = 0,
        $fill = false
    ) {
        $oldY = $y;
        $temObs = false;
        $resetou = false;
        if ($w < 0) {
            return $y;
        }
        if (is_object($text)) {
            $text = '';
        }
        if (is_string($text)) {
            //remover espaços desnecessários
            $text = trim($text);
            //converter o charset para o fpdf
            $text = $this->convertToIso($text);
            //decodifica os caracteres html no xml
            $text = html_entity_decode($text);
        } else {
            $text = (string) $text;
        }
        //desenhar a borda da caixa
        if ($border && $fill) {
            $this->roundedRect($x, $y, $w, $h, 0.8, '1234', 'DF');
        } elseif ($border) {
            $this->roundedRect($x, $y, $w, $h, 0.8, '1234', 'D');
        } elseif ($fill) {
            $this->rect($x, $y, $w, $h, 'F');
        }
        //estabelecer o fonte
        $this->setFont($aFont['font'], $aFont['style'], $aFont['size']);
        //calcular o incremento
        $incY = $this->FontSize; //tamanho da fonte na unidade definida
        if (!$force) {
            //verificar se o texto cabe no espaço
            $n = $this->wordWrap($text, $w);
        } else {
            $n = 1;
        }
        //calcular a altura do conjunto de texto
        $altText = $incY * $n;
        //separar o texto em linhas
        $lines = explode("\n", $text);
        //verificar o alinhamento vertical
        if ($vAlign == 'T') {
            //alinhado ao topo
            $y1 = $y + $incY;
        }
        if ($vAlign == 'C') {
            //alinhado ao centro
            $y1 = $y + $incY + (($h - $altText) / 2);
        }
        if ($vAlign == 'B') {
            //alinhado a base
            $y1 = ($y + $h) - 0.5;
        }
        //para cada linha
        foreach ($lines as $line) {
            //verificar o comprimento da frase
            $texto = trim($line);
            $comp = $this->getStringWidth($texto);
            if ($force) {
                $newSize = $aFont['size'];
                while ($comp > $w) {
                    //estabelecer novo fonte
                    $this->setFont($aFont['font'], $aFont['style'], --$newSize);
                    $comp = $this->getStringWidth($texto);
                }
            }
            //ajustar ao alinhamento horizontal
            $x1 = $x;
            if ($hAlign == 'L') {
                $x1 = $x + 0.5;
            }
            if ($hAlign == 'C') {
                $x1 = $x + (($w - $comp) / 2);
            }
            if ($hAlign == 'R') {
                $x1 = $x + $w - ($comp + 0.5);
            }
            //escrever o texto
            if ($vOffSet > 0) {
                if ($y1 > ($oldY + $vOffSet)) {
                    if (!$resetou) {
                        $y1 = $oldY;
                        $resetou = true;
                    }
                    $this->text($x1, $y1, $texto);
                }
            } else {
                $this->text($x1, $y1, $texto);
            }
            //incrementar para escrever o proximo
            $y1 += $incY;
            if (($hmax > 0) && ($y1 > ($y + ($hmax - 1)))) {
                $temObs = true;
                break;
            }
        }
        return ($y1 - $y) - $incY;
    }

    protected function convertToIso($text)
    {
        return mb_convert_encoding($text, 'ISO-8859-1', ['UTF-8', 'windows-1252']);
    }
}
