<?php
/**
 * Components_Config_Bootstrap:: class provides simple options for the bootstrap
 * process.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Config_Bootstrap:: class provides simple options for the bootstrap
 * process.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Config_Bootstrap
extends Components_Config_Base
{
    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        $this->_options = array(
            'include' => 'ALL',
            'exclude' => 'channel:pecl.php.net,PEAR_CompatInfo',
            'force' => true,
            'symlink' => true,
        );
        $this->_arguments = array();
    }
}
