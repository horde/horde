<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * This class represents a javascript script file located in an application's
 * js/ directory.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
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

        case 'uncompressed':
            if (($pos = strripos($this->file, '.min.js')) !== false) {
                $cname = get_class();
                return new $cname(
                    substr($this->file, 0, $pos) . '.js',
                    $this->app
                );
            }
            break;

        case 'url':
        case 'url_full':
            return $this->_url($GLOBALS['registry']->get('jsuri', $this->_app) . '/' . $this->_file, ($name == 'url_full'));
        }

        return parent::__get($name);
    }

}
