<?php
/**
 * This class represents a javascript script file located in an application's
 * js/ directory.
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
class Horde_Script_File_JsDir extends Horde_Script_File
{
    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'path':
            return $GLOBALS['registry']->get('jsfs', $this->_app) . '/';

        case 'url':
        case 'url_full':
            return Horde::url($GLOBALS['registry']->get('jsuri', $this->_app) . '/' . $this->_file, ($name == 'url_full'), -1);
        }

        return parent::__get($name);
    }

}
