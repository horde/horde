<?php

class IMP_Stub_Registry
{
    private $_charset = 'UTF-8';

    public function getCharset()
    {
        return $this->_charset;
    }

    public function setCharset($charset)
    {
        $this->_charset = $charset;
    }
}