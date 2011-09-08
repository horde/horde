<?php
/**
 * @category Horde
 * @package  Controller
 * @author   Bob McKee <bob@bluestatedigital.com>
 * @author   James Pepin <james@bluestatedigital.com>
 * @license  http://www.horde.org/licenses/bsd BSD
 */
interface Horde_Controller_RequestConfiguration
{
    public function getControllerName();

    public function setControllerName($controllerName);

    public function getSettingsExporterName();

    public function setSettingsExporterName($settingsName);
}
