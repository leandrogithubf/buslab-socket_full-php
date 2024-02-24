<?php

namespace App;

final class Decoder3
{
    private const DEVICE_ID_LEN = 15;
    private const MIN_PACKET_LEN = 22;
    private const PROTOCOL_VERSION = 1;
    private const TXT_START_CHAR = 42;
    private const TXT_END_CHAR = 35;
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

    private static $outputText;

    // List<Pgn> pgns;
    private $pgns = [];

    //Author: Carlos
    public static function decode($buffer, $parseAll)
    {
        if (empty($buffer)) {
            echo 'Empty data!';

            return;
        }
        //$hex = (self::isBinary($data) ? bin2hex($data) : $data);

        //$binData = self::hexToStrArray($hex);
        $result = [];
        $offset = 0;
        $strFrame = '';

        echo "Starting decode...\r\n";        

        while (($offset + self::MIN_PACKET_LEN) < count($buffer)) {
            // Arquivos Texts            
            if ($buffer[$offset] == self::TXT_START_CHAR) {
            
                $endPos = array_search(self::TXT_END_CHAR, $buffer, $offset + 1);

                if ($endPos == -1 || ($endPos - $offset) < 20 || !self::textFrameFormatCheck($buffer, $offset, $endPos)) {   
                    $result[] =  $buffer[$offset];
                    
                    $offset++;
                    continue;
                }

                //Verificar esta condição futuramente
                if (count($result) > 0) {
                    
                }

                for ($i = $offset; $i <= $endPos; $i++) {
                    $strFrame .= chr($buffer[$i]);
                }

                self::textFrameDecode($strFrame);

                $offset = $endPos + 1;
            } else if ($buffer[$offset] == self::BIN_FLAG_CHAR) {  //Arquivos Hexadecimais e Binários              
                $endPos = array_search(intval(self::BIN_FLAG_CHAR), array_slice($buffer, $offset + 1));
                if ($endPos !== false) {
                    $endPos += 1;
                }

                if ($endPos === false || ($endPos - $offset) < 12) {
                    if ($parseAll) {
                        $result[] = $buffer[$offset];
                    }
                    $offset++;
                    continue;
                }

                if ($parseAll && count($result) > 0) {
                    $strUnpack = utf8_decode(implode("", $result));
                    if (strlen($strUnpack) > 4) {
                        self::unPacktDataDecode($strUnpack);
                    }
                    $result = array();
                }

                $strHexFrame = "";
                for ($i = $offset; $i < $endPos + 1; $i++) {
                    $strHexFrame .= sprintf("%02X ", $buffer[$i]);
                }

                self::$outputText = "BIN frame: " . $strHexFrame . "\r\n";                 

                $binFrame = array_slice($buffer, $offset + 1, $endPos - $offset - 1);
                $datalen = self::binFrameFormatCheck($binFrame);
                if ($datalen <= 0) {
                    self::$outputText .= "    CRC check error\r\n";                    
                    $offset++;
                    continue;
                }

                self::binFrameDecode($binFrame, $datalen);
                $offset = $endPos + 1;            
            } else {
                ++$offset;
            }            
        }

        //Verificar esta condição futuramente
        if (count($result) > 0) {

        }

        $fp = fopen(__DIR__ . '/../var/obd-log' . '/' . date('YmdH') . "-rows.txt", 'a');
        fwrite($fp, self::$outputText. "\n");
        fclose($fp);

        echo "Decode finished! \r\n";
        return $result;
    }

    //Author: Carlos => Hex | 
    public static function decodeHexadecimal($row)
    {
        $data = str_replace(array(' ', "\r", "\n", "\t"), '', $row);
        $data = strtoupper($data);            

        $binData = array();
        for ($i = 0; $i < strlen($data) / 2; $i++) {
            $binData[$i] = hexdec(substr($data, 2 * $i, 2));
        }

        $ack = self::dataBinAcknowledgement($binData);
        
        $hexAck = "";
        for ($i = 0; $i < count($ack); $i++) {
            $hexAck .= sprintf("%02X ", $ack[$i]);
        }

        //self::$outputText = $hexAck;
        //self::dataTxtAcknowledgement($binData);

        self::decode($binData, false);
    }

    //Author: Carlos => Hex |
    private function dataBinAcknowledgement($data)
    {
        $crcData = self::getCrc16Value($data, count($data));
        $ackData = array(0, 0, 0, 0, 0, 0);
    
        $ackData[0] = self::PROTOCOL_VERSION;
        $ackData[1] = self::ACK_FLAG;
        $ackData[2] = ($crcData >> 8) & 0xFF;
        $ackData[3] = $crcData & 0xFF;
        $crcFrame = self::getCrc16Value($ackData, count($ackData) - 2);
        $ackData[4] = ($crcFrame >> 8) & 0xFF;
        $ackData[5] = $crcFrame & 0xFF;
    
        return self::binDataPacket($ackData);
    }

    //Author: Carlos => Vin Text | Data Text
    private function textFrameFormatCheck($binData, $offset, $endPos)
    {
        $frameFirst = sprintf("*TS%02d,", self::PROTOCOL_VERSION);
        
        for ($i = 0; $i < strlen($frameFirst); $i++) {
            if ($binData[$offset + $i] != unpack('C', $frameFirst[$i])[1])
                return false;
        }

        if ($binData[$endPos] != self::TXT_END_CHAR)
            return false;

        for ($i = $offset + strlen($frameFirst); $i < $endPos; $i++) {
            if ($binData[$i] < 0x20 || $binData[$i] > 0x7F || $binData[$i] == self::TXT_START_CHAR)
                return false;
        }

        return true;
    }

    //Author: Carlos => Vin Text | Data Text
    private function textFrameDecode($str)
    {           
        $tabChars = "    ";
        self::$outputText .= sprintf("TXT frame: %s\r\n", $str);
        $str = substr($str, 0, strlen($str) - 1);
        $info = explode(',', $str);
        if (count($info) < 4 || $info[0] != sprintf("*TS%02d", self::PROTOCOL_VERSION))
        {
            self::$outputText .= sprintf("%sFrame error!\r\n", $tabChars);
            self::$outputText .= ("\r\n");
            return;
        }

        self::$outputText .= sprintf("%sDevice ID: %s\r\n", $tabChars, $info[1]);

        if (strlen($info[2]) != 12 || $info[2] == "000000000000")
        {
            self::$outputText .= sprintf("%sTime stamp: %s\r\n", $tabChars, "Unknown");
        }
        else
        {
            self::$outputText .= sprintf("%sTime stamp: %s\r\n", $tabChars, self::getDateTimeFromString($info[2]));
        }

        self::textSegmentsDecode($info, 3);
    }

    //Author: Carlos => Vin Text | Data Text
    private function getDateTimeFromString($str)
    {
        $strTime = '';
        try {
            $strTime = sprintf('%02d', substr($str, 10, 2)) + 2000;
            $strTime .= '-' . sprintf('%02d', substr($str, 8, 2));
            $strTime .= '-' . sprintf('%02d', substr($str, 6, 2));
            $strTime .= ' ' . sprintf('%02d', substr($str, 0, 2));
            $strTime .= ':' . sprintf('%02d', substr($str, 2, 2));
            $strTime .= ':' . sprintf('%02d', substr($str, 4, 2));
        } catch (Exception $e) {
            $strTime = '2000-01-01 00:00:00';
        }

        return $strTime;
    }

    //Author: Carlos => Vin Text | Data Text 
    private function textSegmentsDecode($segs, $startIdx)
    {
        $infoKeyWords = ["GPS", "LBS", "STT", "MGR", "ADC", "GFS", "OBD", "OAL", "FUL", "HDB", "CAN", "HVD", "VIN", "RFI", "EVT", "BCN",
                        "EGT", "TRP", "SAT", "BRV", "MSI", "BTD", "IBN", "OWS", "NET", "USN", "GDC", "CLG", "DIO", "VHD"];
        
        if ($startIdx < 0) {
            $startIdx = 0;
        }

        $segsLen = count($segs);
        for ($i = $startIdx; $i < $segsLen; $i++)
        {
            $keyIndex;
            $keyId = 0;
            $strFields = explode(':', $segs[$i]);

            if (count($strFields) != 2 || $strFields[1] == "") {
                continue;
            }
                
            for ($keyIndex = 0; $keyIndex < count($infoKeyWords); $keyIndex++)
            {
                if (strpos($strFields[0], $infoKeyWords[$keyIndex]) === 0) {
                    if (strlen($strFields[0]) > 3) {
                        $keyId = (int)substr($strFields[0], 3);
                    }                    
                    break;
                }
            }
            switch ($keyIndex)
            {
                case 0: //GPS--GPS data
                    self::gpsDecodeFromString($strFields[1]);
                    break;
                case 1: //LBS--LBS data
                    self::lbsDecodeFromString($strFields[1]);
                    break;
                case 2: //STT--Device status data
                    self::sttDecodeFromString($strFields[1]);
                    break;
                case 3: //MGR--Mileage
                    self::mgrDecodeFromString($strFields[1], $keyId);
                    break;
                case 4: //ADC--Analog data
                    self::adcDecodeFromString($strFields[1]);
                    break;
                case 5: //GFS--Geo-fence status
                    self::gfsDecodeFromString($strFields[1]);
                    break;
                case 6: //OBD--OBD data
                    self::obdDecodeFromString($strFields[1]);
                    break;
                case 7: //OAL OBD alarm data
                    self::oalDecodeFromString($strFields[1]);
                    break;
                case 8: //FUL--Fuel used data
                    self::fulDecodeFromString($strFields[1], $keyId);
                    break;
                case 9: //HDB--Driver behavior
                    self::hdbDecodeFromString($strFields[1]);
                    break;
                case 10: //CAN--J1939 data
                    //self::canDecodeFromString($strFields[1]);
                    break;
                case 11: //HVD--J1708 data
                    self::hvdDecodeFromString($strFields[1]);
                    break;
                case 12: //VIN--VIN data
                    self::vinDecodeFromString($strFields[1]);
                    break;
                case 13: //RFI--RFID data
                    //RfiDecodeFromString($outStr, $strFields[1]);
                    break;
                case 14: //EVT--Event code data
                    //EvtDecodeFromString($outStr, $strFields[1]);
                    break;
                case 15: //BCN--iBeacon info data
                    //BcnDecodeFromString($outStr, $strFields[1]);
                    break;
                case 16: //EGT--Engine seconds
                    $this->egtDecodeFromString($strFields[1]);
                    break;
                case 17: //TRP--Trip report
                    //TrpDecodeFromString($outStr, $strFields[1]);
                    break;
                case 18: //SAT--GPS Satellites Signal strength
                    //SatDecodeFromString($outStr, $strFields[1]);
                    break;
                case 19: //BRV--BLE Remote event
                    //BrvDecodeFromString($outStr, $strFields[1]);
                    break;
                case 20: //MSI-IMSI information
                    //MsiDecodeFromString($outStr, $strFields[1]);
                    break;
                case 21: //BTD--BLE Temperature sensor data
                    //BtdDecodeFromString($outStr, $strFields[1]);
                    break;
                case 22: //IBN--iButton data
                    //IbnDecodeFromString($outStr, $strFields[1]);
                    break;
                case 23: //OWS--1-Wire temperature sensor data
                    //OwsDecodeFromString($outStr, $strFields[1]);
                    break;
                case 24: //NET--Cellular network status
                    //NetDecodeFromString($outStr, $strFields[1]);
                    break;
                case 25: //USN--Unique ID
                    //UsnDecodeFromString($outStr, $strFields[1]);
                    break;
                case 26: //GDC--GPRS data counter
                    //GdcDecodeFromString($outStr, $strFields[1]);
                    break;
                case 27: //CLG--Can logistic data
                    //ClgDecodeFromString($outStr, $strFields[1]);
                    break;
                case 28: //DIO--Digital IO port status
                    //DioDecodeFromString($outStr, $strFields[1]);
                    break;
                case 29: //VHD--Vehicle information data
                    //VhdDecodeFromString($outStr, $strFields[1]);
                    break;
                default:
                    //CmdDecodeFromString($outStr, $strFields[0], $strFields[1]);
                    break;
            }
        }

        self::$outputText .= "\r\n";
    }

    //Author: Carlos => Vin Text | Data Text 
    private function gpsDecodeFromString(string $str)
    {
        $TabChars = "    ";
        $gps = explode(';', $str);

        if(count($gps) != 6) {
            return;
        }
        
        self::$outputText .= $TabChars . "GPS:" . "\r\n";         
        $TabChars = $TabChars . $TabChars ;

        self::$outputText .= sprintf("%sStatus: %s\r\n", $TabChars, ($gps[0] == "3" ? "3D" : ($gps[0] == "2" ? "2D" : "No fixed")));
        self::$outputText .= sprintf("%sLatitude: %s%s\r\n", $TabChars, ($gps[1][0] == "S" ? "-" : ""), substr($gps[1], 1));
        self::$outputText .= sprintf("%sLongitude: %s%s\r\n", $TabChars, ($gps[2][0] == "W" ? "-" : ""), substr($gps[2], 1));
        self::$outputText .= sprintf("%sSpeed: %s\r\n", $TabChars, $gps[3]);
        self::$outputText .= sprintf("%sCourse: %s\r\n", $TabChars, $gps[4]);
        self::$outputText .= sprintf("%sHDOP: %s\r\n", $TabChars, $gps[5]);
    }

    //Author: Carlos
    private function lbsDecodeFromString(string $str)
    {
        $TabChars = "    ";
        $lbs = explode(';', $str);

        if (count($lbs) < 5) {
            return;
        }

        self::$outputText .= $TabChars . "LBS:\r\n";

        $TabChars .= $TabChars;
        self::$outputText .= sprintf("%sMCC: %s(dec), MNC: %s(dec)\r\n", $TabChars, $lbs[0], $lbs[1]);

        $stations = (count($lbs) - 2) / 3;
        for ($i = 0; $i < $stations && $i < 7; $i++) {
            self::$outputText .= sprintf("%sCell%d: LAC: %s(hex), CID: %s(hex), dbm: -%s\r\n",
                $TabChars,
                $i,
                $lbs[2 + $i * 3],
                $lbs[2 + $i * 3 + 1],
                $lbs[2 + $i * 3 + 2]);
        }
    }
    
    //Author: Carlos => Vin Text | Data Text 
    private function sttDecodeFromString(string $str)
    {
        $TabChars = "    ";
        $stt = explode(';', $str);

        if (count($stt) != 2) {
            return;
        }

        self::$outputText .= $TabChars . "STT:\r\n";

        $TabChars .= $TabChars;
        $iStatus = hexdec($stt[0]);
        $iAlarm = hexdec($stt[1]);

        $infoStatus = array("Power cut",
                            "Moving",
                            "Over speed",
                            "Jamming",
                            "Geo-fence alarming",
                            "Immobolizer",
                            "ACC",
                            "Input high level",
                            "Input mid level",
                            "Engine",
                            "Panic",
                            "OBD alarm",
                            "Course rapid change",
                            "Speed rapid change",
                            "Roaming(T3xx)/BLE connecting(L10x)",
                            "Inter roaming(T3xx)/OBD connecting(L10x)");

        $infoAlarm = array("Power cut",
                        "Moved",
                        "Over speed",
                        "Jamming",
                        "Geo-fence",
                        "Towing",
                        "Reserved",
                        "Input low",
                        "Input high",
                        "Reserved",
                        "Panic",
                        "OBD",
                        "Reserved",
                        "Rollover",
                        "Accident",
                        "Battery low");

        self::$outputText .= $TabChars . "Status:\r\n";

        for ($i = 0; $i < 16; $i++) {
            $bitMask = (0x0001 << $i);

            $strStatus = sprintf("            [Bit%02d]:%d--%s\r\n",
                                $i,
                                (($iStatus & $bitMask) != 0 ? 1 : 0),
                                $infoStatus[$i]);

            self::$outputText .= $strStatus;
        }

        self::$outputText .= $TabChars . "Alarm:\r\n";

        for ($i = 0; $i < 16; $i++) {
            $bitMask = (0x0001 << $i);

            $strAlarm = sprintf("            [Bit%02d]:%d--%s\r\n",
                                $i,
                                (($iAlarm & $bitMask) != 0 ? 1 : 0),
                                $infoAlarm[$i]);

            self::$outputText .= $strAlarm;
        }
    }

    //Author: Carlos => Vin Text | Data Text 
    private function mgrDecodeFromString(string $str, int $id)
    {
        $text = sprintf("    Mileage: Algorithm[%d]%s(meters)\r\n", $id, $str);
        self::$outputText .= $text;
    }

    //Author: Carlos => Vin Text | Data Text 
    private function adcDecodeFromString(string $str)
    {
        $TabChars = "    ";
        $adc = explode(';', $str);

        if (count($adc) % 2 != 0) {
            return;
        }

        self::$outputText .= $TabChars . "Analog value:" . "\r\n";
        $TabChars = $TabChars . $TabChars;

        $infoAdc = ["Car Battery",
                    "Device Temp.",
                    "Inner Battery",
                    "Input voltage",
                    "Inner Battery percent",
                    "Ultrasonic fuel sensor height",
                    "Ultrasonic fuel sensor height",
                    "Ultrasonic fuel sensor height",
                    "Ultrasonic fuel sensor height",
                    "Ultrasonic fuel sensor height",];

        $infoUnit = ["(V)", "(Celsius)", "(V)", "(V)", "(%)", "(mm)", "(mm)", "(mm)", "(mm)", "(mm)"];

        for ($i = 0; $i < count($adc) / 2; $i++) {
            $idx = intval($adc[2 * $i]);

            $strAdc = sprintf("%s%d: %s%s--%s\r\n",
                                $TabChars,
                                $idx,
                                $adc[2 * $i + 1],
                                $idx < count($infoUnit) ? $infoUnit[$idx] : "Unknow",
                                $idx < count($infoAdc) ? $infoAdc[$idx] : "Unknow");

            self::$outputText .= $strAdc;
        }
    }

    //Author: Carlos
    private function gfsDecodeFromString(string $str)
    {
        $TabChars = "    ";
        $gfs = explode(";", $str);

        if (count($gfs) != 2) {
            return;
        }

        self::$outputText .= $TabChars . "Geo-fence:" . "\r\n";

        $TabChars = $TabChars . $TabChars;
        $iStatus = hexdec($gfs[0]);
        $iAlarm = hexdec($gfs[1]);

        self::$outputText .= $TabChars . "Status:\r\n";
        self::$outputText .= "            ";

        for ($i = 0; $i < 16; $i++) {
            $bitMask = (0x0001 << $i);
            $strStatus = sprintf("%02d:%s, ", $i, ($iStatus & $bitMask) != 0 ? "I" : "O");
            self::$outputText .= $strStatus;
        }

        self::$outputText .= "\r\n";
        self::$outputText .= "            ";

        for ($i = 16; $i < 32; $i++) {
            $bitMask = (0x0001 << $i);
            $strStatus = sprintf("%02d:%s, ", $i, ($iStatus & $bitMask) != 0 ? "I" : "O");
            self::$outputText .= $strStatus;
        }

        self::$outputText .= "\r\n";
        self::$outputText .= $TabChars . "Alarm:\r\n";
        self::$outputText .= "            ";

        for ($i = 0; $i < 16; $i++) {
            $bitMask = (0x0001 << $i);
            $strAlarm = sprintf("%02d:%s, ", $i, ($iAlarm & $bitMask) != 0 ? "Y" : "N");
            self::$outputText .= $strAlarm;
        }

        self::$outputText .= "\r\n";
        self::$outputText .= "            ";

        for ($i = 16; $i < 32; $i++) {
            $bitMask = (0x0001 << $i);
            $strAlarm = sprintf("%02d:%s, ", $i, ($iAlarm & $bitMask) != 0 ? "Y" : "N");
            self::$outputText .= $strAlarm;
        }

        self::$outputText .= "\r\n";
    }

    //Author: Carlos => Vin Text | Data Text 
    private function obdDecodeFromString(string $str)
    {
        if (strlen($str) % 2 !== 0) {
            return;
        }

        self::$outputText .= "    " . "OBDII:" . "\r\n";

        $obddata = array();
        for ($i = 0; $i < strlen($str) / 2; $i++) {
            $obddata[$i] = hexdec(substr($str, 2 * $i, 2));
        }

        self::obdDataDecode($obddata);
    }

    //Author: Carlos => Vin Text | Data Text | Hex
    private function obdDataDecode($obddata)
    {
        $pos = 0;
        
        while ($pos < count($obddata)) {
            $len = ($obddata[$pos] >> 4) & 0x0F;

            if ($len + $pos > count($obddata)) {
                break;
            }

            if ($len < 3 || $len > 15) {
                $pos += $len;
                continue;
            }
            
            $service = $obddata[$pos] & 0x0f;
        
            switch ($service) {
                case 1: // Mode 01
                case 2: // Mode 02
                    $pid = $obddata[$pos + 1];                    
                    $pidValue = array_slice($obddata, $pos + 2, $len - 2);                    
                                        
                    self::obdService0102Decode($pidValue, $service, $pid);                    
                    break;
                case 3: // Mode 03
                    $Value = array_slice($obddata, $pos + 1, $len - 1);

                    self::obdService03Decode($Value);
                    break;
                case 4: // Mode 04
                    break;
                case 5: // Mode 05
                    break;
                case 6: // Mode 06
                    break;
                case 7: // Mode 07
                    break;
                case 8: // Mode 08
                    break;
                case 9: // Mode 09
                    break;
                case 10: // Mode 0A
                    break;
                case 11: // mode 21 Read Data By Identifier
                    $pid = $obddata[$pos + 1];
                    $pidValue = array_slice($obddata, $pos + 2, $len - 2);

                    self::obdService0102Decode($pidValue, 0x21, $pid);
                    break;
                case 12: // mode 22 Read Data By Identifier
                    $pid = self::readUint16($obddata, $pos + 1);
                    $pidValue = array_slice($obddata, $pos + 3, $len - 3);

                    self::udsService22Decode($pidValue, $pid);
                    break;
                case 15: // CANBUS sniffer data
                    $Value = array_slice($obddata, $pos + 1, $len - 1);

                    self::obdCanSnifferDecode($Value);
                    break;
                default:
                    break;
            }

            $pos += $len;
        }
    }
    
    //Author: Carlos => Vin Text | Data Text 
    private function obdService0102Decode($value, $service, $pid)
    {
        $str = '';

        switch ($pid) {
            case 0x01:
                if (count($value) !== 4) {
                    $hex = '';
                    for ($i = 0; $i < count($value); $i++) {
                        $hex .= sprintf("%02X ", $value[$i]);
                    }

                    $str = sprintf("        [%02X]: %s\r\n", $service, $hex);
                    self::$outputText .= $str;

                    return;
                }

                $str = sprintf(" [%02X][%02X]: DTC_CNT: %d, MIL: %s\r\n",
                $service,
                $pid,
                $value[0] & 0x7F,
                (($value[0] & 0x80) != 0) ? "ON" : "OFF");

                $str .= sprintf(" MIS_SUP:%s, FUEL_SUP: %s, CCM_SUP: %s, MIS_RDY: %s, FUEL_RDY: %s, CCM_RDY: %s\r\n",
                (($value[1] & 0x01) != 0) ? "YES" : "NO",
                (($value[1] & 0x02) != 0) ? "YES" : "NO",
                (($value[1] & 0x04) != 0) ? "YES" : "NO",
                (($value[1] & 0x10) != 0) ? "YES" : "NO",
                (($value[1] & 0x20) != 0) ? "YES" : "NO",
                (($value[1] & 0x40) != 0) ? "YES" : "NO");

                $str .= sprintf(" CAT_SUP: %s, HCAT_SUP: %s, EVAP_SUP: %s, AIR_SUP: %s, ACRF_SUP: %s, O2S_SUP: %s, HTR_SUP: %s, EGR_SUP: %s\r\n",
                (($value[2] & 0x01) != 0) ? "YES" : "NO",
                (($value[2] & 0x02) != 0) ? "YES" : "NO",
                (($value[2] & 0x04) != 0) ? "YES" : "NO",
                (($value[2] & 0x08) != 0) ? "YES" : "NO",
                (($value[2] & 0x10) != 0) ? "YES" : "NO",
                (($value[2] & 0x20) != 0) ? "YES" : "NO",
                (($value[2] & 0x40) != 0) ? "YES" : "NO",
                (($value[2] & 0x80) != 0) ? "YES" : "NO");

                $str .= sprintf(" CAT_RDY: %s, HCAT_RDY: %s, EVAP_RDY: %s, AIR_RDY: %s, ACRF_RDY: %s, O2S_RDY: %s, HTR_RDY: %s, EGR_RDY: %s\r\n",
                (($value[3] & 0x01) != 0) ? "YES" : "NO",
                (($value[3] & 0x02) != 0) ? "YES" : "NO",
                (($value[3] & 0x04) != 0) ? "YES" : "NO",
                (($value[3] & 0x08) != 0) ? "YES" : "NO",
                (($value[3] & 0x10) != 0) ? "YES" : "NO",
                (($value[3] & 0x20) != 0) ? "YES" : "NO",
                (($value[3] & 0x40) != 0) ? "YES" : "NO",
                (($value[3] & 0x80) != 0) ? "YES" : "NO");

                break;
            case 0x04:
                if (count($value) !== 1) {
                    $hex = "";
                    foreach ($value as $val) {
                        $hex .= sprintf("%02X ", $val);
                    }

                    $str = sprintf("        [%02X]: %s\r\n", $service, $hex);
                    self::$outputText .= $str;

                    return;
                }
                
                $clv = ((double) $value[0]) * 100 / 255;
                $str = sprintf("        [%02X][%02X]: %.2F%% -- Calculated LOAD Value\r\n", $service, $pid, $clv);
                break;
            case 0x05:
                if (count($value) !== 1) {
                    $hex = "";
                    foreach ($value as $val) {
                        $hex .= sprintf("%02X ", $val);
                    }

                    $str = sprintf("        [%02X]: %s\r\n", $service, $hex);
                    self::$outputText .= $str;

                    return;
                }
                
                $ect = $value[0];
                $ect -= 40;

                $str = sprintf("        [%02X][%02X]: %dCelsius--Engine Coolant Temperature\r\n", $service, $pid, $ect);
                break;
            case 0x06:
                if (count($value) == 1) {
                    $str = sprintf("        [%02X][%02X]: Bank1:%.2f%%--Short Term Fuel Trim\r\n",
                                   $service,
                                   $pid,
                                   (($value[0] - 128) / 128) * 100);
                } else if (count($value) == 2) {
                    $str = sprintf("        [%02X][%02X]: Bank1:%.2f%%,Bank3:%.2f%%--Short Term Fuel Trim\r\n",
                                   $service,
                                   $pid,
                                   (($value[0] - 128) / 128) * 100,
                                   (($value[1] - 128) / 128) * 100);
                } else {
                    $hex = "";
                    foreach ($value as $val) {
                        $hex .= sprintf("%02X ", $val);
                    }

                    $str = sprintf("        [%02X]: %s\r\n",
                                   $service,
                                   $hex);

                    self::$outputText .= $str;
                    return;
                }
                break;
            case 0x07:
                if (count($value) == 1) {
                    $str = sprintf("        [%02X][%02X]: Bank1:%.2F%%--Long Term Fuel Trim\r\n",
                        $service,
                        $pid,
                        ((double)$value[0] - 128) / 128 * 100);
                } elseif (count($value) == 2) {
                    $str = sprintf("        [%02X][%02X]: Bank1:%.2F%%,Bank3:%.2F%%--Long Term Fuel Trim\r\n",
                        $service,
                        $pid,
                        ((double)$value[0] - 128) / 128 * 100,
                        ((double)$value[1] - 128) / 128 * 100);
                } else {
                    $hex = "";
                    for ($i = 0; $i < count($value); $i++) {
                        $hex .= sprintf("%02X ", $value[$i]);
                    }

                    $str = sprintf("        [%02X]: %s\r\n",
                        $service,
                        $hex);

                    self::$outputText .= $str;
                    return;
                }
                break;                
            case 0x08:
                if (count($value) == 1) {
                    $str = sprintf("        [%02X][%02X]: Bank2:%.2F%%--Short Term Fuel Trim\r\n",
                        $service,
                        $pid,
                        ((double)($value[0] - 128) / 128) * 100);
                }
                else if (count($value) == 2) {
                    $str = sprintf("        [%02X][%02X]: Bank2:%.2F%%,Bank4:%.2F%%--Short Term Fuel Trim\r\n",
                        $service,
                        $pid,
                        ((double)($value[0] - 128) / 128) * 100,
                        ((double)($value[1] - 128) / 128) * 100);
                }
                else {
                    $hex = "";
                    for ($i = 0; $i < count($value); $i++) {
                        $hex .= sprintf("%02X ", $value[$i]);
                    }

                    $str = sprintf("        [%02X]: %s\r\n", $service, $hex);

                    self::$outputText .= $str;
                    return;
                }
                break;
            case 0x09:    
                if (count($value) == 1) {
                    $str = sprintf("        [%02X][%02X]: Bank2:%0.2f%%--Long Term Fuel Trim\r\n",
                        $service,
                        $pid,
                        ((double)($value[0]) - 128) / 128 * 100
                    );
                } else if (count($value) == 2) {
                    $str = sprintf("        [%02X][%02X]: Bank2:%0.2f%%,Bank4:%0.2f%%--Long Term Fuel Trim\r\n",
                        $service,
                        $pid,
                        ((double)($value[0]) - 128) / 128 * 100,
                        ((double)($value[1]) - 128) / 128 * 100
                    );
                } else {
                    $hex = "";
                    for ($i = 0; $i < count($value); $i++) {
                        $hex .= sprintf("%02X ", $value[$i]);
                    }

                    $str = sprintf("        [%02X]: %s\r\n",
                        $service,
                        $hex
                    );

                    self::$outputText .= $str;
                    return;
                }
                break;
            case 0x0A:
                if (count($value) !== 1) {
                    $hex = '';
                    foreach ($value as $val) {
                        $hex .= sprintf('%02X ', $val);
                    }
                    
                    $str = sprintf("        [%02X]: %s\r\n", $service, $hex);

                    self::$outputText .= $str;
                    return;
                }
                $str = sprintf("        [%02X][%02X]: %dkPa--Fuel Rail Pressure\r\n",
                    $service,
                    $pid,
                    (int)$value[0] * 3
                );
                break;
            case 0x0B:
                if (count($value) !== 1) {
                    $hex = "";
                    foreach ($value as $v) {
                        $hex .= sprintf("%02X ", $v);
                    }

                    $str = sprintf("        [%02X]: %s\r\n", $service, $hex);

                    self::$outputText .= $str;
                    return;
                }
                $str = sprintf("        [%02X][%02X]: %skPa--Intake Manifold Absolute Pressure\r\n",
                            $service,
                            $pid,
                            $value[0]);
                break;
            case 0x0C:
                if (count($value) !== 2) {
                    $hex = "";
                    foreach ($value as $v) {
                        $hex .= sprintf("%02X ", $v);
                    }

                    $str = sprintf("        [%02X]: %s\r\n", $service, $hex);
                    
                    self::$outputText .= $str;
                    return;
                }

                $rpm = ((double)($value[0] * 256 + $value[1])) / 4;
                if ($rpm !== 65535 / 4) {
                    $str = sprintf("        [%02X][%02X]: %.1frpm--Engine ROM\r\n", $service, $pid, $rpm);
                }
                break;
            case 0x0D:
                if (count($value) != 1) {
                    $hex = "";
                    foreach ($value as $v) {
                        $hex .= sprintf("%02X ", $v);
                    }

                    $str = sprintf("        [%02X]: %s\r\n", $service, $hex);

                    self::$outputText .= $str;
                    return;
                }
                $speed = $value[0];
                if ($speed != 255) {
                    $str = sprintf("        [%02X][%02X]: %dkm/h--Vehicle Speed\r\n", $service, $pid, $speed);
                }
                break;
            case 0x0E:
                if (count($value) != 1) {
                    $hex = '';
                    foreach ($value as $val) {
                        $hex .= sprintf('%02X ', $val);
                    }
                    $str = sprintf("        [%02X]: %s\r\n",
                                   $service,
                                   $hex);
                    OutputText($outStr, $str);
                    return;
                }
                
                $str = sprintf("        [%02X][%02X]: %.1fdegree--Ignition Timing Advance for #1 Cylinder\r\n",
                               $service,
                               $pid,
                               (($value[0] - 128) / 2));
                
                break;
            case 0x0F: 
                if (count($value) != 1) {
                    $hex = "";            
                    foreach ($value as $val) {
                        $hex .= sprintf("%02X ", $val);
                    }

                    $str = sprintf("        [%02X]: %s\r\n", $service, $hex);

                    self::$outputText .= $str;
                    return;
                }

                $iat = $value[0];
                $iat -= 40;                
                $str = sprintf("        [%02X][%02X]: %d Celsius--Intake Air Temperature\r\n", $service, $pid, $iat);

                break;
            case 0x10:
                if (count($value) != 2) {
                    $hex = "";
                    foreach ($value as $val) {
                        $hex .= sprintf("%02X ", $val);
                    }

                    $str = sprintf("        [%02X]: %s\r\n", $service, $hex);

                    self::$outputText .= $str;
                    return;
                }
                
                $maf = (($value[0] * 256 + $value[1])) / 100;
                $str = sprintf("        [%02X][%02X]: %.2f g/s--Air Flow Rate from Mass Air Flow Sensor\r\n", $service, $pid, $maf);
                break;
            case 0x11:
                if (count($value) != 1) {
                    $hex = '';
                    foreach ($value as $val) {
                        $hex .= sprintf("%02X ", $val);
                    }

                    $str = sprintf("        [%02X]: %s\r\n", $service, $hex);

                    self::$outputText .= $str;
                    return;
                }
                
                $position = ($value[0] * 100) / 255;
                $str = sprintf("        [%02X][%02X]: %.2F%%--Absolute Throttle Position\r\n", $service, $pid, $position);
                break;
            case 0x21:
                if (count($value) != 2) {
                    $hex = "";
                    foreach ($value as $val) {
                        $hex .= sprintf("%02X ", $val);
                    }

                    $str = sprintf("        [%02X]: %s\r\n", $service, $hex);

                    self::$outputText .= $str;
                    return;
                }

                $distance = 256 * $value[0] + $value[1];
                $str = sprintf("        [%02X][%02X]: %d km--Distance Travelled While MIL is Activated\r\n", $service, $pid, $distance);
                break;
            case 0x2F:
                if (count($value) != 1) {
                    $hex = '';
                    foreach ($value as $val) {
                        $hex .= sprintf('%02X ', $val);
                    }

                    $str = sprintf("        [%X]: %s\r\n", $service, $hex);
                    self::$outputText .= $str;
                    return;
                }
                $percent = $value[0] * 100 / 255;
                $str = sprintf("        [%X][%X]: %.2F%%--Fuel level\r\n", $service, $pid, $percent);
                break;
            case 0x31:
                if (count($value) != 2) {
                    $hex = "";
                    foreach ($value as $v) {
                        $hex .= sprintf("%02X ", $v);
                    }

                    $str = sprintf("        [%02X]: %s\r\n", $service, $hex);
                    self::$outputText .= $str;
                    return;
                }
                $distance = 256 * $value[0] + $value[1];
                $str = sprintf("        [%02X][%02X]: %d km--Distance traveled since codes cleared\r\n", $service, $pid, $distance);
                break;
            case 0x49:
                if (count($value) != 1) {
                    $hex = "";
                    foreach ($value as $v) {
                        $hex .= sprintf("%02X ", $v);
                    }

                    $str = sprintf("        [%02X]: %s\r\n", $service, $hex);
                    self::$outputText .= $str;
                    return;
                }
                $percent = (($value[0]) * 100) / 255;
                $str = sprintf("        [%02X][%02X]: %.2f%%--Accelerator Pedal Position D\r\n", $service, $pid, $percent);
                break;
            default:
                $hex = '';                
                foreach ($value as $v) {
                    $hex .= sprintf("%02X ", $v);
                }
                $str = sprintf("        [%02X][%02X]: %s\r\n", $service, $pid, $hex);
                break;                                            
        }

        if ($str != "") {
            self::$outputText .= $str;
        }
    }

    //Author: Carlos => Data Text |
    private function obdService03Decode($value)
    {
        $offset = (count($value) % 2 != 0) ? 1 : 0;
        $strDtcs = "";
        $dtcChars = array("P", "C", "B", "U");

        for ($i = 0; $i < count($value) / 2; $i++) {
            $dtcA = $value[2 * $i + $offset];
            $dtcB = $value[2 * $i + $offset + 1];

            if ($dtcA == 0 && $dtcB == 0) {
                continue;
            }

            $strDtcs .= sprintf("%s%02X%02X/", 
                    $dtcChars[(($dtcA >> 6) & 0x03)],
                    ($dtcA & 0x3F),
                    $dtcB);
        }

        $strDtcs = substr($strDtcs, 0, -1);
        $strDtcs = sprintf("        [03]: %s\r\n", $strDtcs);
        
        self::$outputText .= $strDtcs;
    }
          
    //Author: Carlos
    private function udsService22Decode($pidValue, $pid) //For VW Amarok
    {
        $str;

        switch($pid) {
            case 0x16A9: // Distance
                if (count($value) != 4) {
                    return;
                }

                $distance = self::readUint32($value, 0);
                $str = sprintf("        [%02X][%04X]: %d km -- Distance\n",
                    0x22,
                    $pid,
                    $distance);
                break;
            case 0x1047: // Engine torque
                if (count($value) != 2) {
                    return;
                }
                $torque = self::readUint16($value, 0);
                $str = sprintf("        [%02X][%04X]: %.1f Nm -- Engine torque\n",
                    0x22,
                    $pid,
                    0.1 * $torque);
                break;
            case 0x1221: // Accelerator pedal
                if (count($value) != 2) {
                    return;
                }
                $pedal = self::readUint16($value, 0);
                $str = sprintf("        [%02X][%04X]: %.1f mV -- Accelerator pedal\n",
                    0x22,
                    $pid,
                    0.2 * $pedal);
                break;
            case 0xF449: // Accelerator position
                if (count($value) != 1) {
                    return;
                }
                $pos = self::readUnsignedByte($value, 0);
                $str = sprintf("        [%02X][%04X]: %.2f%% -- Accelerator position\n",
                    0x22,
                    $pid,
                    $pos * 100 / 255);
                break;
            case 0x17D6: //Brake actuated status                
                if (count($value) != 1) {
                    return;
                }
                    
                $brake = self::readUnsignedByte($value, 0);
                $str = sprintf("        [%02X][%04X]: %d--Brake actuated status\r\n",
                                0x22,
                                $pid,
                                $brake);
                break;                
            case 0xF40C: //Engine speed                
                if (count($value) != 2) {
                    return;
                }
                    
                $rpm = self::readUint16($value, 0);
                $str = sprintf("        [%02X][%04X]: %.1frpm--Engine speed\r\n",
                                0x22,
                                $pid,
                                0.25 * $rpm);
                break;
            case 0x111A: //Fuel consumption
                if (count($value) != 2) {
                    return;
                }

                $consumption = self::readUint16($value, 0);
                $str = sprintf("        [%02X][%04X]: %.1fl/h--Fuel consumption\r\n",
                                0x22,
                                $pid,
                                0.01 * $consumption);
                break;
            case 0x100C: //Fuel Level                
                if (count($value) !== 2) {
                    return;
                }
                
                $level = self::readUint16($value, 0);
                $tr = sprintf("        [%02X][%04X]: %.1fl--Fuel Level\r\n",
                                0x22,
                                $pid,
                                0.01 * $level);                
                break;        
            case 0xF423: //Fuel pressure
                if (count($value) !== 2) {
                    return;
                }

                $pressure = self::readUint16($value, 0);
                
                $str = sprintf("        [%02X][%04X]: %.1fkPa--Fuel pressure\r\n",
                                0x22,
                                $pid,
                                10 * $pressure);
                break;
            case 0x121E: //Fuel pressure regulator value            
                if (count($value) !== 2) {
                    return;
                }

                $val = self::readUint16($value, 0);
                
                $str = sprintf("        [%02X][%04X]: %.1fhPa--Fuel pressure regulator value\r\n",
                                0x22,
                                $pid,
                                100 * $val);
                break;
            case 0x11BF: //Fuel pressure regulator value            
                if (count($value) !== 2) {
                    return;
                }

                $val = self::readUint16($valu, 0);                
                $str = sprintf("                [%02X][%04X]: %.1f%%--Fuel pressure regulator value\r\n",
                                0x22,
                                $pid,
                                0.01 * $val);
                break;
            case 0x106B: //Limitation torque
                if (count($value) !== 2) {
                    return;
                }                
                
                $val = self::readUint16($value, 0);                
                $str = sprintf("                [%02X][%04X]: %.1fNm--Limitation torque\r\n",
                                0x22,
                                $pid,
                                0.1 * $val);
                break;
            case 0x116B: //Rail pressure regulation                
                if (count($value) !== 1) {
                    return;
                }
                
                $regulation = self::readUnsignedByte($value, 0);                
                $str = sprintf("        [%02X][%04X]: %d--Rail pressure regulation\r\n", 
                                0x22, 
                                $pid, 
                                $regulation);
                break;
            case 0x104C: //Air mass: specified value
                if (count($value) !== 2) {
                    return;
                }

                $val = self::readUint16($value, 0);                
                $str = sprintf("                [%02X][%04X]: %.1fmg/stroke--Air mass: specified value\r\n",
                                0x22,
                                $pid,
                                0.1 * $val);
                break;
            case 0x1635: // Sensor f charge air press betw turbochargers
                if (count($value) != 2) {
                    return;
                }

                $val = self::readUint16($value, 0);
                $str = sprintf("        [%02X][%04X]: %.2fmV--Sensor f charge air press betw turbochargers\r\n",
                                0x22,
                                $pid,
                                0.2 * $val);
                break;
            case 0x1634: // Sensor for charge air press
                if (count($value) != 2) {
                    return;
                }

                $val = self::readUint16($value, 0);
                $str = sprintf("        [%02X][%04X]: %.2fhPa--Sensor for charge air press\r\n",
                                0x22,
                                $pid,
                                0.2 * $val);
                break;
            case 0x100D: // Selected gear
                if (count($value) != 1) {
                    return;
                }

                $gear = self::readUnsignedByte($value, 0);
                $str = sprintf("        [%02X][%04X]: %d--Selected gear\r\n",
                                0x22,
                                $pid,
                                $gear);
                break;
            case 0x162D: // Fuel temperature
                if (count($value) != 2) {
                    return;
                }

                $val = self::readUint16($value, 0);
                $str = sprintf("        [%02X][%04X]: %.1f Celsius--Fuel temperature\r\n",
                                0x22,
                                $pid,
                                ($val * 0.1) - 273);
                break;
        
            case 0xF411: // Absolute Throttle Position
                if (count($value) != 1) {
                    return;
                }

                $pos = self::readUnsignedByte($value, 0);
                $str = sprintf("        [%02X][%04X]: %.2f%%--Absolute Throttle Position\r\n",
                                0x22,
                                $pid,
                                ($pos * 100 / 255));
                break;
        
            case 0xF40D: // Vehicle speed
                if (count($value) != 1) {
                    return;
                }

                $val = self::readUnsignedByte($value, 0);
                $str = sprintf("        [%02X][%04X]: %d km/h--Vehicle speed\r\n",
                                0x22,
                                $pid,
                                $val);
                break;
            case 0xF405: // Engine Coolant Temperature
                if (count($value) != 1) {
                    return;
                }

                $val = self::readUnsignedByte($value, 0);
                $str = sprintf("        [%02X][%04X]: %.1fCelsius--Engine Coolant Temperature\r\n",
                                0x22,
                                $pid,
                                $val - 40);
                break;
            case 0x2222: // Indicator lamps
                if (count($value) != 2) {
                    return;
                }

                $val = self::readUint16($value, 0);
                $str = sprintf("        [%02X][%04X]: %s--Airbag indicator lamp\r\n",
                                0x22,
                                $pid,
                                ($val & 0x0100) == 0 ? "Not active" : "Active");
                $str .= sprintf("        [%02X][%04X]: %s--ABS indicator lamp\r\n",
                                0x22,
                                $pid,
                                ($val & 0x0004) == 0 ? "Not active" : "Active");
                break;
            case 0x2223://MIL                
                if (count($value) != 2) {
                    return;
                }
                    
                $val = self::readUint16($value, 0);
                $str = sprintf("        [%02X][%04X]: %s--Malfunction indicator lamp(MIL)\r\n",
                                0x22,
                                $pid,
                                ($val & 0x4000) == 0? "Not active" : "Active");
                break;                
            case 0x2260://ESI: remaining distance                
                if (count($value) != 2) {
                    return;
                }
                    
                $val = self::readUint16($value, 0);
                $str = sprintf("        [%02X][%04X]: %d km--ESI: remaining distance\r\n",
                                0x22,
                                $pid,
                                $val);
                break;
            case 0x2261://ESI: remaining running days
                if (count($value) != 2) {
                    return;
                }

                $val = self::readUint16($value, 0);
                $str = sprintf("        [%02X][%04X]: %dday--ESI: remaining running days\r\n",
                                0x22,
                                $pid,
                                $val);
                break;
            default:
                $hex = "";
                foreach($value as $v) {
                    $hex .= sprintf("%02X ", $v);
                }
                        
                $str = sprintf("        [%02X][%04X]: %s\r\n", 0x22, $pid, $hex);
                break;                            
        }
        self::$outputText .= $str;
    }

    //Author: Carlos
    private function obdCanSnifferDecode($value)
    {
        $addr = self::readUint32($value, 0);
        $hex = "";
        for ($i = 4; $i < count($value); $i++) {
            $hex .= sprintf("%02X ", $value[$i]);
        }

        $str = sprintf("        [CAN]: [ID]-%X, [DAT]-%s\r\n",
                        $addr,
                        $hex);

        switch ($addr) {
            case 0x40A:
                if ($value[4] == 0xC0 && $value[5] == 0x01) {
                    $odo = ReadUint32($value, 6);
                    $str .= sprintf("                Odometer: %skm\r\n", $odo);
                }
                break;
            case 0x5C5:
                if ($value[4] == 0x40 || $value[4] == 0x44) {
                    $odo = ReadUint32($value, 4) & 0x00FFFFFF;
                    $str .= sprintf("                Odometer: %skm\r\n", $odo);
                }
                break;
            case 0x294:
                if ($value[4] == 0x01 && $value[5] == 0x16) {
                    $odo = ReadUint32($value, 6) & 0x00FFFFFF;
                    $str .= sprintf("                Odometer: %skm\r\n", $odo);
                }
                break;
            case 0x120:
                $dis = ReadUint32($value, 4);
                $distance = $dis / 64;
                $str .= sprintf("                Odometer: %.2fkm\r\n", $distance);
                break;
            case 0x611:
                break;
            default:
                break;
        }

        self::$outputText .= $str;
    }

    //Author: Carlos => Data Text |
    private function oalDecodeFromString($str)
    {
        if (strlen($str) % 2 != 0) {
            return;
        }
        
        self::$outputText .= "    " . "OBDII Alarm:" . "\r\n";
        
        $obdalarm = array();
        
        for ($i = 0; $i < strlen($str) / 2; $i++) {
            $obdalarm[$i] = hexdec(substr($str, 2 * $i, 2));
        }
        
        self::obdDataDecode($obdalarm);        
    }
    
    //Author: Carlos => Vin Text | Data Text 
    private function fulDecodeFromString($str, $id)
    {
        $strFul = sprintf("    Fuel consumption: Algorithm[%d]%s\r\n", $id, $str);
        self::$outputText .= $strFul;
    }

    //Author: Carlos
    private function hdbDecodeFromString($str)
    {
        $hdb = hexdec($str);
        if ($hdb == 0){
            return;
        }
            
        self::$outputText .= "    Driver Behavior:\r\n";

        $infoBehavior = array("Rapid acceleration",
                              "Rough braking",
                              "Harsh course",
                              "No warmup",
                              "Long idle",
                              "Fatigue driving",
                              "Rough terrain",
                              "High RPM");

        for ($i = 0; $i < 8; $i++) {
            $bitMask = (1 << $i);
            if (($hdb & $bitMask) == 0)
                continue;
            $strHdb = sprintf("            [Bit%d]--%s\r\n",
                              $i,
                              $infoBehavior[$i]);

            self::$outputText .= $strHdb;
        }
    }
            
    //Author: Carlos => Vin Text | 
    private function vinDecodeFromString($str)
    {
        $strVin = sprintf("    VIN: %s\r\n", $str);
        self::$outputText .= $strVin ;        
    }

    //Author: Carlos => J1708 |
    private function hvdDecodeFromString($str)
    {
        if (strlen($str) % 2 != 0) {
            return;
        }
        
        self::$outputText .= "    " . "J1708:" . "\r\n";         
    
        $j1708data = array();
        for ($i = 0; $i < strlen($str) / 2; $i++) {
            $j1708data[$i] = hexdec(substr($str, 2 * $i, 2));
        }
    
        self::j1708DataDecode($j1708data);
    }

    //Author: Carlos => J1708 |
    private function j1708DataDecode($hvddata)
    {
        $pos = 0;
        while ($pos < count($hvddata)) {
            $len = (int)($hvddata[$pos] & 0x3F);
            $paratype = (int)(($hvddata[$pos] >> 6) & 0x03);

            if ($len + $pos > count($hvddata)) {
                break;
            }
                
            if ($len < 2 || $len > 22 || $paratype == 0) {
                $pos += $len + 1;
                continue;
            }

            if ($paratype == 1) { // MID data
                $Value = array_slice($hvddata, $pos + 1, $len);
                self::j1708MidDecode($Value);
            } else {
                $j1587pid = $hvddata[$pos + 1];

                if ($paratype == 3) {
                    $j1587pid += 256;
                }

                $Value = array_slice($hvddata, $pos + 2, $len - 1);
                self::j1587PidDecode($Value, $j1587pid);
            }

            $pos += $len + 1;
        }
    }
    
    //Author: Carlos => J1708 |
    private function j1708MidDecode($value)
    {
        $hex = "";        

        for ($i = 1; $i < count($value); $i++) {
            $hex .= sprintf("%02X ", $value[$i]);
        }
        
        $strMID = sprintf("        MID%d: %s\r\n", $value[0], $hex);
        self::$outputText .= $strMID;         
    }
    
    //Author: Carlos => J1708 |
    private function j1587PidDecode($value, $pid)
    {
        $strPID = '';
        switch ($pid) {
            case 84: // Road speed
                $strPID = sprintf("        PID%d: %.3fkm/h--Road speed\r\n",
                        $pid,
                        (double)self::readUnsignedByte($value, 0) * 0.805);
                break;
            case 96: // Fuel level
                $strPID = sprintf("        PID%d: %.1f%%--Fuel level\r\n",
                        $pid,
                        (double)self::readUnsignedByte($value, 0) * 0.5);
                break;
            case 110: // Engine Coolant Temperature
                $strPID = sprintf("        PID%d: %dFahrenheit--Engine Coolant Temperature\r\n",
                        $pid,
                        self::readUnsignedByte($value, 0));
                break;
            case 190: // Engine speed
                $strPID = sprintf("        PID%d: %.2fRPM--Engine speed\r\n",
                        $pid,
                        (double)self::reverseBytes16(self::readUint16($value, 0)) * 0.25);
                break;
            case 245: // Total Vehicle Distance
                $strPID = sprintf("        PID%d: %.3fkm--Total Vehicle Distance\r\n",
                        $pid,
                        (double)self::reverseBytes32(self::readUint32($value, 0)) * 0.161);
                break;
            default:
                $hex = "";
                foreach ($value as $byte) {
                $hex .= sprintf("%02X ", $byte);
                }
                
                $strPID = sprintf("        PID%d: %s\r\n",
                                    $pid,
                                    $hex);
                break;
            }

        self::$outputText .= $strPID;
    }
            
    //Author: Carlos => J1708 |
    private function readUint16($dat, $pos)
    {
        if ($pos + 2 > count($dat)) {
            return 0;
        }
          
        $val = 0;
        for ($i = 0; $i < 2; $i++) {
          $val = ($val << 8) + $dat[$pos + $i];
        }

        return $val;
    }

    //Author: Carlos => J1708 | Hex
    private function readUint32($dat, $pos)
    {
        if ($pos + 4 > count($dat)) {
            return 0;
        }
          
        $val = 0;
        for ($i = 0; $i < 4; $i++) {
          $val = ($val << 8) + $dat[$pos + $i];
        }

        return $val;
    }

    //Author: Carlos => J1708 |
    private function readUnsignedByte($dat, $pos)
    {
        if ($pos + 1 > count($dat)) {
            return 0;
        }

        return $dat[$pos];
    }

    //Author: Carlos => J1708 |
    private function reverseBytes16($value)
    {
        return (($value & 0xFF) << 8) | (($value & 0xFF00) >> 8);
    }

    //Author: Carlos => J1708 |
    private function reverseBytes32($value)
    {
        return (($value & 0x000000FF) << 24) | (($value & 0x0000FF00) << 8) |
               (($value & 0x00FF0000) >> 8) | (($value & 0xFF000000) >> 24);
    }

    //Author: Carlos => Hex | 
    private function getCrc16Value($dat, $length)
    {
        $crc_ta = array(0x0000, 0x1021, 0x2042, 0x3063, 0x4084, 0x50a5, 0x60c6, 0x70e7,
                        0x8108, 0x9129, 0xa14a, 0xb16b, 0xc18c, 0xd1ad, 0xe1ce, 0xf1ef,
                        0x1231, 0x0210, 0x3273, 0x2252, 0x52b5, 0x4294, 0x72f7, 0x62d6,
                        0x9339, 0x8318, 0xb37b, 0xa35a, 0xd3bd, 0xc39c, 0xf3ff, 0xe3de,
                        0x2462, 0x3443, 0x0420, 0x1401, 0x64e6, 0x74c7, 0x44a4, 0x5485,
                        0xa56a, 0xb54b, 0x8528, 0x9509, 0xe5ee, 0xf5cf, 0xc5ac, 0xd58d,
                        0x3653, 0x2672, 0x1611, 0x0630, 0x76d7, 0x66f6, 0x5695, 0x46b4,
                        0xb75b, 0xa77a, 0x9719, 0x8738, 0xf7df, 0xe7fe, 0xd79d, 0xc7bc,
                        0x48c4, 0x58e5, 0x6886, 0x78a7, 0x0840, 0x1861, 0x2802, 0x3823,
                        0xc9cc, 0xd9ed, 0xe98e, 0xf9af, 0x8948, 0x9969, 0xa90a, 0xb92b,
                        0x5af5, 0x4ad4, 0x7ab7, 0x6a96, 0x1a71, 0x0a50, 0x3a33, 0x2a12,
                        0xdbfd, 0xcbdc, 0xfbbf, 0xeb9e, 0x9b79, 0x8b58, 0xbb3b, 0xab1a,
                        0x6ca6, 0x7c87, 0x4ce4, 0x5cc5, 0x2c22, 0x3c03, 0x0c60, 0x1c41,
                        0xedae, 0xfd8f, 0xcdec, 0xddcd, 0xad2a, 0xbd0b, 0x8d68, 0x9d49,
                        0x7e97, 0x6eb6, 0x5ed5, 0x4ef4, 0x3e13, 0x2e32, 0x1e51, 0x0e70,
                        0xff9f, 0xefbe, 0xdfdd, 0xcffc, 0xbf1b, 0xaf3a, 0x9f59, 0x8f78,
                        0x9188, 0x81a9, 0xb1ca, 0xa1eb, 0xd10c, 0xc12d, 0xf14e, 0xe16f,
                        0x1080, 0x00a1, 0x30c2, 0x20e3, 0x5004, 0x4025, 0x7046, 0x6067,
                        0x83b9, 0x9398, 0xa3fb, 0xb3da, 0xc33d, 0xd31c, 0xe37f, 0xf35e,
                        0x02b1, 0x1290, 0x22f3, 0x32d2, 0x4235, 0x5214, 0x6277, 0x7256,
                        0xb5ea, 0xa5cb, 0x95a8, 0x8589, 0xf56e, 0xe54f, 0xd52c, 0xc50d,
                        0x34e2, 0x24c3, 0x14a0, 0x0481, 0x7466, 0x6447, 0x5424, 0x4405,
                        0xa7db, 0xb7fa, 0x8799, 0x97b8, 0xe75f, 0xf77e, 0xc71d, 0xd73c,
                        0x26d3, 0x36f2, 0x0691, 0x16b0, 0x6657, 0x7676, 0x4615, 0x5634,
                        0xd94c, 0xc96d, 0xf90e, 0xe92f, 0x99c8, 0x89e9, 0xb98a, 0xa9ab,
                        0x5844, 0x4865, 0x7806, 0x6827, 0x18c0, 0x08e1, 0x3882, 0x28a3,
                        0xcb7d, 0xdb5c, 0xeb3f, 0xfb1e, 0x8bf9, 0x9bd8, 0xabbb, 0xbb9a,
                        0x4a75, 0x5a54, 0x6a37, 0x7a16, 0x0af1, 0x1ad0, 0x2ab3, 0x3a92,
                        0xfd2e, 0xed0f, 0xdd6c, 0xcd4d, 0xbdaa, 0xad8b, 0x9de8, 0x8dc9,
                        0x7c26, 0x6c07, 0x5c64, 0x4c45, 0x3ca2, 0x2c83, 0x1ce0, 0x0cc1,
                        0xef1f, 0xff3e, 0xcf5d, 0xdf7c, 0xaf9b, 0xbfba, 0x8fd9, 0x9ff8,
                        0x6e17, 0x7e36, 0x4e55, 0x5e74, 0x2e93, 0x3eb2, 0x0ed1, 0x1ef0
        );
    
        $crc = 0;
        for ($i = 0; $i < $length; $i++) {
            $da = ($crc >> 8) & 0xFF;  // treat as byte
            $crc <<= 8;
            $crc ^= $crc_ta[$da ^ $dat[$i]];
            $crc &= 0xffff;
        }
        return $crc;
    }

    //Author: Carlos => Hex | 
    private function binDataPacket($data)
    {
        $packet = array();        
        
        array_push($packet, self::BIN_FLAG_CHAR);
        for ($i = 0; $i < 6; $i++) {
            if ($data[$i] == self::BIN_FLAG_CHAR || $data[$i] == self::BIN_ESCAPE_CHAR) {
                array_push($packet, self::BIN_ESCAPE_CHARr);
                array_push($packet, ($data[$i] ^ self::BIN_ESCAPE_CHAR) & 0xFF);
            } else {
                array_push($packet, $data[$i]);
            }
        }
        array_push($packet, self::BIN_FLAG_CHAR);
        return $packet;
    }

    //Author: Carlos => Hex | 
    private function dataTxtAcknowledgement($data)
    {
        $crc = self::getCrc16Value($data, count($data));
        return sprintf("*TS01,ACK:%04X#", $crc);
    }
    
    
    private function unPacktDataDecode($str)
    {
        self::$outputText .= sprintf("Unpack data: %s\r\n", $str);
        $segs = preg_split('/[\*\#\,\r\n]/', $str, null, PREG_SPLIT_NO_EMPTY);
        
        self::textSegmentsDecode($segs, 0);
    }
    
    //Author: Carlos => Hex | 
    private function binFrameFormatCheck(&$binFrame)
    {
        $bEscape = false;
        $len = 0;
        for ($i = 0; $i < count($binFrame); $i++) {
            if ($bEscape) {
                $bEscape = false;
                $binFrame[$len++] = $binFrame[$i] ^ self::BIN_ESCAPE_CHAR;
            } else {
                if ($binFrame[$i] == self::BIN_ESCAPE_CHAR) {
                    $bEscape = true;
                    continue;
                } else {
                    $binFrame[$len++] = $binFrame[$i];
                }
            }
        }
        if (self::getCrc16Value($binFrame, $len) != 0) {
            return 0;
        }
        return $len - 2;
    }
    
    //Author: Carlos => Hex | 
    private function binFrameDecode($dat, $len)
    {
        if ($len < 10) {
            return;
        }
        
        $pos = 0;        
        if ($dat[$pos] != self::PROTOCOL_VERSION) {
            self::$outputText .= sprintf("    Can not support protocol version: %02X\r\n", $dat[$pos]);             
            return;
        }
        
        $pos++;
        
        if ($dat[$pos] != 0x01) {
            if (($dat[$pos] & 0x80) != 0) {
                self::fwdFrameDecode($dat, $len); 
            } else {
                self::$outputText .= sprintf("    Can not support frame NO: %02X\r\n", $dat[$pos]);                
            }
            return;
        }

        $pos++;
        $DeviceID = "";

        for ($i = 0; $i < (int)((self::DEVICE_ID_LEN + 1) / 2); $i++) {
            if ($i == 0 && (self::DEVICE_ID_LEN & 0x01) != 0) {
                $DeviceID .= strtoupper(dechex($dat[$pos++]));
            } else {
                $DeviceID .= strtoupper(sprintf("%02X", $dat[$pos++]));
            }
        }

        $TabChars = "    ";
        self::$outputText .= sprintf("%sDevice ID: %s\r\n", $TabChars, $DeviceID);         

        $timeSeconds = self::readUint32($dat, $pos);
        $pos += 4;

        $Fix3D = ($timeSeconds & 0x80000000) != 0;
        $timeSeconds &= 0x7FFFFFFF;

        if ($timeSeconds == 0) {
            self::$outputText .= sprintf("%sTime stamp: %s\r\n", $TabChars, "Unknown");            
        } else {

            $dtOffset = new \DateTime("2000-01-01 00:00:00", new \DateTimeZone("UTC"));
            $dtTicks = $dtOffset->getTimestamp() * 10000000 + $dtOffset->format("u") * 10 + $timeSeconds * 10000000;
            $dt = \DateTime::createFromFormat("U.u", number_format($dtTicks / 10000000, 6, ".", ""), new \DateTimeZone(date_default_timezone_get()));

            self::$outputText .= sprintf("%sTime stamp: %s\r\n", $TabChars, $dt->format('d-m-Y H:i:s'));             
        }

        while ($pos < $len -2 ) {
            $infoId = $dat[$pos++];
            $infoLen = null;

            switch ($infoId) {
                case 1:     //GPS
                    $infoLen = $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        self::gpsDecodeFromBinary($infoData, $Fix3D);
                    }
                    break;
                case 2:     //LBS
                    $infoLen = $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        LbsDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 3:     //STT
                    $infoLen = $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        self::sttDecodeFromBinary($infoData); 
                    }
                    break;
                case 4:     //MGR
                    $infoLen = $dat[$pos++];
                    $algorithm = ($infoLen >> 4) & 0x0f;
                    $infoLen &= 0x0f;

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        self::mgrDecodeFromBinary($infoData, $algorithm);
                    }
                    break;
                case 5:     //ADC
                    $infoLen = $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        self::adcDecodeFromBinary($infoData);
                    }
                    break;
                case 6:     //GFS
                    $infoLen = $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        self::gfsDecodeFromBinary($infoData);
                    }
                    break;
                case 7:     //OBD
                    $infoLen = $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        self::obdDecodeFromBinary($infoData);
                    }
                    break;
                case 8:     //FUL
                    $infoLen = $dat[$pos++];
                    $algorithm = ($infoLen >> 4) & 0x0f;
                    $infoLen &= 0x0F;

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        self::fulDecodeFromBinary($infoData, $algorithm);
                    }
                    break;
                case 9:     //OAL
                    $infoLen = $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        OalDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 0x0A:  //HDB
                    $infoLen = $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        HdbDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 0x0B:  //CAN--J1939
                    $infoLen = $dat[$pos++];
                    $infoLen = $infoLen * 256 + $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        CanDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 0x0C:  //HVD--J1708
                    $infoLen = $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        self::hvdDecodeFromBinary($infoData);
                    }
                    break;
                case 0x0D:  //VIN
                    $infoLen = $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        self::vinDecodeFromBinary($infoData);
                    }
                    break;
                case 0x0E:  //RFI
                    $infoLen = $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        RfiDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 0x0F:  //EGT
                    $infoLen = $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        $this->egtDecodeFromBinary($infoData);
                    }
                    break;
                case 0x10:  //EVT
                    $infoLen = $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        EvtDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 0x11:  //USN
                    $infoLen = $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        UsnDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 0x12:  //GDC
                    $infoLen = $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        GdcDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 0x13:  //DIO
                    $infoLen = $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        DioDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 0x14:  //VHD
                    $infoLen = $dat[$pos++];
                    $infoLen = $infoLen * 256 + $dat[$pos++];

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        VhdDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 0x20:  //TRP (Trip Report)
                    $infoLen = $dat[$pos++];                    

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        TrpDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 0x21:  //SAT--GPS (Satellites Signal Strength)
                    $infoLen = $dat[$pos++];                    

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        SatDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 0x3F:  //BCN--iBeacon Info Data
                    $infoLen = $dat[$pos++];
                    $infoLen = $infoLen * 256 + $dat[$pos++];
                    
                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        BcnDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 0x3E:  //BRV--BLE Remote Event
                    $infoLen = $dat[$pos] & 0x0F;
                    
                    if ($len >= $pos + $infoLen) {
                        $ble_evt = $dat[$pos++] >> 4;
                        $infoData = array_slice($dat, $pos, $infoLen);
                        BrvDecodeFromBinary($outStr, $infoData, $ble_evt); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 0x40:  //MSI--IMSI Information
                    $infoLen = $dat[$pos++];                    

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        MsiDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 0x42:  //BTD-BLE Temp. Sensor Data
                    $infoLen = $dat[$pos++];                    

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        BtdDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 0x29:  //IBN--iButton Data
                    $infoLen = $dat[$pos++];                    

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        IbnDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 0x2A:  //OWS--1-Wire Temperature Sensor data
                    $infoLen = $dat[$pos] & 0x7F;
                    
                    if ($len >= $pos + $infoLen) {
                        $withID = (($dat[$pos] & 0x80) != 0);
                        $pos++;
                        $infoData = array_slice($dat, $pos, $infoLen);
                        OwsDecodeFromBinary($outStr, $infoData, $withID);
                    }
                    break;
                case 0x23:  //NET--Cellular Network Status
                    $infoLen = $dat[$pos++];                    

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        NetDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                case 86:
                    $infoLen = $dat[$pos++];                    

                    if ($len >= $pos + $infoLen) {
                        $infoData = array_slice($dat, $pos, $infoLen);
                        ClgDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    }
                    break;
                default:
                    $infoLen = $dat[$pos++];  
                    break;
            }

            $pos += $infoLen;
        }        
    }

    private function fwdFrameDecode($dat, $len)
    {
        $pos = 0;
        $TabChars = "    ";
        $flag = ($dat[1] >> 5) & 0x03;
        $index = $dat[1] & 0x1F;

        if ($flag >= 3) {
            self::$outputText .= sprintf("%sUnknown forward frame\r\n", $TabChars);             
            return;
        } else {
            self::$outputText .= sprintf("%sForward frame, index: %d\r\n", $TabChars, $index);            
        }

        $Fix3D = false;
        $pos += 4;

        if ($flag != 0) {
            $DeviceID = "";

            for ($i = 0; $i < (int)((self::DEVICE_ID_LEN + 1) / 2); $i++) {
                if ($i == 0 && (self::DEVICE_ID_LEN & 0x01) != 0)
                    $DeviceID .= dechex($dat[$pos++]);
                else
                    $DeviceID .= sprintf("%02X", $dat[$pos++]);
            }

            self::$outputText .= sprintf("%sDevice ID: %s\r\n", $TabChars, $DeviceID);             
        
            $timeSeconds = self::readUint32($dat, $pos);
            $pos += 4;
        
            $Fix3D = ($timeSeconds & 0x80000000) != 0;
            $timeSeconds &= 0x7FFFFFFF;

            if ($timeSeconds == 0) {
                self::$outputText .= sprintf("%sTime stamp: %s\r\n", $TabChars, "Unknown");                
            } else {
                $dtOffset = new DateTime(2000, 1, 1, 0, 0, 0);
                $dt = new DateTime($dtOffset->getTimestamp() + (int)$timeSeconds * 10000000);
                self::$outputText .= sprintf("%sTime stamp: %s\r\n", $TabChars, $dt->format('Y-m-d H:i:s'));                 
            }
        }

        if ($flag == 2) {
            $infoId = $dat[$pos++];
            switch ($infoId) {
                case 1: // GPS
                    {
                        $infoLen = $dat[$pos++];
                        $infoData = array_slice($dat, $pos, $infoLen);
                        GpsDecodeFromBinary($outStr, $infoData, $Fix3D); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                        break;
                    }
                case 2: // LBS
                    {
                        $infoLen = $dat[$pos++];
                        $infoData = array_slice($dat, $pos, $infoLen);
                        LbsDecodeFromBinary($outStr, $infoData); //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                        break;
                    }
                default:
                    $infoLen = $dat[$pos++];
                    break;
            }
            $pos += $infoLen;
        }

        if ($pos < $len) {
            $fwdData = array_slice($dat, $pos, $len - $pos);
            self::$outputText .= sprintf("%sForward data(ASCII): %s\r\n", $TabChars, utf8_decode($fwdData));             
        
            $strHexFwd = "";
            foreach ($fwdData as $b) {
                $strHexFwd .= sprintf("%02X ", $b);
            }
            self::$outputText .= sprintf("%sForward data(HEX): %s\r\n", $TabChars, $strHexFwd);             
        }        
    }

    //Author: Carlos => Hex | Binary
    private function gpsDecodeFromBinary($info, $b3d)
    {
        if (count($info) != 14) {
            return;
        }
    
        $TabChars = "    ";
        self::$outputText .= $TabChars . "GPS:" . "\r\n";        
        $TabChars .= $TabChars;
    
        $gpsfixed = self::readInt32($info, 0) == 0 && self::readInt32($info, 4) == 0 ? "NoFix" : ($b3d ? "3D" : "2D");
        self::$outputText .= sprintf("%sStatus: %s\r\n", $TabChars, $gpsfixed);
        self::$outputText .= sprintf("%sLatitude: %0.6f\r\n", $TabChars, self::readInt32($info, 0) / 1000000.0);
        self::$outputText .= sprintf("%sLongitude: %0.6f\r\n", $TabChars, self::readInt32($info, 4) / 1000000.0);
        self::$outputText .= sprintf("%sSpeed: %d\r\n", $TabChars, self::readUint16($info, 8));
        self::$outputText .= sprintf("%sCourse: %d\r\n", $TabChars, self::readUint16($info, 10));
        self::$outputText .= sprintf("%sHDOP: %0.2f\r\n", $TabChars, self::readUint16($info, 12) / 100.0);
    }

    //Author: Carlos => Hex | Binary
    private function readInt32($dat, $pos)
    {
        return (int)self::readUint32($dat, $pos);
    }

    //Author: Carlos => Hex | Binary
    private function sttDecodeFromBinary($info)
    {
        $TabChars = "    ";
        if (count($info) != 4) {
            return;
        }

        self::$outputText .= $TabChars . "STT:" . "\r\n";
        $TabChars .= $TabChars;

        $iStatus = self::readUint16($info, 0);
        $iAlarm = self::readUint16($info, 2);

        $infoStatus = ["Power cut", 
            "Moving", 
            "Over speed", 
            "Jamming", 
            "Geo-fence alarming",
            "Immobolizer",
            "ACC",
            "Input high level",
            "Input mid level",
            "Engine",
            "Panic",
            "OBD alarm",
            "Course rapid change",
            "Speed rapid change",
            "Roaming(T3xx)/BLE connecting(L10x)",
            "Inter roaming(T3xx)/OBD connecting(L10x)"
        ];

        $infoAlarm = ["Power cut",
            "Moved",
            "Over speed",
            "Jamming",
            "Geo-fence",
            "Towing",
            "Reserved",
            "Input low",
            "Input high",
            "Reserved",
            "Panic",
            "OBD",
            "Reserved",
            "Rollover",
            "Accident",
            "Battery low"
        ];

        self::$outputText .=  $TabChars . "Status:" . "\r\n";

        for ($i=0; $i < 16; $i++) {
            $bitMask = (int)(0x0001 << $i);
            $strStatus = sprintf("            [Bit%02d]:%d--%s\r\n",
                     $i,
                     (($iStatus & $bitMask) != 0) ? 1 : 0,
                     $infoStatus[$i]);
            self::$outputText .= $strStatus;
        }

        self::$outputText .= $TabChars . "Alarm:" . "\r\n";
        
        for ($i=0; $i < 16; $i++) {
            $bitMask = (int)(0x0001 << $i);
            $strAlarm = sprintf("            [Bit%02d]:%d--%s\r\n",
                     $i,
                     (($iAlarm & $bitMask) != 0) ? 1 : 0,
                     $infoStatus[$i]);
            self::$outputText .= $strAlarm;
        }
    }

    //Author: Carlos => Hex | Binary
    private function mgrDecodeFromBinary($info, $id)
    {
        if (count($info) != 4) {
            return;
        }

        self::$outputText .= sprintf("    Mileage: Algorithm[%d]%d(meters)\r\n", $id, self::readUint32($info, 0));
    }

    //Author: Carlos => Hex | Binary
    private function adcDecodeFromBinary($info)
    {
        if ((count($info) % 2) != 0) {
            if (count($info) > 32) {
                return;
            }
        }

        $TabChars = "    ";
        self::$outputText .= $TabChars . "Analog value:" . "\r\n";
        $TabChars .= $TabChars;

        $infoAdc = [
            "Car Battery",
            "Device Temp.",
            "Inner Battery",
            "Input voltage",
            "Inner Battery percent",
            "Ultrasonic fuel sensor height",
            "Ultrasonic fuel sensor height",
            "Ultrasonic fuel sensor height",
            "Ultrasonic fuel sensor height",
            "Ultrasonic fuel sensor height"
        ];
        
        $infoUnit = ["(V)", "(Celsius)", "(V)", "(V)", "(%)", "(mm)", "(mm)", "(mm)", "(mm)", "(mm)" ];

        for ($i=0; $i < (count($info) / 2); $i++) {
            $val = self::readUint16($info, 2 * $i);
            $valId = (int)(($val >> 12) & 0x000f);
            $val &= 0x0fff;
            $strAdc;

            switch ($valId) {
                case 0:
                case 2:
                case 3:
                    $strAdc = sprintf("%s%d: %.2f%s--%s\r\n",
                                    $TabChars,
                                    $valId,
                                    ($val * (100 - (-10))/4096) + (-10),
                                    $infoUnit[$valId],
                                    $infoAdc[$valId]);
                    break;
                case 1:
                    $strAdc = sprintf("%s%d: %.2f%s--%s\r\n",
                                    $TabChars,
                                    $valId,
                                    ($val * (125 - (-55))/4096) + (-55),
                                    $infoUnit[$valId],
                                    $infoAdc[$valId]);
                    break;
                case 4:
                    $strAdc = sprintf("%s%d: %.2f%s--%s\r\n",
                                    $TabChars,
                                    $valId,
                                    ($val * (200 - (-100))/4096) + (-100),
                                    $infoUnit[$valId],
                                    $infoAdc[$valId]);
                    break;
                case 5:
                case 6:
                case 7:
                case 8:
                case 9:
                    $strAdc = sprintf("%s%d: %.2f%s--%s\r\n",
                                    $TabChars,
                                    $valId,
                                    ($val * (2000 - 0)/4096) + 0,
                                    $infoUnit[$valId],
                                    $infoAdc[$valId]);
                    break;
                default:
                    $strAdc = sprintf("%s%d: %f--Unknow\r\n", $TabChars, $valId, $val);
                    break;
            }

            self::$outputText .= $strAdc;
        }
    }

    //Author: Carlos => Hex | Binary
    private function gfsDecodeFromBinary($info)
    {
        $TabChars = "    ";
        if (count($info) != 8) {
            return;
        }

        self::$outputText .= $TabChars . "Geo-fence:" . "\r\n";
        $TabChars .= $TabChars;

        $iStatus = self::readUint32($info, 0);
        $iAlarm = self::readUint32($info, 4);

        self::$outputText .= $TabChars . "Status:\r\n";
        self::$outputText .= "            ";

        for ($i = 0; $i < 16; $i++) {
            $bitMask = (0x0001 << $i);
            $strStatus = sprintf("%02d:%s, ",
                        $i,
                        (($iStatus & $bitMask) != 0) ? "I" : "O");
            self::$outputText .= $strStatus;
        }

        self::$outputText .= "\r\n";
        self::$outputText .= "            ";

        for ($i = 16; $i < 32; $i++) {
            $bitMask = (0x0001 << $i);
            $strStatus = sprintf("%02d:%s, ",
                        $i,
                        (($iStatus & $bitMask) != 0) ? "I" : "O");
            self::$outputText .= $strStatus;
        }

        self::$outputText .= "\r\n";
        self::$outputText .= $TabChars . "Alarm:\r\n";
        self::$outputText .= "            ";

        for ($i = 0; $i < 16; $i++) {
            $bitMask = (0x0001 << $i);
            $strAlarm = sprintf("%02d:%s, ",
                        $i,
                        (($iAlarm & $bitMask) != 0) ? "Y" : "N");
            self::$outputText .= $strAlarm;
        }

        self::$outputText .= "\r\n";
        self::$outputText .= "            ";

        for ($i = 16; $i < 32; $i++) {
            $bitMask = (0x0001 << $i);
            $strAlarm = sprintf("%02d:%s, ",
                        $i,
                        (($iAlarm & $bitMask) != 0) ? "Y" : "N");
            self::$outputText .= $strAlarm;
        }
        self::$outputText .= "\r\n";
    }

    //Author: Carlos => Hex | Binary
    private function obdDecodeFromBinary($info)
    {
        self::$outputText .= "    " . "OBDII:" . "\r\n";
        self::obdDataDecode($info);
    }

    //Author: Carlos => Hex | Binary
    private function fulDecodeFromBinary($info, $id)
    {
        if (count($info) != 4) {
            return;
        }

        $fuel = self::readUint32($info, 0);
        $strFul = sprintf("    Fuel consumption: Algorithm[%d]%s\r\n",
                  $id,
                  $fuel);
        
        self::$outputText .= $strFul;
    }

    private function vinDecodeFromBinary($infoData)
    {        
        $strVin = "    VIN: ";
        foreach ($infoData as $byte) {
            $strVin .= chr($byte);
        }
        
        self::$outputText .= $strVin . "\r\n";
    }

    //Author: Carlos => Hex | Binary
    private function hvdDecodeFromBinary($info)
    {
        self::$outputText .= "    " . "J1708:" . "\r\n";
        self::j1708DataDecode($info);
    }

    private function egtDecodeFromString($str) {
        $output = sprintf("    Engine seconds: %s(seconds)\r\n", $str);
        self::$outputText .= $output;         
    }
    
    private function egtDecodeFromBinary($info) {
        if (count($info) != 4) {
            return;
        }

        $output = sprintf("    Engine seconds: %s(seconds)\r\n", self::readUint32($info, 0));
        self::$outputText .= $output;         
    }

    //Author: Carlos
    /* private function canDecodeFromString($str)
    {
        if (strlen($str) % 2 != 0) {
            return;
        }
        
        self::$outputText .= "    " . "J1939:" . "\r\n";         
    
        $j1939data = array();
    
        for ($i = 0; $i < strlen($str); $i += 2) {
            $j1939data[] = hexdec(substr($str, $i, 2));
        }

        self::j1939DataDecode($j1939data);
    } */
    
    //Author: Carlos
    /* private function j1939DataDecode($candata)
    {
        $pos = 0;

        while ($pos < count($candata)) {
            $len = $candata[$pos] & 0x7F;
            $canFrame = ($candata[$pos] & 0x80) != 0;

            if ($len + $pos + 1 > count($candata)) {
                break;
            }
                
            if ($len < 4 || (!$canFrame && $candata[$pos + 1] != 0)) {
                $pos += $len + 1;
                continue;
            }

            if ($canFrame) {
                $value = array_slice($candata, $pos + 1, $len - 1);
                self::obdCanSnifferDecode($outStr, $value);
            } else {
                $pgn = self::readUint16($candata, $pos + 2);
                $value = array_slice($candata, $pos + 4, $len - 3);
                self::j1939PgnDecode($outStr, $value, $pgn);
            }

            $pos += $len + 1;
        }
    } */
    
    //Author: Carlos
    /* private function j1939PgnDecode($value, $pgn)
    {
        $hex = "";
        for ($i = 0; $i < count($value); $i++) {
            $hex .= sprintf("%02X ", $value[$i]);
        }

        if ($pgn == 65226 || $pgn == 65227) {
            $strCan = sprintf("        PGN%d: %s---%s\r\n%s",
                                $pgn,
                                $hex,
                                ($pgn == 65226 ? "Active diagnostic trouble codes(DM1)" : "Previously active diagnostic trouble codes (DM2)"),
                                J1939DtcsDecode($value));
            
            self::$outputText .= $strCan;
            return;
        }

        foreach (self::$pgns as $p) {
            if ($p->PgnNum != $pgn) {
                continue;
            }
                
            if ($p->ParseData((uint)$pgn, $value)) {
                $strCan = sprintf("        PGN%d: %s---%s\r\n",
                        $pgn,
                        $hex, 
                        $p->Name);
                $blanks = "                ";
                foreach ($p->Spns as $s) {
                    $sval = $s->PrintValue();
                    if ($sval != null && $sval != "") {
                        $strCan .= sprintf("%s%s\r\n", $blanks, $sval);
                    }
                }
                OutputText($outStr, $strCan);
                return;
            }
        }
        OutputText($outStr, 
            sprintf("        PGN%d: %s\r\n",
                        $pgn,
                        $hex));
    } */
    
}
