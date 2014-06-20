<?php
/**
 * @category Horde
 * @package Core
 */
class Horde_Core_Factory_ActiveSyncState extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        global $conf;

        if (empty($conf['activesync']['enabled'])) {
            throw new Horde_Exception('ActiveSync is disabled.');
        }

        $driver = $conf['activesync']['storage'];
        switch (Horde_String::lower($driver)) {
        case 'nosql':
            $nosql = $injector->getInstance('Horde_Core_Factory_Nosql')->create('horde', 'activesync');
            return new Horde_ActiveSync_State_Mongo(array(
                'connection' => $nosql
            ));
        case 'sql':
            return new Horde_ActiveSync_State_Sql(array(
                'db' => $injector->getInstance('Horde_Core_Factory_Db')->create('horde', 'activesync')
            ));
        }
    }

}
