<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Mail extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        $transport = isset($GLOBALS['conf']['mailer']['type'])
            ? $GLOBALS['conf']['mailer']['type']
            : 'null';
        $params = isset($GLOBALS['conf']['mailer']['params'])
            ? $GLOBALS['conf']['mailer']['params']
            : array();

        if ((strcasecmp($transport, 'smtp') == 0) &&
            $params['auth'] &&
            empty($params['username'])) {
            $params['username'] = $GLOBALS['registry']->getAuth();
            $params['password'] = $GLOBALS['registry']->getAuthCredential('password');
        }

        $class = $this->_getDriverName($transport, 'Horde_Mail_Transport');
        $ob = new $class($params);

        if (!empty($params['sendmail_eol']) &&
            (strcasecmp($transport, 'sendmail') == 0)) {
            $ob->sep = $params['sendmail_eol'];
        }

        return new $class($params);
    }

}
