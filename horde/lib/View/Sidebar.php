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
     */
    public function addNewButton($label, $url)
    {
        $ak = Horde::getAccessKey($label);
        $attributes = $ak
            ? Horde::getAccessKeyAndTitle($label, true, true)
            : array();
        $this->newLink = $url->link($attributes);
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
     *                           - cssClass: (string) CSS class for the icon.
     *                           - id: (string) DOM ID for the row link.
     *                           - label: (string) The row text.
     *                           - selected: (boolean) Whether to mark the row
     *                             as active.
     *                           - style: (string) Additional CSS styles to
     *                             apply to the row.
     *                           - url (string) URL to link the row to.
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
        $row['link'] = $url->link($attributes)
            . Horde::highlightAccessKey($row['label'], $ak)
            . '</a>';

        $this->containers[$container]['rows'][] = $row;
    }
}
