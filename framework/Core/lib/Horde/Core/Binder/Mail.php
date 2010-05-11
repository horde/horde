<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Mail implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $driver = isset($GLOBALS['conf']['mailer']['type'])
            ? $GLOBALS['conf']['mailer']['type']
            : 'null';
        $params = isset($GLOBALS['conf']['mailer']['params'])
            ? $GLOBALS['conf']['mailer']['params']
            : array();

        if (($driver == 'smtp') &&
            $params['auth'] &&
            empty($params['username'])) {
            $params['username'] = Horde_Auth::getAuth();
            $params['password'] = Horde_Auth::getCredential('password');
        }

        try {
            return Horde_Mime_Mail::getMailOb($driver, $params);
        } catch (Horde_Mime_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
