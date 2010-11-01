<?php
/**
 * Dummy IMP_Prefs_Identity stub.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    IMP
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Imp
 */

/**
 * Dummy IMP_Prefs_Identity stub.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @category   Horde
 * @package    IMP
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/gpl.html GPL
 * @link       http://pear.horde.org/index.php?package=Imp
 */
class Horde_Itip_Stub_Identity
{
    private $_id = 'default';

    public function getMatchingIdentity($mail)
    {
        if ($mail == 'test@example.org') {
            return 'test';
        }
    }

    public function setDefault($id)
    {
        if ($id != 'test' && $id != 'other' && $id != 'default') {
            throw new Exception("Unexpected default $id!");
        }
        $this->_id = $id;
    }

    public function getDefault()
    {
        return $this->_id;
    }

    public function getFromAddress()
    {
        if ($this->_id == 'test') {
            return 'test@example.org';
        }
        if ($this->_id == 'default') {
            return 'default@example.org';
        }
    }

    public function getValue($value)
    {
        switch ($value) {
        case 'fullname':
            if ($this->_id == 'test') {
                return 'Mr. Test';
            } else {
                return '';
            }
        case 'replyto_addr':
            switch ($this->_id) {
            case 'test':
                return 'test@example.org';
            case 'other':
                return 'reply@example.org';
            }
        }
    }
}