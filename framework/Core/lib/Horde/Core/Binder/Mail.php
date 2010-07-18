<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Mail implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $transport = isset($GLOBALS['conf']['mailer']['type'])
            ? $GLOBALS['conf']['mailer']['type']
            : 'null';
        $params = isset($GLOBALS['conf']['mailer']['params'])
            ? $GLOBALS['conf']['mailer']['params']
            : array();

        if (($transport == 'smtp') &&
            $params['auth'] &&
            empty($params['username'])) {
            $params['username'] = $GLOBALS['registry']->getAuth();
            $params['password'] = $GLOBALS['registry']->getAuthCredential('password');
        }

        $class = 'Horde_Mail_Transport_' . ucfirst($transport);
        if (class_exists($class)) {
            return new $class($params);
        }
        throw new Horde_Exception('Unable to find class for transport ' . $transport);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
