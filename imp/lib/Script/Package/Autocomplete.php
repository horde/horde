<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This class identifies the javascript necessary to output the autocompleter
 * javascript to the browser.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Script_Package_Autocomplete
extends Horde_Core_Script_Package_Keynavlist
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->_files[] = new Horde_Script_File_JsDir('autocomplete.js', 'imp');
        $this->_files[] = new Horde_Script_File_JsDir('external/latinize.js', 'imp');
    }

}
