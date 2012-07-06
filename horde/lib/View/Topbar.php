<?php
/**
 * This is a view of the Horde topbar.
 *
 * Useful properties:
 * - subinfo: (string) Right-aligned content of the sub-bar.
 * - search: (boolean) Whether to show the search bar.
 * - searchAction: (string) The form action attribute of the search form.
 * - searchMenu: (boolean) whether to show a drop down icon inside the search
 *               field.
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
class Horde_View_Topbar extends Horde_View
{
    /**
     * Constructor.
     *
     * @param array $config  Configuration key-value pairs.
     */
    public function __construct($config = array())
    {
        global $registry;

        if (empty($config['templatePath'])) {
            $config['templatePath'] = $registry->get('templates', 'horde') . '/topbar';
        }
        parent::__construct($config);
        $this->addHelper('Text');

        /* Logo. */
        $this->portalUrl = $registry->getServiceLink(
            'portal', $registry->getApp());
        if (class_exists('Horde_Bundle')) {
            $this->version = Horde_Bundle::SHORTNAME . ' ' . Horde_Bundle::VERSION;
        } else {
            $this->version = $registry->getVersion('horde');
        }

        /* Main menu. */
        $this->menu = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Topbar')
            ->create('Horde_Tree_Renderer_Menu', array('nosession' => true))
            ->getTree();

        /* Login/Logout. */
        if ($registry->getAuth()) {
            if (Horde_Menu::showService('logout')) {
                $this->logoutUrl = $registry->getServiceLink(
                    'logout',
                    $registry->getApp())
                    ->setRaw(false);
            }
        } else {
            if (Horde_Menu::showService('login')) {
                $this->logoutUrl = $registry->getServiceLink(
                    'login',
                    $registry->getApp())
                    ->setRaw(false);
            }
        }

        /* Sub bar. */
        $this->date = strftime($GLOBALS['prefs']->getValue('date_format'));
        $pageOutput = $GLOBALS['injector']->getInstance('Horde_PageOutput');
        $pageOutput->addScriptPackage('Datejs');
        $pageOutput->addScriptFile('topbar.js', 'horde');
        $pageOutput->addInlineJsVars(array('HordeTopbar.format' =>
            Horde_Core_Script_Package_Datejs::translateFormat($GLOBALS['prefs']->getValue('date_format'))
        ));
    }

    /**
     * Returns the HTML code for the topbar.
     *
     * @param string $name  The template to process.
     *
     * @return string  The topbar's HTML code.
     */
    public function render($name = 'topbar', $locals = array())
    {
        return parent::render($name, $locals);
    }
}
