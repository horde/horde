<?php
/**
 * The Horde_Kolab_Cli_Module:: interface describes the module structure for
 * Kolab_Cli.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Cli_Modular
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Cli_Modular
 */

/**
 * The Horde_Kolab_Cli_Module:: interface describes the module structure for
 * Kolab_Cli.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Cli_Modular
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Cli_Modular
 */
interface Horde_Kolab_Cli_Module
extends Horde_Cli_Modular_Module
{
    /**
     * Handle the options and arguments.
     *
     * @param mixed &$options   An array of options.
     * @param mixed &$arguments An array of arguments.
     *
     * @return NULL
     */
    public function handleArguments(&$options, &$arguments);
}