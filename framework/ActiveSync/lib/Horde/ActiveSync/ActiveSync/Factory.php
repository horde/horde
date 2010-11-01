<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

class Horde_ActiveSync_Factory
{

    static public function getActiveSync(Horde_Injector $injector)
    {
        // @TODO: For now, just drop all this in here, need to create factories
        // for lots of these dependencies. Also, some of these (like registry
        // and request might already be bound to the injector).
        $registry = new Horde_Registry();
        $connector = new Horde_ActiveSync_Driver_Horde_Connector_Registry(array('registry' => $registry));
        $driver = new Horde_ActiveSync_Driver_Horde(array('connectory' => $connector));
        $state = new Horde_ActiveSync_StateMachine_File(array('stateDir' => '/tmp'));
        $encoder = new Horde_ActiveSync_Wbxml_Encoder(fopen("php://output", "w+"),
                                                      Horde_ActiveSync::$zpushdtd);
        $decoder = new Horde_ActiveSync_Wbxml_Decoder(fopen('php://input', 'r'),
                                                      Horde_ActiveSync::$zpushdtd);

        $request = $injector->getInstance('Horde_Controller_Request');
        $server = new Horde_ActiveSync($driver, $state, $decoder, $encoder, $request);
    }
    
}
