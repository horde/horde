<?php
/**
 * Horde_Imsp_Options Class - provides an interface to IMSP server-based
 * options storage.
 *
 * Required parameters:<pre>
 *   'username'     Username to logon to IMSP server as.
 *   'password'     Password for current user.
 *   'auth_method'  The authentication method to use to login.
 *   'server'       The hostname of the IMSP server.
 *   'port'         The port of the IMSP server.</pre>
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 * @package Horde_Imsp
 */
class Horde_Imsp_Options
{
    /**
     * Horde_Imsp object.
     *
     * @var Horde_Imsp
     */
    protected $_imsp;

    /**
     * Parameter list.
     *
     * @var array
     */
    protected $_params;

    /**
     * Constructor function.
     *
     * @param array $params  Hash containing IMSP parameters.
     */
    public function __construct(array $params)
    {
        $this->_params = $params;
        $auth = Horde_Imsp_Auth_Factory::create($this->_params['auth_method']);
        $this->_imsp = $auth->authenticate($this->_params);
        $this->_imsp->_logger->debug('Horde_Imsp_Options initialized.');
    }

    /**
     * Function sends a GET command to IMSP server and retrieves values.
     *
     * @param  string $optionName Name of option to retrieve. Accepts '*'
     *                            as wild card.
     *
     * @return array  Associative array containing option=>value pairs.
     * @throws Horde_Imsp_Exception
     */
    public function get($optionName)
    {
        $options = array();
        $this->_imsp->imspSend("GET $optionName", true, true);
        $server_response = $this->_imsp->imspReceive();
        while (preg_match("/^\* OPTION/", $server_response)) {
            /* First, check for a {}. */
            if (preg_match(Horde_Imsp::OCTET_COUNT, $server_response, $tempArray)) {
                $temp = explode(' ', $server_response);
                $options[$temp[2]] = $this->_imsp->receiveStringLiteral($tempArray[2]);
                $this->_imsp->imspReceive();
            } else {
                $temp = explode(' ', $server_response);
                $options[$temp[2]] = trim($temp[3]);
                $i = 3;
                $lastChar = "";
                $nextElement = trim($temp[3]);

                /* Was the value quoted and spaced? */
                if ((substr($nextElement,0,1) == '"') &&
                    (substr($nextElement,strlen($nextElement) - 1, 1) != '"')) {
                    do {
                        $nextElement = $temp[$i+1];
                        $lastChar = substr($nextElement,
                                           strlen($nextElement) - 1, 1);
                        $options[$temp[2]] .= ' ' . $nextElement;
                        if ($lastChar == '"') {
                            $done = true;
                        } else {
                            $done = false;
                            $lastChar = substr($temp[$i+2],
                                               strlen($temp[$i+2]) - 1, 1);
                            $i++;
                        }

                    } while ($lastChar != '"');

                    if (!$done) {
                        $nextElement = $temp[$i+1];
                        $options[$temp[2]] .= ' ' . $nextElement;
                    }
                }
            }
            $server_response = $this->_imsp->imspReceive();
        }

        if ($server_response != 'OK') {
            throw new Horde_Imsp_Exception('Did not receive the expected response from the server.');
        }

        $this->_imsp->_logger->debug('GET command OK.');
        return $options;
    }

    /**
     * Function sets an option value on the IMSP server.
     *
     * @param string $optionName  Name of option to set.
     * @param string $optionValue Value to assign.
     *
     * @throws Horde_Imsp_Exception
     */
    public function set($optionName, $optionValue)
    {
        /* Send the beginning of the command. */
        $this->_imsp->imspSend("SET $optionName ", true, false);

        /* Send $optionValue as a literal {}? */
        if (preg_match(Horde_Imsp::MUST_USE_LITERAL, $optionValue)) {
            $biValue = sprintf("{%d}", strlen($optionValue));
            $result = $this->_imsp->imspSend($biValue, false, true, true);
        }

        /* Now send the rest of the command. */
        $result = $this->_imsp->imspSend($optionValue, false, true);
        $server_response = $this->_imsp->imspReceive();
        if ($server_response != 'OK') {
            throw new Horde_Imsp_Exception('The option could not be set on the IMSP server.');
        }
        $this->_imsp->_logger->debug('SET command OK.');
    }

    public function logout()
    {
        $this->_imsp->logout();
    }

}
