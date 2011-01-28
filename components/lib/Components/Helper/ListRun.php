<?php
/**
 * Components_Helper_ListRun:: provides a utility that produces a dependency
 * list and records what has already been listed.
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
 * Components_Helper_ListRun:: provides a utility that produces a dependency
 * list and records what has already been listed.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
class Components_Helper_ListRun
{
    /**
     * The output handler.
     *
     * @param Component_Output
     */
    private $_output;

    /**
     * The tree for this run.
     *
     * @var Components_Helper_Tree
     */
    private $_tree;

    /**
     * The list of dependencies already displayed.
     *
     * @var array
     */
    private $_displayed_dependencies = array();

    /**
     * The list of elements in case we are producing condensed output.
     *
     * @var array
     */
    private $_quiet_list = array();

    /**
     * Constructor.
     *
     * @param Component_Output       $output The output handler.
     * @param Components_Helper_Tree $tree   The tree for this run.
     */
    public function __construct(
        Components_Output $output,
        Components_Helper_Tree $tree
    ) {
        $this->_output = $output;
        $this->_tree = $tree;
        if ($this->_output->isVerbose()) {
            $output->bold('The list contains optional dependencies!');
        } else {
            $output->bold('The list only contains required dependencies!');
        }
        $output->blue('Dependencies on PEAR itself are not displayed.');
        $output->bold('');
    }

    /**
     * List the dependency tree for this package.
     *
     * @param Components_Pear_Package $package The package that should be installed.
     * @param int                     $level  The current list level.
     * @param string                  $parent The name of the parent element.
     *
     * @return NULL
     */
    public function listTree(
        Components_Pear_Package $package,
        $level = 0,
        $parent = '',
        $required = true
    ) {
        if ($this->_listHordePackage($package, $level, $parent, $required)) {
            $this->_listExternalPackages($package->getDependencyHelper(), $level + 1);
            $this->_listHordeDependencies($package->getDependencyHelper(), $level + 1);
        }
    }

    /**
     * List a Horde component as dependency.
     *
     * @param Components_Pear_Package $package The package that should be listed.
     * @param int                     $level   The current list level.
     * @param string                  $parent  Name of the parent element.
     *
     * @return boolean True in case listing should continue.
     */
    private function _listHordePackage(
        Components_Pear_Package $package,
        $level,
        $parent
    ) {
        $key = $package->getName() . '/pear.horde.org';
        if (!$this->_output->isQuiet()) {
            if (in_array($key, array_keys($this->_displayed_dependencies))) {
                if (empty($this->_displayed_dependencies[$key])) {
                    $add = ' (RECURSION) ***STOP***';
                } else {
                    $add = ' (ALREADY LISTED WITH '
                        . $this->_displayed_dependencies[$key] . ') ***STOP***';
                }
            } else {
                $add = '';
            }
            $this->_output->green(
                Horde_String::pad(
                    $this->_listLevel($level) . '|_'
                    . $package->getName(), 40
                )
                . Horde_String::pad(' [pear.horde.org]', 20)
                . $add
            );
            if (in_array($key, array_keys($this->_displayed_dependencies))) {
                return false;
            } else {
                $this->_displayed_dependencies[$key] = $parent;
                return true;
            }
        } else {
            if (!isset($this->_quiet_list[$key])) {
                $this->_quiet_list[$key] = array(
                    'channel' => 'pear.horde.org',
                    'name' => $package->getName(),
                    'color' => 'green'
                );
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * List the horde dependencies.
     *
     * @param Components_Pear_Dependencies $dependencies The package dependencies.
     * @param int                          $level        The current list level.
     *
     * @return NULL
     */
    private function _listHordeDependencies(
        Components_Pear_Dependencies $dependencies,
        $level
    ) {
        if ($this->_output->isVerbose()) {
            $list = $dependencies->listAllHordeDependencies();
        } else {
            $list = $dependencies->listRequiredHordeDependencies();
        }
        foreach ($this->_tree->getChildren($list) as $child) {
            $this->listTree($child, $level, $child->getName());
        }
    }

    /**
     * List an external package as dependency.
     *
     * @param Components_Pear_Dependencies $dependencies The package dependencies.
     * @param int                          $level        The current list level.
     *
     * @return NULL
     */
    private function _listExternalPackages(
        Components_Pear_Dependencies $dependencies,
        $level
    ) {
        if ($this->_output->isVerbose()) {
            $list = $dependencies->listAllExternalDependencies();
        } else {
            $list = $dependencies->listRequiredExternalDependencies();
        }
        foreach ($list as $dependency) {
            if ($dependency->isPearBase()) {
                // Showing PEAR does not make much sense.
                continue;
            }

            if (!$this->_output->isQuiet()) {
                $this->_output->yellow(
                    Horde_String::pad(
                        $this->_listLevel($level) . '|_' 
                        . $dependency->name(), 40
                    )
                    . Horde_String::pad(' [' . $dependency->channelOrType() . ']', 20)
                    . ' (EXTERNAL) ***STOP***'
                );
            } else {
                $this->_quiet_list[$dependency->key()] = array(
                    'channel' => $dependency->channelOrType(),
                    'name' => $dependency->name(),
                    'color' => 'yellow'
                );
            }
        }
    }

    /**
     * Wrap up the listing. This will produce a condensed list of packages in
     * case quiet Output was requested.
     *
     * @return NULL
     */
    public function finish()
    {
        if (empty($this->_quiet_list)) {
            return;
        }
        $channels = array();
        $names = array();
        $colors = array();
        foreach ($this->_quiet_list as $key => $element) {
            $channels[] = $element['channel'];
            $names[] = $element['name'];
            $colors[] = $element['color'];
        }
        array_multisort($channels, $names, $colors);
        foreach ($names as $key => $name) {
            $this->_output->$colors[$key](
                Horde_String::pad($name, 20) .
                Horde_String::pad('[' . $channels[$key] . ']', 20)
            );
        }
    }

    /**
     * Produces an amount of whitespace depending on the specified level.
     *
     * @param int $level The level of indentation.
     *
     * @return string Whitespace.
     */
    private function _listLevel($level)
    {
        return str_repeat('  ', $level);
    }
}