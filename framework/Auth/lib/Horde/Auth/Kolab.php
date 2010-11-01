<?php
/**
 * The Horde_Auth_Kolab implementation of the Horde authentication system.
 * Derives from the Horde_Auth_Imap authentication object, and provides
 * parameters to it based on the global Kolab configuration.
 *
 * Copyright 2004-2007 Stuart Binge <s.binge@codefusion.co.za>
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Stuart Binge <s.binge@codefusion.co.za>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @category Horde
 * @package  Auth
 */
class Horde_Auth_Kolab extends Horde_Auth_Base
{
    /**
     * An array of capabilities, so that the driver can report which
     * operations it supports and which it doesn't.
     *
     * @var array
     */
    protected $_capabilities = array(
        'authenticate'  => true
    );

    /**
     * Constructor.
     *
     * @params array $params  Parameters:
     * <pre>
     * 'kolab' - (Horde_Kolab_Session) [REQUIRED] TODO
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['kolab'])) {
            throw new InvalidArgumentException('Missing kolab parameter.');
        }

        parent::__construct($params);
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * For Kolab this requires to identify the IMAP server the user should
     * be authenticated against before the credentials can be checked using
     * this server. The Kolab_Server module handles identification of the
     * correct IMAP server.
     *
     * @param string $userId      The userId to check.
     * @param array $credentials  An array of login credentials. For Kolab,
     *                            this must contain a "password" entry.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        try {
            $this->_params['kolab']->connect($userId, $credentials);
        } catch (Horde_Kolab_Session_Exception_Badlogin $e) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        } catch (Horde_Kolab_Session_Exception $e) {
            if ($this->_logger) {
                $this->_logger->log($e, 'ERR');
            }
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

        $this->_credentials['userId'] = $this->_params['kolab']->getMail();

        return true;
    }

}
