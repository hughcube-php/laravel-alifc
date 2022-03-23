<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/9/22
 * Time: 10:03.
 */

namespace HughCube\Laravel\AliFC\Fc;

class Util
{
    public static function unescape($str): string
    {
        $ret = '';
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            if ($str[$i] == '%' && $str[$i + 1] == 'u') {
                $val = hexdec(substr($str, $i + 2, 4));
                if ($val < 0x7F) {
                    $ret .= chr($val);
                } else {
                    if ($val < 0x800) {
                        $ret .= chr(0xC0 | ($val >> 6)).
                            chr(0x80 | ($val & 0x3F));
                    } else {
                        $ret .= chr(0xE0 | ($val >> 12)).
                            chr(0x80 | (($val >> 6) & 0x3F)).
                            chr(0x80 | ($val & 0x3F));
                    }
                }

                $i += 5;
            } else {
                if ($str[$i] == '%') {
                    $ret .= urldecode(substr($str, $i, 3));
                    $i += 2;
                } else {
                    $ret .= $str[$i];
                }
            }
        }

        return $ret;
    }
}
