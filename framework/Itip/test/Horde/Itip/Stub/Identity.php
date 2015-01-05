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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Imp
 */

/**
 * Dummy IMP_Prefs_Identity stub.
 *
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category   Horde
 * @package    IMP
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/gpl GPL
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
            return new Horde_Mail_Rfc822_Address('test@example.org');
        }
        if ($this->_id == 'default') {
            return new Horde_Mail_Rfc822_Address('default@example.org');
        }
    }

    public function getDefaultFromAddress($fullname = false)
    {
        $ob = $this->getFromAddress();
        if ($fullname) {
            $ob->personal = $this->getValue('fullname');
        }
        return $ob;
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