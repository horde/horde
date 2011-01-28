<?php
/**
 * Copyright 2000-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Chora
 */
class Chora_Readme_Collection
{
    protected $_readmes;

    const CHOOSE_A = -1;
    const CHOOSE_B = 1;
    const EQUAL = 0;

    public function __construct(array $readmes)
    {
        $this->_readmes = $readmes;
    }

    public function chooseReadme()
    {
        $count = count($this->_readmes);
        if ($count == 0) {
            throw new Chora_Exception('No README files to choose from');
        }

        if ($count > 1) {
            usort($this->_readmes, array($this, 'compareReadmes'));
        }

        return $this->_readmes[0];
    }

    public function compareReadmes($a, $b)
    {
        if ($this->_isHtmlReadme($a)) { return self::CHOOSE_A; }
        if ($this->_isHtmlReadme($b)) { return self::CHOOSE_B; }

        $a_len = Horde_String::length($a->queryName());
        $b_len = Horde_String::length($b->queryName());
        if ($a_len < $b_len) {
            return self::CHOOSE_A;
        } elseif ($b_len < $a_len) {
            return self::CHOOSE_B;
        } else {
            return strcasecmp($a->queryName(), $b->queryName());
        }
    }

    protected function _isHtmlReadme(Horde_Vcs_File $readme)
    {
        $file = Horde_String::lower($readme->queryName());
        return ($file == 'readme.html' || $file == 'readme.htm' || ($file == 'readme' && $readme->mimeType == 'text/html'));
    }
}
