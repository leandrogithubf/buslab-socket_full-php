<?php

namespace App\Decoder\Entity;

class SpnInfo
{
    private $_name = "";
    private $_unit = "";
    private $_spn = 0;
    private $_pgn = 0;
    private $_bitResolution = 1;
    private $_offset = 0;
    private $_value = 0;
    private $_minVal = 0;
    private $_maxVal = 0;
    private $_valValid = false;
    private $_strVal = "";
    private $_strValid = false;
    private $_valList = array();
    private $_type = "";

    public function getName()
    {
        return $this->_name;
    }

    public function getUnit()
    {
        return $this->_unit;
    }

    public function getSpn()
    {
        return $this->_spn;
    }

    public function getPgn()
    {
        return $this->_pgn;
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function getValList()
    {
        return $this->_valList;
    }
}