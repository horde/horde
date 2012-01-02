<?php
/**
 * Components_Helper_Dependencies:: provides a utility that produces a dependency
 * list and records what has already been listed.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Helper_Dependencies:: provides a utility that produces a dependency
 * list and records what has already been listed.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Helper_Dependencies
{
    /**
     * The output handler.
     *
     * @param Component_Output
     */
    private $_output;

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
    private $_short_list = array();

    /**
     * Constructor.
     *
     * @param Component_Output       $output The output handler.
     */
    public function __construct(Components_Output $output)
    {
        $this->_output = $output;
    }

    /**
     * List the dependency tree for this package.
     *
     * @param Components_Component $component The component for which the
     *                                        dependency tree should be shown.
     * @param array                $options   Options for generating the list.
     *
     * @return NULL
     */
    public function listTree(
        Components_Component $component,
        $options
    ) {
        if (!empty($options['alldeps'])) {
            $this->_output->bold('The list contains optional dependencies!');
        } else {
            $this->_output->bold('The list only contains required dependencies!');
        }

        $this->_output->blue('Dependencies on PEAR itself are not displayed.');
        $this->_output->bold('');

        $this->_listTree($component, $options);
        $this->_finish();
    }

    /**
     * List the dependency tree for this package.
     *
     * @param Components_Component $component The component for which the
     *                                        dependency tree should be shown.
     * @param array                $options   Options for generating the list.
     * @param int                  $level     The current list level.
     * @param string               $parent    The name of the parent element.
     *
     * @return NULL
     */
    private function _listTree(
        Components_Component $component,
        $options,
        $level = 0,
        $parent = ''
    ) {
        if ($this->_listComponent($component, $level, $parent, $options)) {
            foreach ($component->getDependencyList() as $dependency) {
                if (!$dependency->isPhp() && !$dependency->isPearBase()) {
                    if (!empty($options['alldeps']) || $dependency->isRequired()) {
                        if ($dependency->isPackage()) {
                            $dep = $dependency->getComponent($options);
                        } else {
                            $dep = false;
                        }
                        if ($dep === false) {
                            $this->_listExternal(
                                $dependency, $level + 1, $options
                            );
                        } else {
                            $this->_listTree(
                                $dep, $options, $level + 1, $component->getName()
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * List a Horde component as dependency.
     *
     * @param Components_Component $component The component for which the
     *                                        dependency tree should be shown     * @param int                  $level     The current list level.
     * @param string               $parent    Name of the parent element.
     * @param array                $options   Options for generating the list.
     *
     * @return boolean True in case listing should continue.
     */
    private function _listComponent(
        Components_Component $component,
        $level,
        $parent,
        $options
    ) {
        $key = $component->getName() . '/' . $component->getChannel();
        if (in_array($key, array_keys($this->_displayed_dependencies))) {
            if (empty($this->_displayed_dependencies[$key])) {
                $add = '(RECURSION) ***STOP***';
            } else {
                $add = '(ALREADY LISTED WITH '
                    . $this->_displayed_dependencies[$key] . ') ***STOP***';
            }
        } else {
            $add = '';
        }
        $this->_element(
            ($component->getChannel() == 'pear.horde.org') ? 'green' : 'yellow',
            $level,
            $key,
            $component->getName() . '-' . $component->getVersion(),
            $component->getChannel(),
            $add,
            $options
        );
        if (in_array($key, array_keys($this->_displayed_dependencies))) {
            return false;
        } else {
            $this->_displayed_dependencies[$key] = $parent;
            return true;
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
    private function _listExternal(
        Components_Component_Dependency $dependency,
        $level,
        $options
    ) {
        $this->_element(
            'yellow',
            $level,
            $dependency->key(),
            $dependency->getName(),
            $dependency->channelOrType(),
            '(NOT RESOLVED) ***STOP***',
            $options
        );
    }

    /**
     * List a single element.
     *
     * @return NULL
     */
    private function _element(
        $color,
        $level,
        $key,
        $name,
        $channel,
        $info,
        $options
    )
    {
        if (empty($options['short'])) {
            $this->_output->$color(
                Horde_String::pad(
                    $this->_listLevel($level) . '|_' . $name, 45
                )
                . Horde_String::pad(' [' . $channel . ']', 20) . ' ' . $info
            );
        } else {
            $this->_short_list[$key] = array(
                'channel' => $channel,
                'name' => $name,
                'color' => $color
            );
        }
    }

    /**
     * Wrap up the listing. This will produce a condensed list of packages in
     * case quiet Output was requested.
     *
     * @return NULL
     */
    private function _finish()
    {
        if (empty($this->_short_list)) {
            return;
        }
        $channels = array();
        $names = array();
        $colors = array();
        foreach ($this->_short_list as $key => $element) {
            $channels[] = $element['channel'];
            $names[] = $element['name'];
            $colors[] = $element['color'];
        }
        array_multisort($channels, $names, $colors);
        foreach ($names as $key => $name) {
            $this->_output->$colors[$key](
                Horde_String::pad($name, 28) .
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