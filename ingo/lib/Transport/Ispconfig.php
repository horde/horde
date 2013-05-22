<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Michael Bunk <mb@computer-leipzig.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package Ingo
 */

/**
 * Ingo_Transport_Ispconfig implements an Ingo transport driver to allow
 * scripts to be installed and set active on an ISPConfig server.
 *
 * Ingo_Transport_
 *
 * @author  Michael Bunk <mb@computer-leipzig.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package Ingo
 */
class Ingo_Transport_Ispconfig extends Ingo_Transport_Base
{
    /**
     * The SOAP connection
     *
     * @var SoapClient
     */
    protected $_soap;

    /**
     * The SOAP session id
     *
     * @var string
     */
    protected $_soap_session;

    /**
     * The current vacation details.
     *
     * @var array
     */
    protected $_details = null;


    /**
     * Sets a script running on the backend.
     *
     * @param array $script  The filter script information. Passed elements:
     *                       - 'name': (string) the script name.
     *                       - 'recipes': (array) the filter recipe objects.
     *                       - 'script': (string) the filter script.
     *
     * @throws Ingo_Exception
     */
    public function setScriptActive($script)
    {
        $vacation = null;
        foreach ($script['recipes'] as $recipe) {
            if ($recipe['rule'] == Ingo::RULE_VACATION) {
                $vacation = $recipe['object']->vacation;
                break;
            } else {
                throw new Ingo_Exception('The ISPConfig transport driver only supports vacation rules.');
            }
        }

        if (!$vacation) {
            return;
        }

        // Fill mailuser_id and client_id.
        $this->_getUserDetails($this->_params['password']);

        try {
            $user = $this->_soap->mail_user_get(
                $this->_soap_session, $this->_details['mailuser_id']);

            $user['autoresponder'] = $recipe['object']->disable ? 'n' : 'y';
            // UNIX timestamp.
            $start = $vacation->getVacationStart();
            $end = $vacation->getVacationEnd();
            if (empty($start)) {
                $start = time();
            }
            if (empty($end)) {
                $end = time();
            }
            $user['autoresponder_start_date'] = array(
                'year' => date('Y', $start),
                'month' => date('m', $start),
                'day' => date('d', $start),
                'hour' => date('H', $start),
                'minute' => date('i', $start));
            $user['autoresponder_end_date'] = array(
                'year' => date('Y', $end),
                'month' => date('m', $end),
                'day' => date('d', $end),
                'hour' => 23,
                'minute' => 59);
            // $vacation->getVacationSubject() not supported by ISPConfig
            $user['autoresponder_text'] = $vacation->getVacationReason();
            // otherwise ISPConfig calculates the hash of this hash... braindead
            unset($user['password']);

            $affected_rows = $this->_soap->mail_user_update(
                $this->_soap_session, $this->_details['client_id'],
                $this->_details['mailuser_id'], $user);
        } catch (SoapFault $e) {
            throw new Ingo_Exception($e);
        }
    }

    /**
     * Retrieves the current vacation details for the user.
     *
     * @param string $password  The password for user.
     *
     * @return array  Vacation details
     * @throws Ingo_Exception
     */
    protected function _getUserDetails($password)
    {
        if (!is_null($this->_details)) {
            return $this->_details;
        }

        $this->_checkConfig();
        $this->_connect();

        try {
            $users = $this->_soap->mail_user_get(
                $this->_soap_session,
                array('login' => $this->_params['username']));
        } catch (SoapFault $e) {
            throw new Ingo_Exception($e);
        }
        if (count($users) != 1) {
            throw new Ingo_Exception(
                sprintf(_("%d users with login %s found, one expected."),
                        count($users),
                        $this->_params['username']));
        }

        $user = $users[0];
        $this->_details['vacation'] =
            ($user['autoresponder'] === 'y') ? 'Y' : 'N';
        $this->_details['message'] = $user['autoresponder_text'];
        $this->_details['mailuser_id'] = $user['mailuser_id'];
        // 0 == admin
        $this->_details['client_id'] = 0;
        $this->_details['autoresponder_start_date'] =
            $user['autoresponder_start_date'];
        $this->_details['autoresponder_end_date'] =
            $user['autoresponder_end_date'];
        return $this->_details;
    }

    /**
     * Checks if the realm has a specific configuration. If not, tries to fall
     * back on the default configuration. If still not a valid configuration
     * then returns an exception.
     *
     * @throws Ingo_Exception
     */
    protected function _checkConfig()
    {
        if (empty($this->_params['soap_uri']) ||
            empty($this->_params['soap_user']) ) {
            throw new Ingo_Exception('The Ingo Ispconfig transport is not properly configured, edit your ingo/config/backends.local.php.');
        }
    }

    /**
     * Connects to the SOAP server.
     *
     * @throws Ingo_Exception
     */
    protected function _connect()
    {
        if ($this->_soap) {
            return;
        }

        $soap_uri = $this->_params['soap_uri'];
        $client = new SoapClient(null, array(
            'location' => $soap_uri . 'index.php',
            'uri'      => $soap_uri));

        try {
            if (!$session_id = $client->login(
                $this->_params['soap_user'],
                $this->_params['soap_pass'])) {
                throw new Ingo_Exception(
                    sprintf(_("Login to %s failed."), $soap_uri));
            }
        } catch (SoapFault $e) {
            throw new Ingo_Exception($e);
        }

        $this->_soap = &$client;
        $this->_soap_session = $session_id;
    }
}
