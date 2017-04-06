<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Pear
 */

/**
 * Horde_Pear_Package_Type_HordeTheme deals with theme packages provided by
 * Horde.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pear
 */
class Horde_Pear_Package_Type_HordeTheme
extends Horde_Pear_Package_Type_Horde
{
    /**
     * Return the path to the root of the package.
     *
     * @return string The path to the root.
     */
    public function getRootPath()
    {
        return $this->getRepositoryRoot();
    }

    /**
     * Return the include handler for this package.
     *
     * @return Horde_Pear_Package_Contents_Include The include handler.
     */
    public function getInclude()
    {
        return new Horde_Pear_Package_Contents_Include_Patterns(
            array('themes/' . basename($this->_root) . '/*'),
            $this->getRepositoryRoot()
        );
    }

    /**
     * Return the role handler for this package.
     *
     * @return Horde_Pear_Package_Contents_Role The role handler.
     */
    public function getRole()
    {
        return new Horde_Pear_Package_Contents_Role_HordeApplication();
    }

    /**
     * Return the install-as handler for this package.
     *
     * @return Horde_Pear_Package_Contents_InstallAs The install-as handler.
     */
    public function getInstallAs()
    {
        return new Horde_Pear_Package_Contents_InstallAs_HordeTheme($this);
    }

    /**
     * Return the path to the root of the Horde repository..
     *
     * @return string The repository path.
     */
    public function writePackageXmlDraft()
    {
        if (file_exists($this->getPackageXmlPath())) {
            throw new Horde_Pear_Exception(
                sprintf(
                    'File %s already exists and will not be overwritten!',
                    $this->getPackageXmlPath()
                )
            );
        }
        $infoFile = $this->_root . '/info.php';
        if (!file_exists($infoFile)) {
            throw new Horde_Pear_Exception(
                sprintf('File %s missing', $infoFile)
            );
        }

        require $infoFile;

        file_put_contents(
            $this->getPackageXmlPath(),
            '<?xml version="1.0" encoding="UTF-8"?>
<package packagerversion="1.9.2" version="2.0" xmlns="http://pear.php.net/dtd/package-2.0" xmlns:tasks="http://pear.php.net/dtd/tasks-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://pear.php.net/dtd/tasks-1.0 http://pear.php.net/dtd/tasks-1.0.xsd http://pear.php.net/dtd/package-2.0 http://pear.php.net/dtd/package-2.0.xsd">
 <name>' . $this->getName() . '</name>
 <channel>pear.horde.org</channel>
 <summary>' . $theme_name . ' Theme</summary>
 <description>' . $theme_name . ' Theme</description>
 <lead>
  <name>Jan Schneider</name>
  <user>jan</user>
  <email>jan@horde.org</email>
  <active>yes</active>
 </lead>
 <lead>
  <name>Michael J Rubinsky</name>
  <user>mrubinsk</user>
  <email>mrubinsk@horde.org</email>
  <active>yes</active>
 </lead>
 <date>' . date('Y-m-d') . '</date>
 <version>
  <release>1.0.0</release>
  <api>1.0.0</api>
 </version>
 <stability>
  <release>stable</release>
  <api>stable</api>
 </stability>
 <license uri="proprietary">Proprietary</license>
 <notes>
* Initial version.
 </notes>
 <dependencies>
  <required>
   <php>
    <min>5.3.0</min>
   </php>
   <pearinstaller>
    <min>1.7.0</min>
   </pearinstaller>
  </required>
 </dependencies>
 <changelog>
  <release>
   <version>
    <release>1.0.0</release>
    <api>1.0.0</api>
   </version>
   <stability>
    <release>stable</release>
    <api>stable</api>
   </stability>
   <license uri="proprietary">Proprietary</license>
   <notes>
* Initial version.
   </notes>
  </release>
 </changelog>
</package>
'
        );
    }
}