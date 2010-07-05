<?php
/**
 * Horde_Qc_Module:: represents a single quality control module.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Qc
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Qc
 */

/**
 * Horde_Qc_Module:: represents a single quality control module.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Qc
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Qc
 */
interface Horde_Qc_Module
{
    public function getOptionGroupTitle();

    public function getOptionGroupDescription();

    public function getOptionGroupOptions();

    public function handle(Horde_Qc_Config $config);

    public function run(Horde_Qc_Config $config);
}