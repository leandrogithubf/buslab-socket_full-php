<?php

namespace App\Decoder\Entity;

class ValueDesc {
    private $val_first = 0;
    private $val_last = 0;
    private $_desc = "";

    public function __construct($s) {
        $desc = explode(",", $s);
        $vals = explode("-", $desc[0]);
        if (count($vals) > 0) {
            $this->val_first = (double)$vals[0];
        } else {
            return;
        }
        if (count($vals) > 1) {
            $this->val_last = (double)$vals[1];
        } else {
            $this->val_last = $this->val_first;
        }
        $this->_desc = $desc[1];
    }

    public function getDesc($val) {
        if ($val >= $this->val_first && $val <= $this->val_last)
            return $this->_desc;
        else
            return null;
    }

    public function getDescProperty() {
        return $this->_desc;
    }
}
