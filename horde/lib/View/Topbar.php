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
 * - searchLabel: (string) Ghost label of the search field.
 * - searchParameters: (array) Key-value-hash with additional hidden form
 *                     fields.
 *
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
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
        global $injector, $prefs, $registry;

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
        $topbar = $injector->getInstance('Horde_Core_Factory_Topbar')
            ->create('Horde_Tree_Renderer_Menu', array('nosession' => true));
        $this->menu = $topbar->getTree();

        /* Search form. */
        $this->searchAction = '#';
        $this->searchIcon = Horde_Themes::img('search-topbar.png');
        $this->searchLabel = _("Search");

        /* Login/Logout. */
        if ($registry->getAuth()) {
            if ($registry->showService('logout')) {
                $this->logoutUrl =
                    $registry->getServiceLink(
                        'logout',
                        $registry->getApp()
                    )
                    ->setRaw(false);
            }
        } else {
            if ($registry->showService('login')) {
                $this->loginUrl =
                    $registry->getServiceLink(
                        'login',
                        $registry->getApp()
                    )
                    ->setRaw(false)
                    ->add('url', Horde::selfUrl(true, true, true));
            }
        }

        /* Sub bar. */
        $this->date = strftime($prefs->getValue('date_format'));
        $pageOutput = $injector->getInstance('Horde_PageOutput');
        $pageOutput->addScriptPackage('Horde_Core_Script_Package_Datejs');
        $pageOutput->addScriptFile('topbar.js', 'horde');
        $pageOutput->addInlineJsVars(array('HordeTopbar.conf' => array(
            /* Need explicit URI here, since topbar may be running in
             * an application's scope. */
            'URI_AJAX' => $registry->getServiceLink('ajax', 'horde')->url,
            'app' => $registry->getApp(),
            'format' => Horde_Core_Script_Package_Datejs::translateFormat($prefs->getValue('date_format')),
            'hash' => $topbar->getHash(),
            'refresh' => intval($prefs->getValue('menu_refresh_time'))
        )));

        /* Sidebar. */
        $this->sidebarWidth = $prefs->getValue('sidebar_width');
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
        if ($this->search) {
            $GLOBALS['injector']->getInstance('Horde_PageOutput')
                ->addScriptFile('form_ghost.js', 'horde');
            if (!isset($this->searchParameters)) {
                $action = new Horde_Url($this->searchAction);
                $this->searchAction = $action->url;
                $this->searchParameters = $action->parameters;
            }
        }
        $this->sidebar = $GLOBALS['page_output']->sidebar;
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
}
