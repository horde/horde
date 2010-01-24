<?php
/**
 * @category Horde
 * @package  Horde_Controller
 * @author   Bob McKee <bob@bluestatedigital.com>
 * @author   James Pepin <james@bluestatedigital.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */
interface Horde_Controller_RequestConfiguration
{
    public function getControllerName();

    public function setControllerName($controllerName);

    public function getSettingsExporterName();

    public function setSettingsExporterName($settingsName);
}
