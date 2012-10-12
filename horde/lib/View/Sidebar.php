<?php
/**
 * This is a view of the application-specific sidebar.
 *
 * Useful properties:
 * - newLink: (string, optional) Link of the "New" button
 *   - newText: (string) Text of the "New" button
 * - newExtra: (string, optional) HTML content of the extra link
 * - containers: (array, optional) HTML content of any sidebar sections. A list
 *               of hashes with the following properties:
 *               - id: (string, optional) The container's DOM ID.
 *               - header: (array, optional) Container header, also used to
 *                         toggle the section:
 *                 - id: (string) The header's DOM ID.
 *                 - label: (string) Header label.
 *                 - collapsed: (boolean, optional) Start section collapsed?
 *                   Overriden by cookies.
 *                 - add: (string|array, optional) Link to add something:
 *                   - url: (string) Link URL.
 *                   - label: (string) Link text.
 *               - content: (string, optional) The container's HTML content.
 *               - rows: (array, optional) A list of row hashes, if 'content'
 *                       is not specified. @see addRow().
 *               - resources: (boolean, optional) Does the container contain
 *                            switchable resource lists? Automatically set
 *                            through addRow().
 *               - type: (string, optional) @see addRow().
 * - content: (string, optional) HTML content of the sidebar, if 'containers'
 *            is not specified.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  horde
 */
class Horde_View_Sidebar extends Horde_View
{
    /**
     * Constructor.
     *
     * @param array $config  Configuration key-value pairs.
     */
    public function __construct($config = array())
    {
        if (empty($config['templatePath'])) {
            $config['templatePath'] = $GLOBALS['registry']->get('templates', 'horde') . '/sidebar';
        }
        parent::__construct($config);
        $this->addHelper('Text');

        $this->containers = array();
        $this->width = $GLOBALS['prefs']->getValue('sidebar_width');

        $pageOutput = $GLOBALS['injector']->getInstance('Horde_PageOutput');
        $pageOutput->addScriptFile('sidebar.js', 'horde');
        $pageOutput->addInlineJsVars(array(
            'HordeSidebar.text' => array(
                'collapse' => _("Collapse"),
                'expand' => _("Expand"),
             ),
            'HordeSidebar.opts' => array(
                'cookieDomain' => $GLOBALS['conf']['cookie']['domain'],
                'cookiePath' => $GLOBALS['conf']['cookie']['path'],
            ),
        ));
    }

    /**
     * Returns the HTML code for the sidebar.
     *
     * @param string $name  The template to process.
     *
     * @return string  The sidebar's HTML code.
     */
    public function render($name = 'sidebar', $locals = array())
    {
        $effects = false;
        foreach ($this->containers as $id => &$container) {
            if (!isset($container['header'])) {
                continue;
            }
            if (isset($container['header']['id'])) {
                $id = $container['header']['id'];
            }
            if (isset($_COOKIE['horde_sidebar_c_' . $id])) {
                $container['header']['collapsed'] = !empty($_COOKIE['horde_sidebar_c_' . $id]);
            }
            $effects = true;
        }
        if ($effects) {
            $GLOBALS['injector']
                ->getInstance('Horde_PageOutput')
                ->addScriptFile('scriptaculous/effects.js', 'horde');
        }
        $this->containers = array_values($this->containers);
        return parent::render($name, $locals);
    }

    /**
     * Handler for string casting.
     *
     * @return string  The sidebar's HTML code.
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Adds a "New ..." button to the sidebar.
     *
     * @param string $label  The button text, including access key.
     * @param string $url    The button URL.
     * @param array $extra   Extra attributes for the link tag.
     */
    public function addNewButton($label, $url, $extra = array())
    {
        $ak = Horde::getAccessKey($label);
        $attributes = $ak
            ? Horde::getAccessKeyAndTitle($label, true, true)
            : array();
        $this->newLink = $url->link($attributes + $extra);
        $this->newText = Horde::highlightAccessKey($label, $ak);
    }

    /**
     * Adds a row to the sidebar.
     *
     * If containers/sections are not added explicitly to the view
     * through the "containers" property, these rows will be used
     * instead.
     *
     * @param array $row         A hash with the row information. Possible
     *                           values:
     *   - label: (string) The row text.
     *   - selected: (boolean) Whether to mark the row as active.
     *   - style: (string) Additional CSS styles to apply to the row.
     *   - url (string) URL to link the row to.
     *   - type (string, optional) The row type, defaults to "tree". Further
     *     $row properties depending on the type:
     *     - tree:
     *       - cssClass: (string) CSS class for the icon.
     *       - id: (string) DOM ID for the row link.
     *     - checkbox:
     *     - radiobox:
     *       - color: (string, optional) Background color.
     *       - edit: (string, optional) URL for extra edit icon.
     * @param string $container  If using multiple sidebar sections, the ID of
     *                           the section to add the row to. Sections will
     *                           be rendered in the order of their first usage.
     */
    public function addRow(array $row, $container = '')
    {
        if (!isset($this->containers[$container])) {
            $this->containers[$container] = array('rows' => array());
            if ($container) {
                $this->containers[$container]['id'] = $container;
            }
        }

        $boxrow = isset($row['type']) &&
            ($row['type'] == 'checkbox' || $row['type'] == 'radiobox');

        if (isset($row['url'])) {
            $ak = Horde::getAccessKey($row['label']);
            $url = empty($row['url']) ? new Horde_Url() : $row['url'];
            $attributes = $ak
                ? array('accesskey' => $ak)
                : array();
            foreach (array('onclick', 'target', 'class') as $attribute) {
                if (!empty($row[$attribute])) {
                   $attributes[$attribute] = $row[$attribute];
                }
            }
            if ($boxrow) {
                $class = 'horde-resource-'
                    . (empty($row['selected']) ? 'off' : 'on');
                if ($row['type'] == 'radiobox') {
                    $class .= ' horde-radiobox';
                }
                if (empty($attributes['class'])) {
                    $attributes['class'] = $class;
                } else {
                    $attributes['class'] .= ' ' . $class;
                }
            }
            $row['link'] = $url->link($attributes)
                . Horde::highlightAccessKey($row['label'], $ak)
                . '</a>';
        } else {
            $row['link'] = '<span class="horde-resource-none">'
                . $row['label'] . '</span>';
        }

        if ($boxrow) {
            $this->containers[$container]['type'] = $row['type'];
            if (!isset($row['style'])) {
                $row['style'] = '';
            }
            if (!isset($row['color'])) {
                $row['color'] = '#dddddd';
            }
            $foreground = '000';
            if (Horde_Image::brightness($row['color']) < 128) {
                $foreground = 'fff';
            }
            if (strlen($row['style'])) {
                $row['style'] .= ';';
            }
            $row['style'] .= 'background-color:' . $row['color']
                . ';color:#' . $foreground;
            if (isset($row['edit'])) {
                $row['editLink'] = $row['edit']
                    ->link(array(
                        'title' =>  _("Edit"),
                        'class' => 'horde-resource-edit-' . $foreground))
                    . '&#9658;' . '</a>';
            }
        }

        $this->containers[$container]['rows'][] = $row;
    }

}
