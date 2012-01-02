<?php
/**
 * Implements the Accounts API using finger to fetch information.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Peter Paul Elfferich <pp@lazyfox.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde
 */
class Horde_Block_Account_Finger extends Horde_Block_Account_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $params = array_merge(
            array('finger_path' => 'finger'),
            $params);
        parent::__construct($params);
    }

    /**
     * Returns a hash with parsed account information.
     *
     * @param array $output  Array of finger output strings
     *
     * @return array  A hash with account details parsed from output
     */
    protected function _parseAccount($output)
    {
        $info = array();

        foreach ($output as $line) {
            if (preg_match('/^.*Name: (.*)$/', $line, $regs)) {
                $info['fullname'] = $regs[1];
            } elseif (preg_match('/^Directory: (.*)Shell: (.*)$/', $line, $regs)) {
                $info['home'] = trim($regs[1]);
                $info['shell'] = $regs[2];
            }
        }

        return $info;
    }

    /**
     * Returns the user account.
     *
     * @return array  A hash with complete account details.
     */
    protected function _getAccount()
    {
        if (!isset($this->_information)) {
            $user = Horde_String::lower($this->getUsername());
            if (!empty($this->_params['host'])) {
                $user .= '@' . $this->_params['host'];
            }
            $command = $this->_params['finger_path'] . ' ' . escapeshellarg($user);
            exec($command, $output);
            $this->_information = $this->_parseAccount($output);
        }
        return $this->_information;
    }

    /**
     * Returns some user detail.
     *
     * @param string $what  Which information to return.
     *
     * @return string  The user's detail.
     */
    protected function _get($what)
    {
        $information = $this->_getAccount();
        return $information[$what];
    }

    /**
     * Returns the user's full name.
     *
     * @return string  The user's full name.
     */
    public function getFullname()
    {
        return $this->_get('fullname');
    }

    /**
     * Returns the user's home (login) directory.
     *
     * @return string  The user's directory.
     */
    public function getHome()
    {
        return $this->_get('home');
    }

    /**
     * Returns the user's default shell.
     *
     * @return string  The user's shell.
     */
    public function getShell()
    {
        return $this->_get('shell');
    }
}
