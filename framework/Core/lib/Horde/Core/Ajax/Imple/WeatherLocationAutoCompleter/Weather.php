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
class Horde_Core_Ajax_Imple_WeatherLocationAutoCompleter_Weather
  extends Horde_Core_Ajax_Imple_WeatherLocationAutoCompleter_Base
{
    /**
     */
    protected function _getAutoCompleter()
    {
        return parent::_getAutoCompleterForBlock('Horde_Block_Weather');
    }

    /**
     */
    protected function _handleAutoCompleter($input)
    {
        return $GLOBALS['injector']->getInstance('Horde_Weather')->autocompleteLocation($input);
    }

}
