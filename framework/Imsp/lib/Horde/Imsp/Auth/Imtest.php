<?php
/**
 * IMSP authentication class for authentication through imtest.
 *
 * Required parameters:<pre>
 *   'username'        Username to logon to IMSP server as.
 *   'password'        Password for current user.
 *   'server'          The hostname of the IMSP server.
 *   'port'            The port of the IMSP server.
 *   'socket'          The named socket to use for connection
 *   'command'         Path to the imtest command on localhost
 *   'auth_mechanism'  Authentication method to use with imtest</pre>
 *
 * Copyright 2005-2007      Liam Hoekenga <liamr@umich.edu>
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Liam Hoekenga <liamr@umich.edu>
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Horde_Imsp
 */
class Horde_Imsp_Auth_Imtest extends Horde_Imsp_Auth_Base
{
    /**
     * Private authentication function.  Provides actual
     * authentication code.
     *
     * @return boolean
     */
    protected function _authenticate()
    {
        $command = '';
        $error_return = '';
        if (strtolower($this->_params['auth_mechanism']) == 'gssapi' &&
            isset($_SERVER['KRB5CCNAME'])) {
                $command .= 'KRB5CCNAME=' . $_SERVER['KRB5CCNAME'];
        }

        $command .= ' '    . $this->_params['command'].
                    ' -m ' . $this->_params['auth_mechanism'] .
                    ' -u ' . escapeshellarg($this->_params['username']) .
                    ' -a ' . escapeshellarg($this->_params['username']) .
                    ' -w ' . escapeshellarg($this->_params['password']).
                    ' -p ' . $this->_params['port'] .
                    ' -X ' . $this->_params['socket'] .
                    ' '    . $this->_params['server'];

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
            $fp = @fsockopen($this->_params['socket'], 0, $error_number, $error_string, 30);
            $error_return = $error_string;
            if ($fp) break;
            unlink($this->_params['socket']);

        }
        //Failure?
        if (!empty($error_return)) {
            throw new Horde_Imsp_Exception('Connection to IMSP host failed.');
        }
        //Success
        // @TODO:
        $this->_imsp->_stream = $fp;

        return true;
    }

    /**
     * Force a logout command to the imsp stream.
     *
     */
    public function logout()
    {
        $this->_imsp->logout();
    }

    /**
     * Returns the driver type.
     *
     * @return string  The type of this IMSP_Auth driver.
     */
    public function getDriverType()
    {
        return 'imtest';
    }

}
