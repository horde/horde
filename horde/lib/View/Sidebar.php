<?php
/**
 * This is a view of the application-specific sidebar.
 *
 * Useful properties:
 * - newLink: (string, optional) Link of the "New" button
 *   - newText: (string) Text of the "New" button
 * - newRefresh: (string, optional) HTML content of the refresh button
 * - containers: (array, optional) HTML content of any sidebar sections.
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
     * Containers and rows added through {@link addRow()}.
     *
     * @var array
     */
    protected $_containers = array();

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

        $this->width = $GLOBALS['prefs']->getValue('sidebar_width');
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
        $GLOBALS['page_output']->sidebarLoaded = true;
        if (!$this->containers) {
            $this->containers = array_values($this->_containers);
        }
        return parent::render($name, $locals);
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
     *                           - cssClass: (string) CSS class for the icon.
     *                           - id: (string) DOM ID for the row link.
     *                           - link (string) Link tag for the row.
     *                           - selected: (boolean) Whether to mark the row
     *                             as active.
     *                           - style: (string) Additional CSS styles to
     *                             apply to the row.
     * @param string $container  If using multiple sidebar sections, the ID of
     *                           the section to add the row to. Sections will
     *                           be rendered in the order of their first usage.
     */
    public function addRow(array $row, $container = '')
    {
        if (!isset($this->_containers[$container])) {
            $this->_containers[$container] = array('rows' => array());
            if ($container) {
                $this->_containers[$container]['id'] = $container;
            }
        }

        $ak = Horde::getAccessKey($row['label']);
        $url = empty($row['url']) ? new Horde_Url() : $row['url'];
        $attributes = $ak
            ? Horde::getAccessKeyAndTitle($row['label'], true, true)
            : array();
        $row['link'] = $url->link($attributes)
            . Horde::highlightAccessKey($row['label'], $ak)
            . '</a>';

        $this->_containers[$container]['rows'][] = $row;
    }
}
