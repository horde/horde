<?php
class Horde_Core_Binder_Mail implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $driver = $GLOBALS['conf']['mailer']['type'];
        $params = $GLOBALS['conf']['mailer']['params'];

        if (($driver == 'smtp') &&
            $params['auth'] &&
            empty($params['username'])) {
            $params['username'] = Horde_Auth::getAuth();
            $params['password'] = Horde_Auth::getCredential('password');
        }

        $result = Mail::factory($driver, $params);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception($result);
        }

        return $result;
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
