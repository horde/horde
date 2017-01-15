<?php
/**
 * Imple to provide weather/location autocompletion.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 */
class Horde_Core_Ajax_Imple_WeatherLocationAutoCompleter_Metar
  extends Horde_Core_Ajax_Imple_WeatherLocationAutoCompleter_Base
{
    /**
     */
    protected function _getAutoCompleter()
    {
        return parent::_getAutoCompleterForBlock('Horde_Block_Metar');
    }
    /**
     */
    protected function _handleAutoCompleter($input)
    {
        global $injector;

        $weather =  new Horde_Service_Weather_Metar(array(
            'cache' => $injector->getInstance('Horde_Cache'),
            'cache_lifetime' => $conf['weather']['params']['lifetime'],
            'http_client' => $injector->createInstance('Horde_Core_Factory_HttpClient')->create(),
            'db' => $injector->getInstance('Horde_Db_Adapter'))
        );

        return $weather->autocompleteLocation($input);
    }

}
