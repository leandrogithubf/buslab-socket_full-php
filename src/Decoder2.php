<?php

namespace App;

final class Decoder2
{
    private const DEVICE_ID_LEN = 15;
    private const MIN_PACKET_LEN = 22;
    private const PROTOCOL_VERSION = 1;
    private const TXT_START_CHAR = '*';
    private const TXT_END_CHAR = '#';
    private const BIN_FLAG_CHAR = 0xF8;
    private const BIN_ESCAPE_CHAR = 0xF7;
    private const ACK_FLAG = 0xFE;

    private static $eventInfo0 = ['None',
                                'Interval upload',
                                'Angle change upload',
                                'Distance upload',
                                'Request upload', ];

    private static $eventInfo1 = ['Rfid reader',
                                'iBeacon', ];

    private $iniFilePath = '';

    // List<Pgn> pgns;
    private $pgns = [];

    public static function isBinary($str)
    {
        return preg_match('~[^\x20-\x7E\t\r\n]~', $str) > 0;
    }

    public static function hexToStrArray($hex)
    {
        $hex = strtoupper($hex);
        // $binData é um arranjo em hex dos dados
        $hexArray = str_split($hex, 2);

        return self::hexStringDec($hexArray);
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

    public static function decode(string $data)
    {
        if (empty($data)) {
            echo 'Empty data!';

            return;
        }
        $hex = (self::isBinary($data) ? bin2hex($data) : $data);

        $binData = self::hexToStrArray($hex);
        $result = [];
        $offset = 0;
        echo "Starting decode...\r\n";

        while (($offset + self::MIN_PACKET_LEN) < count($binData)) {
            // começar daqui
                if ($binData[$offset] == self::TXT_START_CHAR) {// Text frame start flag
                    // dump("texto");
                    // die;
                    $endPos = array_search(
                        self::BIN_FLAG_CHAR,
                        array_slice($binData, $offset + 1, null, true)
                    ); // Get Text frame end flag position
                    // if($endPos == -1 || ($endPos - $offset) < 20|| !TextFrameFormatCheck($offset, $endPos))//Check Text frame format
                    // {
                        // if (parseAll)
                        // {
                        //     unPacketData.Add(buffer[Offset]);
                        // }
                    //     Offset ++;
                    //     continue;
                    // }

                //     if (parseAll && unPacketData.Count > 0)
                //     {
                //         String strUnpack = System.Text.Encoding.ASCII.GetString(unPacketData.ToArray(), 0, unPacketData.Count);
                //         if (strUnpack.Length > 4)
                //         {
                //             unPacktDataDecode(outStr, strUnpack);
                //         }
                //         unPacketData.Clear();
                //     }
                //     String strFrame = System.Text.Encoding.ASCII.GetString(buffer, Offset, endPos - Offset + 1);
                //     TextFrameDecode(outStr, strFrame);
                //     $offset = $endPos + 1;
                }
            if ($binData[$offset] == self::BIN_FLAG_CHAR) {// Binary packet flag
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
                // veio até aqui
                array_push($result, self::binFrameDecode($binFrame, $dataLen));
                $offset = $endPos + 1;
            } else {
                ++$offset;
            }
        }

        // if (parseAll && unPacketData.Count > 0)
        // {
        //     while (Offset < buffer.Length)
        //     {
        //         unPacketData.Add(buffer[Offset]);
        //         Offset++;
        //     }
        //     String strUnpack = System.Text.Encoding.ASCII.GetString(unPacketData.ToArray(), 0, unPacketData.Count);
        //     if (strUnpack.Length > 4)
        //     {
        //         unPacktDataDecode(outStr, strUnpack);
        //     }
        //     unPacketData.Clear();
        // }
        echo 'Decode finished!';

        return $result;
    }

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
            if (($dat[$pos] & 0x80) != 0) {
                // falta converter pra php a função FwdFrameDecode
                array_push($array, [self::fwdFrameDecode($dat, $len)]);
            } else {
                echo 'Can not support frame NO: ' . $dat[$pos];
            }

            return;
        }

        ++$pos;
        $deviceID = '';
        for ($i = 0; $i < 8; ++$i) {
            if ($i == 0 && (self::DEVICE_ID_LEN & 0x01) != 0) {
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
                    case 1:// GPS
                            //dump('GPS=>passou');
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['gps'] = self::gpsDecodeFromBinary($infoData, $fix3D);
                            break;

                    case 2:// LBS
                            //dump('LBS=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            // LbsDecodeFromBinary(outStr, infoData);
                            $array['LBS'] = self::lbsDecodeFromBinary($infoData);
                            break;

                    case 3:// STT
                            //dump('STT=>passou');
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['STT'] = self::sttDecodeFromBinary($infoData);
                            break;

                    case 4:// MGR
                            //dump('MRG=>passou');
                            $infoLen = $dat[$pos++];
                            $algorithm = ($infoLen >> 4) & 0x0F;
                            $infoLen &= 0x0F;
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['distance'] = [
                                'value' => self::mgrDecodeFromBinary($infoData, $algorithm),
                                'unit' => 'meters',
                            ];
                            break;

                    case 5:// ADC
                            //dump('ADC=>passou');
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['ADC'] = self::adcDecodeFromBinary($infoData);
                            break;

                    case 6:// GFS
                            //dump('GFS=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['GFS'] = self::gfsDecodeFromBinary($infoData);
                            break;

                    case 7:// OBD
                            //dump('OBD=>passou');
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['OBD'] = self::obdDecodeFromBinary($infoData);
                            break;

                    case 8:// FUL
                            //dump('FUL=>passou');
                            $infoLen = $dat[$pos++];
                            $algorithm = ($infoLen >> 4) & 0x0F;
                            $infoLen &= 0x0F;
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['FUL'] = self::fulDecodeFromBinary($infoData, $algorithm);
                            break;

                    case 9:// OAL
                            //dump('OAL=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['OAL'] = self::oalDecodeFromBinary($infoData);
                            break;

                    case 0x0A:// HDB
                            //dump('HDB=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['HDB'] = self::hdbDecodeFromBinary($infoData);
                            break;

                    case 0x0B:// CAN--J1939 data
                            //dump('CAN=>passou');
                            $infoLen = $dat[$pos++];
                            $infoLen = $infoLen * 256 + $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['CAN'] = self::canDecodeFromBinary($infoData);
                            break;

                    case 0x0C:// HVD--J1708 data
                            //dump('HVD=>passou');
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['HVD'] = self::hvdDecodeFromBinary($infoData);
                            break;

                    case 0x0D:// VIN
                            //dump('VIN=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['VIN'] = self::vinDecodeFromBinary($infoData);
                            break;

                    case 0x0E:// RFI
                            //dump('RFI=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['RFI'] = self::rfiDecodeFromBinary($infoData);
                            break;

                    case 0x0F:// EGT
                            //dump('EGT=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['EGT'] = self::egtDecodeFromBinary($infoData);
                            break;

                    case 0x10:// EVT
                            //dump('EVT=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['EVT'] = self::evtDecodeFromBinary($infoData);
                            break;

                    case 0x11:// USN
                            //dump('USN=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['USN'] = self::usnDecodeFromBinary($infoData);
                            break;

                    case 0x12:// GDC
                            //dump('GDC=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['GDC'] = self::gdcDecodeFromBinary($infoData);
                            break;

                    case 0x13:// DIO
                            //dump('DIO=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            // vai dar erro
                            $array['DIO'] = self::dioDecodeFromBinary($infoData);
                            break;

                    case 0x14:// VHD--Vehicle information data
                            //dump('VHD=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['VHD'] = self::vhdDecodeFromBinary($infoData);
                            break;

                    case 0x20:// TRP--Trip report
                            //dump('TRP=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['TRP'] = self::trpDecodeFromBinary($infoData);
                            break;

                    case 0x21:// SAT--GPS Satellites Signal strength
                            //dump('SAT=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['SAT'] = self::satDecodeFromBinary($infoData);
                            break;

                    case 0x3F:// BCN--iBeacon info data
                            //dump('BCN=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoLen = $infoLen * 256 + $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['BCN'] = self::bcnDecodeFromBinary($infoData);
                            break;

                    case 0x3E:// BRV--BLE remote event
                            //dump('BRV=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos] & 0x0F;
                            $ble_evt = $dat[$pos++] >> 4;
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['BRV'] = self::brvDecodeFromBinary($infoData, $ble_evt);
                            break;

                    case 0x40:// MSI--IMSI information
                            //dump('MSI=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['MSI'] = self::msiDecodeFromBinary($infoData);
                            break;

                    case 0x42:// BTD--BLE Temp sensor Data
                            //dump('BTD=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['BTD'] = self::btdDecodeFromBinary($infoData);
                            break;

                    case 0x29:// IBN--iButton data
                            //dump('IBM=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['IBN'] = self::ibnDecodeFromBinary($infoData);
                            break;

                    case 0x2A:// OWS--1-Wire temperature sensor data
                            //dump('OWS=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos] & 0x7F;
                            $withID = (($dat[$pos] & 0x80) != 0);
                            ++$pos;
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['OWS'] = self::owsDecodeFromBinary($infoData, $withID);
                            break;

                    case 0x23:// NET--Cellular network status
                            //dump('NET=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['NET'] = self::netDecodeFromBinary($infoData);
                            break;

                    case 86:
                            //dump('CLG=>passou');
                            // j1708 não entrou
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['CLG'] = self::clgDecodeFromBinary($infoData);
                            break;

                    default:
                        $infoLen = $dat[$pos++];
                        break;
                }
            $pos += $infoLen;
        }

        return $array;
    }

    public static function fwdFrameDecode($dat, $len)
    {
        $tabChars = '    ';
        $flag = ($dat[1] >> 5) & 0x03;
        $index = $dat[1] & 0x1F;
        if ($flag >= 3) {
            echo $tabChars . " Unknown forward frame\r\n";

            return;
        } else {
            echo $tabChars . ' Forward frame, index: ' . $index . "\r\n";
        }
        $fix3D = false;
        $pos = 2;
        if ($flag != 0) {
            $deviceID = '';
            // for (int i = 0; i < 8; i++)
            for ($i = 0; $i < (self::DEVICE_ID_LEN + 1) / 2; ++$i) {
                if ($i == 0 && (self::DEVICE_ID_LEN & 0x01) != 0) {
                    // $deviceID += $dat[$pos++].ToString("X");
                    $deviceID += dechex($dat[$pos++]);
                } else { // $deviceID += $dat[$pos++].ToString("X2");
                    $deviceID += '0' . dechex($dat[$pos++]);
                }
            }
            echo $tabChars . 'Device ID: ' . $deviceID;

            $timeSeconds = self::readUint32($dat, $pos);
            $pos += 4;

            $fix3D = ($timeSeconds & 0x80000000) != 0;
            $timeSeconds &= 0x7FFFFFFF;
            if ($timeSeconds == 0) {
                echo 'Time stamp: ' . $tabChars . 'Unknown';
            } else {
                $dt = \DateTime::createFromFormat('Y-m-d H:i:s', '1999-12-31 22:00:00');
                $dt->add(new \DateInterval('PT' . $timeSeconds . 'S'));
                echo 'Time stamp: ' . $dt;

                $array['date'] = $date->format('Y-m-d H:i:s');
            }
        }

        if ($flag == 2) {
            $infoId = $dat[$pos++];

            switch ($infoId) {
                    case 1:// GPS
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['GPS'] = self::gpsDecodeFromBinary($infoData, $fix3D);
                            break;

                    case 2:// LBS
                            $infoLen = $dat[$pos++];
                            $infoData = array_slice($dat, $pos, $infoLen);
                            $array['LBS'] = self::lbsDecodeFromBinary($infoData);
                            break;

                    default:
                        $infoLen = $dat[$pos++];
                        break;
                }
            $pos += $infoLen;
        }

        if ($pos < $len) {
            $fwdData = array_slice($dat, $pos, $len - $pos);
            $string = '';
            foreach ($fwdData as $chr) {
                $string .= chr($chr);
            }
            echo 'Forward data(ASCII):' . $string;

            $strHexFwd = '';
            foreach ($fwdData as $key => $fwd) {
                $strHexFwd .= sprintf('%2X ', $fwd);
            }
            echo 'Forward data(HEX):' . $strHexFwd;
        }

        return 1;
    }

    public static function gpsDecodeFromBinary($info, $is3d)
    {
        if (count($info) != 14) {
            return;
        }

        // Latitude and Longitude all zero
        if (self::readInt32($info, 0) == 0 && self::readInt32($info, 4) == 0) {
            $gpsfixed = 'NoFix';
        } else {
            $gpsfixed = $is3d ? '3D' : '2D';
        }

        $array['gpsStatus'] = [$gpsfixed];
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

    // exibe na tela mas tem que retornar um array no return
    public static function LbsDecodeFromBinary($info)
    {
        if (count($info) < 9) {
            return;
        }

        if (count($info) == 0x0B) {
            $var = [
                    'MCC' => self::readUint16($info, 0),
                    'MNC' => self::readUint16($info, 2),
                    'Cell' => [
                        'LAC' => self::readUint16($info, 4),
                        'CID' => self::readUint32($info, 6),
                        'dbm' => self::readUnsignedByte($info, 10),
                    ],
                ];

            return $var;
        } else {
            if ((count($info) - 4) % 5 != 0) {
                return;
            }
            if ((count($info) - 4) / 5 > 7) {
                return;
            }

            $cells = [];
            for ($i = 0; $i < (count($info) - 4) / 5; ++$i) {
                $temp = [
                        'LAC' => self::readUint16($info, 4 + $i * 5),
                        'CID' => self::readUint16($info, 4 + $i * 5 + 2),
                        'dbm' => self::eadUnsignedByte($info, 4 + $i * 5 + 4),
                    ];
                array_push($cells, $temp);
            }
            $var = [
                    'MCC' => self::readUint16($info, 0),
                    'MNC' => self::readUint16($info, 2),
                    'Cell' => $cells,
                ];

            return $var;
        }
    }

    public static function sttDecodeFromBinary($info)
    {
        if (count($info) != 4) {
            return;
        }
        // OutputText(outStr, TabChars + "STT:" + "\r\n");
        // TabChars = TabChars + TabChars;
        $iStatus = self::readUint16($info, 0);
        $iAlarm = self::readUint16($info, 2);
        $infoStatus = ['Power cut',
                              'Moving',
                              'Over speed',
                              'Jamming',
                              'Geo-fence alarming',
                              'Immobolizer',
                              'ACC',
                              'Input high level',
                              'Input mid level',
                              'Engine',
                              'Panic',
                              'OBD alarm',
                              'Course rapid change',
                              'Speed rapid change',
                              'Roaming(T3xx)/BLE connecting(L10x)',
                              'Inter roaming(T3xx)/OBD connecting(L10x)', ];
        $infoAlarm = ['Power cut',
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
                             'Rollover',
                             'Accident',
                             'Battery low', ];

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

    public static function mgrDecodeFromBinary($info, $id)
    {
        if (count($info) != 4) {
            return;
        }

        return self::readUint32($info, 0);
    }

    public static function adcDecodeFromBinary($info)
    {
        if (count($info) % 2 != 0) {
            if (count($info) > 32) {
                return;
            }
        }

        $infoAdc = ['Car Battery',
                            'Device Temp.',
                            'Inner Battery',
                            'Input voltage',
                            'Inner Battery percent',
                            'Ultrasonic fuel sensor height',
                            'Ultrasonic fuel sensor height',
                            'Ultrasonic fuel sensor height',
                            'Ultrasonic fuel sensor height',
                            'Ultrasonic fuel sensor height', ];
        $infoUnit = ['(V)', '(Celsius)', '(V)', '(V)', '(%)', '(mm)', '(mm)', '(mm)', '(mm)', '(mm)'];
        for ($i = 0; $i < count($info) / 2; ++$i) {
            $val = self::readUint16($info, 2 * $i);
            $valId = (($val >> 12) & 0x000F);
            $val &= 0x0FFF;

            switch ($valId) {
                    case 0:
                    case 1:
                        $strAdc[$infoAdc[$valId]] = [
                                'value' => $val * (125 - (-55)) / 4096 + (-55),
                                'unit' => $infoUnit[$valId],
                        ];
                        break;
                    case 2:
                    case 3:
                        $strAdc[$infoAdc[$valId]] = [
                                'value' => $val * (100 - (-10)) / 4096 + (-10),
                                'unit' => $infoUnit[$valId],
                        ];
                        break;
                    case 4:
                        $strAdc[$infoAdc[$valId]] = [
                                'value' => $val * (200 - (-100)) / 4096 + (-100),
                                'unit' => $infoUnit[$valId],
                        ];
                        break;
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                    case 9:
                        $strAdc[$infoAdc[$valId]] = [
                                'value' => $val * (2000 - 0) / 4096 + 0,
                                'unit' => $infoUnit[$valId],
                        ];
                        break;
                    default:
                        $strAdc[$infoAdc[$valId]] = [
                                'value' => $val,
                                'unit' => '',
                        ];
                        break;
                }
        }

        return $strAdc;
    }

    public static function gfsDecodeFromBinary($info)
    {
        if (count($info) != 8) {
            return;
        }

        $iStatus = self::readUint32($info, 0);
        $iAlarm = self::readUint32($info, 4);
        $strStatus = [];
        $strAlarm = [];
        $strArray = [];
        // OutputText(outStr, TabChars + "Status:\r\n");
        for ($i = 0; $i < 16; ++$i) {
            $bitMask = (0x0001 << $i);
            array_push($strStatus, [$i, ($iStatus & $bitMask) != 0 ? 'I' : 'O']);
        }

        for ($i = 16; $i < 32; ++$i) {
            $bitMask = (0x0001 << $i);
            array_push($strStatus, [$i, ($iStatus & $bitMask) != 0 ? 'I' : 'O']);
        }

        // OutputText(outStr, TabChars + "Alarm:\r\n");
        for ($i = 0; $i < 16; ++$i) {
            $bitMask = (0x0001 << $i);
            array_push($strAlarm, [$i, ($iAlarm & $bitMask) != 0 ? 'Y' : 'N']);
        }

        for ($i = 16; $i < 32; ++$i) {
            $bitMask = (0x0001 << $i);
            array_push($strAlarm, [$i, ($iAlarm & $bitMask) != 0 ? 'Y' : 'N']);
        }

        $strArray = [
                'status' => $strStatus,
                'alarm' => $strAlarm,
            ];

        return $strArray;
    }

    public static function obdDecodeFromBinary($info)
    {
        return self::obdDataDecode($info);
    }

    public static function oalDecodeFromBinary($info)
    {
        return self::obdDataDecode($info);
    }

    public static function obdDataDecode($obddata)
    {
        $pos = 0;
        $array = [];
        while ($pos < count($obddata)) {
            $len = (($obddata[$pos] >> 4) & 0x0F);
            if ($len + $pos > count($obddata)) {
                break;
            }
            if ($len < 3 || $len > 15) {
                $pos += $len;
                continue;
            }
            $service = ($obddata[$pos] & 0x0F);
            switch ($service) {
                    case 1:// Mode 01
                    case 2:// Mode 02
                            // j1708 não passou aqui
                            $pid = $obddata[$pos + 1];
                            $pidValue = array_slice($obddata, $pos + 2, $len - 2);
                            array_push($array, self::obdService0102Decode($pidValue, $service, $pid));
                            // if (count($preArray) == 2) {
                            //     # code...
                            // }
                            break;

                    case 3:// Mode 03
                            // j1708 não passou aqui
                            $value = $len - 1;
                            $value = array_slice($obddata, $pos + 1, $value);
                            array_push($array, self::obdService03Decode($value));
                            break;

                    case 4:// Mode 04
                        break;
                    case 5:// Mode 05
                        break;
                    case 6:// Mode 06
                        break;
                    case 7:// Mode 07
                        break;
                    case 8:// Mode 08
                        break;
                    case 9:// Mode 09
                        break;
                    case 10:// Mode 0A
                        break;
                    case 11:// mode 21  Read Data By Identifier
                        /*{
                            byte[] Value = new byte[len - 1];
                            Array.Copy(obddata, pos + 1, Value, 0, Value.Length);
                            ObdService21Decode(Value);
                            break;
                        }*/
                        /*{
                            byte[] Value = new byte[len - 1];
                            Array.Copy(obddata, pos + 1, Value, 0, Value.Length);
                            ObdService21Decode(Value);
                            break;
                        }*/
                        /*{
                            int pid = obddata[pos + 1];
                            byte[] pidValue = new byte[len - 2];
                            Array.Copy(obddata, pos + 2, pidValue, 0, pidValue.Length);
                            ObdService21Decode(pidValue, pid);
                            break;
                        }*/

                            // j1708 não passou aqui
                            $pid = $obddata[$pos + 1];
                            $pidValue = $len - 2;
                            $pidValue = array_slice($obddata, $pos + 2, $pidValue);
                            array_push($array, self::obdService0102Decode($pidValue, 0x21, $pid));
                            break;

                    case 12:// mode 22  Read Data By Identifier
                        /*{
                            byte[] Value = new byte[len - 1];
                            Array.Copy(obddata, pos + 1, Value, 0, Value.Length);
                            ObdService22Decode(Value);
                            break;
                        }*/

                            // j1708 não passou aqui
                            $pid = self::readUint16($obddata, $pos + 1);
                            $pidValue = $len - 3;
                            $pidValue = array_slice($obddata, $pos + 3, $pidValue);
                            array_push($array, self::udsService22Decode($pidValue, $pid));
                            break;

                    case 15:// CANBUS sniffer data
                            // j1708 não passou aqui
                            $value = $len - 1;
                            $value = array_slice($obddata, $pos + 1, $value);
                            array_push($array, self::obdCanSnifferDecode($value));
                            break;

                    default:
                        break;
                }
            $pos += $len;
        }

        return $array;
    }

    public static function fulDecodeFromBinary($info, $id)
    {
        if (count($info) != 4) {
            return;
        }
        $fuel = self::readUint32($info, 0);

        $strFul = [
                'id' => $id,
                'Fuel consumption: Algorithm ' => $fuel,
                ];

        return $strFul;
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
        return self::j1939DataDecode($info);
    }

    public static function j1939DataDecode($candata)
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

        // decoder antigo
        // foreach ($result as $key => $pgns) {
        //     $result[$pgns[1]['pgn']] = $pgns;
        //     unset($result[$key]);
        // }

        // foreach ($result as $key => $pgns) {
        //     foreach ($pgns as $key2 => $values) {
        //         foreach ($values as $key3 => $value) {
        //             if ($key3 == 'pgn') {
        //                 continue;
        //             }
        //             if (!isset($result[$key][$key3])) {
        //                 $result[$key][$key3] = $value;
        //             }
        //         }
        //         unset($result[$key][$key2]);
        //     }
        // }
        return $result;
    }

    public static function hvdDecodeFromBinary($info)
    {
        return self::j1708DataDecode($info);
    }

    public static function j1708DataDecode($hvddata)
    {
        $array = [];
        $pos = 0;
        while ($pos < count($hvddata)) {
            $len = $hvddata[$pos] & 0x3F;
            $paratype = ($hvddata[$pos] >> 6) & 0x03;
            if ($len + $pos > count($hvddata)) {
                break;
            }
            if ($len < 2 || $len > 22 || $paratype == 0) {
                $pos += $len + 1;
                continue;
            }
            if ($paratype == 1) {// MID data
                $value = array_slice($hvddata, $pos + 1, $infoLen);
                array_push($array, self::j1708MidDecode($value));
            } else {
                $j1587pid = $hvddata[$pos + 1];
                if ($paratype == 3) {
                    $j1587pid += 256;
                }
                $value = array_slice($hvddata, $pos + 2, $len - 1);
                array_push($array, self::j1587PidDecode($value, $j1587pid));
            }
            $pos += $len + 1;
        }

        return $array;
    }

    public static function vinDecodeFromBinary($info)
    {
        $hex = '';
        for ($i = 0; $i < count($info); ++$i) {
            $hex .= chr($info[$i]);
        }

        return $hex;
    }

    public static function rfiDecodeFromBinary($info)
    {
        $hex = '';
        for ($i = 0; $i < count($info); ++$i) {
            $hex .= chr($info[$i]);
        }

        $array = [
                'value' => $hex,
                'status' => $info[count($info) - 1] == 0 ? 'Unauthorized' : 'Authorized',
            ];

        return $array;
    }

    public static function evtDecodeFromBinary($info)
    {
        if (count($info) != 1 && count($info) != 5) {
            return;
        }
        $eventCode = $info[0];
        $mask = 0;
        if (count($info) == 5) {
            $mask = self::readUint32($info, 1);
        }

        return self::evtCodeStringOut($eventCode, $mask);
    }

    public static function evtCodeStringOut($evt, $mask)
    {
        $eventInfo = '';
        if ($evt < 0x10) {
            if ($evt < count(self::$eventInfo0)) {
                $eventInfo = self::$eventInfo0[$evt];
            }
        } elseif ($evt < 0x80) {
            $evt -= 0x10;
            if ($evt < count(self::$eventInfo1)) {
                $eventInfo = self::$eventInfo1[$evt];
            }
        } elseif ($evt == 0x80) {
            $strEvent = '';
            $msk = 1;
            for ($i = 0; $i < 32; ++$i) {
                if (($mask & $msk) != 0) {
                    $strEvent += strval($i + $i) . '|';
                }
                $msk <<= 1;
            }
            $strEvent = substr($strEvent, 0, -1);
            $eventInfo = [
                    'GeoFence status changed' => $strEvent,
                ];
        } elseif ($evt == 0x90) {
            $infoBehavior = ['Rapid acceleration',
                                     'Rough braking',
                                     'Harsh course',
                                     'No warmup',
                                     'Long idle',
                                     'Fatigue driving',
                                     'Rough terrain',
                                     'High RPM', ];
            $strEvent = '';
            $msk = 1;
            for ($i = 0; $i < 8; ++$i) {
                if (($mask & $msk) != 0) {
                    $strEvent .= $infoBehavior[$i] . '|';
                }
                $msk <<= 1;
            }
            $strEvent = substr($strEvent, 0, -1);
            $eventInfo = [
                    'Hash driving detected' => $strEvent,
                ];
        } elseif ($evt == 0xE0 || $evt == 0xF8) {
            $infoAlarm = ['Power cut',
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
                                 'Battery low', ];
            $strEvent = '';
            $msk = 1;
            for ($i = 0; $i < 16; ++$i) {
                if (($mask & $msk) != 0) {
                    $strEvent += $infoAlarm[$i] . '|';
                }
                $msk <<= 1;
            }

            $strEvent = substr($strEvent, 0, -1);
            $eventInfo = ['Alarm trigged' => $strEvent];
        } elseif ($evt == 0xF0) {
            $infoStatus = ['Power cut',
                                  'Moving',
                                  'Over speed',
                                  'Jamming',
                                  'Geo-fence alarming',
                                  'Immobolizer',
                                  'ACC',
                                  'Input high level',
                                  'Input mid level',
                                  'Engine',
                                  'Panic',
                                  'OBD alarm',
                                  'Course rapid change',
                                  'Speed rapid change',
                                  'Roaming',
                                  'Inter roaming', ];
            $strEvent = '';
            $msk = 1;
            for ($i = 0; $i < 16; ++$i) {
                if (($mask & $msk) != 0) {
                    $strEvent .= $infoStatus[$i] . '|';
                }
                $msk <<= 1;
            }
            if ($strEvent != '') {
                $strEvent = substr($strEvent, 0, -1);
            }
            $eventInfo = [
                        'Device status changed' => $strEvent,
                    ];
        }
        if ($eventInfo == '') {
            $eventInfo = 'Unknown';
        }
        $strEventCode = [
                'Event code' => $eventInfo,
            ];

        return $strEventCode;
    }

    public static function usnDecodeFromBinary($info)
    {
        $sn = 0;
        if (count($info) == 4) {
            $sn = self::readUint32($info, 0);
        } elseif (count($info) == 2) {
            $sn = self::readUint16($info, 0);
        } elseif (count($info) == 1) {
            $sn = self::readUnsignedByte($info, 0);
        } else {
            return;
        }
        $result = ['Unique No' => $sn];

        return $result;
    }

    public static function bcnDecodeFromBinary($info)
    {
        $i = 0;

        while ($i < count($info)) {
            if (($info[$i] & 0x80) == 0) {// iBeacon lost
                $count = ($info[$i++] & 0x7F);
                if (count($info) < ($count * 20 + $i)) {
                    return;
                }

                $cnt = 0;
                while ($cnt < $count) {
                    for ($j = 0; $j < 16; ++$j) {
                        $strUUID .= sprintf('%2X ', $info[$i++]);
                    }
                    $major = self::readUint16($info, $i);
                    $i += 2;
                    $minor = self::readUint16($info, $i);
                    $i += 2;
                    // rever se der erro com valores----------------------------------
                    array_push($strBeacon, [
                            'iBeacon lost' => [
                                'UUID' => '0x' . $strUUID,
                                'Major' => '0x' . $major,
                                'Minor' => '0x' . $minor,
                            ],
                        ]);
                    ++$cnt;
                }
            }// iBeacon found
            else {
                $count = ($info[$i++] & 0x7F);
                if (count($info) < ($count * 22 + $i)) {
                    return;
                }
                $cnt = 0;
                while ($cnt < $count) {
                    for ($j = 0; $j < 16; ++$j) {
                        $strUUID .= sprintf('%2X ', $info[$i++]);
                    }
                    $major = self::readUint16($info, $i);
                    $i += 2;
                    $minor = self::readUint16($info, $i);
                    $i += 2;
                    $power = $info[$i++];
                    $rssi = $info[$i++];
                    // String strBeacon = String.Format("    iBeacon found UUID:0x{0} Major:0x{1:X0000} Minor:0x{2:X0000} Power:0x{3:X0000} RSSI:-{4}dbm\r\n",
                    //                                      strUUID,
                    //                                      major,
                    //                                      minor,
                    //                                      power,
                    //                                      rssi);
                    // rever se der erro com valores----------------------------------
                    array_push($strBeacon, [
                            'iBeacon found' => [
                                'UUID' => '0x' . $strUUID,
                                'Major' => '0x' . $major,
                                'Minor' => '0x' . $minor,
                                'Power' => '0x' . $minor,
                                'RSSI' => '0x' . $minor,
                            ],
                        ]);
                    ++$cnt;
                }
            }
        }

        return $strBeacon;
    }

    public static function trpDecodeFromBinary($info)
    {
        if (count($info) != 0x31) {
            return;
        }

        $pos = 0;

        $startTime = self::readUint32($info, $pos);
        $pos += 4;
        $timeOffset = \DateTime::createFromFormat('Y-m-d H:i:s', '2000-01-01 00:00:00');
        $sTime = clone $timeOffset;
        $sTime->add(new DateInterval('PT' . ($startTime * 10000000) . 'S'));
        array_push($array, [
                'Start time' => $sTime,
            ]);

        $endTime = self::readUint32($info, $pos);
        $pos += 4;
        $eTime = clone $timeOffset;
        $sTime->add(new DateInterval('PT' . ($endTime * 10000000) . 'S'));
        array_push($array, [
                'End time' => $sTime,
            ]);

        $startLat = self::readInt32($info, $pos);
        $pos += 4;
        $startLon = self::readInt32($info, $pos);
        $pos += 4;
        array_push($array, [
                'Start Position' => [
                    'lat' => $startLat / 1000000,
                    'lon' => $startLon / 1000000,
                ],
            ]);

        $endLat = self::readInt32($info, $pos);
        $pos += 4;
        $endLon = self::readInt32($info, $pos);
        $pos += 4;
        array_push($array, [
                'End Position' => [
                    'lat' => $endLat / 1000000,
                    'lon' => $endLon / 1000000,
                ],
            ]);

        $startMile = self::readUint32($info, $pos);
        $pos += 4;
        array_push($array, [
                'Start mileage' => [
                    'distance' => $startMile,
                    'unit' => 'metros',
                ],
            ]);

        $endMile = self::readUint32($info, $pos);
        $pos += 4;
        array_push($array, [
                'End mileage' => [
                    'distance' => $endMile,
                    'unit' => 'metros',
                ],
            ]);

        $fuelCalId = self::readUnsignedByte($info, $pos);
        ++$pos;

        $startFuel = self::readUint32($info, $pos);
        $pos += 4;
        array_push($array, [
                'Start fuel consumption' => [
                    'cal' => $fuelCalId,
                    'fuel' => $startFuel,
                ],
            ]);

        $endFuel = self::readUint32($info, $pos);
        $pos += 4;
        array_push($array, [
                'End fuel consumption' => [
                    'cal' => $fuelCalId,
                    'fuel' => $endFuel,
                ],
            ]);

        $idleSec = self::readUint32($info, $pos);
        $pos += 4;
        array_push($array, [
                'Idle time' => [
                    'value' => $idleSec,
                    'unit' => 'seconds',
                ],
            ]);

        $maxSpd = self::readUint16($info, $pos);
        $pos += 2;
        array_push($array, [
                'Max speed' => [
                    'value' => $maxSpd,
                    'unit' => 'km/h',
                ],
            ]);

        $maxRpm = self::readUint16($info, $pos);
        $pos += 2;
        array_push($array, [
                'Max RPM' => [
                    'value' => $maxRpm,
                    'unit' => 'rpm',
                ],
            ]);

        return $array;
    }

    public static function satDecodeFromBinary($info)
    {
        if (count($info) != 3) {
            return;
        }
        $array = [
                'GPS Sat' => [
                    'value1' => self::readUnsignedByte($info, 0),
                    'value2' => self::readUnsignedByte($info, 1),
                    'value3' => self::readUnsignedByte($info, 2),
                    'unit' => 'dBH',
                ],
            ];

        return $array;
    }

    public static function egtDecodeFromBinary($info)
    {
        if (count($info) != 4) {
            return;
        }
        $array = [
                'Engine seconds' => self::readUint32($info, 0),
            ];

        return $array;
    }

    public static function gdcDecodeFromBinary($info)
    {
        if (count($info) != 8) {
            return;
        }
        $result = [
                'GPRS data count' => [
                    'UL' => self::readUint32($info, 0),
                    'DL' => self::readUint32($info, 4),
                    'unit' => 'bytes',
                ],
            ];

        return $result;
    }

    public static function clgDecodeFromBinary($info)
    {
        $canSegNames = [
                        'Ignition key',
                        'Total distance',
                        'Total fuel used',
                        'Fuel level in liters',
                        'Fuel level in percents',
                        'Range',
                        'Vehicle speed',
                        'Engine speed',
                        'Accelerator pedal pressure',
                        'Engine coolant temperature',
                        'Total engine hours',
                        'Total driving time',
                        'Total engine idle time',
                        'Total idle fuel used',
                        'Axle weight',
                        'Tachograph information',
                        'Detailed information/indicators',
                        'Doors',
                        'Rapid brakings',
                        'Rapid accelerations',
                        'Total vehicle overspeed time',
                        'Total engine overspeed time',
                    ];
        $canSegSize = [1, 4, 4, 2, 2, 3, 2, 2, 2, 2, 4, 4, 4, 4, 2, 2, 4, 1, 3, 3, 4, 4];
        $canSegCof = [1,   0.01, 0.01, 0.1, 0.1, 1, 0.1, 0.25, 0.1, 1, 0.001, 0.001, 0.001, 0.01, 0.5, 1, 1, 1, 1, 1, 0.001, 0.001];
        $canSegOfs = [0, 0, 0, 0, 0, 0, 0, 0, 0, -50, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $canSegFmt = [
                '{0:D}',
                '{0:0.00}',
                '{0:0.00}',
                '{0:0.0}',
                '{0:0.0}',
                '{0:D}',
                '{0:0.0}',
                '{0:0.00}',
                '{0:0.0}',
                '{0:D}',
                '{0:0.000}',
                '{0:0.000}',
                '{0:0.000}',
                '{0:0.00}',
                '{0:0.0}',
                '{0:X}',
                '{0:X}',
                '{0:X}',
                '{0:D}',
                '{0:D}',
                '{0:0.000}',
                '{0:0.000}',
            ];
        if (count($info) <= 3) {
            return;
        }
        $offset = 0;
        $mask = self::readUint24($info, $offset);
        $offset += 3;

        $bit_mask = 0x01;
        $strOut = '';
        for ($i = 0; $i < 22; ++$i) {
            if (($mask & $bit_mask) != 0) {
                if ($canSegSize[$i] == 1) {
                    $val = self::readUnsignedByte($info, $offset) * $canSegCof[$i] + $canSegOfs[$i];
                    ++$offset;
                } elseif ($canSegSize[$i] == 2) {
                    $val = self::readUint16($info, $offset) * $canSegCof[$i] + $canSegOfs[$i];
                    $offset += 2;
                } elseif ($canSegSize[$i] == 3) {
                    $val = self::readUint24($info, $offset) * $canSegCof[$i] + $canSegOfs[$i];
                    $offset += 3;
                } elseif ($canSegSize[$i] == 4) {
                    $val = self::readUint32($info, $offset) * $canSegCof[$i] + $canSegOfs[$i];
                    $offset += 4;
                } else {
                    $bit_mask <<= 1;
                    continue;
                }

                if ($canSegFmt[$i] == '{0:X}' || $canSegFmt[$i] == '{0:D}') {
                    $strVal = [$canSegFmt[$i], intval($val)];
                } else {
                    $strVal = sprintf($canSegFmt[$i], $val);
                }
                array_push($strOut, [$canSegNames[$i] => $strVal]);
            }
            $bit_mask <<= 1;
        }

        return ['Can Logistic' => $strOut];
    }

    public static function dioDecodeFromBinary($info)
    {
        $canSegNames = [
                            'Ignition key',
                            'Total distance',
                            'Total fuel used',
                            'Fuel level in liters',
                            'Fuel level in percents',
                            'Range',
                            'Vehicle speed',
                            'Engine speed',
                            'Accelerator pedal pressure',
                            'Engine coolant temperature',
                            'Total engine hours',
                            'Total driving time',
                            'Total engine idle time',
                            'Total idle fuel used',
                            'Axle weight',
                            'Tachograph information',
                            'Detailed information/indicators',
                            'Doors',
                            'Rapid brakings',
                            'Rapid accelerations',
                            'Total vehicle overspeed time',
                            'Total engine overspeed time',
                        ];

        $canSegSize = [1, 4, 4, 2, 2, 3, 2, 2, 2, 2, 4, 4, 4, 4, 2, 2, 4, 1, 3, 3, 4, 4];
        $canSegCof = [1, 0.01, 0.01, 0.1, 0.1, 1, 0.1, 0.25, 0.1, 1, 0.001, 0.001, 0.001, 0.01, 0.5, 1, 1, 1, 1, 1, 0.001, 0.001];
        $canSegOfs = [0, 0, 0, 0, 0, 0, 0, 0, 0, -50, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $canSegFmt = [
                        '{0:D}',
                        '{0:0.00}',
                        '{0:0.00}',
                        '{0:0.0}',
                        '{0:0.0}',
                        '{0:D}',
                        '{0:0.0}',
                        '{0:0.00}',
                        '{0:0.0}',
                        '{0:D}',
                        '{0:0.000}',
                        '{0:0.000}',
                        '{0:0.000}',
                        '{0:0.00}',
                        '{0:0.0}',
                        '{0:X}',
                        '{0:X}',
                        '{0:X}',
                        '{0:D}',
                        '{0:D}',
                        '{0:0.000}',
                        '{0:0.000}',
                    ];
        if (count($info) <= 3) {
            return;
        }
        $offset = 0;
        $mask = self::readUint24($info, $offset);
        $offset += 3;
        $vals = [];
        $bit_mask = 0x01;
        $strOut = '';
        for ($i = 0; $i < 22; ++$i) {
            if (($mask & $bit_mask) != 0) {
                if ($canSegSize[$i] == 1) {
                    $val = self::readUnsignedByte($info, $offset) * $canSegCof[$i] + $canSegOfs[$i];
                    ++$offset;
                } elseif ($canSegSize[$i] == 2) {
                    $val = self::readUint16($info, $offset) * $canSegCof[$i] + $canSegOfs[$i];
                    $offset += 2;
                } elseif ($canSegSize[$i] == 3) {
                    $val = self::readUint24($info, $offset) * $canSegCof[$i] + $canSegOfs[$i];
                    $offset += 3;
                } elseif ($canSegSize[$i] == 4) {
                    $val = self::readUint32($info, $offset) * $canSegCof[$i] + $canSegOfs[$i];
                    $offset += 4;
                } else {
                    $bit_mask <<= 1;
                    continue;
                }

                // vai dar erro---------------------------------------------------------
                if ($canSegFmt[$i] == '{0:X}' || $canSegFmt[$i] == '{0:D}') {
                    $strVal = sprintf($canSegFmt[$i], intval($val));
                } else {
                    $strVal = sprintf($canSegFmt[$i], $val);
                }
                array_push($strOut, [$canSegNames[$i] => $strVal]);
            }
            $bit_mask <<= 1;
        }

        return ['Can Logistic' => $strOut];
    }

    public static function vhdSegmentParse($index, $val)
    {
        $vhdSegNames = [
                            'Total distance',
                            'Total fuel used',
                            'Fuel level in liters',
                            'Fuel level in percents',
                            'Range',
                            'Vehicle speed',
                            'Engine speed',
                            'Accelerator pedal pressure',
                            'Engine coolant temperature',
                            'Total engine hours',
                            'Total driving time',
                            'Total engine idle time',
                            'Total idle fuel used',
                            'Indicators',
                            'Doors',
                            'Lights',
                        ];
        switch ($index) {
                case 0:// Total distance
                        $value = $val * 0.01;

                        return [$vhdSegNames[$index] => $value, 'unit' => 'km'];

                case 1:// Total fuel used
                        $value = $val * 0.001;

                        return [$vhdSegNames[$index] => $value, 'unit' => 'L'];

                case 2:// Fuel level in liters
                        $value = $val * 0.1;

                        return [$vhdSegNames[$index] => $value, 'unit' => 'L'];

                case 3:// Fuel level in percents
                        $value = $val * 0.4;

                        return [$vhdSegNames[$index] => $value, 'unit' => '%'];

                case 4:// Range
                        return [$vhdSegNames[$index] => $val, 'unit' => 'km'];

                case 5:// Vehicle speed
                        $value = $val * 0.1;

                        return [$vhdSegNames[$index] => $value, 'unit' => 'km/h'];

                case 6:// Engine speed
                        $value = $val * 0.25;

                        return [$vhdSegNames[$index] => $value, 'unit' => 'rpm'];

                case 7:// Accelerator pedal pressure
                        $value = $val * 0.4;

                        return [$vhdSegNames[$index] => $value, 'unit' => '%'];

                case 8:// Engine coolant temperature
                        $value = $val - 40;

                        return [$vhdSegNames[$index] => $value, 'unit' => '°C'];

                case 9:// Total engine hours
                        $value = $val / 3600;

                        return [$vhdSegNames[$index] => $value, 'unit' => 'hours'];

                case 10:// Total driving time
                        $value = $val / 3600;

                        return [$vhdSegNames[$index] => $value, 'unit' => 'hours'];

                case 11:// Total engine idle time
                        $value = $val / 3600;

                        return [$vhdSegNames[$index] => $value, 'unit' => 'hours'];

                case 12:// Total idle fuel used
                        $value = $val * 0.001;

                        return [$vhdSegNames[$index] => $value, 'unit' => 'L'];

                case 13:// Indicators
                        $indsNames =
                            [
                                'Engine status',
                                'Hand brake',
                                'Brake pedal',
                                'Clutch pedal',
                                'Central lock',
                                'Reverse gear',
                                'Seatbelt',
                                'Air condition',
                                'Cruise controller',
                                'Battery charging',
                            ];
                        $strStatus = ['OFF', 'ON', 'RUNNING', 'NA'];
                        $bitWidth = [2, 1, 1, 1, 1, 1, 1, 1, 1, 1];
                        $strOut = [$vhdSegNames[$index] => $val];
                        for ($i = 0; $i < count($indsNames); ++$i) {
                            $mask = (0x01 << $bitWidth[$i]) - 1;
                            $bitVal = ($val & $mask);
                            array_push($strOut, ['indicators' => [$indsNames[$i] => $strStatus[$bitVal]]]);
                            $val >>= $bitWidth[$i];
                        }

                        return $strOut;

                case 14:// Doors
                        $doorNames = [
                                'Driver',
                                'Passenger',
                                'Rear left',
                                'Rear right',
                                'Truck',
                                'Hood',
                            ];
                        $strStatus = ['CLOSE', 'OPEN', 'NA', 'NA'];
                        $strOut = [$vhdSegNames[$index], $val];
                        for ($i = 0; $i < count($doorNames); ++$i) {
                            $bitVal = $val & 0x01;
                            array_push($strOut, ['doors' => [$doorNames[$i], $strStatus[$bitVal]]]);
                            $val >>= 1;
                        }

                        return $strOut;

                case 15:// Lights
                        $lightNames = [
                                    'Running',
                                    'Low beams',
                                    'High beams',
                                    'Front fog',
                                    'Rear fog',
                                    'Hazard',
                                ];
                        $strStatus = ['OFF', 'ON', 'NA', 'NA'];
                        $strOut = [$vhdSegNames[$index] => $val];
                        for ($i = 0; $i < count($lightNames); ++$i) {
                            $bitVal = $val & 0x01;
                            array_push($strOut, ['lights' => [$lightNames[$i], $strStatus[$bitVal]]]);
                            $val >>= 1;
                        }

                        return $strOut;

                default:
                    return '';
            }
    }

    public static function vhdDecodeFromBinary($info)
    {
        if (count($info) <= 4) {
            return;
        }
        $vhdSegSize = [4, 4, 2, 1, 2, 2, 2, 1, 1, 4, 4, 4, 4, 2, 1, 1, 4];
        $offset = 0;
        $mask = self::readUint32($info, $offset);
        $offset += 4;
        $strOut = '';
        for ($i = 0; $i < 32 && $mask != 0; ++$i) {
            if (($mask & 0x01) != 0) {
                if ($vhdSegSize[$i] == 1) {
                    $val = self::readUnsignedByte($info, $offset);
                    ++$offset;
                } elseif ($vhdSegSize[$i] == 2) {
                    $val = self::readUint16($info, $offset);
                    $offset += 2;
                } elseif ($vhdSegSize[$i] == 3) {
                    $val = self::readUint24($info, $offset);
                    $offset += 3;
                } elseif ($vhdSegSize[$i] == 4) {
                    $val = self::readUint32($info, $offset);
                    $offset += 4;
                } else {
                    $mask >>= 1;
                    continue;
                }
                array_push($strOut, self::vhdSegmentParse($i, $val));
            }
            $mask >>= 1;
        }
        $result = [
                'Vehicle Info' => $strOut,
            ];

        return $result;
    }

    public static function brvDecodeFromBinary($info, $evt_type)
    {
        switch ($evt_type) {
                case 0:
                        if (count($info) != 1) {
                            break;
                        }
                        $remote_keys = ['Personal', 'Business', 'End trip', 'Fun1', 'Fun2', 'Fun3', 'Fun4'];
                        $key_val = $info[0];

                        for ($i = 0; $i < 8; ++$i) {
                            if (($key_val & (0x01 << $i)) != 0) {
                                break;
                            }
                        }
                        $strBrv = [
                            'Remote Event' => [
                                'Key pressed' => $remote_keys[$i],
                                'unit' => '',
                            ],
                        ];
                        break;

                case 1:
                        if (count($info) != 1) {
                            break;
                        }
                        $strBrv = [
                            'Remote Event' => [
                                'Batt percent' => $info[0],
                                'unit' => '%',
                            ],
                        ];
                        break;

                case 2:
                        if (count($info) != 6) {
                            break;
                        }
                        $strBrv = [
                            'Remote Event' => [
                                'Remote paired FleetID' => self::readUint32($info, 0),
                                'Remote paired UniqueID' => self::readUint16($info, 4),
                            ],
                        ];
                        break;

                default:
                    break;
            }

        return $strBrv;
    }

    public static function msiDecodeFromBinary($info)
    {
        $string = '';
        foreach ($info as $chr) {
            $string .= chr($chr);
        }
        $strMsi = ['IMSI' => $string];

        return $strMsi;
    }

    public static function btdDecodeFromBinary($info)
    {
        if (count($info) % 7 != 0) {
            return;
        }

        for ($i = 0; $i < count($info) / 7; ++$i) {
            $id = sprintf('%8X', self::readUint32($info, $i * 7));
            array_push($values, [
                    'ID' => $id,
                    'Bat(%)' => $info[$i * 7 + 4],
                    'Temp (C)' => 0.01 * self::readInt16($info, $i * 7 + 5),
                ]);
        }
        $array = [
                'BLE Temp Sensor' => $values,
            ];

        return $array;
    }

    public static function ibnDecodeFromBinary($info)
    {
        if (count($info) != 11) {
            return;
        }
        $strId = '';
        for ($i = 0; $i < 6; ++$i) {
            $strId .= sprintf('%2X', $info[$i]);
        }
        $strButton = [
                'iButton' => $strId,
                'status' => $info[count($info) - 1] == 0 ? 'Unauthorized' : 'Authorized',
            ];
    }

    public static function owsDecodeFromBinary($info, $wId)
    {
        if ($wId) {
            if (count($info) % 8 != 0) {
                return;
            }
        } else {
            if (count($info) % 2 != 0) {
                return;
            }
        }
        $arrayddd['Wire Temp Sensor'] = [];

        if ($wId) {
            for ($i = 0; $i < count($info) / 8; ++$i) {
                $strId = '';
                for ($j = 0; $j < 6; ++$j) {
                    $strId .= sprintf('%2X ', $info[8 * $i + $j]);
                }
                array_push($array['Wire Temp Sensor'], [
                        'ID' => $strId,
                        'Temp' => 0.0625 * self::readInt16($info, $i * 8 + 6),
                        'unit' => 'C',
                    ]);
            }
        } else {
            for ($i = 0; $i < count($info) / 2; ++$i) {
                array_push($array['Wire Temp Sensor'], [
                        'Temp' => 0.0625 * self::readInt16($info, $i * 2),
                        'unit' => 'C',
                    ]);
            }
        }

        return $array;
    }

    public static function netDecodeFromBinary($info)
    {
        $strNet = '';
        if (count($info) == 4) {
            $strNet = [
                    'Cellular Net Status' => [
                        'Status' => 'Off',
                        'Time' => -self::readInt32($info, 0),
                        'unit' => 'sec',
                    ],
                ];

            return $strNet;
        } elseif (count($info) == 6) {
            $netTypes = ['2G', '2G', '3G', '2.5G', '3.5G+', '3.5G+', '3.5G+', '4G'];
            $status = self::readUnsignedByte($info, 4);
            $netStatus = [
                    'Reg' => $status & 0x07,
                    'Signal' => ($status >> 3) & 0x07,
                    'Data Conn.' => ($status & 0x40) != 0 ? 'Yes' : 'No',
                    'Server Conn.' => ($status & 0x80) != 0 ? 'Yes' : 'No',
                    ];
            $netType = $netTypes[self::readUnsignedByte($info, 5) >= count($netTypes) ? 0 : self::readUnsignedByte($info, 5)];

            $strNet = [
                    'Cellular Net Status' => [
                        'Status' => 'On',
                        'Time' => self::readInt32($info, 0),
                        'unit' => 'sec',
                        'Net Status' => $netStatus,
                        'Net Type' => $netType,
                        ],
                    ];

            return $strNet;
        }
        if ($strNet != '') {
            return null;
        }
    }

    public static function obdService0102Decode($value, $service, $pid)
    {
        $str = '';
        $array = [];
        switch ($pid) {
                case 0x01:
                        if (count($value) != 4) {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }
                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                'service' => $serviceHex,
                                'value' => $hex,
                            ];

                            return $array;
                        }

                        $serviceHex = sprintf('%02X', $service);
                        $pidHex = sprintf('%02X', $pid);

                        $dtc = [
                                'service' => $serviceHex,
                                'pid' => $pidHex,
                                'DTC_CNT' => $value[0] & 0x7F,
                                'MIL' => $value[0] & 0x80 != 0 ? 'ON' : 'OFF',
                            ];

                        $fuel = [
                                'MIS_SUP' => ($value[1] & 0x01) != 0 ? 'YES' : 'NO',
                                'FUEL_SUP' => ($value[1] & 0x02) != 0 ? 'YES' : 'NO',
                                'CCM_SUP' => ($value[1] & 0x04) != 0 ? 'YES' : 'NO',
                                'MIS_RDY' => ($value[1] & 0x10) != 0 ? 'YES' : 'NO',
                                'FUEL_RDY' => ($value[1] & 0x20) != 0 ? 'YES' : 'NO',
                                'CCM_RDY' => ($value[1] & 0x40) != 0 ? 'YES' : 'NO',
                            ];

                        $airSup = [
                                'CAT_SUP' => ($value[2] & 0x01) != 0 ? 'YES' : 'NO',
                                'HCAT_SUP' => ($value[2] & 0x02) != 0 ? 'YES' : 'NO',
                                'EVAP_SUP' => ($value[2] & 0x04) != 0 ? 'YES' : 'NO',
                                'AIR_SUP' => ($value[2] & 0x08) != 0 ? 'YES' : 'NO',
                                'ACRF_SUP' => ($value[2] & 0x10) != 0 ? 'YES' : 'NO',
                                'O2S_SUP' => ($value[2] & 0x20) != 0 ? 'YES' : 'NO',
                                'HTR_SUP' => ($value[2] & 0x40) != 0 ? 'YES' : 'NO',
                                'EGR_SUP' => ($value[2] & 0x80) != 0 ? 'YES' : 'NO',
                            ];

                        $airRdy = [
                                'CAT_RDY' => ($value[3] & 0x01) != 0 ? 'YES' : 'NO',
                                'HCAT_RDY' => ($value[3] & 0x02) != 0 ? 'YES' : 'NO',
                                'EVAP_RDY' => ($value[3] & 0x04) != 0 ? 'YES' : 'NO',
                                'AIR_RDY' => ($value[3] & 0x08) != 0 ? 'YES' : 'NO',
                                'ACRF_RDY' => ($value[3] & 0x10) != 0 ? 'YES' : 'NO',
                                'O2S_RDY' => ($value[3] & 0x20) != 0 ? 'YES' : 'NO',
                                'HTR_RDY' => ($value[3] & 0x40) != 0 ? 'YES' : 'NO',
                                'EGR_RDY' => ($value[3] & 0x80) != 0 ? 'YES' : 'NO',
                            ];

                        $array = [
                            'dtc' => $dtc,
                            'fuel' => $fuel,
                            'airSup' => $airSup,
                            'airRdy' => $airRdy,
                        ];

                        return $array;
                        break;

                case 0x04:
                        if (count($value) != 1) {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }

                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                'service' => $serviceHex,
                                'value' => $hex,
                            ];

                            return $array;
                        }

                        $clv = ($value[0]) * 100 / 255;

                        $serviceHex = sprintf('%02X', $service);
                        $pidHex = sprintf('%02X', $pid);

                        $array = [
                                // 'service' => $serviceHex,
                                // 'pid' => $pidHex,
                                'Calculated LOAD Value' => $clv,
                            ];

                        return $array;
                        break;

                case 0x05:
                        if (count($value) != 1) {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }

                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                'service' => $serviceHex,
                                'value' => $hex,
                            ];

                            return $array;
                        }
                        $ect = $value[0];
                        $ect -= 40;

                        // $serviceHex = sprintf('%02X', $service);
                        // $pidHex = sprintf('%02X', $pid);

                        $array = [
                                // 'service' => $serviceHex,
                                // 'pid' => $pidHex,
                                'Engine Coolant Temperature' => $ect,
                                'unit' => 'Celsius',
                            ];

                        return $array;
                        break;

                case 0x06:
                        if (count($value) == 1) {
                            // $serviceHex = sprintf('%02X', $service);
                            // $pidHex = sprintf('%02X', $pid);

                            $array = [
                                    // 'service' => $serviceHex,
                                    // 'pid' => $pidHex,
                                    'Short Term Fuel Trim Bank1' => (($value[0] - 128) / 128) * 100,
                                    'unit' => '%',
                                ];
                        } elseif (count($value) == 2) {
                            // $serviceHex = sprintf('%02X', $service);
                            // $pidHex = sprintf('%02X', $pid);
                            $array = [
                                    // 'service' => $serviceHex,
                                    // 'pid' => $pidHex,
                                    'Short Term Fuel Trim Bank1' => (($value[0] - 128) / 128) * 100,
                                    'Short Term Fuel Trim Bank3' => (($value[1] - 128) / 128) * 100,
                                    'unit' => '%',
                                ];
                        } else {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }
                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                    'Short Term Fuel service Bank1/Bank3' => $serviceHex,
                                    'value' => $hex,
                                ];
                        }

                        return $array;
                        break;

                case 0x07:
                        if (count($value) == 1) {
                            // $serviceHex = sprintf('%02X', $service);
                            // $pidHex = sprintf('%02X', $pid);

                            $array = [
                                    // 'service' => $serviceHex,
                                    // 'pid' => $pidHex,
                                    'Long Term Fuel Trim Bank1' => (($value[0] - 128) / 128) * 100,
                                    'unit' => '%',
                                ];
                        } elseif (count($value) == 2) {
                            // $serviceHex = sprintf('%02X', $service);
                            // $pidHex = sprintf('%02X', $pid);
                            $array = [
                                    // 'service' => $serviceHex,
                                    // 'pid' => $pidHex,
                                    'Long Term Fuel Trim Bank1' => (($value[0] - 128) / 128) * 100,
                                    'Long Term Fuel Trim Bank3' => (($value[1] - 128) / 128) * 100,
                                    'unit' => '%',
                                ];
                        } else {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }
                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                    'Long Term Fuel service Bank1/Bank3' => $serviceHex,
                                    'value' => $hex,
                                ];
                        }

                        return $array;
                        break;

                case 0x08:
                        if (count($value) == 1) {
                            // $serviceHex = sprintf('%02X', $service);
                            // $pidHex = sprintf('%02X', $pid);
                            $array = [
                                    // 'service' => $serviceHex,
                                    // 'pid' => $pidHex,
                                    'Short Term Fuel Trim Bank2' => (($value[0] - 128) / 128) * 100,
                                    'unit' => '%',
                                ];
                        } elseif (count($value) == 2) {
                            // $serviceHex = sprintf('%02X', $service);
                            // $pidHex = sprintf('%02X', $pid);
                            $array = [
                                    // 'service' => $serviceHex,
                                    // 'pid' => $pidHex,
                                    'Short Term Fuel Trim Bank2' => (($value[0] - 128) / 128) * 100,
                                    'Short Term Fuel Trim Bank4' => (($value[1] - 128) / 128) * 100,
                                    'unit' => '%',
                                ];
                        } else {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }
                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                    'Short Term Fuel service Bank2/Bank4' => $serviceHex,
                                    'value' => $hex,
                                ];
                        }

                        return $array;
                        break;

                case 0x09:
                        if (count($value) == 1) {
                            // $serviceHex = sprintf('%02X', $service);
                            // $pidHex = sprintf('%02X', $pid);
                            $array = [
                                    // 'service' => $serviceHex,
                                    // 'pid' => $pidHex,
                                    'Long Term Fuel Trim Bank2' => (($value[0] - 128) / 128) * 100,
                                    'unit' => '%',
                                ];
                        } elseif (count($value) == 2) {
                            // $serviceHex = sprintf('%02X', $service);
                            // $pidHex = sprintf('%02X', $pid);
                            $array = [
                                    // 'service' => $serviceHex,
                                    // 'pid' => $pidHex,
                                    'Long Term Fuel Trim Bank2' => (($value[0] - 128) / 128) * 100,
                                    'Long Term Fuel Trim Bank4' => (($value[1] - 128) / 128) * 100,
                                    'unit' => '%',
                                ];
                        } else {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }
                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                    'Long Term Fuel service Bank2/Bank4' => $serviceHex,
                                    'value' => $hex,
                                ];
                        }

                        return $array;
                        break;

                case 0x0A:
                        if (count($value) != 1) {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }
                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                    'Fuel Rail Pressure service' => $serviceHex,
                                    'value' => $hex,
                                ];

                            return $array;
                        }
                        // $serviceHex = sprintf('%02X', $service);
                        // $pidHex = sprintf('%02X', $pid);
                        $array = [
                                    // 'service' => $serviceHex,
                                    // 'pid' => $pidHex,
                                    'Fuel Rail Pressure' => $value[0] * 3,
                                    'unit' => 'kPa',
                                ];

                        return $array;
                        break;

                case 0x0B:
                        if (count($value) != 1) {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }
                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                    '(map)Intake Manifold Absolute service' => $serviceHex,
                                    'value' => $hex,
                                ];

                            return $array;
                        }
                        // $serviceHex = sprintf('%02X', $service);
                        // $pidHex = sprintf('%02X', $pid);
                        $array = [
                                    // 'service' => $serviceHex,
                                    // 'pid' => $pidHex,
                                    '(map)Intake Manifold Absolute Pressure' => $value[0],
                                    'unit' => 'kPa',
                                ];

                        return $array;
                        break;

                case 0x0C:
                        if (count($value) != 2) {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }
                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                    'Engine RPM service' => $serviceHex,
                                    'value' => $hex,
                                ];

                            return $array;
                        }
                        $rpm = (($value[0] * 256 + $value[1])) / 4;
                        if ($rpm != 65535 / 4) {
                            // $serviceHex = sprintf('%02X', $service);
                            // $pidHex = sprintf('%02X', $pid);
                            $array = [
                                        // 'service' => $serviceHex,
                                        // 'pid' => $pidHex,
                                        'Engine RPM' => $rpm,
                                        'unit' => 'rpm',
                                    ];

                            return $array;
                        }
                        break;

                case 0x0D:
                        if (count($value) != 1) {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }
                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                    'Vehicle Speed service' => $serviceHex,
                                    'value' => $hex,
                                ];

                            return $array;
                        }
                        $speed = $value[0];
                        if ($speed != 255) {
                            // $serviceHex = sprintf('%02X', $service);
                            // $pidHex = sprintf('%02X', $pid);
                            $array = [
                                        // 'service' => $serviceHex,
                                        // 'pid' => $pidHex,
                                        'Vehicle Speed' => $speed,
                                        'unit' => 'km/h',
                                    ];

                            return $array;
                        }

                        return 0;
                        break;

                case 0x0E:
                        if (count($value) != 1) {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }
                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                    'Ignition Timing service' => $serviceHex,
                                    'value' => $hex,
                                ];

                            return $array;
                        }
                        // $serviceHex = sprintf('%02X', $service);
                        // $pidHex = sprintf('%02X', $pid);
                        $array = [
                                    // 'service' => $serviceHex,
                                    // 'pid' => $pidHex,
                                    'Ignition Timing Advance for #1 Cylinder' => ($value[0] - 128) / 2,
                                    'unit' => 'degree',
                                ];

                        return $array;
                        break;

                case 0x0F:
                        if (count($value) != 1) {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }
                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                    'Intake Air Temperature service' => $serviceHex,
                                    'value' => $hex,
                                ];

                            return $array;
                        }
                        $iat = $value[0];
                        $iat -= 40;
                        // $serviceHex = sprintf('%02X', $service);
                        // $pidHex = sprintf('%02X', $pid);
                        $array = [
                                    // 'service' => $serviceHex,
                                    // 'pid' => $pidHex,
                                    'Intake Air Temperature' => $iat,
                                    'unit' => 'Celsius',
                                ];

                        return $array;
                        break;

                case 0x10:
                        if (count($value) != 2) {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }
                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                    'Air Flow Rate from Mass Air Flow Sensor service' => $serviceHex,
                                    'value' => $hex,
                                ];

                            return $array;
                        }
                        $maf = (($value[0] * 256 + $value[1])) / 100;
                        // $serviceHex = sprintf('%02X', $service);
                        // $pidHex = sprintf('%02X', $pid);
                        $array = [
                                    // 'service' => $serviceHex,
                                    // 'pid' => $pidHex,
                                    'Air Flow Rate from Mass Air Flow Sensor' => $maf,
                                    'unit' => 'g/s',
                                ];

                        return $array;
                        break;

                case 0x11:
                        if (count($value) != 1) {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }
                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                    'Absolute Throttle Position service' => $serviceHex,
                                    'value' => $hex,
                                ];

                            return $array;
                        }
                        $position = ($value[0]) * 100 / 255;
                        // $serviceHex = sprintf('%02X', $service);
                        // $pidHex = sprintf('%02X', $pid);
                        $array = [
                                    // 'service' => $serviceHex,
                                    // 'pid' => $pidHex,
                                    'Absolute Throttle Position' => $position,
                                    'unit' => '%',
                                ];

                        return $array;
                        break;

                case 0x21:
                        if (count($value) != 2) {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }
                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                    'Distance Travelled service' => $serviceHex,
                                    'value' => $hex,
                                ];

                            return $array;
                        }
                        $distance = 256 * $value[0] + $value[1];
                        // $serviceHex = sprintf('%02X', $service);
                        // $pidHex = sprintf('%02X', $pid);
                        $array = [
                                    // 'service' => $serviceHex,
                                    // 'pid' => $pidHex,
                                    'Distance Travelled While MIL is Activated' => $distance,
                                    'unit' => 'km',
                                ];

                        return $array;
                        break;

                case 0x2F:
                        if (count($value) != 1) {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }
                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                    'Fuel level service' => $serviceHex,
                                    'value' => $hex,
                                ];

                            return $array;
                        }
                        $percent = ($value[0]) * 100 / 255;
                        // $serviceHex = sprintf('%02X', $service);
                        // $pidHex = sprintf('%02X', $pid);
                        $array = [
                                    // 'service' => $serviceHex,
                                    // 'pid' => $pidHex,
                                    'Fuel level' => $percent,
                                    'unit' => '%',
                                ];

                        return $array;
                        break;

                case 0x31:
                        if (count($value) != 2) {
                            $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }
                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                    'Distance traveled since codes cleared service' => $serviceHex,
                                    'value' => $hex,
                                ];

                            return $array;
                        }
                        $distance = 256 * $value[0] + $value[1];
                        // $serviceHex = sprintf('%02X', $service);
                        // $pidHex = sprintf('%02X', $pid);
                        $array = [
                                    // 'service' => $serviceHex,
                                    // 'pid' => $pidHex,
                                    'Distance traveled since codes cleared' => $distance,
                                    'unit' => 'km',
                                ];

                        return $array;
                        break;

                default:
                        $hex = '';
                            for ($i = 0; $i < count($value); ++$i) {
                                $hex .= sprintf('%02X', $value[$i]);
                            }
                            $serviceHex = sprintf('%02X', $service);
                            $array = [
                                    'default service' => $serviceHex,
                                    'value' => $hex,
                                ];

                            return $array;
                        break;
            }
    }

    public static function obdService03Decode($value)
    {
        if (count($value) % 2 != 0) {
            $offset = 1;
        } else {
            $offset = 0;
        }
        $strDtcs = '';
        $dtcChars = ['P', 'C', 'B', 'U'];
        for ($i = 0; $i < count($value) / 2; ++$i) {
            $dtcA = $value[2 * $i + $offset];
            $dtcB = $value[2 * $i + $offset + 1];
            if ($dtcA == 0 && $dtcB == 0) {
                continue;
            }
            $itemA = sprintf($dtcChars[(($dtcA >> 6) & 0x03)]);
            $itemB = sprintf('%02X', ($dtcA & 0x3F));
            $itemC = sprintf('%02X', $dtcB);
            $strDtcs[] = [$itemA, $itemB, $itemC];
        }

        return $strDtcs;
    }

//         void ObdService21Decode(MemoryStream outStr, byte[] value)
//         {
//             string hex = "";
//             for (int i = 0; i < value.Length; i++)
//             {
//                 hex += String.Format("{0:X2} ", value[i]);
//             }
//             string str = String.Format("        [{0:X2}]: {1}\r\n",
//                             0x21,
//                             hex);
//             OutputText(outStr, str);
//         }

//                 void ObdService21Decode(MemoryStream outStr, byte[] value, int pid)//For Toyota Hilux
//         {
//             String str;
//             switch (pid)
//             {
//                 case 0x28:
//                     {
//                         if (value.Length != 3)
//                             return;
//                         Int32 distance = 65536 * value[0] + 256 * value[1] + value[2];
//                         str = String.Format("        [{0:X2}][{1:X2}]: {2}km--Distance\r\n",
//                                         0x21,
//                                         pid,
//                                         distance);
//                         break;
//                     }
//                 case 0x29:
//                     {
//                         if (value.Length != 1)
//                             return;
//                         double fuel = (double)value[0] * 0.5;
//                         str = String.Format("        [{0:X2}][{1:X2}]: {2}L--Fuel\r\n",
//                                         0x21,
//                                         pid,
//                                         fuel);
//                         break;
//                     }
//                 default:
//                     {
//                         string hex = "";
//                         for (int i = 0; i < value.Length; i++)
//                         {
//                             hex += String.Format("{0:X2} ", value[i]);
//                         }
//                         str = String.Format("        [{0:X2}][{1:X2}]: {2}\r\n",
//                                         0x21,
//                                         pid,
//                                         hex);
//                         break;
//                     }
//             }
//             OutputText(outStr, str);
//         }

//         void ObdService22Decode(MemoryStream outStr, byte[] value)
//         {
//             string hex = "";
//             for (int i = 0; i < value.Length; i++)
//             {
//                 hex += String.Format("{0:X2} ", value[i]);
//             }
//             string str = String.Format("        [{0:X2}]: {1}\r\n",
//                             0x22,
//                             hex);
//             OutputText(outStr, str);
//         }

    public static function udsService22Decode($value, $pid)// For VW Amarok
    {
        switch ($pid) {
                case 0x16A9:// Distance
                        if (count($value) != 4) {
                            return;
                        }
                        $distance = self::readUint32($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);

                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Distance' => $distance,
                                'unit' => 'km',
                            ];

                        return $array;
                        break;

                case 0x1047:// Engine torque
                        if (count($value) != 2) {
                            return;
                        }
                        $torque = self::readUint16($value, 0);
                        $str = sprintf('[%2X] [%4X]: %dNm--Engine torque', 0x22, $pid, 0.1 * $torque);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Engine torque' => 0.1 * $torque,
                                'unit' => 'Nm',
                            ];

                        return $array;
                        break;

                case 0x1221:// Accelerator pedal
                        if (count($value) != 2) {
                            return;
                        }
                        $pedal = self::readUint16($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Accelerator pedal' => 0.2 * $pedal,
                                'unit' => 'mV',
                            ];

                        return $array;
                        break;

                case 0xF449:// Accelerator position
                        if (count($value) != 1) {
                            return;
                        }
                        $pos = self::readUnsignedByte($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Accelerator positon' => $pos * 100 / 255,
                                'unit' => '%',
                            ];

                        return $array;
                        break;

                case 0x17D6:// Brake actuated status
                        if (count($value) != 1) {
                            return;
                        }
                        $brake = self::readUnsignedByte($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Brake actuated status' => $brake,
                                'unit' => '',
                            ];

                        return $array;
                        break;

                case 0xF40C:// Engine speed
                        if (count($value) != 2) {
                            return;
                        }
                        $rpm = self::readUint16($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Engine speed' => 0.25 * $rpm,
                                'unit' => 'rpm',
                            ];

                        return $array;
                        break;

                case 0x111A:// Fuel consumption
                        if (count($value) != 2) {
                            return;
                        }
                        $consumption = self::readUint16($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Fuel consumption' => 0.01 * $consumption,
                                'unit' => 'l/h',
                            ];

                        return $array;
                        break;

                case 0x100C:// Fuel Level
                        if (count($value) != 2) {
                            return;
                        }
                        $level = self::readUint16($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Fuel Level' => 0.01 * $level,
                                'unit' => 'l',
                            ];

                        return $array;
                        break;

                case 0xF423:// Fuel pressure
                        if (count($value) != 2) {
                            return;
                        }
                        $pressure = self::readUint16($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Fuel pressure' => 10 * $pressure,
                                'unit' => 'kPa',
                            ];

                        return $array;
                        break;

                case 0x121E:// Fuel pressure regulator value
                        if (count($value) != 2) {
                            return;
                        }
                        $val = self::readUint16($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Fuel pressure regulator value' => 100 * $val,
                                'unit' => 'hPa',
                            ];

                        return $array;
                        break;

                case 0x11BF:// Fuel pressure regulator value
                        if (count($value) != 2) {
                            return;
                        }
                        $val = self::readUint16($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Fuel pressure regulator value (%)' => 0.01 * $val,
                                'unit' => '%',
                            ];

                        return $array;
                        break;

                case 0x106B:// Limitation torque
                        if (count($value) != 2) {
                            return;
                        }
                        $val = self::readUint16($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Limitation torque' => 0.1 * $val,
                                'unit' => 'Nm',
                            ];

                        return $array;
                        break;

                case 0x116B:// Rail pressure regulation
                        if (count($value) != 1) {
                            return;
                        }
                        $regulation = self::readUnsignedByte($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Rail pressure regulation' => $regulation,
                                'unit' => '',
                            ];

                        return $array;
                        break;

                case 0x104C:// Air mass: specified value
                        if (count($value) != 2) {
                            return;
                        }
                        $val = self::readUint16($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Air mass: specified value' => 0.1 * $val,
                                'unit' => 'mg/stroke',
                            ];

                        return $array;
                        break;

                case 0x1635:// Sensor f charge air press betw turbochargers
                        if (count($value) != 2) {
                            return;
                        }
                        $val = self::readUint16($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Sensor f charge air press betw turbochargers' => 0.2 * $val,
                                'unit' => 'mV',
                            ];

                        return $array;
                        break;

                case 0x1634:// Sensor for charge air press
                        if (count($value) != 2) {
                            return;
                        }
                        $val = self::readUint16($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Sensor for charge air press' => 0.2 * $val,
                                'unit' => 'hPa',
                            ];

                        return $array;
                        break;

                case 0x100D:// Selected gear
                        if (count($value) != 1) {
                            return;
                        }
                        $gear = self::readUnsignedByte($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Selected gear' => $gear,
                                'unit' => '',
                            ];

                        return $array;
                        break;

                case 0x162D:// Fuel temperature
                        if (count($value) != 2) {
                            return;
                        }
                        $val = self::readUint16($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Fuel temperature' => $val * 0.1 - 273,
                                'unit' => 'Celsius',
                            ];

                        return $array;
                        break;

                case 0xF411:// Absolute Throttle Position
                        if (count($value) != 1) {
                            return;
                        }
                        $pos = self::readUnsignedByte($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Absolute Throttle Position' => $pos * 100 / 255,
                                'unit' => '%',
                            ];

                        return $array;
                        break;

                case 0xF40D:// Vehicle speed
                        if (count($value) != 1) {
                            return;
                        }
                        $val = self::readUnsignedByte($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Vehicle speed' => $val,
                                'unit' => 'km/h',
                            ];

                        return $array;
                        break;

                case 0xF405:// Engine Coolant Temperature
                        if (count($value) != 1) {
                            return;
                        }
                        $val = self::readUnsignedByte($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Engine Coolant Temperature' => $val - 40,
                                'unit' => 'Celsius',
                            ];

                        return $array;
                        break;

                case 0x2222:// Indicator lamps
                        if (count($value) != 2) {
                            return;
                        }
                        $val = self::readUint16($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Airbag indicator lamp' => ($val & 0x0100) == 0 ? 'Not active' : 'Active',
                                'ABS indicator lamp' => ($val & 0x0004) == 0 ? 'Not active' : 'Active',
                            ];

                        return $array;
                        break;

                case 0x2223:// MIL
                        if (count($value) != 2) {
                            return;
                        }
                        $val = self::readUint16($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'Malfunction indicator lamp(MIL)' => ($val & 0x4000) == 0 ? 'Not active' : 'Active',
                                'unit' => '',
                            ];

                        return $array;
                        break;

                case 0x2260:// ESI: remaining distance
                        if (count($value) != 2) {
                            return;
                        }
                        $val = self::readUint16($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'ESI: remaining distance' => $val,
                                'unit' => 'km',
                            ];

                        return $array;
                        break;

                case 0x2261:// ESI: remaining running days
                        if (count($value) != 2) {
                            return;
                        }
                        $val = self::readUint16($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'ESI: remaining running days' => $val,
                                'unit' => 'day',
                            ];

                        return $array;
                        break;

                default:
                        $hex = '';
                        for ($i = 0; $i < count($value); ++$i) {
                            $hex .= sprintf('%02X', $value[$i]);
                        }
                        $val = self::readUint16($value, 0);
                        $valorBase = sprintf('%02X', 0x22);
                        $pidHex = sprintf('%04X', $pid);
                        $array = [
                                'valor base' => $valorBase,
                                'pid' => $pidHex,
                                'default' => $hex,
                                'unit' => '',
                            ];

                        return $array;
                        break;
            }

        return $array;
    }

    public static function obdCanSnifferDecode($value)
    {
        $addr = self::readUint32($value, 0);
        $hex = '';
        $array = [];
        for ($i = 4; $i < count($value); ++$i) {
            $hex .= sprintf('%02X', $value[$i]);
        }
        array_push($array, [
                        'CAN-ID' => $addr,
                        'DAT' => $hex,
                    ]);
        switch ($addr) {
                case 0x40A:
                    if ($value[4] == 0xC0 && $value[5] == 0x01) {
                        $odo = self::readUint32($value, 6);
                        array_push($array, [
                            'Odometer' => $odo,
                            'unit' => 'km',
                        ]);
                    }
                    break;
                case 0x5C5:
                    if ($value[4] == 0x40 || $value[4] == 0x44) {
                        $odo = self::readUint32($value, 4) & 0x00FFFFFF;
                        array_push($array, [
                            'Odometer' => $odo,
                            'unit' => 'km',
                        ]);
                    }
                    break;
                case 0x294:
                    if ($value[4] == 0x01 && $value[5] == 0x16) {
                        $odo = self::readUint32($value, 6) & 0x00FFFFFF;
                        array_push($array, [
                            'Odometer' => $odo,
                            'unit' => 'km',
                        ]);
                    }
                    break;
                case 0x120:
                        $dis = self::readUint32($value, 4);
                        $distance = $dis / 64;
                        array_push($array, [
                            'Odometer' => $distance,
                            'unit' => 'km',
                        ]);

                    break;
                case 0x611:
                    break;
                default:
                    break;
            }

        return $array;
    }

    // começar daqui
    public static function j1939PgnDecode($value, $pgn)
    {
        // foreach (Pgn p in pgns)
        // {
        //     if (p.PgnNum != pgn)
        //         continue;
        //     if (p.ParseData((uint)pgn, value))
        //     {
        //         string strCan;
        //         strCan = String.Format("        PGN{0}: {1}---{2}\r\n",
        //                 pgn,
        //                 hex,
        //                 p.Name);
        //         string blanks = "                ";
        //         foreach (Spn s in p.Spns)
        //         {
        //             string sval = s.PrintValue();
        //             if (sval != null && sval != "")
        //             {
        //                 strCan += string.Format("{0}{1}\r\n", blanks, sval);
        //             }

        //         }
        //         OutputText(outStr, strCan);
        //         return;
        //     }
        // }
        // OutputText(outStr, String.Format("        PGN{0}: {1}\r\n", pgn, hex));
        // if (false)
        switch ($pgn) {
                case 61444:// (0x00F004)Engine speed
                    $pgns_info = [
                        '(R) Electronic Engine Controller 1' => [
                            'PGN' => 61444,
                            'length' => 8,
                            'spns' => [
                                'Engine Torque Mode' => [
                                    // "SPN" => "899",
                                    'value' => self::readUnsignedByte($value, 0) & 0x0F,
                                    'unit' => '',
                                        ],
                                '(R) Actual Engine - Percent Torque High Resolution' => [
                                    // "SPN" => "4154",
                                    'value' => 0.125 * ((self::readUnsignedByte($value, 0) >> 4) & 0x0F),
                                    'unit' => '%',
                                    ],
                                'Drivers Demand Engine' => [
                                    // "SPN" => "512",
                                    'value' => self::readUnsignedByte($value, 1),
                                    'unit' => '%',
                                    ],
                                'Actual Engine' => [
                                    // "SPN" => "513",
                                    'value' => self::readUnsignedByte($value, 2),
                                    'unit' => '%',
                                    ],
                                'Engine Speed' => [
                                    // "SPN" => "190",
                                    // "parse" => 0.125 * self::reverseBytes(self::readUint16($value, 3)),
                                    'value' => 0.125 * self::reverseBytes16(self::readUint16($value, 3)),
                                    'unit' => 'rpm',
                                    ],
                                'Source Address of Controlling Device for Engine Control' => [
                                    // "SPN" => "1483",
                                    'value' => self::readUnsignedByte($value, 5),
                                    'unit' => '',
                                    ],
                                'Engine Starter Mode' => [
                                    // "SPN" => "1675",
                                    'unit' => '',
                                    'value' => self::readUnsignedByte($value, 6) & 0x0F,
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
                                'Engine Demand' => [
                                    // "SPN" => "2432",
                                    'value' => (-125) + self::readUnsignedByte($value, 7),
                                    'unit' => '%',
                                    ],
                                ],
                            ],
                        ];

                    return $pgns_info;
                    break;
                case 65132:// (0x00FE6C)Vehicle speed
                    $pgns_info = [
                        'Tachograph' => [
                            'PGN' => 65132,
                            'length' => 1,
                            'spns' => [
                                'Vehicle speed' => [
                                    'SPN' => 1624,
                                    'value' => self::reverseBytes16(self::readUint16($value, 6)) / 256,
                                    'unit' => 'km/h',
                                    ],
                                ],
                            ],
                        ];

                    return $pgns_info;
                    break;
                case 65215:// (0x00FEBF)Wheel Speed Information
                    $pgns_info = [
                        'Wheel Speed Information' => [
                            'PGN' => 65215,
                            'length' => 1,
                            'spns' => [
                                'Front Axle Speed' => [
                                    'SPN' => 1624,
                                    'value' => self::reverseBytes(self::readUint16($value, 0)) / 256,
                                    'unit' => 'km/h',
                                ],
                            ],
                        ],
                    ];

                    return $pgns_info;
                    break;
                case 65217:// (0x00FEC1)High Resolution Total Vehicle Distance
                    $pgns_info = [
                        'High Resolution Vehicle Distance' => [
                            'PGN' => 65217,
                            'length' => 1,
                            'spns' => [
                                'High Resolution Total Vehicle Distance' => [
                                    'SPN' => 1624,
                                    'value' => self::reverseBytes(self::readUint32($value, 0)) / 200,
                                    'unit' => 'km',
                                ],
                            ],
                        ],
                    ];

                    return $pgns_info;
                    break;
                case 65248:// (0x00FEE0)Vehicle Distance
                    $pgns_info = [
                        'Vehicle Distance' => [
                            'PGN' => 65248,
                            'length' => 2,
                            'spns' => [
                                'Trip Distance' => [
                                    'SPN' => 244,
                                    'value' => self::reverseBytes(self::readUint32($value, 0)) / 8,
                                    'unit' => 'km',
                                    ],
                                'Total Vehicle Distance' => [
                                    'SPN' => 245,
                                    'value' => self::reverseBytes(self::readUint32($value, 4)) / 8,
                                    'unit' => 'km',
                                    ],
                                ],
                            ],
                        ];

                    return $pgns_info;
                    break;
                case 65260:// (0x00FEEC)VIN
                    $strVin = strval($value);
                    $strVins = implode('*', $strVin);
                    $pgns_info = [
                        'Vehicle Identification' => [
                            'PGN' => 65260,
                            'length' => 2,
                            'spns' => [
                                'VIN' => [
                                    'SPN' => 'XXX',
                                    'value' => $strVins[0],
                                    'unit' => '',
                                ],
                            ],
                        ],
                    ];

                    return $pgns_info;
                    break;
                case 65262:// (0x00FEEE)Engine Coolant Temperature
                    $pgns_info = [
                        'Engine Temperature 1' => [
                            'PGN' => 65262,
                            'length' => 1,
                            'spns' => [
                                'Engine Coolant Temperature' => [
                                    'SPN' => 110,
                                    'value' => self::readUnsignedByte($value, 0) - 40,
                                    'unit' => 'deg C',
                                ],
                            ],
                        ],
                    ];

                    return $pgns_info;
                    break;
                case 65253:// (0x00FEE5)Engine Hours, Revolutions
                    $pgns_info = [
                        'Engine Hours, Revolutions' => [
                            'PGN' => 65253,
                            'length' => 2,
                            'spns' => [
                                'Engine Total Hours of Operation' => [
                                    'SPN' => 247,
                                    'value' => self::reverseBytes(self::readUint32($value, 0)) * 0.05,
                                    'unit' => 'H',
                                ],
                                'Engine Total Revolutions' => [
                                    'SPN' => 249,
                                    'value' => self::reverseBytes(self::readUint32($value, 4)) * 1000,
                                    'unit' => 'r',
                                ],
                            ],
                        ],
                    ];

                    return $pgns_info;
                    break;
                case 65256:// (0x00FEE8)Vehicle Direction/Speed
                    $pgns_info = [
                        'Vehicle Direction/Speed' => [
                            'PGN' => 65256,
                            'length' => 4,
                            'spns' => [
                                'Compass Bearing' => [
                                    'SPN' => 165,
                                    'value' => self::reverseBytes(self::readUint16($value, 0)) / 128,
                                    'unit' => 'deg',
                                ],
                                'Navigation-Based Vehicle Speed' => [
                                    'SPN' => 517,
                                    'value' => self::reverseBytes(self::readUint16($value, 2)) / 256,
                                    'unit' => 'km/h',
                                ],
                                'Pitch' => [
                                    'SPN' => 583,
                                    'value' => self::reverseBytes(self::readUint16($value, 4)) / 128 - 200,
                                    'unit' => 'deg',
                                ],
                                'Altitude' => [
                                    'SPN' => 580,
                                    'value' => self::reverseBytes(self::readUint16($value, 6)) / 8 - 2500,
                                    'unit' => 'm',
                                ],
                            ],
                        ],
                    ];

                    return $pgns_info;
                    break;
                case 65257:// (0x00FEE9)Fuel consumption
                    $pgns_info = [
                        'Fuel Consumption (Liquid)' => [
                            'PGN' => 65257,
                            'length' => 2,
                            'spns' => [
                                'Engine trip fuel' => [
                                    'SPN' => 182,
                                    'value' => self::reverseBytes32(self::readUint32($value, 0)) * 0.5,
                                    'unit' => 'L',
                                ],
                                'Engine total fuel used' => [
                                    'SPN' => 250,
                                    'value' => self::reverseBytes32(self::readUint32($value, 4)) * 0.5,
                                    'unit' => 'L',
                                ],
                            ],
                        ],
                    ];

                    return $pgns_info;
                    break;
                case 61443:// (0x00F003)Accelerator Pedal Position
                    $pgns_info = [
                        'Electronic Engine Controller 2' => [
                            'PGN' => 61443,
                            'length' => 1,
                            'spns' => [
                                'Accelerator Pedal Position' => [
                                    'SPN' => 91,
                                    'value' => self::readUnsignedByte($value, 1) * 0.4,
                                    'unit' => '%',
                                ],
                            ],
                        ],
                    ];

                    return $pgns_info;
                    break;
                case 65259:// (0x00FEEB)//Component Identification
                    $pgns_info = [
                        'Component Identification' => [
                            'PGN' => 65259,
                            'length' => 1,
                            'spns' => [],
                        ],
                    ];
                    $strId = strval($value);
                    $strIds = implode('*', $strId);
                    if ($strIds[0] >= 1) {
                        array_push(
                            $pgns_info['spns'],
                            [
                                'Make' => [
                                    'SPN' => 'xx',
                                    'value' => $strIds[0],
                                    'unit' => '',
                                ],
                            ],
                        );
                    }
                    if ($strIds[0] >= 2) {
                        array_push(
                            $pgns_info['spns'],
                            [
                                'Model' => [
                                    'SPN' => 'xx',
                                    'value' => $strIds[1],
                                    'unit' => '',
                                ],
                            ],
                        );
                    }
                    if ($strIds[0] >= 3) {
                        array_push(
                            $pgns_info['spns'],
                            [
                                'Serial Number' => [
                                    'SPN' => 'xx',
                                    'value' => $strIds[2],
                                    'unit' => '',
                                ],
                            ],
                        );
                    }
                    if ($strIds[0] >= 4) {
                        array_push(
                            $pgns_info['spns'],
                            [
                                'Unit Number(Power Unit)' => [
                                    'SPN' => 'xx',
                                    'value' => $strIds[3],
                                    'unit' => '',
                                ],
                            ],
                        );
                    }

                    return $pgns_info;
                    break;
                case 65263:// (0x00FEEF)Engine Fluid Level/Pressure 1
                    $pgns_info = [
                        'Engine Fluid Level/Pressure 1' => [
                            'PGN' => 65263,
                            'length' => 7,
                            'spns' => [
                                'Engine Fuel Delivery Pressure' => [
                                    'SPN' => 94,
                                    'value' => 4 * self::readUnsignedByte($value, 0),
                                    'unit' => 'kPa',
                                ],
                                'Engine Extended Crankcase Blow-by Pressure' => [
                                    'SPN' => 22,
                                    'value' => 0.05 * self::readUnsignedByte($value, 1),
                                    'unit' => 'kPa',
                                ],
                                'Engine Oil Level' => [
                                    'SPN' => 98,
                                    'value' => 0.4 * self::readUnsignedByte($value, 2),
                                    'unit' => '%',
                                ],
                                'Engine Oil Pressure' => [
                                    'SPN' => 100,
                                    'value' => 4 * self::readUnsignedByte($value, 3),
                                    'unit' => 'kPa',
                                ],
                                'Engine Crankcase Pressure' => [
                                    'SPN' => 101,
                                    'value' => self::reverseBytes(self::readUint16($value, 4)) / 128,
                                    'unit' => 'kPa',
                                ],
                                'Engine Coolant Pressure' => [
                                    'SPN' => 109,
                                    'value' => 2 * self::readUnsignedByte($value, 6),
                                    'unit' => 'kPa',
                                ],
                                'Engine Coolant Level' => [
                                    'SPN' => 111,
                                    'value' => 0.4 * self::readUnsignedByte($value, 7),
                                    'unit' => '%',
                                ],
                            ],
                        ],
                    ];

                    return $pgns_info;
                    break;
                case 65265:// (0x00FEF1)Cruise Control/Vehicle Speed
                    $pgns_info = [
                        'Cruise Control/Vehicle Speed' => [
                            'PGN' => 65265,
                            'length' => 20,
                            'spns' => [
                                'Two Speed Axle Switch' => [
                                    'SPN' => 69,
                                    'unit' => '',
                                    'value' => self::readUnsignedByte($value, 0) & 0x03,
                                    'val_desc' => [
                                        [0, 'Low speed range'],
                                        [1, 'High speed range'],
                                        [2, 'Error'],
                                        ],
                                    ],
                                'Parking Brake Switch' => [
                                    'SPN' => 70,
                                    'unit' => '',
                                    'value' => (self::readUnsignedByte($value, 0) >> 2) & 0x03,
                                    'val_desc' => [
                                        [0, 'Parking brake not set'],
                                        [1, 'Parking brake set'],
                                        [2, 'Error'],
                                        ],
                                    ],
                                'Cruise Control Pause Switch' => [
                                    'SPN' => 1633,
                                    'unit' => '',
                                    'value' => (self::readUnsignedByte($value, 0) >> 4) & 0x03,
                                    'val_desc' => [
                                        [0, 'Off'],
                                        [1, 'On'],
                                        [2, 'Error Indicator'],
                                        ],
                                    ],
                                'Park Brake Release Inhibit Request' => [
                                    'SPN' => 3807,
                                    'unit' => '',
                                    'value' => (self::readUnsignedByte($value, 0) >> 6) & 0x03,
                                    'val_desc' => [
                                        [0, 'Park Brake Release Inhibit not requested'],
                                        [1, 'Park Brake Release Inhibit requested'],
                                        [2, 'SAE reserved'],
                                        ],
                                    ],
                                'Wheel-Based Vehicle Speed' => [
                                    'SPN' => 84,
                                    'unit' => 'km/h',
                                    'value' => self::reverseBytes(self::readUint16($value, 1)) / 256,
                                    ],
                                'Cruise Control Active' => [
                                    'SPN' => 595,
                                    'unit' => '',
                                    'value' => self::readUnsignedByte($value, 3) & 0x03,
                                    'val_desc' => [
                                        [0, 'Cruise control switched off'],
                                        [1, 'Cruise control switched on'],
                                        [2, 'Error'],
                                        ],
                                    ],
                                'Cruise Control Enable Switch' => [
                                    'SPN' => 596,
                                    'unit' => '',
                                    'value' => (self::readUnsignedByte($value, 3) >> 2) & 0x03,
                                    'val_desc' => [
                                        [0, 'Cruise control disabled'],
                                        [1, 'Cruise control enabled'],
                                        [2, 'Error'],
                                        ],
                                    ],
                                'Brake Switch' => [
                                    'SPN' => 597,
                                    'unit' => '',
                                    'value' => (self::readUnsignedByte($value, 3) >> 4) & 0x03,
                                    'val_desc' => [
                                        [0, 'Brake pedal released'],
                                        [1, 'Brake pedal depressed'],
                                        [2, 'Error'],
                                        ],
                                    ],
                                'Clutch Switch' => [
                                    'SPN' => 598,
                                    'unit' => '',
                                    'value' => (self::readUnsignedByte($value, 3) >> 6) & 0x03,
                                    'val_desc' => [
                                        [0, 'Clutch pedal released'],
                                        [1, 'Clutch pedal depressed'],
                                        [2, 'Error'],
                                        ],
                                    ],
                                'Cruise Control Set Switch' => [
                                    'SPN' => 599,
                                    'unit' => '',
                                    'value' => self::readUnsignedByte($value, 4) & 0x03,
                                    'val_desc' => [
                                        [0, 'Cruise control activator not in the position "set"'],
                                        [1, 'Cruise control activator in position "set"'],
                                        [2, 'Error'],
                                        ],
                                    ],
                                'Cruise Control Coast (Decelerate) Switch' => [
                                    'SPN' => 600,
                                    'unit' => '',
                                    'value' => (self::readUnsignedByte($value, 4) >> 2) & 0x03,
                                    'val_desc' => [
                                        [0, 'Cruise control activator not in the position "coast"'],
                                        [1, 'Cruise control activator in position "coast"'],
                                        [2, 'Error'],
                                        ],
                                    ],
                                'Cruise Control Resume Switch' => [
                                    'SPN' => 601,
                                    'unit' => '',
                                    'value' => (self::readUnsignedByte($value, 4) >> 4) & 0x03,
                                    'val_desc' => [
                                        [0, 'Cruise control activator not in the position "resume"'],
                                        [1, 'Cruise control activator in position "resume"'],
                                        [2, 'Error'],
                                        ],
                                    ],
                                'Cruise Control Accelerate Switch' => [
                                    'SPN' => 602,
                                    'unit' => '',
                                    'value' => (self::readUnsignedByte($value, 4) >> 6) & 0x03,
                                    'val_desc' => [
                                        [0, 'Cruise control activator not in the position "accelerate"'],
                                        [1, 'Cruise control activator in position "accelerate"'],
                                        [2, 'Error'],
                                        ],
                                    ],
                                'Cruise Control Set Speed' => [
                                    'SPN' => 86,
                                    'unit' => 'km/h',
                                    'value' => self::readUnsignedByte($value, 5),
                                    ],
                                'PTO Governor State' => [
                                    'SPN' => 976,
                                    'unit' => '',
                                    'value' => self::readUnsignedByte($value, 6) & 0x1F,
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
                                'Cruise Control States' => [
                                    'SPN' => 527,
                                    'unit' => '',
                                    'value' => (self::readUnsignedByte($value, 6) >> 5) & 0x07,
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
                                'Engine Idle Increment Switch' => [
                                    'SPN' => 968,
                                    'unit' => '',
                                    'value' => self::readUnsignedByte($value, 7) & 0x03,
                                    'val_desc' => [
                                        [0, 'Off'],
                                        [1, 'On'],
                                        [2, 'Error'],
                                        ],
                                    ],
                                'Engine Idle Decrement Switch' => [
                                    'SPN' => 967,
                                    'unit' => '',
                                    'value' => (self::readUnsignedByte($value, 7) >> 2) & 0x03,
                                    'val_desc' => [
                                        [0, 'Off'],
                                        [1, 'On'],
                                        [2, 'Error'],
                                        ],
                                    ],
                                'Engine Test Mode Switch' => [
                                    'SPN' => 966,
                                    'unit' => '',
                                    'value' => (self::readUnsignedByte($value, 7) >> 4) & 0x03,
                                    'val_desc' => [
                                        [0, 'Off'],
                                        [1, 'On'],
                                        [2, 'Error'],
                                        ],
                                    ],
                                'Engine Shutdown Override Switchh' => [
                                    'SPN' => 1237,
                                    'unit' => '',
                                    'value' => (self::readUnsignedByte($value, 7) >> 6) & 0x03,
                                    'val_desc' => [
                                        [0, 'Off'],
                                        [1, 'On'],
                                        [2, 'Error'],
                                        ],
                                    ],
                            ],
                        ],
                    ];

                    return $pgns_info;
                    break;
                case 65270:// (0x00FEF6)Inlet/Exhaust Conditions 1
                    $pgns_info = [
                        'Inlet/Exhaust Conditions 1' => [
                            'PGN' => 65270,
                            'length' => 7,
                            'spns' => [
                                'Engine Diesel Particulate Filter Inlet Pressure' => [
                                    'SPN' => 81,
                                    'value' => 0.5 * self::readUnsignedByte($value, 7),
                                    'unit' => 'kPa',
                                ],
                                'Engine Intake Manifold #1 Pressure' => [
                                    'SPN' => 102,
                                    'value' => 2 * self::readUnsignedByte($value, 1),
                                    'unit' => 'kPa',
                                ],
                                'Engine Intake Manifold 1 Temperature' => [
                                    'SPN' => 105,
                                    'value' => -40 + self::readUnsignedByte($value, 2),
                                    'unit' => 'deg C',
                                ],
                                'Engine Air Inlet Pressure' => [
                                    'SPN' => 106,
                                    'value' => 2 * self::readUnsignedByte($value, 3),
                                    'unit' => 'kPa',
                                ],
                                'Engine Air Filter 1 Differential Pressure' => [
                                    'SPN' => 107,
                                    'value' => 0.05 * self::readUnsignedByte($value, 4),
                                    'unit' => 'kPa',
                                ],
                                'Engine Exhaust Gas Temperature' => [
                                    'SPN' => 173,
                                    'value' => -273 + 0.03125 * self::reverseBytes(self::readUint16($value, 5)),
                                    'unit' => 'deg C',
                                ],
                                'Engine Coolant Filter Differential Pressure' => [
                                    'SPN' => 112,
                                    'value' => 0.5 * self::readUnsignedByte($value, 7),
                                    'unit' => 'kPa',
                                ],
                            ],
                        ],
                    ];

                    return $pgns_info;
                    break;
                case 65271:// (0x00FEF7)Vehicle Electrical Power 1
                    $pgns_info = [
                        'Vehicle Electrical Power 1' => [
                            'PGN' => 65271,
                            'length' => 5,
                            'spns' => [
                                'Net Battery Current' => [
                                    'SPN' => 114,
                                    'value' => -125 + self::readUnsignedByte($value, 0),
                                    'unit' => 'A',
                                ],
                                'Alternator Current' => [
                                    'SPN' => 115,
                                    'value' => self::readUnsignedByte($value, 1),
                                    'unit' => 'A',
                                ],
                                'Charging System Potential' => [
                                    'SPN' => 167,
                                    'value' => 0.05 * self::reverseBytes(self::readUint16($value, 2)),
                                    'unit' => 'V',
                                ],
                                'Battery Potential / Power Input 1' => [
                                    'SPN' => 168,
                                    'value' => 0.05 * self::reverseBytes(self::readUint16($value, 4)),
                                    'unit' => 'V',
                                ],
                                'Keyswitch Battery Potential' => [
                                    'SPN' => 158,
                                    'value' => 0.05 * self::reverseBytes(self::readUint16($value, 6)),
                                    'unit' => 'V',
                                ],
                            ],
                        ],
                    ];

                    return $pgns_info;
                    break;
                case 65276:// (0x00FEFC)(R) Dash Display
                    $pgns_info = [
                        '(R)Dash Display' => [
                            'PGN' => 65276,
                            'length' => 6,
                            'spns' => [
                                'Washer Fluid Level' => [
                                    'SPN' => 80,
                                    'value' => 0.4 * self::readUnsignedByte($value, 0),
                                    'unit' => '%',
                                ],
                                'Fuel Level 1' => [
                                    'SPN' => 96,
                                    'value' => 0.4 * self::readUnsignedByte($value, 1),
                                    'unit' => '%',
                                ],
                                'Engine Fuel Filter Differential Pressure' => [
                                    'SPN' => 95,
                                    'value' => 2 * self::readUnsignedByte($value, 2),
                                    'unit' => 'kPa',
                                ],
                                'Engine Oil Filter Differential Pressure' => [
                                    'SPN' => 99,
                                    'value' => 0.5 * self::readUnsignedByte($value, 3),
                                    'unit' => 'kPa',
                                ],
                                'Cargo Ambient Temperature' => [
                                    'SPN' => 169,
                                    'value' => -273 + 0.03125 * self::reverseBytes(self::readUint16($value, 4)),
                                    'unit' => 'deg C',
                                ],
                                'Fuel Level 2' => [
                                    'SPN' => 38,
                                    'value' => 0.4 * self::readUnsignedByte($value, 6),
                                    'unit' => '%',
                                ],
                            ],
                        ],
                    ];

                    return $pgns_info;
                    break;
                case 65226:// (DM1)Active DTCs and lamp status information
                    $pgns_info = [
                        '(DM1)Active DTCs and lamp status information' => [
                            'PGN' => 65226,
                            'value' => self::j1939DtcsDecode($value),
                        ],
                    ];

                    return $pgns_info;
                    break;
                case 65227:// (DM2)Previously active DTCs and lamp status information
                $pgns_info = [
                        '(DM2)Previously active DTCs and lamp status information' => [
                            'PGN' => 65227,
                            'value' => self::j1939DtcsDecode($value),
                        ],
                    ];

                    return $pgns_info;
                    break;
                default:
                    return;
                    break;
            }
        // endif
    }

    // j1939DtcsDecode
    public static function j1939DtcsDecode($value)
    {
        if (count($value) < 7) {
            return;
        }
        $lampStatus = ['OFF', 'ON', 'Unknown', 'Unknown'];

        $array = [
                'MIL' => $lampStatus[($value[0] >> 6) & 0x03],
                'RSL' => $lampStatus[($value[0] >> 4) & 0x03],
                'AWL' => $lampStatus[($value[0] >> 2) & 0x03],
                'PL' => $lampStatus[$value[0] & 0x03],
                'values' => [],
            ];

        $parameters = [];
        for ($i = 0; $i < intdiv((count($value) - 2), 4); ++$i) {
            $SPN = $value[2 + 4 * $i + 2] >> 5;
            $SPN = ($SPN << 8) + $value[2 + 4 * $i + 1];
            $SPN = ($SPN << 8) + $value[2 + 4 * $i];
            $FMI = $value[2 + 4 * $i + 2] & 0x1F;
            $OC = $value[2 + 4 * $i + 3] & 0x7F;
            array_push($parameters, ['DTC' . $i => [
                    'SPN' => $SPN,
                    'FMI' => $FMI,
                    'OC' => $OC,
                    ],
                ]);
        }
        $array['values'] = $parameters;

        return $array;
    }

    // j1708MidDecode
    public static function j1708MidDecode($value)
    {
        $hex = '';
        for ($i = 1; $i < count($value); ++$i) {
            $hex .= sprintf('%2X ', $value[$i]);
        }
        $array = [
                'MID' . $value[0] => $hex,
            ];

        return $array;
    }

    // j1587PidDecode
    public static function j1587PidDecode($value, $pid)
    {
        $infos = [];
        switch ($pid) {
                case 84:// Road speed
                    array_push($infos, [
                        'Road speed' => (self::readUnsignedByte($value, 0)) * 0.805,
                        'unit' => 'km/h',
                    ]);
                    break;
                case 96:// Fuel level
                    array_push($infos, [
                        'Fuel level' => self::readUnsignedByte($value, 0) * 0.5,
                        'unit' => '%',
                    ]);
                    break;
                case 110:// Engine Coolant Temperature
                    array_push($infos, [
                        'Engine Coolant Temperature' => self::readUnsignedByte($value, 0),
                        'unit' => 'Fahrenheit',
                    ]);
                    break;
                case 190:// Engine speed
                     array_push($infos, [
                        'Engine speed' => self::reverseBytes16(self::readUint16($value, 0)) * 0.25,
                        'unit' => 'RPM',
                    ]);
                    break;
                case 245:// Total Vehicle Distance
                     array_push($infos, [
                        'Total Vehicle Distance' => self::reverseBytes32(self::readUint32($value, 0)) * 0.161,
                        'unit' => 'km',
                    ]);
                    break;
                default:
                    $hex = '';
                    for ($i = 0; $i < count($value); ++$i) {
                        $hex .= sprintf('%2X ', $value[$i]);
                    }
                     array_push($infos, [
                        'default' => $hex,
                        'unit' => '',
                        'PID' => $pid,
                    ]);
                    break;
            }

        return $infos;
    }

    public static function dataTxtAcknowledgement($data)
    {
        return '*TS01,ACK:{0:X4}#' . getCrc16Value($data, count($data));
    }

    // dataBinAcknowledgement
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

    // binDataPacket esta semelhante
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

    public static function readUnsignedByte($dat, $pos)
    {
        if ($pos + 1 > count($dat)) {
            return 0;
        }

        return $dat[$pos];
    }

    public static function readSignedByte($dat, $pos)
    {
        if ($pos + 1 > count($dat)) {
            return 0;
        }

        return $dat[$pos];
    }

    // ReadUint24
    public static function readUint24($dat, $pos)
    {
        if ($pos + 3 > count($dat)) {
            return 0;
        }
        $val = 0;
        for ($i = 0; $i < 3; ++$i) {
            $val = ($val << 8) + $dat[$pos + $i];
        }

        return $val;
    }

    public static function readUint32($dat, $pos)
    {
        if ($pos + 4 > count($dat)) {
            return 0;
        }
        $val = 0;
        for ($i = 0; $i < 4; ++$i) {
            $val = ($val << 8) + $dat[$pos + $i];
        }

        return $val;
    }

    public static function readInt32($dat, $pos)
    {
        // return self::readUint32($dat, $pos);
        $val = self::readUint32($dat, $pos);
        if ($val & 0x80000000) {
            $val = ~$val & 0x7FFFFFFF;
            $val = -($val + 1);
        }

        return $val;
    }

    // public static function readInt32($dat, $pos)
    // {
    //     $val = self::readUint32($dat, $pos);
    //     if ($val & 0x80000000) {
    //         $val = ~$val & 0x7FFFFFFF;
    //         $val = -($val + 1);
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

    // ReadInt16
    public static function readInt16($dat, $pos)
    {
        return self::readUint16($dat, $pos);
    }

    // reverse Uint16
    public static function reverseBytes16($value)
    {
        return ($value & 0xFF) << 8 | ($value & 0xFF00) >> 8;
    }

    // reverse Uint32
    public static function reverseBytes32($value)
    {
        return ($value & 0x000000FF) << 24 | ($value & 0x0000FF00) << 8 |
                    ($value & 0x00FF0000) >> 8 | ($value & 0xFF000000) >> 24;
    }

    // reverse Uint64
    public static function reverseBytes64($value)
    {
        return ($value & 0x00000000000000FF) << 56 | ($value & 0x000000000000FF00) << 40 |
                ($value & 0x0000000000FF0000) << 24 | ($value & 0x00000000FF000000) << 8 |
                ($value & 0x000000FF00000000) >> 8 | ($value & 0x0000FF0000000000) >> 24 |
                ($value & 0x00FF000000000000) >> 40 | ($value & 0xFF00000000000000) >> 56;
    }
}
