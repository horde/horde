<?php
/**
 * Components_Helper_Template_Printf:: converts a template into a target file using vsprintf().
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
 * Components_Helper_Template_Printf:: converts a template into a target file using vsprintf().
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
class Components_Helper_Template_Printf
extends Components_Helper_Template
{
    /**
     * Rewrite the template from the source to the target location.
     *
     * @param array  $parameters The template parameters.
     *
     * @return NULL
     */
    public function write(array $parameters = array())
    {
        $source = file_get_contents($this->_source);
        file_put_contents(
            $this->_target, vsprintf($source, $parameters)
        );
    }
}