<?php
/**
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Bob McKee <bob@bluestatedigital.com>
 * @author   James Pepin <james@bluestatedigital.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Controller
 */

/**
 *
 *
 * @author    Bob McKee <bob@bluestatedigital.com>
 * @author    James Pepin <james@bluestatedigital.com>
 * @category  Horde
 * @copyright 2008-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Controller
 */
interface Horde_Controller_RequestConfiguration
{
    public function getControllerName();

    public function setControllerName($controllerName);

    public function getSettingsExporterName();

    public function setSettingsExporterName($settingsName);
}
