<?php

namespace App;

final class Decoder
{
    private const BIN_FLAG_CHAR = 0xF8;
    private const MIN_PACKET_LEN = 22;
    private const PROTOCOL_VERSION = 0x01;
    private const BIN_ESCAPE_CHAR = 0xF7;
    private const ACK_FLAG = 0xFE;

    public static function isBinary($str)
    {
        return preg_match('~[^\x20-\x7E\t\r\n]~', $str) > 0;
    }

    /**
     * @var contém o pacode em binário
     */
    public static function decode(string $data)
    {        
        $hex = (self::isBinary($data) ? bin2hex($data) : $data);
        // dump($hex);
        // if (strlen($hex) % 2 != 0 || !ctype_xdigit($hex)) {
        //     throw new \Exception('Data format error');
        // }

        $binData = self::hexToStrArray($hex);
        $result = [];
        $offset = 0;
        while (($offset + self::MIN_PACKET_LEN) < count($binData)) {
            // Get binary frame end flag position
            $endPos = array_search(
                self::BIN_FLAG_CHAR,
                array_slice($binData, $offset + 1, null, true)
            );
            if (!$endPos || ($endPos - $offset) < 12) {
                ++$offset;
                continue;
            }

            $binFrame = array_slice($binData, $offset + 1, $endPos - $offset - 1);
            $dataLen = self::binFrameFormatCheck($binFrame);

            if ($dataLen <= 0) { // CRC check error: skip
                ++$offset;
                continue;
            }
            $result[] = self::binFrameDecode($binFrame, $dataLen);
            $offset = $endPos + 1;
        }

        return $result;
    }

    public static function hexToStrArray($hex)
    {
        $hex = strtoupper($hex);
        // $binData é um arranjo em hex dos dados
        $hexArray = str_split($hex, 2);

        return self::hexStringDec($hexArray);
    }

    public static function binDataPacket($ackData)
    {
        $packet = [self::BIN_FLAG_CHAR];
        for ($i = 0; $i < 6; ++$i) {
            if ($ackData[$i] == self::BIN_FLAG_CHAR || $ackData[$i] == self::BIN_ESCAPE_CHAR) {
                $packet[] = self::BIN_ESCAPE_CHAR;
                $packet[] = (($ackData[$i] ^ self::BIN_ESCAPE_CHAR) & 0xFF);
            } else {
                $packet[] = $ackData[$i];
            }
        }
        $packet[] = self::BIN_FLAG_CHAR;

        return $packet;
    }

    public static function binFrameFormatCheck(&$binFrame)
    {
        $bEscape = false;
        $dataLen = 0;
        for ($i = 0; $i < count($binFrame); ++$i) {
            if ($bEscape) {
                $bEscape = false;
                $binFrame[$dataLen++] = ($binFrame[$i] ^ self::BIN_ESCAPE_CHAR);
            } else {
                if ($binFrame[$i] == self::BIN_ESCAPE_CHAR) {
                    $bEscape = true;
                } else {
                    $binFrame[$dataLen++] = $binFrame[$i];
                }
            }
        }
        if (self::getCrc16Value($binFrame, $dataLen) != 0) {
            return 0;
        }

        return $dataLen - 2;
    }

    // CRC-CCITT (XModem)
    public static function getCrc16Value($dat, $length)
    {
        $crc_table = [
        0x0000, 0x1021, 0x2042, 0x3063, 0x4084, 0x50A5, 0x60C6, 0x70E7,
        0x8108, 0x9129, 0xA14A, 0xB16B, 0xC18C, 0xD1AD, 0xE1CE, 0xF1EF,
        0x1231, 0x0210, 0x3273, 0x2252, 0x52B5, 0x4294, 0x72F7, 0x62D6,
        0x9339, 0x8318, 0xB37B, 0xA35A, 0xD3BD, 0xC39C, 0xF3FF, 0xE3DE,
        0x2462, 0x3443, 0x0420, 0x1401, 0x64E6, 0x74C7, 0x44A4, 0x5485,
        0xA56A, 0xB54B, 0x8528, 0x9509, 0xE5EE, 0xF5CF, 0xC5AC, 0xD58D,
        0x3653, 0x2672, 0x1611, 0x0630, 0x76D7, 0x66F6, 0x5695, 0x46B4,
        0xB75B, 0xA77A, 0x9719, 0x8738, 0xF7DF, 0xE7FE, 0xD79D, 0xC7BC,
        0x48C4, 0x58E5, 0x6886, 0x78A7, 0x0840, 0x1861, 0x2802, 0x3823,
        0xC9CC, 0xD9ED, 0xE98E, 0xF9AF, 0x8948, 0x9969, 0xA90A, 0xB92B,
        0x5AF5, 0x4AD4, 0x7AB7, 0x6A96, 0x1A71, 0x0A50, 0x3A33, 0x2A12,
        0xDBFD, 0xCBDC, 0xFBBF, 0xEB9E, 0x9B79, 0x8B58, 0xBB3B, 0xAB1A,
        0x6CA6, 0x7C87, 0x4CE4, 0x5CC5, 0x2C22, 0x3C03, 0x0C60, 0x1C41,
        0xEDAE, 0xFD8F, 0xCDEC, 0xDDCD, 0xAD2A, 0xBD0B, 0x8D68, 0x9D49,
        0x7E97, 0x6EB6, 0x5ED5, 0x4EF4, 0x3E13, 0x2E32, 0x1E51, 0x0E70,
        0xFF9F, 0xEFBE, 0xDFDD, 0xCFFC, 0xBF1B, 0xAF3A, 0x9F59, 0x8F78,
        0x9188, 0x81A9, 0xB1CA, 0xA1EB, 0xD10C, 0xC12D, 0xF14E, 0xE16F,
        0x1080, 0x00A1, 0x30C2, 0x20E3, 0x5004, 0x4025, 0x7046, 0x6067,
        0x83B9, 0x9398, 0xA3FB, 0xB3DA, 0xC33D, 0xD31C, 0xE37F, 0xF35E,
        0x02B1, 0x1290, 0x22F3, 0x32D2, 0x4235, 0x5214, 0x6277, 0x7256,
        0xB5EA, 0xA5CB, 0x95A8, 0x8589, 0xF56E, 0xE54F, 0xD52C, 0xC50D,
        0x34E2, 0x24C3, 0x14A0, 0x0481, 0x7466, 0x6447, 0x5424, 0x4405,
        0xA7DB, 0xB7FA, 0x8799, 0x97B8, 0xE75F, 0xF77E, 0xC71D, 0xD73C,
        0x26D3, 0x36F2, 0x0691, 0x16B0, 0x6657, 0x7676, 0x4615, 0x5634,
        0xD94C, 0xC96D, 0xF90E, 0xE92F, 0x99C8, 0x89E9, 0xB98A, 0xA9AB,
        0x5844, 0x4865, 0x7806, 0x6827, 0x18C0, 0x08E1, 0x3882, 0x28A3,
        0xCB7D, 0xDB5C, 0xEB3F, 0xFB1E, 0x8BF9, 0x9BD8, 0xABBB, 0xBB9A,
        0x4A75, 0x5A54, 0x6A37, 0x7A16, 0x0AF1, 0x1AD0, 0x2AB3, 0x3A92,
        0xFD2E, 0xED0F, 0xDD6C, 0xCD4D, 0xBDAA, 0xAD8B, 0x9DE8, 0x8DC9,
        0x7C26, 0x6C07, 0x5C64, 0x4C45, 0x3CA2, 0x2C83, 0x1CE0, 0x0CC1,
        0xEF1F, 0xFF3E, 0xCF5D, 0xDF7C, 0xAF9B, 0xBFBA, 0x8FD9, 0x9FF8,
        0x6E17, 0x7E36, 0x4E55, 0x5E74, 0x2E93, 0x3EB2, 0x0ED1, 0x1EF0,
        ];
        $crc = 0;

        for ($i = 0; $i < $length; ++$i) {
            $da = $crc >> 8;
            $da &= 0x00000000000000FF;
            $crc <<= 8;
            $crc &= 0x000000000000FFFF;
            $crc ^= $crc_table[$da ^ $dat[$i]];
        }

        return $crc;
    }

    public static function hexStringDec($hexArray)
    {
        if (!ctype_xdigit($hexArray[0])) {
            return $hexArray;
        }
        foreach ($hexArray as $i) {
            $binData[] = hexdec($i);
        }

        return $binData;
    }

    public static function dataBinAcknowledgement($data)
    {
        $crcData = self::getCrc16Value($data, count($data));
        $ackData = [
        self::PROTOCOL_VERSION,
        self::ACK_FLAG,
        (($crcData >> 8) & 0xFF),
        $crcData & 0xFF,
        ];
        $crcFrame = self::getCrc16Value($ackData, count($ackData));
        $ackData[] = ($crcFrame >> 8) & 0x00FF;
        $ackData[] = $crcFrame & 0x00FF;

        return self::binDataPacket($ackData);
    }

    public static function binFrameDecode($dat, $len)
    {
        if ($len < 10) {
            // echo "Pacote menor que 10\n";
            return;
        }
        $pos = 0;
        if ($dat[$pos] != self::PROTOCOL_VERSION) {
            // echo "Can not support protocol version: ". $dat[$pos];
            return;
        }
        ++$pos;
        if ($dat[$pos] != 0x01) {
            // echo "Can not support frame NO: ". $dat[$pos];
            return;
        }
        ++$pos;
        $deviceID = '';
        for ($i = 0; $i < 8; ++$i) {
            if ($i == 0) {
                $deviceID .= $dat[$pos++];
            } else {
                $deviceID .= sprintf('%02X', $dat[$pos++]);
            }
        }
        $array['serial'] = $deviceID;

        $timeSeconds = self::readUint32($dat, $pos);
        $pos += 4;
        $fix3D = ($timeSeconds & 0x80000000) != 0;
        $timeSeconds &= 0x7FFFFFFF;
        if ($timeSeconds != 0) {
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', '1999-12-31 22:00:00');
            $date->add(new \DateInterval('PT' . $timeSeconds . 'S'));

            $array['date'] = $date->format('Y-m-d H:i:s');
        }

        while ($pos < $len - 2) {
            $infoId = $dat[$pos++];

            switch ($infoId) {
                case 1:// GPS e.g. Latitude, Longitude, Velocidade, Angulo, HDOP
                    $infoLen = $dat[$pos++];
                    $infoData = array_slice($dat, $pos, $infoLen);
                    // $array = array_merge($array, self::gpsDecodeFromBinary($infoData, $fix3D));
                    $array['gps'] = self::gpsDecodeFromBinary($infoData, $fix3D);
                    break;
                case 3:// STT e.g. "Moving, ACC, Engine, "
                    $infoLen = $dat[$pos++];
                    $infoData = array_slice($dat, $pos, $infoLen);

                    $status = self::sttDecodeFromBinary($infoData);

                    if ($status) {
                        $array['obd_status'] = $status;
                    }
                    break;
                case 4:// MGR e.g. um número inteiro
                    $infoLen = $dat[$pos++];
                    $infoData = array_slice($dat, $pos, $infoLen);
                    $array['distance'] = [
                        'value' => self::mgrDecodeFromBinary($infoData),
                        'unit' => 'meters',
                    ];
                    break;
                case 7:// OBD e.g. ECT, RPM, SPD, Combustível*
                    $infoLen = $dat[$pos++];
                    $infoData = array_slice($dat, $pos, $infoLen);
                    $array = array_merge($array, self::obdDecodeFromBinary($infoData));
                    $array['obd'] = self::obdDecodeFromBinary($infoData);
                    break;
                case 8:// FUL( vem um numero decima, pra converter pra litros deve-se usar Valor/10/AFR/Densidade(g/l)
                    $infoLen = $dat[$pos++];
                    $infoLen &= 0x0F;
                    $algorithm = ($infoLen >> 4) & 0x0F;
                    $infoData = array_slice($dat, $pos, $infoLen);
                    $array['fuel_consumption'] = self::fulDecodeFromBinary($infoData, $algorithm);
                    break;
                case 9:// OAL
                    $infoLen = $dat[$pos++];
                    $infoData = array_slice($dat, $pos, $infoLen);
                    // $array = array_merge($array, self::obdDecodeFromBinary($infoData));
                    $array['obd_errors'] = self::obdDecodeFromBinary($infoData)['errors'];
                    $array['obd_errors'] = explode($array['obd_errors'], ',');
                    break;
                case 10:
                case 0x0A:// HDB e.g. Rough braking
                    $infoLen = $dat[$pos++];
                    $infoData = array_slice($dat, $pos, $infoLen);
                    $alarm = self::hdbDecodeFromBinary($infoData);

                    if ($alarm) {
                        $array['alerts'] = $alarm;
                    }
                    break;
                case 11:
                case 0x0B:// CAN--j1939 data
                dump('j1939');
                    $infoLen = $dat[$pos++];
                    $infoLen = $infoLen * 256 + $dat[$pos++];
                    $infoData = array_slice($dat, $pos, $infoLen);
                    $can = self::canDecodeFromBinary($infoData);
                    if ($can) {
                        $array['can'] = $can;
                    }
                    break;
                case 12:
                case 0x0C:// HVD--j1708 data
                    $infoLen = $dat[$pos++];
                    $infoData = array_slice($dat, $pos, $infoLen);
                    self::hvdDecodeFromBinary($infoData);
                    break;
                case 13:
                case 0x0D:// VIN e.g. chassi
                    $infoLen = $dat[$pos++];
                    $infoData = array_slice($dat, $pos, $infoLen);
                    $array['chassi'] = self::vinDecodeFromBinary($infoData);
                    break;
                case 15:
                case 0x0F:// EGT e.g. um número inteiro*
                    $infoLen = $dat[$pos++];
                    $infoData = array_slice($dat, $pos, $infoLen);
                    $array['time'] = self::egtDecodeFromBinary($infoData);
                    break;
                default:
                    $infoLen = $dat[$pos++];
                    break;
            }
            $pos += $infoLen;
        }

        return $array;
    }

    public static function readInt32($dat, $pos)
    {
        $val = self::readUint32($dat, $pos);
        if ($val & 0x80000000) {
            $val = ~$val & 0x7FFFFFFF;
            $val = -($val + 1);
        }

        return $val;
    }

    public static function readUint32($dat, $pos)
    {
        if ($pos + 4 > count($dat)) {
            return 0;
        }
        $val = 0;
        $aux = '';
        for ($i = 0; $i < 4; ++$i) {
            $val = ($val << 8) + $dat[$pos + $i];
        }

        return $val;
    }

    // public static function readUint32($dat, $pos, $big_end)
    // {
    //     if ($pos + 4 > count($dat)) {
    //         return 0;
    //     }
    //     $val = 0;
    //     if ($big_end) {
    //         for ($i = 0; $i < 4; ++$i) {
    //             $val = ($val << 8) + $dat[$pos + $i];
    //         }
    //     }
    //     else {
    //         for ($i = 0; $i < 4; ++$i) {
    //             $val = ($val << 8) + $dat[$pos + 3 - $i];
    //         }
    //     }

    //     return $val;
    // }

    public static function readUint16($dat, $pos)
    {
        if ($pos + 2 > count($dat)) {
            return 0;
        }
        $val = 0;
        for ($i = 0; $i < 2; ++$i) {
            $val = ($val << 8) + $dat[$pos + $i];
        }

        return $val;
    }

    // public static function readUint16($dat, $pos, $big_end)
    // {
    //     if ($pos + 2 > count($dat)) {
    //         return 0;
    //     }
    //     $val = 0;
    //     if ($big_end) {
    //         for ($i = 0; $i < 2; ++$i) {
    //             $val = ($val << 8) + $dat[$pos + $i];
    //         }
    //     }
    //     else {
    //         for ($i = 0; $i < 2; ++$i) {
    //             $val = ($val << 8) + $dat[$pos + 1 - $i];
    //         }
    //     }

    //     return $val;
    // }

    public static function gpsDecodeFromBinary($info, $is3d)
    {
        if (count($info) != 14) {
            return;
        }

        $array['latitude'] = self::readInt32($info, 0) / 1000000;
        $array['longitude'] = self::readInt32($info, 4) / 1000000;
        $array['speed'] = [
            'value' => self::readUInt16($info, 8),
            'unit' => 'km/h',
            ];
        $array['angle'] = [
            'value' => self::readUInt16($info, 10),
            'unit' => 'degrees',
            ];
        $array['hdop'] = ['value' => self::readUInt16($info, 12) / 100, 'description' => 'range 0 - 99.99 valor que representa perda de exatidão da coordenada conforme o valor aumenta'];

        return $array;
    }

    public static function mgrDecodeFromBinary($info)
    {
        if (count($info) != 4) {
            return;
        }

        return self::readUint32($info, 0);
    }

    public static function obdDecodeFromBinary($info)
    {
        return self::obdDataDecode($info);
    }

    public static function obdDataDecode($obdData)
    {
        $pos = 0;
        $array = [];
        while ($pos < count($obdData)) {
            $len = ($obdData[$pos] >> 4) & 0x0F;
            if ($len + $pos > count($obdData)) {
                break;
            }
            if ($len < 3 || $len > 8) {
                $pos += $len;
                continue;
            }
            $service = ($obdData[$pos] & 0x0F);
            switch ($service) {
                case 1:
                case 2:
                    $pid = $obdData[$pos + 1];
                    $pidValue = array_slice($obdData, $pos + 2, $len - 2);
                    $atual = self::obdService0102Decode($pidValue, $service, $pid);
                    if (count($atual) > 0) {
                        $array = array_merge($array, $atual);
                    }
                    break;
                case 3:
                    $value = array_slice($obdData, $pos + 1, $len - 1);
                    $errors = self::obdService03Decode($value);
                    if ($errors) {
                        $array['errors'] = $errors;
                    }
                    break;
                case 9:
                    $pid = $obdData[$pos + 1];
                    $pidValue = array_slice($obdData, $pos + 2, $len - 2);
                    $atual = self::obdService09Decode($pidValue, $pid);
                    if (array_key_exists('VIN', $array)) {
                        $array['VIN'] .= $atual;
                    } else {
                        $array['VIN'] = $atual;
                    }
                    break;
                default:
                    break;
            }
            $pos += $len;
        }

        return $array;
    }

    public static function obdService09Decode($info, $pid)
    {
        // se $pid nao for do vin, não retorna nada
        if ($pid != 2) {
            return;
        }
        $hex = '';
        for ($i = 1; $i < count($info); ++$i) {
            if ($info[$i] == 255) {
                $hex .= '*';
            } else {
                $hex .= chr($info[$i]);
            }
        }

        return $hex;
    }

    public static function obdService0102Decode($value, $service, $pid)
    {
        $array = [];
        switch ($pid) {
            case 0x04:
                if (count($value) != 1) {
                    return;
                }
                $clv = ($value[0]) * 100 / 255;
                $array['load'] = $clv;
                break;
            case 0x05:
                if (count($value) != 1) {
                    return;
                }
                $ect = $value[0];
                $ect -= 40;

                $array['ect'] = [
                    'value' => $ect,
                    'unit' => 'degrees',
                    'description' => 'leitura do sensor de temperatura do liquido de arrefecimento do motor',
                ];
                break;
            case 0x0B:
                if (count($value) != 1) {
                    return;
                }
                $array['map'] = [
                    'value' => $value[0],
                    'unit' => 'kPa',
                    'description' => 'leitura do sensor de pressão absoluta presente no coletor de admissão',
                ];
                break;
            case 0x0C:
                if (count($value) != 2) {
                    return;
                }
                $rpm = ($value[0] * 256 + $value[1]) / 4;
                $array['rpm'] = $rpm;
                break;
            case 0x0D:
                if (count($value) != 1) {
                    return;
                }
                $speed = $value[0];
                $array['spd'] = [
                    'value' => $speed,
                    'unit' => 'km/h',
                ];
                break;
            case 0x0F:
                if (count($value) != 1) {
                    return;
                }
                $iat = $value[0];
                $iat -= 40;
                $array['iat'] = [
                    'value' => $iat,
                    'unit' => 'celsius',
                    'description' => 'leitura do sensor de temperatura do ar da admissão',
                ];
                break;
            case 0x10:
                if (count($value) != 2) {
                    return;
                }
                $maf = (($value[0] * 256 + $value[1])) / 100;
                $array['maf'] = [
                    'value' => $maf,
                    'unit' => 'g/s',
                    'description' => 'leitura do ensor de fluxo de massa usado para determinar a taxa de fluxo de massa de ar que entra no motor',
                    ];
                break;
            case 0x11:
                if (count($value) != 1) {
                    return;
                }
                $position = ($value[0]) * 100 / 255;
                $array['acceleration'] = $position;
                break;
            case 0x1F: // run time since engine start
                if (count($value) != 2) {
                    return;
                }
                $position = $value[0] * 256 + $value[1];
                $array['time'] = $position;
                break;
            case 0x2F:
                if (count($value) != 1) {
                    return;
                }
                $percent = $value[0] / 255;
                $array['fuel'] = round($percent, 3);
                break;
            case 0x4F:
                if (count($value) != 4) {
                    return;
                }
                $array['MAXOXILAMBDA'] = $value[0] ? $value[0] / 65535 : 2 / 65535;
                $array['MAXOXIVOLT'] = $value[1] ? $value[1] / 65535 : 8 / 65535;
                $array['MAXOXICURRENT'] = $value[2] ? $value[2] / 32768 : 128 / 32768;
                $array['MAXMAP'] = $value[3] ? $value[3] * 10 / 255 : 1;
                break;
        }

        return $array;
    }

    public static function obdService03Decode($value)
    {
        $errors = '';

        $offset = (count($value) % 2 != 0) ? 1 : 0;
        $dtcChars = ['P', 'C', 'B', 'U'];
        for ($i = 0; $i < count($value) / 2; ++$i) {
            $dtcA = $value[2 * $i + $offset];
            $dtcB = $value[2 * $i + $offset + 1];
            if ($dtcA == 0 && $dtcB == 0) {
                continue;
            }
            $errors .= sprintf(
                '%s%02d%02X,',
                $dtcChars[(($dtcA >> 6) & 0x03)],
                ($dtcA & 0x3F),
                $dtcB
            );
        }

        return substr($errors, 0, -1);
    }

    public static function fulDecodeFromBinary($info, $id)
    {
        if (count($info) != 4) {
            return;
        }
        $fuel = self::readUint32($info, 0);

        return $fuel;
    }

    public static function hdbDecodeFromBinary($info)
    {
        if (count($info) != 1) {
            return;
        }

        $hdb = $info[0] & 0x000F;
        if ($hdb == 0) {
            return;
        }
        // printf("Driver Behavior: \r\n");
        $infoBehavior = [
            'Rapid acceleration',
            'Rough braking',
            'Harsh course',
            'No warmup',
            'Long idle',
            'Fatigue driving',
            'Rough terrain',
            'High RPM',
        ];
        $alarm = '';
        for ($i = 0; $i < 8; ++$i) {
            $bitMask = (0x0001 << $i);
            if (($hdb & $bitMask) == 0) {
                continue;
            }
            $alarm .= $infoBehavior[$i] . ', ';
        }

        return $alarm;
    }

    public static function canDecodeFromBinary($info)
    {
        printf("j1939: \r\n");

        return self::decodej1939($info);
    }

    public static function decodej1939($candata)
    {
        $pos = 0;
        $result = [];
        while ($pos < count($candata)) {
            $len = $candata[$pos];
            if ($len + $pos + 1 > count($candata)) {
                break;
            }
            if ($len < 4 || $candata[$pos + 1] != 0) {
                $pos += $len + 1;
                continue;
            }

            $pgn = self::readUint16($candata, $pos + 2);
            $value = array_slice($candata, $pos + 4, $len - 3);
            array_push($result, self::j1939PgnDecode($value, $pgn));
            $pos += $len + 1;
        }

        foreach ($result as $key => $pgns) {
            $result[$pgns[1]['pgn']] = $pgns;
            unset($result[$key]);
        }

        foreach ($result as $key => $pgns) {
            foreach ($pgns as $key2 => $values) {
                foreach ($values as $key3 => $value) {
                    if ($key3 == 'pgn') {
                        continue;
                    }
                    if (!isset($result[$key][$key3])) {
                        $result[$key][$key3] = $value;
                    }
                }
                unset($result[$key][$key2]);
            }
        }

        return $result;
    }

    public static function sttDecodeFromBinary($info)
    {
        if (count($info) != 4) {
            return;
        }
        $iStatus = self::readUint16($info, 0);
        $iAlarm = self::readUint16($info, 2);
        $infoStatus = [
            'Power cut',
            'Moving',
            'Over speed',
            'Jamming',
            'Geo-fence alarming',
            'Immobilizer',
            'ACC',
            'Input high level',
            'Input mid level',
            'Engine',
            'Panic',
            'OBD alarm',
            'Course rapid change',
            'Speed rapid change',
            'Roaming',
            'Inter roaming',
        ];
        $infoAlarm = [
            'Power cut',
            'Moved',
            'Over speed',
            'Jamming',
            'Geo-fence',
            'Towing',
            'Reserved',
            'Input low',
            'Input high',
            'Reserved',
            'Panic',
            'OBD',
            'Reserved',
            'Reserved',
            'Accident',
            'Battery low',
        ];

        $status = [];
        for ($i = 0; $i < 16; ++$i) {
            $bitMask = (0x0001 << $i);
            if ($iStatus & $bitMask) {
                $status[] = $infoStatus[$i];
            }
        }
        for ($i = 0; $i < 16; ++$i) {
            $bitMask = (0x0001 << $i);
            if ($iAlarm & $bitMask) {
                $status[] = $infoAlarm[$i];
            }
        }

        return $status;
    }

    public static function j1939ValRead($dat, $parse)
    {
        if ($parse[0] + $parse[2] > count($dat)) {
            return 0xFFFFFFFF;
        }

        $val = 0;
        for ($i = $parse[2] - 1; $i >= 0; --$i) {
            $val = ($val << 8) + $dat[$parse[0] + $i];
        }

        $val = ($val >> $parse[1]);

        $val &= $parse[3];

        return $val;
    }

    public static function j1939PgnDecode($value, $pgn)
    {
        $hex = '';
        for ($i = 0; $i < count($value); ++$i) {
            $hex .= sprintf('%02X ', $value[$i]);
        }
        printf(
            "\tPGN%d: %s--- ",
            $pgn,
            $hex
        );
        // "val_op" => array("coeff", "offset", "range" => array("min", "max"))
        // "parse" => array("index", "shift", "length", "mask")
        $pgns_info = [
            'PGN61444' => [
                'id' => 61444,
                'name' => '(R) Electronic Engine Controller 1',
                'length' => 8,
                'spns' => [
                    'SPN899' => [
                        'name' => 'Engine Torque Mode',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 15]],
                        'parse' => [0, 0, 1, 0x0F],
                    ],
                    'SPN4154' => [
                        'name' => '(R) Actual Engine - Percent Torque High Resolution',
                        'unit' => '%',
                        'val_op' => [0.125, 0, [0, 0.875]],
                        'parse' => [0, 4, 1, 0x0F],
                    ],
                    'SPN512' => [
                        'name' => 'Drivers Demand Engine',
                        'unit' => '%',
                        'val_op' => [1, -125, [-125, 125]],
                        'parse' => [1, 0, 1, 0xFF],
                    ],
                    'SPN513' => [
                        'name' => 'Actual Engine',
                        'unit' => '%',
                        'val_op' => [1, -125, [-125, 125]],
                        'parse' => [2, 0, 1, 0xFF],
                    ],
                    'SPN190' => [
                        'name' => 'Engine Speed',
                        'unit' => 'rpm',
                        'val_op' => [0.125, 0, [0, 8031.875]],
                        'parse' => [3, 0, 2, 0xFFFF],
                    ],
                    'SPN1483' => [
                        'name' => 'Source Address of Controlling Device for Engine Control',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 255]],
                        'parse' => [5, 0, 1, 0xFF],
                    ],
                    'SPN1675' => [
                        'name' => 'Engine Starter Mode',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 15]],
                        'parse' => [6, 0, 1, 0x0F],
                        'val_desc' => [
                            [0, 'start not requested'],
                            [1, 'starter active, gear not engaged'],
                            [2, 'starter active, gear engaged'],
                            [3, 'start finished; starter not active after having been actively engaged (after 50ms mode goes to 0000)'],
                            [4, 'starter inhibited due to engine already running'],
                            [5, 'starter inhibited due to engine not ready for start (preheating)'],
                            [6, 'starter inhibited due to driveline engaged or other transmission inhibit'],
                            [7, 'starter inhibited due to active immobilizer'],
                            [8, 'starter inhibited due to starter over-temp'],
                            [12, 'starter inhibited - reason unknown'],
                            [13, 'error (legacy implementation only, use 1110)'],
                            [14, 'error'],
                            [15, 'not available'],
                        ],
                    ],
                    'SPN2432' => [
                        'name' => 'Engine Demand – Percent Torque',
                        'unit' => '%',
                        'val_op' => [1, -125, [-125, 125]],
                        'parse' => [7, 0, 1, 0xFF],
                    ],
                ],
            ],
            'PGN65132' => [
                'id' => 65132,
                'name' => 'Tachograph',
                'length' => 8,
                'spns' => [
                    'SPN1612' => [
                        'name' => 'Driver 1 working state',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 6]],
                        'parse' => [0, 0, 1, 0x07],
                        'val_desc' => [
                            [0, 'Rest - sleeping'],
                            [1, 'Driver available – short break'],
                            [2, 'Work – loading, unloading, working in an office'],
                            [3, 'Drive – behind wheel'],
                            [6, 'Error'],
                        ],
                    ],
                    'SPN1613' => [
                        'name' => 'Driver 2 working state',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 6]],
                        'parse' => [0, 3, 1, 0x07],
                        'val_desc' => [
                            [0, 'Rest - sleeping'],
                            [1, 'Driver available – short break'],
                            [2, 'Work – loading, unloading, working in an office'],
                            [3, 'Drive – behind wheel'],
                            [6, 'Error'],
                        ],
                    ],
                    'SPN1611' => [
                        'name' => 'Vehicle motion',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [0, 6, 1, 0x03],
                        'val_desc' => [
                            [0, 'Vehicle motion not detected'],
                            [1, 'Vehicle motion detected'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN1617' => [
                        'name' => 'Driver 1 Time Related States',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 14]],
                        'parse' => [1, 0, 1, 0x0F],
                        'val_desc' => [
                            [0, 'Normal/No limits reached'],
                            [1, "Limit #1 ' 15 min before 4 ½ h"],
                            [2, "Limit #2 ' 4 ½ h reached"],
                            [3, "Limit #3 ' 15 min before 9 h"],
                            [4, "Limit #4 ' 9 h reached"],
                            [5, "Limit #5 ' 15 min before 16 h"],
                            [6, "Limit #6 ' 16 h reached"],
                            [13, 'Other'],
                            [14, 'Error'],
                        ],
                    ],
                    'SPN1615' => [
                        'name' => 'Driver card, driver 1',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [1, 4, 1, 0x03],
                        'val_desc' => [
                            [0, 'Driver card not present'],
                            [1, 'Driver card present'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN1614' => [
                        'name' => 'Vehicle Overspeed',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [1, 6, 1, 0x03],
                        'val_desc' => [
                            [0, 'No overspeed'],
                            [1, 'Overspeed'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN1618' => [
                        'name' => 'Driver 2 Time Related States',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 14]],
                        'parse' => [2, 0, 1, 0x0F],
                        'val_desc' => [
                            [0, 'Normal/No limits reached'],
                            [1, "Limit #1 ' 15 min before 4 ½ h"],
                            [2, "Limit #2 ' 4 ½ h reached"],
                            [3, "Limit #3 ' 15 min before 9 h"],
                            [4, "Limit #4 ' 9 h reached"],
                            [5, "Limit #5 ' 15 min before 16 h"],
                            [6, "Limit #6 ' 16 h reached"],
                            [13, 'Other'],
                            [14, 'Error'],
                        ],
                    ],
                    'SPN1616' => [
                        'name' => 'Driver card, driver 2',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [2, 4, 1, 0x03],
                        'val_desc' => [
                            [0, 'Driver card not present'],
                            [1, 'Driver card present'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN1622' => [
                        'name' => 'System event',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [3, 0, 1, 0x03],
                        'val_desc' => [
                            [0, 'No tachograph event'],
                            [1, 'Tachograph event'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN1621' => [
                        'name' => 'Handling information',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [3, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'No handling information'],
                            [1, 'Handling information'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN1620' => [
                        'name' => 'Tachograph performance',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [3, 4, 1, 0x03],
                        'val_desc' => [
                            [0, 'Normal performance'],
                            [1, 'Performance analysis'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN1619' => [
                        'name' => 'Direction indicator',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [3, 6, 1, 0x03],
                        'val_desc' => [
                            [0, 'Forward'],
                            [1, 'Reverse'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN1623' => [
                        'name' => 'Tachograph output shaft speed',
                        'unit' => 'rpm',
                        'val_op' => [0.125, 0, [0, 8031.875]],
                        'parse' => [4, 0, 2, 0xFFFF],
                    ],
                    'SPN1624' => [
                        'name' => 'Tachograph vehicle speed',
                        'unit' => 'km/h',
                        'val_op' => [0.00390625, 0, [0, 250.996]],
                        'parse' => [6, 0, 2, 0xFFFF],
                    ],
                ],
            ],
            'PGN65217' => [
                'id' => 65217,
                'name' => 'High Resolution Vehicle Distance',
                'length' => 8,
                'spns' => [
                    'SPN917' => [
                        'name' => 'High Resolution Total Vehicle Distance',
                        'unit' => 'km',
                        'val_op' => [0.005, 0, [0, 21055406]],
                        'parse' => [0, 0, 4, 0xFFFFFFFF],
                    ],
                    'SPN918' => [
                        'name' => 'High Resolution Trip Distance',
                        'unit' => 'km',
                        'val_op' => [0.005, 0, [0, 21055406]],
                        'parse' => [4, 0, 4, 0xFFFFFFFF],
                    ],
                ],
            ],
            'PGN65248' => [
                'id' => 65248,
                'name' => 'Vehicle Distance',
                'length' => 8,
                'spns' => [
                    'SPN244' => [
                        'name' => 'Trip Distance',
                        'unit' => 'km',
                        'val_op' => [0.125, 0, [0, 526385151.9]],
                        'parse' => [0, 0, 4, 0xFFFFFFFF],
                    ],
                    'SPN245' => [
                        'name' => 'Total Vehicle Distance',
                        'unit' => 'km',
                        'val_op' => [0.125, 0, [0, 526385151.9]],
                        'parse' => [4, 0, 4, 0xFFFFFFFF],
                    ],
                ],
            ],
            'PGN65262' => [
                'id' => 65262,
                'name' => 'Engine Temperature 1',
                'length' => 8,
                'spns' => [
                    'SPN110' => [
                        'name' => 'Engine Coolant Temperature',
                        'unit' => 'deg C',
                        'val_op' => [1, -40, [-40, 210]],
                        'parse' => [0, 0, 1, 0xFF],
                    ],
                    'SPN174' => [
                        'name' => 'Engine Fuel Temperature 1',
                        'unit' => 'deg C',
                        'val_op' => [1, -40, [-40, 210]],
                        'parse' => [1, 0, 1, 0xFF],
                    ],
                    'SPN175' => [
                        'name' => 'Engine Oil Temperature 1',
                        'unit' => 'deg C',
                        'val_op' => [0.03125, -273, [-273, 1735]],
                        'parse' => [2, 0, 2, 0xFFFF],
                    ],
                    'SPN176' => [
                        'name' => 'Engine Turbocharger Oil Temperature',
                        'unit' => 'deg C',
                        'val_op' => [0.03125, -273, [-273, 1735]],
                        'parse' => [4, 0, 2, 0xFFFF],
                    ],
                    'SPN52' => [
                        'name' => 'Engine Intercooler Temperature',
                        'unit' => 'deg C',
                        'val_op' => [1, -40, [-40, 210]],
                        'parse' => [6, 0, 1, 0xFF],
                    ],
                    'SPN1134' => [
                        'name' => 'Engine Intercooler Thermostat Opening',
                        'unit' => '%',
                        'val_op' => [0.4, 0, [0, 100]],
                        'parse' => [7, 0, 1, 0xFF],
                    ],
                ],
            ],
            'PGN65253' => [
                'id' => 65253,
                'name' => 'Engine Hours, Revolutions',
                'length' => 8,
                'spns' => [
                    'SPN247' => [
                        'name' => 'Engine Total Hours of Operation',
                        'unit' => 'hr',
                        'val_op' => [0.05, 0, [0, 210554060.75]],
                        'parse' => [0, 0, 4, 0xFFFFFFFF],
                    ],
                    'SPN249' => [
                        'name' => 'Engine Total Revolutions',
                        'unit' => 'r',
                        'val_op' => [1000, 0, [0, 4211081215000]],
                        'parse' => [4, 0, 4, 0xFFFFFFFF],
                    ],
                ],
            ],
            'PGN65256' => [
                'id' => 65256,
                'name' => 'Vehicle Direction/Speed',
                'length' => 8,
                'spns' => [
                    'SPN165' => [
                        'name' => 'Compass Bearing',
                        'unit' => 'deg',
                        'val_op' => [0.0078125, 0, [0, 501.99]],
                        'parse' => [0, 0, 2, 0xFFFF],
                    ],
                    'SPN517' => [
                        'name' => 'Navigation-Based Vehicle Speed',
                        'unit' => 'km/h',
                        'val_op' => [0.00390625, 0, [0, 250.996]],
                        'parse' => [2, 0, 2, 0xFFFF],
                    ],
                    'SPN583' => [
                        'name' => 'Pitch',
                        'unit' => 'deg',
                        'val_op' => [-200, 0, [-200, 301.99]],
                        'parse' => [4, 0, 2, 0xFFFF],
                    ],
                    'SPN580' => [
                        'name' => 'Altitude',
                        'unit' => 'm',
                        'val_op' => [0.125, -2500, [-2500, 5531.875]],
                        'parse' => [6, 0, 2, 0xFFFF],
                    ],
                ],
            ],
            'PGN65257' => [
                'id' => 65257,
                'name' => 'Fuel Consumption (Liquid)',
                'length' => 8,
                'spns' => [
                    'SPN182' => [
                        'name' => 'Engine Trip Fuel',
                        'unit' => 'L',
                        'val_op' => [0.5, 0, [0, 2105540607.5]],
                        'parse' => [0, 0, 4, 0xFFFFFFFF],
                    ],
                    'SPN250' => [
                        'name' => 'Engine Total Fuel Used',
                        'unit' => 'L',
                        'val_op' => [0.5, 0, [0, 2105540607.5]],
                        'parse' => [4, 0, 4, 0xFFFFFFFF],
                    ],
                ],
            ],
            'PGN61443' => [
                'id' => 61443,
                'name' => 'Fuel Consumption (Liquid)',
                'length' => 8,
                'spns' => [
                    'SPN558' => [
                        'name' => 'Accelerator Pedal 1 Low Idle Switch',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [0, 0, 1, 0x03],
                        'val_desc' => [
                            [0, 'Accelerator pedal 1 not in low idle condition'],
                            [1, 'Accelerator pedal 1 in low idle condition'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN559' => [
                        'name' => 'Accelerator Pedal Kickdown Switch',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [0, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Kickdown passive'],
                            [1, 'Kickdown active'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN1437' => [
                        'name' => 'Road Speed Limit Status',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [0, 4, 1, 0x03],
                        'val_desc' => [
                            [0, 'Active'],
                            [1, 'Not Active'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN2970' => [
                        'name' => 'Accelerator Pedal 2 Low Idle Switch',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [0, 6, 1, 0x03],
                        'val_desc' => [
                            [0, 'Accelerator pedal 2 not in low idle condition'],
                            [1, 'Accelerator pedal 2 in low idle condition'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN91' => [
                        'name' => 'Accelerator Pedal Position 1',
                        'unit' => '%',
                        'val_op' => [0.4, 0, [0, 100]],
                        'parse' => [1, 0, 1, 0xFF],
                    ],
                    'SPN92' => [
                        'name' => 'Engine Percent Load At Current Speed',
                        'unit' => '%',
                        'val_op' => [1, 0, [0, 250]],
                        'parse' => [2, 0, 1, 0xFF],
                    ],
                    'SPN974' => [
                        'name' => 'Remote Accelerator Pedal Position',
                        'unit' => '%',
                        'val_op' => [0.4, 0, [0, 100]],
                        'parse' => [3, 0, 1, 0xFF],
                    ],
                    'SPN29' => [
                        'name' => 'Accelerator Pedal Position 2',
                        'unit' => '%',
                        'val_op' => [0.4, 0, [0, 100]],
                        'parse' => [4, 0, 1, 0xFF],
                    ],
                    'SPN2979' => [
                        'name' => 'Vehicle Acceleration Rate Limit Status',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [5, 0, 1, 0x03],
                        'val_desc' => [
                            [0, 'Limit not active'],
                            [1, 'Limit active'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN3357' => [
                        'name' => 'Actual Maximum Available Engine - Percent Torque',
                        'unit' => '%',
                        'val_op' => [0.4, 0, [0, 100]],
                        'parse' => [6, 0, 1, 0xFF],
                    ],
                ],
            ],
            'PGN65263' => [
                'id' => 65263,
                'name' => 'Engine Fluid Level/Pressure 1',
                'length' => 8,
                'spns' => [
                    'SPN94' => [
                        'name' => 'Engine Fuel Delivery Pressure',
                        'unit' => 'kPa',
                        'val_op' => [4, 0, [0, 1000]],
                        'parse' => [0, 0, 1, 0xFF],
                    ],
                    'SPN22' => [
                        'name' => 'Engine Extended Crankcase Blow-by Pressure',
                        'unit' => 'kPa',
                        'val_op' => [0.05, 0, [0, 12.5]],
                        'parse' => [1, 0, 1, 0xFF],
                    ],
                    'SPN98' => [
                        'name' => 'Engine Oil Level',
                        'unit' => '%',
                        'val_op' => [0.4, 0, [0, 100]],
                        'parse' => [2, 0, 1, 0xFF],
                    ],
                    'SPN100' => [
                        'name' => 'Engine Oil Pressure',
                        'unit' => 'kPa',
                        'val_op' => [4, 0, [0, 1000]],
                        'parse' => [3, 0, 1, 0xFF],
                    ],
                    'SPN101' => [
                        'name' => 'Engine Crankcase Pressure',
                        'unit' => 'kPa',
                        'val_op' => [0.0078125, -250, [-250, 251.99]],
                        'parse' => [4, 0, 2, 0xFFFF],
                    ],
                    'SPN109' => [
                        'name' => 'Engine Coolant Pressure',
                        'unit' => 'kPa',
                        'val_op' => [2, 0, [0, 500]],
                        'parse' => [6, 0, 1, 0xFF],
                    ],
                    'SPN111' => [
                        'name' => 'Engine Coolant Level',
                        'unit' => '%',
                        'val_op' => [0.4, 0, [0, 100]],
                        'parse' => [7, 0, 1, 0xFF],
                    ],
                ],
            ],
            'PGN65265' => [
                'id' => 65265,
                'name' => 'Cruise Control/Vehicle Speed',
                'length' => 8,
                'spns' => [
                    'SPN69' => [
                        'name' => 'Two Speed Axle Switch',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [0, 0, 1, 0x03],
                        'val_desc' => [
                            [0, 'Low speed range'],
                            [1, 'High speed range'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN70' => [
                        'name' => 'Parking Brake Switch',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [0, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Parking brake not set'],
                            [1, 'Parking brake set'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN1633' => [
                        'name' => 'Cruise Control Pause Switch',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [0, 4, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                            [2, 'Error Indicator'],
                        ],
                    ],
                    'SPN3807' => [
                        'name' => 'Park Brake Release Inhibit Request',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [0, 6, 1, 0x03],
                        'val_desc' => [
                            [0, 'Park Brake Release Inhibit not requested'],
                            [1, 'Park Brake Release Inhibit requested'],
                            [2, 'SAE reserved'],
                        ],
                    ],
                    'SPN84' => [
                        'name' => 'Wheel-Based Vehicle Speed',
                        'unit' => 'km/h',
                        'val_op' => [0.00390625, 0, [0, 250.996]],
                        'parse' => [1, 0, 2, 0xFFFF],
                    ],
                    'SPN595' => [
                        'name' => 'Cruise Control Active',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [3, 0, 1, 0x03],
                        'val_desc' => [
                            [0, 'Cruise control switched off'],
                            [1, 'Cruise control switched on'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN596' => [
                        'name' => 'Cruise Control Enable Switch',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [3, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Cruise control disabled'],
                            [1, 'Cruise control enabled'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN597' => [
                        'name' => 'Brake Switch',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [3, 4, 1, 0x03],
                        'val_desc' => [
                            [0, 'Brake pedal released'],
                            [1, 'Brake pedal depressed'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN598' => [
                        'name' => 'Clutch Switch',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [3, 6, 1, 0x03],
                        'val_desc' => [
                            [0, 'Clutch pedal released'],
                            [1, 'Clutch pedal depressed'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN599' => [
                        'name' => 'Cruise Control Set Switch',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [4, 0, 1, 0x03],
                        'val_desc' => [
                            [0, 'Cruise control activator not in the position "set"'],
                            [1, 'Cruise control activator in position "set"'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN600' => [
                        'name' => 'Cruise Control Coast (Decelerate) Switch',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [4, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Cruise control activator not in the position "coast"'],
                            [1, 'Cruise control activator in position "coast"'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN601' => [
                        'name' => 'Cruise Control Resume Switch',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [4, 4, 1, 0x03],
                        'val_desc' => [
                            [0, 'Cruise control activator not in the position "resume"'],
                            [1, 'Cruise control activator in position "resume"'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN602' => [
                        'name' => 'Cruise Control Accelerate Switch',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [4, 6, 1, 0x03],
                        'val_desc' => [
                            [0, 'Cruise control activator not in the position "accelerate"'],
                            [1, 'Cruise control activator in position "accelerate"'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN86' => [
                        'name' => 'Cruise Control Set Speed',
                        'unit' => 'km/h',
                        'val_op' => [1, 0, [0, 250]],
                        'parse' => [5, 0, 1, 0xFF],
                    ],
                    'SPN976' => [
                        'name' => '(R) PTO Governor State',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 30]],
                        'parse' => [6, 0, 1, 0x1F],
                        'val_desc' => [
                            [0, 'Off/Disabled'],
                            [1, 'Hold'],
                            [2, 'Remote Hold'],
                            [3, 'Standby'],
                            [4, 'Remote Standby'],
                            [5, 'Set'],
                            [6, 'Decelerate/Coast'],
                            [7, 'Resume'],
                            [8, 'Accelerate'],
                            [9, 'Accelerator Override'],
                            [10, 'Preprogrammed set speed 1'],
                            [11, 'Preprogrammed set speed 2'],
                            [12, 'Preprogrammed set speed 3'],
                            [13, 'Preprogrammed set speed 4'],
                            [14, 'Preprogrammed set speed 5'],
                            [15, 'Preprogrammed set speed 6'],
                            [16, 'Preprogrammed set speed 7'],
                            [17, 'Preprogrammed set speed 8'],
                            [18, 'PTO set speed memory 1'],
                            [19, 'PTO set speed memory 2'],
                        ],
                    ],
                    'SPN527' => [
                        'name' => 'Cruise Control States',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 6]],
                        'parse' => [6, 5, 1, 0x07],
                        'val_desc' => [
                            [0, 'Off/Disabled'],
                            [1, 'Hold'],
                            [2, 'Accelerate'],
                            [3, 'Decelerate'],
                            [4, 'Resume'],
                            [5, 'Set'],
                            [6, 'Accelerator Override'],
                        ],
                    ],
                    'SPN968' => [
                        'name' => 'Engine Idle Increment Switch',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [7, 0, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN967' => [
                        'name' => 'Engine Idle Decrement Switch',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [7, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN966' => [
                        'name' => 'Engine Test Mode Switch',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [7, 4, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                            [2, 'Error'],
                        ],
                    ],
                    'SPN1237' => [
                        'name' => 'Engine Shutdown Override Switch',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 2]],
                        'parse' => [7, 6, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                            [2, 'Error'],
                        ],
                    ],
                ],
            ],
            'PGN65270' => [
                'id' => 65270,
                'name' => 'Inlet/Exhaust Conditions 1',
                'length' => 8,
                'spns' => [
                    'SPN81' => [
                        'name' => '(R) Engine Diesel Particulate Filter Inlet Pressure',
                        'unit' => 'kPa',
                        'val_op' => [0.5, 0, [0, 125]],
                        'parse' => [0, 0, 1, 0xFF],
                    ],
                    'SPN102' => [
                        'name' => 'Engine Intake Manifold #1 Pressure',
                        'unit' => 'kPa',
                        'val_op' => [2, 0, [0, 500]],
                        'parse' => [1, 0, 1, 0xFF],
                    ],
                    'SPN105' => [
                        'name' => 'Engine Intake Manifold 1 Temperature',
                        'unit' => 'deg C',
                        'val_op' => [1, -40, [-40, 210]],
                        'parse' => [2, 0, 1, 0xFF],
                    ],
                    'SPN106' => [
                        'name' => 'Engine Air Inlet Pressure',
                        'unit' => 'kPa',
                        'val_op' => [2, 0, [0, 500]],
                        'parse' => [3, 0, 1, 0xFF],
                    ],
                    'SPN107' => [
                        'name' => 'Engine Air Filter 1 Differential Pressure',
                        'unit' => 'kPa',
                        'val_op' => [0.05, 0, [0, 12.5]],
                        'parse' => [4, 0, 1, 0xFF],
                    ],
                    'SPN173' => [
                        'name' => 'Engine Exhaust Gas Temperature',
                        'unit' => 'deg C',
                        'val_op' => [0.03125, -273, [-273, 1735]],
                        'parse' => [5, 0, 2, 0xFFFF],
                    ],
                    'SPN112' => [
                        'name' => 'Engine Coolant Filter Differential Pressure',
                        'unit' => 'kPa',
                        'val_op' => [0.5, 0, [0, 125]],
                        'parse' => [7, 0, 1, 0xFF],
                    ],
                ],
            ],
            'PGN65271' => [
                'id' => 65271,
                'name' => 'Vehicle Electrical Power 1',
                'length' => 8,
                'spns' => [
                    'SPN114' => [
                        'name' => 'Net Battery Current',
                        'unit' => 'A',
                        'val_op' => [1, -125, [-125, 125]],
                        'parse' => [0, 0, 1, 0xFF],
                    ],
                    'SPN115' => [
                        'name' => 'Alternator Current',
                        'unit' => 'A',
                        'val_op' => [1, 0, [0, 250]],
                        'parse' => [1, 0, 1, 0xFF],
                    ],
                    'SPN167' => [
                        'name' => 'Charging System Potential (Voltage)',
                        'unit' => 'V',
                        'val_op' => [0.05, 0, [0, 3212.75]],
                        'parse' => [2, 0, 2, 0xFFFF],
                    ],
                    'SPN168' => [
                        'name' => 'Battery Potential / Power Input 1',
                        'unit' => 'V',
                        'val_op' => [0.05, 0, [0, 3212.75]],
                        'parse' => [4, 0, 2, 0xFFFF],
                    ],
                    'SPN158' => [
                        'name' => 'Keyswitch Battery Potential',
                        'unit' => 'V',
                        'val_op' => [0.05, 0, [0, 3212.75]],
                        'parse' => [6, 0, 2, 0xFFFF],
                    ],
                ],
            ],
            'PGN65276' => [
                'id' => 65276,
                'name' => '(R) Dash Display',
                'length' => 8,
                'spns' => [
                    'SPN80' => [
                        'name' => 'Washer Fluid Level',
                        'unit' => '%',
                        'val_op' => [0.4, 0, [0, 100]],
                        'parse' => [0, 0, 1, 0xFF],
                    ],
                    'SPN96' => [
                        'name' => '(R) Fuel Level 1',
                        'unit' => '%',
                        'val_op' => [0.4, 0, [0, 100]],
                        'parse' => [1, 0, 1, 0xFF],
                    ],
                    'SPN95' => [
                        'name' => 'Engine Fuel Filter Differential Pressure',
                        'unit' => 'kPa',
                        'val_op' => [2, 0, [0, 500]],
                        'parse' => [2, 0, 1, 0xFF],
                    ],
                    'SPN99' => [
                        'name' => 'Engine Oil Filter Differential Pressure',
                        'unit' => 'kPa',
                        'val_op' => [0.5, 0, [0, 125]],
                        'parse' => [3, 0, 1, 0xFF],
                    ],
                    'SPN169' => [
                        'name' => 'Cargo Ambient Temperature',
                        'unit' => 'deg C',
                        'val_op' => [0.03125, -273, [-273, 1735]],
                        'parse' => [4, 0, 2, 0xFFFF],
                    ],
                    'SPN38' => [
                        'name' => '(R) Fuel Level 2',
                        'unit' => '%',
                        'val_op' => [0.4, 0, [0, 100]],
                        'parse' => [6, 0, 1, 0xFF],
                    ],
                ],
            ],
            'PGN65226' => [
                'id' => 65226,
                'name' => 'Active diagnostic trouble codes (DM1)',
                'length' => 0,
                'spns' => [
                    'SPN65536' => [
                        'name' => 'Malfunction Indicator Lamp Status 1',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [0, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                        ],
                    ], // <spn>65536,0,2,1,3,0</spn>
                    'SPN65537' => [
                        'name' => 'Red Stop Lamp Status 1',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [2, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                        ],
                    ], // <spn>65537,2,2,1,3,0</spn>
                    'SPN65538' => [
                        'name' => 'Amber Warning Lamp Status 1',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [4, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                        ],
                    ], // <spn>65538,4,2,1,3,0</spn>
                    'SPN65539' => [
                        'name' => 'Protect Lamp Status 1',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [6, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                        ],
                    ], // <spn>65539,6,2,1,3,0</spn>
                    'SPN65540' => [
                        'name' => 'Reserved for SAE assignment Lamp Status 1',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [8, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                        ],
                    ], // <spn>65540,8,2,1,3,0</spn>
                    'SPN65541' => [
                        'name' => 'Reserved for SAE assignment Lamp Status 2',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [10, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                        ],
                    ], // <spn>65541,10,2,1,3,0</spn>
                    'SPN65542' => [
                        'name' => 'Reserved for SAE assignment Lamp Status 3',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [12, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                        ],
                    ], // <spn>65542,12,2,1,3,0</spn>
                    'SPN65543' => [
                        'name' => 'Reserved for SAE assignment Lamp Status 4',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [14, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                        ],
                    ], // <spn>65543,14,2,1,3,0</spn>
                    'SPN65544' => [
                        'name' => 'SPN 1',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 524287]],
                        'parse' => [16, 19, 3, 0x03FFFF],
                    ], // <spn>65544,16,19,3,3FFFF,1</spn>
                    'SPN65545' => [
                        'name' => 'Failure Mode Identifier (FMI) 1',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 31]],
                        'parse' => [35, 5, 1, 0x1F],
                    ], // <spn>65545,35,5,1,1F,1</spn>
                    'SPN65546' => [
                        'name' => 'Reserved for Future Assignment 1',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [40, 1, 1, 0x01],
                    ], // <spn>65546,40,1,1,1,1</spn>
                    'SPN65547' => [
                        'name' => 'Occurrence Count 1',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 126]],
                        'parse' => [41, 7, 1, 0x7F],
                    ], // <spn>65547,41,7,1,7F,1</spn>
                ],
            ],
            'PGN65227' => [
                'id' => 65227,
                'name' => 'Previously active diagnostic trouble codes (DM2)',
                'length' => 0,
                'spns' => [
                    'SPN65556' => [
                        'name' => 'Malfunction Indicator Lamp Status 2',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [0, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                        ],
                    ], // <spn>65556,0,2,1,3,0</spn>
                    'SPN65557' => [
                        'name' => 'Red Stop Lamp Status 2',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [2, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                        ],
                    ], // <spn>65557,2,2,1,3,0</spn>
                    'SPN65558' => [
                        'name' => 'Amber Warning Lamp Status 2',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [4, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                        ],
                    ], // <spn>65558,4,2,1,3,0</spn>
                    'SPN65559' => [
                        'name' => 'Protect Lamp Status 2',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [6, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                        ],
                    ], // <spn>65559,6,2,1,3,0</spn>
                    'SPN65560' => [
                        'name' => 'Reserved for SAE assignment Lamp Status 5',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [8, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                        ],
                    ], // <spn>65560,8,2,1,3,0</spn>
                    'SPN65561' => [
                        'name' => 'Reserved for SAE assignment Lamp Status 6',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [10, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                        ],
                    ], // <spn>65561,10,2,1,3,0</spn>
                    'SPN65562' => [
                        'name' => 'Reserved for SAE assignment Lamp Status 7',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [12, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                        ],
                    ], // <spn>65562,12,2,1,3,0</spn>
                    'SPN65563' => [
                        'name' => 'Reserved for SAE assignment Lamp Status 8',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [14, 2, 1, 0x03],
                        'val_desc' => [
                            [0, 'Off'],
                            [1, 'On'],
                        ],
                    ], // <spn>65563,14,2,1,3,0</spn>
                    'SPN65564' => [
                        'name' => 'SPN 2',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 524287]],
                        'parse' => [16, 19, 3, 0x03FFFF],
                    ], // <spn>65564,16,19,3,3FFFF,1</spn>
                    'SPN65565' => [
                        'name' => 'Failure Mode Identifier (FMI) 2',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 31]],
                        'parse' => [35, 5, 1, 0x01F],
                    ], // <spn>65565,35,5,1,1F,1</spn>
                    'SPN65566' => [
                        'name' => 'Reserved for Future Assignment 2',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 1]],
                        'parse' => [40, 1, 1, 0x01],
                    ], // <spn>65566,40,1,1,1,1</spn>
                    'SPN65567' => [
                        'name' => 'Occurrence Count 2',
                        'unit' => '',
                        'val_op' => [1, 0, [0, 126]],
                        'parse' => [41, 7, 1, 0x07F],
                    ], // <spn>65567,41,7,1,7F,1</spn>
                ],
            ],
        ];

        $pgns = [];
        foreach ($pgns_info as $info) {
            if ($info['id'] == $pgn) {
                if ($info['length'] == 0 || $info['length'] == count($value)) {
                    if ($pgn == 65226 || $pgn == 65227) {
                        $dm = ['Active diagnostic trouble codes (DM1)',
                                    'Previously active diagnostic trouble codes (DM2)', ];
                        printf($dm[$pgn - 65226]);
                        printf(self::j1939DtcsDecode($value));

                        $parameters = [
                            $dm[$pgn - 65226] => [
                                'value' => self::j1939DtcsDecode($value),
                                'unit' => 'Indefinido',
                            ],
                        ];

                        $pgns = [
                            'pgn' => $pgn,
                            'description' => $dm[$pgn - 65226],
                            'parameters' => $parameters,
                        ];

                        return $pgns;
                    } else {
                        foreach ($info['spns'] as $spn) {
                            $val = self::j1939ValRead($value, $spn['parse']);
                            $val *= $spn['val_op'][0];
                            $val += $spn['val_op'][1];
                            if ($val >= $spn['val_op'][2][0] && $val <= $spn['val_op'][2][1]) {
                                $parameter = [
                                    'pgn' => $info['id'],
                                    $spn['name'] => [
                                        'value' => $val,
                                        'unit' => $spn['unit'],
                                        ],
                                ];
                                array_push($pgns, $parameter);
                            }
                        }

                        return $pgns;
                    }
                }
            }
        }
    }

    public static function reverseBytes($value)
    {
        return ($value & 0x000000FF) << 24 | ($value & 0x0000FF00) << 8 |
                ($value & 0x00FF0000) >> 8 | ($value & 0xFF000000) >> 24;
    }

    public static function j1939DtcsDecode($value)
    {
        if (count($value) < 7) {
            return;
        }
        $lampStatus = ['OFF', 'ON', 'Unknown', 'Unknown'];

        printf("\tMIL: ");
        printf($lampStatus[($value[0] >> 6) & 0x03] . "\r\n");

        printf("\tRSL: ");
        printf($lampStatus[($value[0] >> 4) & 0x03] . "\r\n");

        printf("\tAWL: ");
        printf($lampStatus[($value[0] >> 2) & 0x03] . "\r\n");

        printf("\tPL: ");
        printf($lampStatus[$value[0] & 0x03] . "\r\n");

        for ($i = 0; $i < intdiv((count($value) - 2), 4); ++$i) {
            printf("              DTC%d:\r\n", $i);
            $SPN = $value[2 + 4 * $i + 2] >> 5;
            $SPN = ($SPN << 8) + $value[2 + 4 * $i + 1];
            $SPN = ($SPN << 8) + $value[2 + 4 * $i];
            $FMI = $value[2 + 4 * $i + 2] & 0x1F;
            $OC = $value[2 + 4 * $i + 3] & 0x7F;
            printf("\tSPN: %d\r\n", $SPN);
            printf("\tFMI: %d\r\n", $FMI);
            printf("\tOC: %d\r\n", $OC);
        }
    }

    public static function hvdDecodeFromBinary($info)
    {
        printf("j1708:\r\n");
        self::j1708DataDecode($info);
    }

    public static function j1708DataDecode($hvddata)
    {
        $pos = 0;
        while ($pos < count($hvddata)) {
            $len = $hvddata[$pos] & 0x3F;
            $paratype = $hvddata[$pos] >> 6 & 0x03;
            if ($len + $pos > count($hvddata)) {
                break;
            }
            if ($len < 2 || $len > 22 || $paratype == 0) {
                $pos += $len + 1;
                continue;
            }
            if ($paratype == 1) {// MID data
                $value = array_slice($hvddata, $pos + 1, $len);
                self::j1708MidDecode($value);
            } else {
                $j1587pid = $hvddata[$pos + 1];
                if ($paratype == 3) {
                    $j1587pid += 256;
                }
                $value = array_slice($hvddata, $pos + 2, $len - 1);
                self::j1587PidDecode($value, $j1587pid);
            }
            $pos += $len + 1;
        }
    }

    public static function j1708MidDecode($value)
    {
        $hex = '';
        for ($i = 1; $i < count($value); ++$i) {
            $hex .= sprintf('%2X ', $value[$i]);
        }
        printf(
            "\tMID%d: %s\r\n",
            $value[0],
            $hex
        );
    }

    public static function j1587PidDecode($value, $pid)
    {
        switch ($pid) {
            case 84:// Road speed
                printf(
                    "        PID%d: %.3fkm/h--Road speed\r\n",
                    $pid,
                    $value[0] * 0.805
                );
                break;
            case 96:// Fuel level
                printf(
                    "        PID%d: %.2f%%--Fuel level\r\n",
                    $pid,
                    $value[0] * 0.5
                );
                break;
            case 110:// Engine Coolant Temperature
                printf(
                    "        PID%d: %dFahrenheit--Engine Coolant Temperature\r\n",
                    $pid,
                    $value[0]
                );
                break;
            case 190:// Engine speed
                printf(
                    "        PID%d: %.2fRPM--Engine speed\r\n",
                    $pid,
                    self::readUint16($value, 0) * 0.25
                );
                break;
            case 245:// Total Vehicle Distance
                printf(
                    "        PID%d: %.3fkm--Total Vehicle Distance\r\n",
                    $pid,
                    self::readUint32($value, 0) * 0.161
                );
                break;
            default:
                $hex = '';
                for ($i = 0; $i < count($value); ++$i) {
                    $hex .= sprintf('%2X ', $value[$i]);
                }
                printf(
                    "        PID%d: %s\r\n",
                    $pid,
                    $hex
                );
                break;
        }
    }

    public static function vinDecodeFromBinary($info)
    {
        $hex = '';
        for ($i = 0; $i < count($info); ++$i) {
            $hex .= chr($info[$i]);
        }

        return $hex;
    }

    public static function hex2str($hex)
    {
        $str = '';
        for ($i = 0; $i < count($hex); $i += 2) {
            $str .= chr(hexdec($hex[$i]));
        }

        return $str;
    }

    public static function egtDecodeFromBinary($info)
    {
        if (count($info) != 4) {
            return;
        }

        return self::readUint32($info, 0);
    }
}
