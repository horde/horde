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
        return parent::render($name, $locals);
    }

    /**
     * Returns the HTML for a single tree-style row in the sidebar
     *
     * @param array $values  The template values to assign. Possible values:
     *                       id, style, cssClass, label.
     *
     * @return string  The row's HTML code.
     */
    public function getTreeRow(array $values)
    {
        $view = new Horde_View(array('templatePath' => $GLOBALS['registry']->get('templates', 'horde') . '/sidebar'));
        $view->addHelper('Text');
        $view->assign($values);
        return $view->render('container');
    }
}
