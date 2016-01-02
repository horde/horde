<?php
/**
 * Copyright 2003-2016 Horde LLC (http://www.horde.org/)
 * Copyright 2004-2007 Liam Hoekenga <liamr@umich.edu>
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Liam Hoekenga <liamr@umich.edu>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

use \Horde\ManageSieve;

/**
 * Ingo_Transport_Sivtest implements an Ingo transport driver to allow scripts
 * to be installed and set active via the Cyrus sivtest command line utility.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Liam Hoekenga <liamr@umich.edu>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Transport_Sivtest extends Ingo_Transport_Timsieved
{
    /**
     * Constructor.
     */
    public function __construct(array $params = array())
    {
        $default_params = array(
            'hostspec'   => 'localhost',
            'logintype'  => '',
            'port'       => 4190,
            'scriptname' => 'ingo',
            'admin'      => '',
            'usetls'     => true,
            'command'    => '',
            'socket'     => '',
        );

        $this->_supportShares = false;

        parent::__construct(array_merge($default_params, $params));
    }

    /**
     * Connect to the sieve server.
     *
     * @throws Ingo_Exception;
     */
    protected function _connect()
    {
        if (!empty($this->_sieve)) {
            return;
        }

        $this->sivtestSocket(
            $this->_params['username'],
            $this->_params['password'],
            $this->_params['hostspec']);

        try {
            $this->_sieve = new ManageSieve(array(
                'user'       => $this->_params['username'],
                'password'   => $this->_params['password'],
                'host'       => 'unix://' . $this->_params['socket'],
                'port'       => null,
                'bypassauth' => true,
                'usetls'     => $this->_params['usetls']
            ));
        } catch (ManageSieve\Exception $e) {
            throw new Ingo_Exception($e);
        }
    }

    /**
     * Used to figure out which Sieve server the script will be run
     * on, and then open a GSSAPI authenticated socket to said server.
     *
     * @param string $username  The username.
     * @param string $password  The password.
     * @param string $hostspec  The hostspec.
     *
     * @return TODO
     * @throws Ingo_Exception
     */
    public function sivtestSocket($username, $password, $hostspec)
    {
        $command = '';
        $error_return = null;

        if (Horde_String::lower($this->_params['logintype']) == 'gssapi' &&
            isset($_SERVER['KRB5CCNAME'])) {
            $command .= 'KRB5CCNAME=' . $_SERVER['KRB5CCNAME'] . ' ';
        }

        $domain_socket = 'unix://' . $this->_params['socket'];

        $command .= $this->_params['command']
            . ' -m ' . $this->_params['logintype']
            . ' -u ' . $username
            . ' -a ' . $username
            . ' -w ' . $password
            . ' -p ' . $this->_params['port']
            . ' -X ' . $this->_params['socket']
            . ' ' . $hostspec;

        $conn_attempts = 0;
        while ($conn_attempts++ < 4) {
            $attempts = 0;
            if (!file_exists($this->_params['socket'])) {
                exec($command . ' > /dev/null 2>&1');
                sleep(1);
                while (!file_exists($this->_params['socket'])) {
                    usleep(200000);
                    if ($attempts++ > 5) {
                        $error_return = _("No socket after 10 seconds of trying");
                        continue 2;
                    }
                }
            }
            try {
                $socket = new \Horde\Socket\Client($domain_socket, 0, 30);
            } catch (Horde_Exception $error_return) {
                break;
            }

            // We failed, break this connection.
            unlink($this->_params['socket']);
        }

        if ($error_return) {
            throw new Ingo_Exception($error_return);
        }

        try {
            $status = $socket->getStatus();
            if ($status['eof']) {
                throw new Ingo_Exception(_("Failed to write to socket: (connection lost!)"));
            }
            $socket->write("CAPABILITY\r\n");
        } catch (Horde_Exception $e) {
            throw new Ingo_Exception(sprintf(_("Failed to write to socket:"), $e->getMessage()));
        }

        try {
            $result = rtrim($socket->gets(), "\r\n");
        } catch (Horde_Exception $e) {
            throw new Ingo_Exception(sprintf(_("Failed to read from socket:"), $e->getMessage()));
        }
        $socket->close();

        if (preg_match('|^bye \(referral "(sieve://)?([^"]+)|i',
                       $result, $matches)) {
            $this->sivtestSocket($username, $password, $matches[2]);
        } else {
            exec($command . ' > /dev/null 2>&1');
            sleep(1);
        }
    }

}
