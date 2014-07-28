<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * This class identifies the javascript necessary to output the autocomplete
 * javascript code to the browser.
 *
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.13.0
 */
class Horde_Core_Script_Package_Autocomplete extends Horde_Script_Package
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        global $page_output;

        $page_output->addScriptPackage('Horde_Core_Script_Package_Keynavlist');

        $this->_files[] = new Horde_Script_File_JsDir('autocomplete.js', 'horde');
        $this->_files[] = new Horde_Script_File_JsDir('liquidmetal.js', 'horde');
    }

}
