<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * This class identifies the javascript necessary to allow scriptaculous'
 * Sortable widge to work.
 *
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.12.0
 */
class Horde_Core_Script_Package_Sortable extends Horde_Script_Package
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_files[] = new Horde_Script_File_JsDir('scriptaculous/effects.js', 'horde');
        $this->_files[] = new Horde_Script_File_JsDir('scriptaculous/dragdrop.js', 'horde');
    }

}
