<?php

namespace App\Decoder\Entity;

class Pgn
{
    private $_name = "";
    private $_pgn = 0;
    private $_len = 0;
    private $_rate = 0;
    private $_split = '\0';
    private $_bitOrder = false;
    private $_spns = array();

    public function Name()
    {
        return $this->_name;
    }

    public function PgnNum()
    {
        return $this->_pgn;
    }

    public function Len()
    {
        return $this->_len;
    }

    public function Rate()
    {
        return $this->_rate;
    }

    public function Spns()
    {
        return $this->_spns;
    }

    public function __construct($xe)
    {
        foreach ($xe->childNodes as $cn) {
            switch ($cn->nodeName) {
                case "pgn":
                    $this->_pgn = intval($cn->nodeValue);
                    break;
                case "name":
                    $this->_name = $cn->nodeValue;
                    break;
                case "length":
                    $this->_len = intval($cn->nodeValue);
                    break;
                case "rate":
                    $this->_rate = intval($cn->nodeValue);
                    break;
                case "split":
                    $this->_split = $cn->nodeValue[0];
                    break;
                case "bitorder":
                    $this->_bitOrder = intval($cn->nodeValue) != 0;
                    break;
                case "spn":
                    {
                        $spn = new Spn($cn->nodeValue);
                        if ($spn->getSpnNum() != 0) {
                            $find = false;
                            foreach ($this->_spns as $s) {
                                if ($s->getSpnNum() == $spn->getSpnNum()) {
                                    $find = true;
                                }
                            }
                            if (!$find) {
                                array_push($this->_spns, $spn);
                            }
                        }
                    }
                    break;
                default:
                    break;
            }
        }
    }

    public function parseData($pgn, $data)
    {
        $parsed = false;
        if ($pgn != $this->_pgn) return false;
        if ($this->_len != 0 && count($data) != $this->_len) return false;
        foreach ($this->_spns as $s) {
            $parsed |= $s->parseData($data, $this->_split);
        }
        return $parsed;
    }

    public function addSpnInfo($inf)
    {
        $added = false;
        if ($inf->getPgn() == $this->_pgn) {
            foreach ($this->_spns as $s) {
                $added |= $s->addSpnInfo($inf);
            }
        }
        return $added;
    }

    public function printValue()
    {
        $val = sprintf("PGN%-10s%s\r\n", $this->_pgn, $this->_name);
        foreach ($this->_spns as $s) {
            $sval = $s->printValue();
            if ($sval != null && $sval != "") {
                $val .= sprintf("\t%s\r\n", $sval);
            }
        }
        return $val;
    }
}
