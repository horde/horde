<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Controller_SettingsFinder
{
    public function getSettingsExporterName($controllerName)
    {
        $settingsName = $this->_mapName($controllerName);
        $current = $controllerName;
        while (class_exists($current)) {
            $settingsName = $this->_mapName($current);
            if (class_exists($settingsName)) {
                return $settingsName;
            }

            $current = $this->_getParentName($current);
        }

        return 'Horde_Controller_SettingsExporter_Default';
    }

    private function _mapName($controllerName)
    {
        return str_replace('_Controller', '_SettingsExporter', $controllerName);
    }

    private function _getParentName($controllerName)
    {
        $klass = new ReflectionClass($controllerName);
        $parent = $klass->getParentClass();
        return $parent->name;
    }
}
