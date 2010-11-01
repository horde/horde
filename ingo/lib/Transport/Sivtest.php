<?php
/**
 * Ingo_Transport_Sivtest implements the Sieve_Driver api to allow scripts to
 * be installed and set active via the Cyrus sivtest command line utility.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 * Copyright 2004-2007 Liam Hoekenga <liamr@umich.edu>
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Liam Hoekenga <liamr@umich.edu>
 * @package Ingo
 */
class Ingo_Transport_Sivtest extends Ingo_Transport
{
    /**
     * The Net_Sieve object.
     *
     * @var Net_Sieve
     */
    protected $_sieve;

    /**
     * Constructor.
     */
    public function __construct($params = array())
    {
        $default_params = array(
            'hostspec'   => 'localhost',
            'logintype'  => '',
            'port'       => 2000,
            'scriptname' => 'ingo',
            'admin'      => '',
            'usetls'     => true,
            'command'    => '',
            'socket'     => '',
        );

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

        $this->sivtestSocket($this->_params['username'],
        $this->_params['password'], $this->_params['hostspec']);
        $domain_socket = 'unix://' . $this->_params['socket'];

        $this->_sieve = new Net_Sieve($this->_params['username'],
                                      $this->_params['password'],
                                      $domain_socket,
                                      0,
                                      null,
                                      null,
                                      false,
                                      true,
                                      $this->_params['usetls']);

        $res = $this->_sieve->getError();
        if ($res instanceof PEAR_Error) {
            unset($this->_sieve);
            throw new Ingo_Exception($res);
        }
    }

    /**
     * Sets a script running on the backend.
     *
     * @param string $script  The sieve script.
     *
     * @throws Ingo_Exception
     */
    public function setScriptActive($script)
    {
        $this->_connect();

        $res = $this->_sieve->haveSpace($this->_params['scriptname'], strlen($script));
        if ($res instanceof PEAR_Error) {
            throw new Ingo_Exception($res);
        }

        return $this->_sieve->installScript($this->_params['scriptname'], $script, true);
    }

    /**
     * Returns the content of the currently active script.
     *
     * @return string  The complete ruleset of the specified user.
     * @throws Ingo Exception
     */
    public function getScript()
    {
        $this->_connect();
        return $this->_sieve->getScript($this->_sieve->getActive());
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
        $error_return = '';

        if (strtolower($this->_params['logintype']) == 'gssapi'
            && isset($_SERVER['KRB5CCNAME'])) {
            $command .= 'KRB5CCNAME=' . $_SERVER['KRB5CCNAME'];
        }

        $domain_socket = 'unix://' . $this->_params['socket'];

        $command .= ' ' . $this->_params['command']
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
                        $error_return = ': No socket after 10 seconds of trying!';
                        continue 2;
                    }
                }
            }
            $socket = new Net_Socket();
            $error = $socket->connect($domain_socket, 0, true, 30);
            if (!($error instanceof PEAR_Error)) {
                break;
            }

            // We failed, break this connection.
            unlink($this->_params['socket']);
        }

        if (!empty($error_return)) {
            throw new Ingo_Exception($error_return);
        }

        $status = $socket->getStatus();
        if ($status instanceof PEAR_Error || $status['eof']) {
            throw new Ingo_Exception(_("Failed to write to socket: (connection lost!)"));
        }

        $error = $socket->writeLine("CAPABILITY");
        if ($error instanceof PEAR_Error) {
            throw new Ingo_Exception(_("Failed to write to socket: " . $error->getMessage()));
        }

        $result = $socket->readLine();
        if ($result instanceof PEAR_Error) {
            throw new Ingo_Exception(_("Failed to read from socket: " . $error->getMessage()));
        }

        if (preg_match('|^bye \(referral "(sieve://)?([^"]+)|i',
                       $result, $matches)) {
            $socket->disconnect();

            $this->sivtestSocket($username, $password, $matches[2]);
        } else {
            $socket->disconnect();
            exec($command . ' > /dev/null 2>&1');
            sleep(1);
        }
    }

}
