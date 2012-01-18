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

        $class = 'Horde_Mail_Transport_' . ucfirst($transport);
        if (class_exists($class)) {
            $ob = new $class($params);

            if (!empty($params['sendmail_eol']) &&
                (strcasecmp($transport, 'sendmail') == 0)) {
                $ob->sep = $params['sendmail_eol'];
            }

            return new $class($params);
        }

        throw new Horde_Exception('Unable to find class for transport ' . $transport);
    }

}
