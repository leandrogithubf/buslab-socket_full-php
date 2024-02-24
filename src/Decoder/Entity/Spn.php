<?php

namespace App\Decoder\Entity;

class Spn 
{
    private $spn = 0;
    private $byteOfs = 0;
    private $bitOfs = 0;
    private $byteCnt = 0;
    private $mask = 0;
    private $repeat = false;
    private $parseInfo = null;

    public function __construct(string $spnInfo = null, $spn = 0, $byteOfs = 0, $bitOfs = 0, $byteCnt = 0, $mask = 0)
    {        
        $this->spn = $spn;
        $this->byteOfs = $byteOfs;
        $this->bitOfs = $bitOfs;
        $this->byteCnt = $byteCnt;
        $this->mask = $mask;

        if ($spnInfo !== null) {
            $segs = explode(',', $spnInfo);
            if (count($segs) >= 5) {
                $this->spn = (int)$segs[0];
                $this->byteOfs = (int)$segs[1];
                $this->bitOfs = (int)$segs[2];
                $this->byteCnt = (int)$segs[3];
                $this->mask = hexdec($segs[4]);
                if (count($segs) >= 6) {
                    $this->repeat = (int)$segs[5] > 0;
                }
            }
        }        
    }

    public function getSpnNum(): int
    {
        return $this->spn;
    }

    public function getByteCnt(): int
    {
        return $this->byteCnt;
    }

    public function getByteOfs(): int
    {
        return $this->byteOfs;
    }

    public function getBitOfs(): int
    {
        return $this->bitOfs;
    }

    public function getMask(): int
    {
        return $this->mask;
    }

    public function SetParseInfo($info)
    {
        $this->parseInfo = $info;
    }

    public function ParseData($dat, $split)
    {
        if ($this->parseInfo == null) {
            return false;
        }

        $this->parseInfo->ValValid = false;
        $this->parseInfo->StrValid = false;

        if ($split != '\0') {
            $strData = utf8_decode($dat);
            $segs = explode($split, $strData);
            if ($this->byteOfs < count($segs)) {
                $this->parseInfo->SetValue($segs[$this->byteOfs]);
                return true;
            }
        } else {
            switch ($this->_byteCnt) {
                case 1:
                    $val = $this->ReadUnsignedByte($dat, $this->byteOfs);
                    $val >>= $this->bitOfs;
                    $val &= (int)$this->mask;
                    $this->parseInfo->SetValue($val);
                    return true;

                case 2:
                    $val = $this->ReadUint16($dat, $this->byteOfs);
                    $val >>= $this->bitOfs;
                    $val &= (int)$this->mask;
                    $this->parseInfo->SetValue($val);
                    return true;

                case 4:
                    $val = $this->ReadUInt32($dat, $this->byteOfs);
                    $val >>= $this->bitOfs;
                    $val &= (int)$this->mask;
                    $this->parseInfo->SetValue($val);
                    return true;

                case 8:
                    $val = $this->ReadUInt64($dat, $this->byteOfs);
                    $val >>= $this->bitOfs;
                    $val &= $this->mask;
                    $this->parseInfo->SetValue($val);
                    return true;

                case 3:
                case 5:
                case 6:
                case 7:
                    $val = $this->ReadBytesVal($dat, $this->byteOfs, $this->_byteCnt);
                    $val >>= $this->bitOfs;
                    $val &= $this->mask;
                    $this->parseInfo->SetValue($val);
                    return true;

                default:
                    break;
            }
        }

        return false;
    }

    public function PrintValue()
    {
        $val = "";
        if ($this->parseInfo != null) {
            if ($this->parseInfo->StrValid) {
                $val = sprintf("SPN%-8s%15s%-10s%s",
                    $this->spn,
                    $this->parseInfo->StrVal,
                    "",
                    $this->parseInfo->Name);
            } else {
                if ($this->spn < 65000) {
                    $val = sprintf("SPN%-8s%15s%-10s%s",
                        $this->spn,
                        $this->parseInfo->ValValid ? $this->parseInfo->Value : "(NA)",
                        $this->parseInfo->ValValid ? $this->parseInfo->Unit : "",
                        $this->parseInfo->Name);
                } else {
                    $val = sprintf("%15s%-10s%s",
                        $this->parseInfo->ValValid ? $this->parseInfo->Value : "(NA)",
                        $this->parseInfo->ValValid ? $this->parseInfo->Unit : "",
                        $this->parseInfo->Name);
                }
                $valDesc = $this->parseInfo->GetValueDesc();
                if ($valDesc != null && $valDesc != "") {
                    $val .= sprintf(" [ %s ] ", $valDesc);
                }
            }
        }
        return $val;
    }

    public function AddSpnInfo($inf)
    {
        if ($inf->Spn == $this->spn) {
            $this->parseInfo = $inf;
            return true;
        }
        return false;
    }
    
    private function ReadUnsignedByte($dat, $pos)
    {
        if ($pos + 1 > count($dat))
            return 0;
        return $dat[$pos];
    }
    
    private function ReadSignedByte($dat, $pos)
    {
        if ($pos + 1 > count($dat))
            return 0;
        
        return unpack('c', substr($dat, $pos, 1))[1];
    }
    
    private function ReadUInt32($dat, $pos)
    {
        if ($pos + 4 > count($dat))
            return 0;
        $val = 0;
        for ($i = 3; $i >= 0; $i--) {
            $val = ($val << 8) + $dat[$pos + $i];
        }
        return $val;
    }
    
    private function ReadInt32($dat, $pos)
    {
        $uint32 = $this->ReadUInt32($dat, $pos);        
        return unpack("l", pack("L", $uint32))[1];        
    }
    
    private function readUint16($dat, $pos)
    {
        if ($pos + 2 > count($dat))
            return 0;
        $val = 0;
        for ($i = 1; $i >= 0; $i--)
        {
            $val = ($val << 8) + $dat[$pos + $i];
        }
        return $val;
    }
    
    private function readInt16($dat, $pos)
    {
        $uint16 = $this->readUint16($dat, $pos);        
        return unpack("s", pack("v", $uint16))[1];
    }
    
    private function readUInt64($dat, $pos)
    {
        if ($pos + 8 > count($dat))
            return 0;
        $val = 0;
        for ($i = 7; $i >= 0; $i--)
        {
            $val = ($val << 8) + $dat[$pos + $i];
        }
        return $val;
    }
    
    private function readBytesVal($dat, $pos, $cnt)
    {
        if ($pos + $cnt > count($dat))
            return 0;
        $val = 0;
        for ($i = $cnt - 1; $i >= 0; $i--)
        {
            $val = ($val << 8) + $dat[$pos + $i];
        }
        return $val;
    }    
}
