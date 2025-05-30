<?php

namespace App\Helpers;

use App\Models\Business;
use App\Models\ProcessAccountingAccount;
use App\Models\SubsidiaryConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request;

class Helper
{
    public static function is_rtl($current_locale = '')
    {
        $current_locale = (!empty($current_locale)) ? $current_locale : app()->getLocale();
        $is_rtl = config('languages.'.$current_locale.'.is_rtl');

        return !empty($is_rtl);
    }

    public static function rlt_ext($current_locale = '')
    {
        $current_locale = (!empty($current_locale)) ? $current_locale : app()->getLocale();

        return self::is_rtl($current_locale) ? '' : '';
    }

    public static function get_translation_url($locale = '')
    {
        return route('switch_lang', $locale);
    }

    public static function get_public_storage_asset_url($path)
    {
        $path = preg_replace('/^(public)[\/]/', '', $path);

        return asset('storage/'.$path);
    }

    public static function formatMoney($number, $abs = true, $decimals = 2)
    {
        if (is_string($number)) {
            $number = floatval($number);
        }

        if ('excel' == Request::get('format')) {
            return $abs ? abs($number) : $number;
        }

        return '$'.number_format($abs ? abs($number) : $number, $decimals);
    }

    public static function cleanNumber($number)
    {
        return preg_replace('/[^0-9]/', '', $number);
    }

    public static function clearFormat($number)
    {
        return str_replace([',', '$', ' '], '', $number ?? 0);
    }

    public static function dateToString(?Carbon $date = null, $withTime = false)
    {
        if (is_null($date)) {
            return '';
        }
        $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $month = $months[$date?->format('n') - 1];

        return $date?->format('d').' de '.$month.' de '.$date?->format('Y').($withTime ? ' a las '.$date?->format('H:i') : '');
    }

    public static function arraySumByKey($array, $key)
    {
        $sum = 0;

        foreach ($array as $row) {
            $sum += $row[$key];
        }

        return $sum;
    }

    public static function percentValue($percent, $total, $decimals = 2)
    {
        return round($percent * $total / 100, $decimals);
    }

    public static function getSubsidiaryConfig($key, $subsidiary_id = 0)
    {
        if (0 == $subsidiary_id) {
            $subsidiary_id = auth()->user()?->subsidiary_id ?? 0;
        }

        return SubsidiaryConfig::where('key', $key)->where('subsidiary_id', $subsidiary_id)->first()?->value ?? config('subsidiary_configs.'.$key.'.default');
    }

    public static function getProcessAccountingAccount($process)
    {
        return ProcessAccountingAccount::where('key', $process)->first()?->value;
    }

    public static function calcularDV($nit)
    {
        if (!is_numeric($nit)) {
            return false;
        }

        try {
            $arr = [
                1 => 3, 4 => 17, 7 => 29, 10 => 43, 13 => 59, 2 => 7, 5 => 19,
                8 => 37, 11 => 47, 14 => 67, 3 => 13, 6 => 23, 9 => 41, 12 => 53, 15 => 71,
            ];
            $x = 0;
            $y = 0;
            $z = strlen($nit);
            $dv = '';

            for ($i = 0; $i < $z; ++$i) {
                $y = substr($nit, $i, 1);
                $x += ($y * $arr[$z - $i]);
            }
            $y = $x % 11;

            if ($y > 1) {
                return 11 - $y;
            }

            return $y;
        } catch (\ErrorException $e) {
            return false;
        }
    }

    public static function isSpecialBusiness($business_id = null)
    {
        return Business::find($business_id ?? auth()->user()->business_id)->is_special;
    }

    public static function subfijo($xx)
    { // esta función regresa un subfijo para la cifra
        $xx = trim($xx);
        $xstrlen = strlen($xx);

        if (1 == $xstrlen || 2 == $xstrlen || 3 == $xstrlen) {
            $xsub = '';
        }

        if (4 == $xstrlen || 5 == $xstrlen || 6 == $xstrlen) {
            $xsub = 'MIL';
        }

        return $xsub;
    }

    public static function moneyToText($xcifra)
    {
        $xarray = [
            0 => 'Cero',
            1 => 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
            'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE',
            'VEINTI', 30 => 'TREINTA', 40 => 'CUARENTA', 50 => 'CINCUENTA', 60 => 'SESENTA', 70 => 'SETENTA', 80 => 'OCHENTA', 90 => 'NOVENTA',
            100 => 'CIENTO', 200 => 'DOSCIENTOS', 300 => 'TRESCIENTOS', 400 => 'CUATROCIENTOS', 500 => 'QUINIENTOS', 600 => 'SEISCIENTOS', 700 => 'SETECIENTOS', 800 => 'OCHOCIENTOS', 900 => 'NOVECIENTOS',
        ];

        $xcifra = trim($xcifra);
        $xlength = strlen($xcifra);
        $xpos_punto = strpos($xcifra, '.');
        $xaux_int = $xcifra;
        $xdecimales = '00';

        if (!(false === $xpos_punto)) {
            if (0 == $xpos_punto) {
                $xcifra = '0'.$xcifra;
                $xpos_punto = strpos($xcifra, '.');
            }
            $xaux_int = substr($xcifra, 0, $xpos_punto); // obtengo el entero de la cifra a covertir
            $xdecimales = substr($xcifra.'00', $xpos_punto + 1, 2); // obtengo los valores decimales
        }

        $XAUX = str_pad($xaux_int, 18, ' ', STR_PAD_LEFT); // ajusto la longitud de la cifra, para que sea divisible por centenas de miles (grupos de 6)
        $xcadena = '';

        for ($xz = 0; $xz < 3; ++$xz) {
            $xaux = substr($XAUX, $xz * 6, 6);
            $xi = 0;
            $xlimite = 6; // inicializo el contador de centenas xi y establezco el límite a 6 dígitos en la parte entera
            $xexit = true; // bandera para controlar el ciclo del While

            while ($xexit) {
                if ($xi == $xlimite) { // si ya llegó al límite máximo de enteros
                    break; // termina el ciclo
                }

                $x3digitos = ($xlimite - $xi) * -1; // comienzo con los tres primeros digitos de la cifra, comenzando por la izquierda
                $xaux = substr($xaux, $x3digitos, abs($x3digitos)); // obtengo la centena (los tres dígitos)

                for ($xy = 1; $xy < 4; ++$xy) { // ciclo para revisar centenas, decenas y unidades, en ese orden
                    switch ($xy) {
                        case 1: // checa las centenas
                            if (substr($xaux, 0, 3) < 100) { // si el grupo de tres dígitos es menor a una centena ( < 99) no hace nada y pasa a revisar las decenas
                            } else {
                                $key = (int) substr($xaux, 0, 3);

                                if (true === array_key_exists($key, $xarray)) {  // busco si la centena es número redondo (100, 200, 300, 400, etc..)
                                    $xseek = $xarray[$key];
                                    $xsub = self::subfijo($xaux); // devuelve el subfijo correspondiente (Millón, Millones, Mil o nada)

                                    if (100 == substr($xaux, 0, 3)) {
                                        $xcadena = ' '.$xcadena.' CIEN '.$xsub;
                                    } else {
                                        $xcadena = ' '.$xcadena.' '.$xseek.' '.$xsub;
                                    }
                                    $xy = 3; // la centena fue redonda, entonces termino el ciclo del for y ya no reviso decenas ni unidades
                                } else { // entra aquí si la centena no fue numero redondo (101, 253, 120, 980, etc.)
                                    $key = (int) substr($xaux, 0, 1) * 100;
                                    $xseek = $xarray[$key]; // toma el primer caracter de la centena y lo multiplica por cien y lo busca en el arreglo (para que busque 100,200,300, etc)
                                    $xcadena = ' '.$xcadena.' '.$xseek;
                                } // ENDIF ($xseek)
                            } // ENDIF (substr($xaux, 0, 3) < 100)

                            break;
                        case 2: // checa las decenas (con la misma lógica que las centenas)
                            if (substr($xaux, 1, 2) < 10) {
                            } else {
                                $key = (int) substr($xaux, 1, 2);

                                if (true === array_key_exists($key, $xarray)) {
                                    $xseek = $xarray[$key];
                                    $xsub = self::subfijo($xaux);

                                    if (20 == substr($xaux, 1, 2)) {
                                        $xcadena = ' '.$xcadena.' VEINTE '.$xsub;
                                    } else {
                                        $xcadena = ' '.$xcadena.' '.$xseek.' '.$xsub;
                                    }
                                    $xy = 3;
                                } else {
                                    $key = (int) substr($xaux, 1, 1) * 10;
                                    $xseek = $xarray[$key];

                                    if (20 == substr($xaux, 1, 1) * 10) {
                                        $xcadena = ' '.$xcadena.' '.$xseek;
                                    } else {
                                        $xcadena = ' '.$xcadena.' '.$xseek.' Y ';
                                    }
                                } // ENDIF ($xseek)
                            } // ENDIF (substr($xaux, 1, 2) < 10)

                            break;
                        case 3: // checa las unidades
                            if (substr($xaux, 2, 1) < 1) { // si la unidad es cero, ya no hace nada
                            } else {
                                $key = (int) substr($xaux, 2, 1);
                                $xseek = $xarray[$key]; // obtengo directamente el valor de la unidad (del uno al nueve)
                                $xsub = self::subfijo($xaux);
                                $xcadena = ' '.$xcadena.' '.$xseek.' '.$xsub;
                            } // ENDIF (substr($xaux, 2, 1) < 1)

                            break;
                    } // END SWITCH
                } // END FOR
                $xi = $xi + 3;
            } // ENDDO

            if ('ILLON' == substr(trim($xcadena), -5, 5)) { // si la cadena obtenida termina en MILLON o BILLON, entonces le agrega al final la conjuncion DE
                $xcadena .= ' DE';
            }

            if ('ILLONES' == substr(trim($xcadena), -7, 7)) { // si la cadena obtenida en MILLONES o BILLONES, entoncea le agrega al final la conjuncion DE
                $xcadena .= ' DE';
            }

            // ----------- esta línea la puedes cambiar de acuerdo a tus necesidades o a tu país -------
            if ('' != trim($xaux)) {
                switch ($xz) {
                    case 0:
                        if ('1' == trim(substr($XAUX, $xz * 6, 6))) {
                            $xcadena .= 'UN BILLON ';
                        } else {
                            $xcadena .= ' BILLONES ';
                        }

                        break;
                    case 1:
                        if ('1' == trim(substr($XAUX, $xz * 6, 6))) {
                            $xcadena .= 'UN MILLON ';
                        } else {
                            $xcadena .= ' MILLONES ';
                        }

                        break;
                    case 2:
                        if ($xcifra < 1) {
                            $xcadena = 'CERO PESOS';
                        }

                        if ($xcifra >= 1 && $xcifra < 2) {
                            $xcadena = 'UN PESO';
                        }

                        if ($xcifra >= 2) {
                            $xcadena .= ' PESOS';
                        }

                        break;
                } // endswitch ($xz)
            } // ENDIF (trim($xaux) != "")
            // ------------------      en este caso, para México se usa esta leyenda     ----------------
            $xcadena = str_replace('VEINTI ', 'VEINTI', $xcadena); // quito el espacio para el VEINTI, para que quede: VEINTICUATRO, VEINTIUN, VEINTIDOS, etc
            $xcadena = str_replace('  ', ' ', $xcadena); // quito espacios dobles
            $xcadena = str_replace('UN UN', 'UN', $xcadena); // quito la duplicidad
            $xcadena = str_replace('  ', ' ', $xcadena); // quito espacios dobles
            $xcadena = str_replace('BILLON DE MILLONES', 'BILLON DE', $xcadena); // corrigo la leyenda
            $xcadena = str_replace('BILLONES DE MILLONES', 'BILLONES DE', $xcadena); // corrigo la leyenda
            $xcadena = str_replace('DE UN', 'UN', $xcadena); // corrigo la leyenda
        } // ENDFOR ($xz)
        $xcadena .= ' CON '.self::numberToText($xdecimales).' CENTAVOS M/CTE';

        return trim($xcadena);
    }

    public static function numberToText($xcifra)
    {
        $xarray = [
            0 => 'Cero',
            1 => 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
            'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE',
            'VEINTI', 30 => 'TREINTA', 40 => 'CUARENTA', 50 => 'CINCUENTA', 60 => 'SESENTA', 70 => 'SETENTA', 80 => 'OCHENTA', 90 => 'NOVENTA',
            100 => 'CIENTO', 200 => 'DOSCIENTOS', 300 => 'TRESCIENTOS', 400 => 'CUATROCIENTOS', 500 => 'QUINIENTOS', 600 => 'SEISCIENTOS', 700 => 'SETECIENTOS', 800 => 'OCHOCIENTOS', 900 => 'NOVECIENTOS',
        ];

        $xcifra = trim($xcifra);

        if (0 == $xcifra) {
            return 'CERO';
        }
        $xlength = strlen($xcifra);
        $xpos_punto = strpos($xcifra, '.');
        $xaux_int = $xcifra;
        $xdecimales = '00';

        if (!(false === $xpos_punto)) {
            if (0 == $xpos_punto) {
                $xcifra = '0'.$xcifra;
                $xpos_punto = strpos($xcifra, '.');
            }
            $xaux_int = substr($xcifra, 0, $xpos_punto); // obtengo el entero de la cifra a covertir
            $xdecimales = substr($xcifra.'00', $xpos_punto + 1, 2); // obtengo los valores decimales
        }

        $XAUX = str_pad($xaux_int, 18, ' ', STR_PAD_LEFT); // ajusto la longitud de la cifra, para que sea divisible por centenas de miles (grupos de 6)
        $xcadena = '';

        for ($xz = 0; $xz < 3; ++$xz) {
            $xaux = substr($XAUX, $xz * 6, 6);
            $xi = 0;
            $xlimite = 6; // inicializo el contador de centenas xi y establezco el límite a 6 dígitos en la parte entera
            $xexit = true; // bandera para controlar el ciclo del While

            while ($xexit) {
                if ($xi == $xlimite) { // si ya llegó al límite máximo de enteros
                    break; // termina el ciclo
                }

                $x3digitos = ($xlimite - $xi) * -1; // comienzo con los tres primeros digitos de la cifra, comenzando por la izquierda
                $xaux = substr($xaux, $x3digitos, abs($x3digitos)); // obtengo la centena (los tres dígitos)

                for ($xy = 1; $xy < 4; ++$xy) { // ciclo para revisar centenas, decenas y unidades, en ese orden
                    switch ($xy) {
                        case 1: // checa las centenas
                            if (substr($xaux, 0, 3) < 100) { // si el grupo de tres dígitos es menor a una centena ( < 99) no hace nada y pasa a revisar las decenas
                            } else {
                                $key = (int) substr($xaux, 0, 3);

                                if (true === array_key_exists($key, $xarray)) {  // busco si la centena es número redondo (100, 200, 300, 400, etc..)
                                    $xseek = $xarray[$key];
                                    $xsub = self::subfijo($xaux); // devuelve el subfijo correspondiente (Millón, Millones, Mil o nada)

                                    if (100 == substr($xaux, 0, 3)) {
                                        $xcadena = ' '.$xcadena.' CIEN '.$xsub;
                                    } else {
                                        $xcadena = ' '.$xcadena.' '.$xseek.' '.$xsub;
                                    }
                                    $xy = 3; // la centena fue redonda, entonces termino el ciclo del for y ya no reviso decenas ni unidades
                                } else { // entra aquí si la centena no fue numero redondo (101, 253, 120, 980, etc.)
                                    $key = (int) substr($xaux, 0, 1) * 100;
                                    $xseek = $xarray[$key]; // toma el primer caracter de la centena y lo multiplica por cien y lo busca en el arreglo (para que busque 100,200,300, etc)
                                    $xcadena = ' '.$xcadena.' '.$xseek;
                                } // ENDIF ($xseek)
                            } // ENDIF (substr($xaux, 0, 3) < 100)

                            break;
                        case 2: // checa las decenas (con la misma lógica que las centenas)
                            if (substr($xaux, 1, 2) < 10) {
                            } else {
                                $key = (int) substr($xaux, 1, 2);

                                if (true === array_key_exists($key, $xarray)) {
                                    $xseek = $xarray[$key];
                                    $xsub = self::subfijo($xaux);

                                    if (20 == substr($xaux, 1, 2)) {
                                        $xcadena = ' '.$xcadena.' VEINTE '.$xsub;
                                    } else {
                                        $xcadena = ' '.$xcadena.' '.$xseek.' '.$xsub;
                                    }
                                    $xy = 3;
                                } else {
                                    $key = (int) substr($xaux, 1, 1) * 10;
                                    $xseek = $xarray[$key];

                                    if (20 == substr($xaux, 1, 1) * 10) {
                                        $xcadena = ' '.$xcadena.' '.$xseek;
                                    } else {
                                        $xcadena = ' '.$xcadena.' '.$xseek.' Y ';
                                    }
                                } // ENDIF ($xseek)
                            } // ENDIF (substr($xaux, 1, 2) < 10)

                            break;
                        case 3: // checa las unidades
                            if (substr($xaux, 2, 1) < 1) { // si la unidad es cero, ya no hace nada
                            } else {
                                $key = (int) substr($xaux, 2, 1);
                                $xseek = $xarray[$key]; // obtengo directamente el valor de la unidad (del uno al nueve)
                                $xsub = self::subfijo($xaux);
                                $xcadena = ' '.$xcadena.' '.$xseek.' '.$xsub;
                            } // ENDIF (substr($xaux, 2, 1) < 1)

                            break;
                    } // END SWITCH
                } // END FOR
                $xi = $xi + 3;
            } // ENDDO

            if ('ILLON' == substr(trim($xcadena), -5, 5)) { // si la cadena obtenida termina en MILLON o BILLON, entonces le agrega al final la conjuncion DE
                $xcadena .= ' DE';
            }

            if ('ILLONES' == substr(trim($xcadena), -7, 7)) { // si la cadena obtenida en MILLONES o BILLONES, entoncea le agrega al final la conjuncion DE
                $xcadena .= ' DE';
            }

            // ----------- esta línea la puedes cambiar de acuerdo a tus necesidades o a tu país -------
            if ('' != trim($xaux)) {
                switch ($xz) {
                    case 0:
                        if ('1' == trim(substr($XAUX, $xz * 6, 6))) {
                            $xcadena .= '';
                        } else {
                            $xcadena .= '';
                        }

                        break;
                    case 1:
                        if ('1' == trim(substr($XAUX, $xz * 6, 6))) {
                            $xcadena .= '';
                        } else {
                            $xcadena .= ' ';
                        }

                        break;
                    case 2:
                        if ($xcifra < 1) {
                            $xcadena = '';
                        }

                        if ($xcifra >= 1 && $xcifra < 2) {
                            $xcadena = '';
                        }

                        if ($xcifra >= 2) {
                            $xcadena .= '';
                        }

                        break;
                } // endswitch ($xz)
            } // ENDIF (trim($xaux) != "")
            // ------------------      en este caso, para México se usa esta leyenda     ----------------
            $xcadena = str_replace('VEINTI ', 'VEINTI', $xcadena); // quito el espacio para el VEINTI, para que quede: VEINTICUATRO, VEINTIUN, VEINTIDOS, etc
            $xcadena = str_replace('  ', ' ', $xcadena); // quito espacios dobles
            $xcadena = str_replace('UN UN', 'UN', $xcadena); // quito la duplicidad
            $xcadena = str_replace('  ', ' ', $xcadena); // quito espacios dobles
            $xcadena = str_replace('BILLON DE MILLONES', 'BILLON DE', $xcadena); // corrigo la leyenda
            $xcadena = str_replace('BILLONES DE MILLONES', 'BILLONES DE', $xcadena); // corrigo la leyenda
            $xcadena = str_replace('DE UN', 'UN', $xcadena); // corrigo la leyenda
        } // ENDFOR ($xz)

        return trim($xcadena);
    }

    public static function getFirstName($name)
    {
        $name = explode(' ', $name);

        switch (count($name)) {
            case 0:
                return '';
            case 1:
                return $name[0];
            case 2:
                return $name[1];
            case 4:
                return $name[2].' '.$name[3];
            default:
                return $name[2];
        }
    }

    public static function getFamilyName($name)
    {
        $name = explode(' ', $name);

        switch (count($name)) {
            case 0:
                return '';
            case 1:
                return $name[0];
            case 2:
                return $name[0];
            default:
                return $name[0].' '.$name[1];
        }
    }

    public static function arrayNotEmptyOrDefault($array, $default = [])
    {
        return count($array ?? []) > 0 ? $array : $default;
    }
}
