<?php
/**
 * This class represents a framework-level javascript script fileW.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Script_File_JsFramework extends Horde_Script_File_JsDir
{
    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'priority':
            // Framework files are always the highest priority.
            return 0;
        }

        return parent::__get($name);
    }

}
