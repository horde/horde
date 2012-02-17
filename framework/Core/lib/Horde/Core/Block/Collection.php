<?php
/**
 * This class provides an API to the blocks (applets) framework.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Block_Collection implements Serializable
{
    /**
     * A hash storing the information about all available blocks from
     * all applications.
     *
     * @var array
     */
    protected $_blocks = array();

    /**
     * Layout configuration preference name.
     *
     * @var string
     */
    protected $_layout;

    /**
     * Constructor.
     *
     * @param array $apps     The applications whose blocks to list.
     * @param string $layout  Layout configuration preference name.
     */
    public function __construct(array $apps, $layout)
    {
        foreach ($apps as $app) {
            $drivers = $GLOBALS['registry']->getAppDrivers($app, 'Block');
            foreach ($drivers as $val) {
                $tmp = new $val($app);
                if ($tmp->enabled) {
                    $this->_blocks[$app][$val]['name'] = $tmp->getName();
                }
            }
        }

        $this->_layout = $layout;
    }

    /**
     * Return the layout configuration for this collection.
     *
     * @return array  The layout configuration.
     */
    public function getLayout()
    {
        $layout = @unserialize($GLOBALS['prefs']->getValue($this->_layout));

        if (empty($layout)) {
            $layout = array();

            if (isset($GLOBALS['conf']['portal']['fixed_blocks'])) {
                foreach ($GLOBALS['conf']['portal']['fixed_blocks'] as $block) {
                    list($app, $type) = explode(':', $block, 2);
                    $layout[] = array(
                        array(
                            'app' => $app,
                            'params' => array(
                                'type2' => $type,
                                'params' => false
                            ),
                            'height' => 1,
                            'width' => 1
                        )
                    );
                }
            }
        }

        return $layout;
    }

    /**
     * Return the layout manager for this collection.
     *
     * @return Horde_Core_Block_Layout_Manager  Layout manager object.
     */
    public function getLayoutManager()
    {
        return new Horde_Core_Block_Layout_Manager($this);
    }

    /**
     * Gets a block object.
     *
     * @param string $app    Application name.
     * @param string $name   The Block's classname.
     * @param array $params  Parameters.  If null, the stored parameters will
     *                       be used.
     *
     * @return Horde_Core_Block  Block object.
     * @throws Horde_Exception
     */
    public function getBlock($app, $name, $params = null)
    {
        global $registry;

        if ($registry->isInactive($app)) {
            throw new Horde_Exception(sprintf('%s is not activated.', $GLOBALS['registry']->get('name', $app)));
        }

        $pushed = $registry->pushApp($app);

        if (!class_exists($name)) {
            if ($pushed) {
                $registry->popApp($app);
            }
            throw new Horde_Exception(sprintf('%s not found.', $name));
        }

        if (is_null($params)) {
            foreach ($this->getLayout() as $row) {
                foreach ($row as $col) {
                    if (is_array($col) &&
                        (strcasecmp($col['params']['type2'], $name) === 0)) {
                        $params = $col['params']['params'];
                        break 2;
                    }
                }
            }
        }

        $ob = new $name($app, $params);

        if ($pushed) {
            $registry->popApp($app);
        }

        if (is_null($ob)) {
            throw new Horde_Exception(sprintf('%s not found.', $name));
        }

        return $ob;
    }

    /**
     * Returns a pretty printed list of all available blocks.
     *
     * @return array  A hash with block IDs as keys and application plus block
     *                block names as values.
     */
    public function getBlocksList()
    {
        $blocks = array();

        /* Get available blocks from all apps. */
        foreach ($this->_blocks as $app => $app_blocks) {
            $app_name = $GLOBALS['registry']->get('name', $app);

            foreach ($app_blocks as $block_id => $block) {
                $blocks[$app . ':' . $block_id] = $app_name . ': ' . $block['name'];
            }
        }

        return $blocks;
    }

    /**
     * Returns a layout with all fixed blocks as per configuration.
     *
     * @return string  A default serialized block layout.
     */
    public function getFixedBlocks()
    {
        $layout = array();


        return $layout;
    }

    /**
     * Returns a select widget with all available blocks.
     *
     * @param string $cur_app    The block from this application gets selected.
     * @param string $cur_block  The block with this name gets selected.
     * @param boolean $onchange  Include the onchange action
     * @param boolean $readonly  Indicates if this block type is changeable.
     *
     * @return string  The select tag with all available blocks.
     */
    public function getBlocksWidget($cur_app = null, $cur_block = null,
                                    $onchange = false, $readonly = false)
    {
        $widget = '<select name=' . (!$readonly ? '"app"' : '"roapp"');

        if ($onchange) {
            $widget .= ' onchange="document.blockform.action.value=\'save-resume\';document.blockform.submit()"';
        }

        if ($readonly) {
            $widget .= ' disabled="disabled"';
        }

        $widget .= ">\n";

        foreach ($this->getBlocksList() as $id => $name) {
            $widget .= sprintf(
                "<option value=\"%s\"%s>%s</option>\n",
                $id,
                ($id == $cur_app . ':' . $cur_block) ? ' selected="selected"' : '',
                $name
            );
        }
        $widget .= "</select>\n";
        if ($readonly) {
            $widget .= '<input type="hidden" name="app" value="' . $cur_app . ':' . $cur_block . '" />' . "\n";
        }

        return $widget;
    }

    /**
     * Returns the option type.
     *
     * @param $app TODO
     * @param $block TODO
     * @param $param_id TODO
     *
     * @return TODO
     */
    public function getOptionType($app, $block, $param_id)
    {
        $this->getParams($app, $block);
        return $this->_blocks[$app][$block]['params'][$param_id]['type2'];
    }

    /**
     * Returns whether the option is required or not. Defaults to true.
     *
     * @param $app TODO
     * @param $block TODO
     * @param $param_id TODO
     *
     * @return TODO
     */
    public function getOptionRequired($app, $block, $param_id)
    {
        $this->getParams($app, $block);
        return isset($this->_blocks[$app][$block]['params'][$param_id]['required'])
            ? $this->_blocks[$app][$block]['params'][$param_id]['required']
            : true;
    }

    /**
     * Returns the values for an option.
     *
     * @param $app TODO
     * @param $block TODO
     * @param $param_id TODO
     *
     * @return TODO
     */
    public function getOptionValues($app, $block, $param_id)
    {
        $this->getParams($app, $block);
        return $this->_blocks[$app][$block]['params'][$param_id]['values'];
    }

    /**
     * Returns the widget necessary to configure this block.
     *
     * @param $app TODO
     * @param $block TODO
     * @param $param_id TODO
     * @param $val TODO
     *
     * @return TODO
     */
    public function getOptionsWidget($app, $block, $param_id, $val = null)
    {
        $widget = '';

        $this->getParams($app, $block);
        $param = $this->_blocks[$app][$block]['params'][$param_id];
        if (!isset($param['default'])) {
            $param['default'] = '';
        }

        switch ($param['type']) {
        case 'boolean':
        case 'checkbox':
            $checked = !empty($val[$param_id]) ? ' checked="checked"' : '';
            $widget = sprintf('<input type="checkbox" name="params[%s]"%s />', $param_id, $checked);
            break;

        case 'enum':
            $widget = sprintf('<select name="params[%s]">', $param_id);
            foreach ($param['values'] as $key => $name) {
                if (Horde_String::length($name) > 30) {
                    $name = substr($name, 0, 27) . '...';
                }
                $widget .= sprintf("<option value=\"%s\"%s>%s</option>\n",
                                   htmlspecialchars($key),
                                   (isset($val[$param_id]) && $val[$param_id] == $key) ? ' selected="selected"' : '',
                                   htmlspecialchars($name));
            }

            $widget .= '</select>';
            break;

        case 'multienum':
            $widget = sprintf('<select multiple="multiple" name="params[%s][]">', $param_id);
            foreach ($param['values'] as $key => $name) {
                if (Horde_String::length($name) > 30) {
                    $name = substr($name, 0, 27) . '...';
                }
                $widget .= sprintf("<option value=\"%s\"%s>%s</option>\n",
                                   htmlspecialchars($key),
                                   (isset($val[$param_id]) && in_array($key, $val[$param_id])) ? ' selected="selected"' : '',
                                   htmlspecialchars($name));
            }

            $widget .= '</select>';
            break;

        case 'mlenum':
            // Multi-level enum.
            if (is_array($val) && isset($val['__' . $param_id])) {
                $firstval = $val['__' . $param_id];
            } else {
                $tmp = array_keys($param['values']);
                $firstval = current($tmp);
            }
            $blockvalues = $param['values'][$firstval];
            asort($blockvalues);

            $widget = sprintf('<select name="params[__%s]" onchange="document.blockform.action.value=\'save-resume\';document.blockform.submit()">', $param_id) . "\n";
            foreach ($param['values'] as $key => $values) {
                $name = Horde_String::length($key) > 30 ? Horde_String::substr($key, 0, 27) . '...' : $key;
                $widget .= sprintf("<option value=\"%s\"%s>%s</option>\n",
                                   htmlspecialchars($key),
                                   $key == $firstval ? ' selected="selected"' : '',
                                   htmlspecialchars($name));
            }
            $widget .= "</select><br />\n";

            $widget .= sprintf("<select name=\"params[%s]\">\n", $param_id);
            foreach ($blockvalues as $key => $name) {
                $name = (Horde_String::length($name) > 30) ? Horde_String::substr($name, 0, 27) . '...' : $name;
                $widget .= sprintf("<option value=\"%s\"%s>%s</option>\n",
                                   htmlspecialchars($key),
                                   $val[$param_id] == $key ? ' selected="selected"' : '',
                                   htmlspecialchars($name));
            }
            $widget .= "</select><br />\n";
            break;

        case 'int':
        case 'text':
            $widget = sprintf('<input type="text" name="params[%s]" value="%s" />', $param_id, !isset($val[$param_id]) ? $param['default'] : $val[$param_id]);
            break;

        case 'password':
            $widget = sprintf('<input type="password" name="params[%s]" value="%s" />', $param_id, !isset($val[$param_id]) ? $param['default'] : $val[$param_id]);
            break;

        case 'error':
            $widget = '<span class="form-error">' . $param['default'] . '</span>';
            break;
        }

        return $widget;
    }

    /**
     * Returns the name of the specified block.
     *
     * @param string $app    An application name.
     * @param string $block  A block name.
     *
     * @return string  The name of the specified block.
     */
    public function getName($app, $block)
    {
        return isset($this->_blocks[$app][$block])
            ? $this->_blocks[$app][$block]['name']
            : sprintf(Horde_Core_Translation::t("Block \"%s\" of application \"%s\" not found."), $block, $app);
    }

    /**
     * Returns the parameter list of the specified block.
     *
     * @param string $app    An application name.
     * @param string $block  A block name.
     *
     * @return array  An array with all parameter names.
     */
    public function getParams($app, $block)
    {
        if (!isset($this->_blocks[$app][$block])) {
            return array();
        }

        if (!isset($this->_blocks[$app][$block]['params'])) {
            $blockOb = $this->getBlock($app, $block);
            $this->_blocks[$app][$block]['params'] = $blockOb->getParams();
        }

        if (isset($this->_blocks[$app][$block]['params']) &&
            is_array($this->_blocks[$app][$block]['params'])) {
            return array_keys($this->_blocks[$app][$block]['params']);
        }

        return array();
    }

    /**
     * Returns the (clear text) name of the specified parameter.
     *
     * @param string $app    An application name.
     * @param string $block  A block name.
     * @param string $param  A parameter name.
     *
     * @return string  The name of the specified parameter.
     */
    public function getParamName($app, $block, $param)
    {
        $this->getParams($app, $block);
        return $this->_blocks[$app][$block]['params'][$param]['name'];
    }

    /**
     * Returns the default value of the specified parameter.
     *
     * @param string $app    An application name.
     * @param string $block  A block name.
     * @param string $param  A parameter name.
     *
     * @return string  The default value of the specified parameter or null.
     */
    public function getDefaultValue($app, $block, $param)
    {
        $this->getParams($app, $block);
        return isset($this->_blocks[$app][$block]['params'][$param]['default'])
            ? $this->_blocks[$app][$block]['params'][$param]['default']
            : null;
    }

    /**
     * Returns if the specified block is customizeable by the user.
     *
     * @param string $app    An application name.
     * @param string $block  A block name.
     *
     * @return boolean  True is the block is customizeable.
     */
    public function isEditable($app, $block)
    {
        $block = $this->getBlock($app, $block);
        return $block->updateable || $block->getParams();
    }

    /* Serializable methods. */

    public function serialize()
    {
        return json_encode(array(
            $this->_blocks,
            $this->_layout
        ));
    }

    public function unserialize($data)
    {
        list($this->_blocks, $this->_layout) = json_decode($data, true);
    }

}
