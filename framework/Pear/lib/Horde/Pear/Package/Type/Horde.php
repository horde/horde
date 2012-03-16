<?php
/**
 * Horde_Pear_Package_Type_Horde:: deals with packages provided by Horde.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * Horde_Pear_Package_Type_Horde:: deals with packages provided by Horde.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Package_Type_Horde
implements Horde_Pear_Package_Type
{
    /**
     * The root path for the package.
     *
     * @var string
     */
    private $_root;

    /**
     * The root path for the repository.
     *
     * @var string
     */
    private $_repository_root;

    /**
     * Constructor.
     *
     * @param string $root The root path for the package.
     */
    public function __construct($root, $repository_root = null)
    {
        $this->_root = $root;
        $this->_repository_root = $repository_root;
    }

    /**
     * Return the path to the root of the package.
     *
     * @return string The path to the root.
     */
    public function getRootPath()
    {
        return $this->_root;
    }

    /**
     * Return the path to the package.xml file for the package.
     *
     * @return string The path to the package.xml file.
     */
    public function getPackageXmlPath()
    {
        return $this->_root . '/package.xml';
    }

    /**
     * Return the include handler for this package.
     *
     * @return Horde_Pear_Package_Contents_Include The include handler.
     */
    public function getInclude()
    {
        return new Horde_Pear_Package_Contents_Include_All();
    }

    /**
     * Return the ignore handler for this package.
     *
     * @return Horde_Pear_Package_Contents_Ignore The ignore handler.
     */
    public function getIgnore()
    {
        return new Horde_Pear_Package_Contents_Ignore_Composite(
            array(
                new Horde_Pear_Package_Contents_Ignore_Dot(),
                new Horde_Pear_Package_Contents_Ignore_Patterns(
                    array(
                        '/package.xml',
                        '*~',
                        'conf.php',
                        'CVS/*',
                        'bin/.htaccess',
                    ),
                    $this->getRootPath()
                ),
                new Horde_Pear_Package_Contents_Ignore_Git(
                    $this->getGitIgnore(),
                    $this->getRepositoryRoot()
                ),
            )
        );
    }

    /**
     * Return the role handler for this package.
     *
     * @return Horde_Pear_Package_Contents_Role The role handler.
     */
    public function getRole()
    {
        $class = 'Horde_Pear_Package_Contents_Role_Horde' . $this->getType();
        return new $class();
    }

    /**
     * Return the install-as handler for this package.
     *
     * @return Horde_Pear_Package_Contents_InstallAs The install-as handler.
     */
    public function getInstallAs()
    {
        switch ($this->getName()) {
        case 'horde':
        case 'groupware':
        case 'webmail':
        case 'kolab_webmail':
            $class = 'Horde_Pear_Package_Contents_InstallAs_Horde';
            break;
        case 'Horde_Role':
            $class = 'Horde_Pear_Package_Contents_InstallAs_HordeRole';
            break;
        case 'components':
            $class = 'Horde_Pear_Package_Contents_InstallAs_HordeComponent';
            break;
        default:
            $class = 'Horde_Pear_Package_Contents_InstallAs_Horde' . $this->getType();
            break;
        }
        return new $class($this);
    }

    /**
     * Get the package type.
     *
     * @return string The type: "Application" or "Component".
     */
    public function getType()
    {
        $location = substr($this->getRootPath(), strlen($this->getRepositoryRoot()));
        if (substr($location, 0, 10) == '/framework') {
            return 'Component';
        } else {
            return 'Application';
        }
    }

    /**
     * Get the package name.
     *
     * @return string The package name.
     */
    public function getName()
    {
        if ($this->getType() == 'Application') {
            return basename($this->getRootPath());
        } else {
            return 'Horde_' . basename($this->getRootPath());
        }
    }

    /**
     * Return the contents of the Horde .gitignore file.
     *
     * @return string The .gitignore patterns.
     */
    public function getGitIgnore()
    {
        return file_get_contents($this->getRepositoryRoot() . '/.gitignore');
    }

    /**
     * Return the path to the root of the Horde repository..
     *
     * @return string The repository path.
     */
    public function getRepositoryRoot()
    {
        if ($this->_repository_root === null) {
            $i = 0;
            $current = $this->getRootPath();
            while ($current != '/' || $i < 10) {
                if (is_dir($current)) {
                    $elements = scandir($current);
                    if (in_array('framework', $elements)
                        && in_array('horde', $elements)
                        && in_array('.gitignore', $elements)) {
                        $this->_repository_root = $current;
                        break;
                    }
                }
                $current = dirname($current);
                $i++;
            }
            if ($this->_repository_root === null) {
                throw new Horde_Pear_Exception(
                    sprintf(
                        'Unable to determine Horde root from path %s!',
                        $this->getRootPath()
                    )
                );
            }
        }
        return $this->_repository_root;
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
        file_put_contents(
            $this->getPackageXmlPath(),
            '<?xml version="1.0" encoding="UTF-8"?>
<package packagerversion="1.9.2" version="2.0" xmlns="http://pear.php.net/dtd/package-2.0" xmlns:tasks="http://pear.php.net/dtd/tasks-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://pear.php.net/dtd/tasks-1.0 http://pear.php.net/dtd/tasks-1.0.xsd http://pear.php.net/dtd/package-2.0 http://pear.php.net/dtd/package-2.0.xsd">
 <name>' . $this->getName() . '</name>
 <channel>pear.horde.org</channel>
 <summary>TODO</summary>
 <description>TODO</description>
 <lead>
  <name>Chuck Hagenbuch</name>
  <user>chuck</user>
  <email>chuck@horde.org</email>
  <active>yes</active>
 </lead>
 <lead>
  <name>Jan Schneider</name>
  <user>jan</user>
  <email>jan@horde.org</email>
  <active>yes</active>
 </lead>
 <date>' . date('Y-m-d') . '</date>
 <time>' . date('H:i:s') . '</time>
 <version>
  <release>1.0.0alpha1</release>
  <api>1.0.0alpha1</api>
 </version>
 <stability>
  <release>alpha</release>
  <api>alpha</api>
 </stability>
 <license uri="TODO">TODO</license>
 <notes>
* Initial release.
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
    <release>1.0.0alpha1</release>
    <api>1.0.0alpha1</api>
   </version>
   <stability>
    <release>alpha</release>
    <api>alpha</api>
   </stability>
   <date>' . date('Y-m-d') . '</date>
   <license uri="TODO">TODO</license>
   <notes>
* Initial release.
   </notes>
  </release>
 </changelog>
</package>'
        );
    }
}