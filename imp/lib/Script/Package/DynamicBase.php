<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This class identifies the javascript necessary to output the base
 * javascript needed for dynamic views.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Script_Package_DynamicBase extends Horde_Script_Package
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        global $injector;

        $ob = new Horde_Script_File_JsDir('core.js', 'imp');

        try {
            $hooks = $injector->getInstance('Horde_Core_Hooks');
            $dprefs = $hooks->callHook('dynamic_prefs', 'imp');
            foreach ($dprefs as $key => $val) {
                $ob->jsvars['ImpCore.prefs.' . $key] = $val;
            }
        } catch (Horde_Exception $e) {}

        $this->_files[] = $ob;

        $this->_files[] = new Horde_Script_File_JsDir('viewport_utils.js', 'imp');
        $this->_files[] = new Horde_Script_File_JsDir('contextsensitive.js', 'horde');
        $this->_files[] = new Horde_Script_File_JsDir('imple.js', 'horde');
    }

}
