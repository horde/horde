<?php
/**
 * Horde_Imsp_Options Class - provides an interface to IMSP server-based
 * options storage.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Horde_Imsp
 */
class Horde_Imsp_Options
{
    /**
     * Horde_Imsp object.
     *
     * @var Horde_Imsp_Client_Base
     */
    protected $_imsp;

    /**
     * Parameter list.
     *
     * @var array
     */
    protected $_params;

    /**
     * Constructor.
     *
     * @param Horde_Imsp_Client_base $client  The client connection.
     * @param array $params                   Hash containing IMSP parameters.
     */
    public function __construct(Horde_Imsp_Client_Base $client, array $params)
    {
        $this->_params = $params;
        $this->_imsp = $client;
        $this->_imsp->_logger->debug('Horde_Imsp_Options initialized.');
    }

    /**
     * Function sends a GET command to IMSP server and retrieves values.
     *
     * @param  string $option  Name of option to retrieve. Accepts '*' as wild
     *                         card.
     *
     * @return array  Hash containing option=>value pairs.
     * @throws Horde_Imsp_Exception
     */
    public function get($option)
    {
        $options = array();
        $this->_imsp->send("GET $option", true, true);
        $server_response = $this->_imsp->receive();
        while (preg_match("/^\* OPTION/", $server_response)) {
            /* First, check for a {}. */
            if (preg_match(Horde_Imsp_Client_Base::OCTET_COUNT, $server_response, $tempArray)) {
                $temp = explode(' ', $server_response);
                $options[$temp[2]] = $this->_imsp->receiveStringLiteral($tempArray[2]);
                $this->_imsp->receive();
            } else {
                $temp = explode(' ', $server_response);
                $options[$temp[2]] = trim($temp[3]);
                $i = 3;
                $lastChar = "";
                $nextElement = trim($temp[3]);

                /* Was the value quoted and spaced? */
                if ((substr($nextElement, 0, 1) == '"') &&
                    (substr($nextElement, strlen($nextElement) - 1, 1) != '"')) {
                    do {
                        $nextElement = $temp[$i + 1];
                        $lastChar = substr($nextElement, strlen($nextElement) - 1, 1);
                        $options[$temp[2]] .= ' ' . $nextElement;
                        if ($lastChar == '"') {
                            $done = true;
                        } else {
                            $done = false;
                            $lastChar = substr($temp[$i + 2], strlen($temp[$i + 2]) - 1, 1);
                            $i++;
                        }

                    } while ($lastChar != '"');

                    if (!$done) {
                        $nextElement = $temp[$i + 1];
                        $options[$temp[2]] .= ' ' . $nextElement;
                    }
                }
            }
            $server_response = $this->_imsp->receive();
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
     * @param string $name  Name of option to set.
     * @param string $value Value to assign.
     *
     * @throws Horde_Imsp_Exception
     */
    public function set($option, $value)
    {
        /* Send the beginning of the command. */
        $this->_imsp->send("SET $option ", true, false);

        /* Send $optionValue as a literal {}? */
        if (preg_match(Horde_Imsp_Client_Base::MUST_USE_LITERAL, $value)) {
            $biValue = sprintf("{%d}", strlen($value));
            $result = $this->_imsp->send($biValue, false, true, true);
        }

        /* Now send the rest of the command. */
        $result = $this->_imsp->send($value, false, true);
        $server_response = $this->_imsp->receive();
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
