<?php
/**
 * Hermes application interface.
 *
 * This file is responsible for initializing the Hermes application.
 *
 * Copyright 2001-2007 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2010 Alkaloid Networks (http://projects.alkaloid.net/)
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Robert E. Coyle <robertecoyle@hotmail.com>
 * @author Ben Klang <ben@alkaloid.net>
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package Hermes
 */

if (!defined('HERMES_BASE')) {
    define('HERMES_BASE', __DIR__. '/..');
}

if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(HERMES_BASE. '/config/horde.local.php')) {
        include HERMES_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', HERMES_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Hermes_Application extends Horde_Registry_Application
{
    /**
     */
    public $features = array(
        'dynamicView' => true
    );

    /**
     */
    public $version = 'H5 (2.0.0-git)';

    /**
     */
    protected function _bootstrap()
    {
        $GLOBALS['injector']->bindFactory('Hermes_Driver', 'Hermes_Factory_Driver', 'create');
    }

    /**
     */
    protected function _init()
    {
        if (!$GLOBALS['prefs']->getValue('dynamic_view')) {
            $this->features['dynamicView'] = false;
        }
    }

    public function download(Horde_Variables $vars)
    {
        global $notification, $injector;

        switch ($vars->actionID) {
        case 'export':

            // 'export' used from Ajax view
            $ids = explode(',', $vars->s);
            if (!is_array($ids)) {
                $notification->push(_("No time slices were submitted"), 'horde.error');
                return false;
            }
            try {
                $hours = $injector
                    ->getInstance('Hermes_Driver')
                    ->getHours(array('id' => $ids));
            } catch (Hermes_Exception $e) {
                $notification->push($e->getMessage(), 'horde.error');
                return false;
            }
            $exportHours = Hermes::makeExportHours($hours);
            $this->_doExport($vars->f, $exportHours);
            if ($vars->m) {
                $injector->getInstance('Hermes_Driver')->markAs('exported', $hours);
            }
            break;

        case 'search_export':
            // Called from Basic view.
            if (!($searchVars = $GLOBALS['session']->get('hermes', 'search_criteria'))) {
                $notification->push(_("No time slices were submitted"), 'horde.error');
                return false;
            }
            $searchForm = new Hermes_Form_Search($searchVars);
            $criteria = $searchForm->getSearchCriteria($searchVars);
            if (is_null($criteria)) {
                $notification->push(_("No time slices were submitted"), 'horde.error');
                return false;
            }
            $form = new Hermes_Form_Export($vars);
            $form->validate($vars);
            if (!$form->isValid()) {
                return false;
            }
            $form->getInfo($vars, $info);
            try {
                $hours = $injector
                    ->getInstance('Hermes_Driver')
                    ->getHours($criteria);
            } catch (Hermes_Exception $e) {
                $notification->push($e, 'horde.error');
                return false;
            }
            $exportHours = Hermes::makeExportHours($hours);
            $this->_doExport($info['format'], $exportHours);
            if (!empty($info['mark_exported']) &&
                $info['mark_exported'] == 'yes' &&
                $GLOBALS['perms']->hasPermission(
                    'hermes:review',
                     $GLOBALS['registry']->getAuth(),
                     Horde_Perms::EDIT)) {

                $injector->getInstance('Hermes_Driver')->markAs('exported', $hours);
            }
        }
        $GLOBALS['notification']->push(_("Export complete."), 'horde.success');

        return true;
    }

    /**
     * Actually perform the export in the appropriate format.
     *
     * @param string $format  The format to export in.
     * @param array $hours     The data to export, as returned from
     *                        Hermes::makeExportHours()
     *
     */
    protected function _doExport($format, $hours)
    {
        switch ($format) {
        case Horde_Data::EXPORT_CSV:
            $class = 'Hermes_Data_Csv';
            $ext = 'csv';
            break;
        case Horde_Data::EXPORT_TSV:
            $class = 'Hermes_Data_Tsv';
            $ext = 'tsv';
            break;
        case 'xls':
            $class = 'Hermes_Data_Xls';
            $ext = 'xls';
            break;
        case 'iif':
            $class = 'Hermes_Data_Iif';
            $ext = 'iif';
            break;
        }
        $data = new $class(
            $GLOBALS['injector']->getInstance('Horde_Core_Data_Storage'),
            array(
                'browser' => $GLOBALS['injector']->getInstance('Horde_Browser'),
                'vars' => Horde_Variables::getDefaultVariables()
            )
        );
        $data->exportFile('time.' . $ext, $hours, true);
    }

    /**
     */
    public function perms()
    {
        return array(
            'review' => array(
                'title' => _("Time Review Screen")
            ),
            'deliverables' => array(
                'title' => _("Deliverables")
            ),
            'invoicing' => array(
                'title' => _("Invoicing")
            ),
            'timeadmin' => array(
                'title' => _("Time Administration"),
                'type' => 'boolean')
        );
    }

    /**
     * Responsible for building the top left menu entries of the sideBar in
     * Basic view.
     */
    public function menu($menu)
    {
        $menu->add(Horde::url('time.php'), _("My _Time"), 'hermes-time');

        $menu->add(
            Horde::url('start.php'),
            _("Start Watch"),
            'hermes-start',
            null,
            null,
            Horde::popupJs(Horde::url('start.php'), array('height' => 200, 'width' => 410)) . 'return false;'
        );

        // Timers
        $timers = Hermes::listTimers();
        $entry = Horde::url('entry.php');
        foreach ($timers as $i => $timer) {
            $menu->add($entry->add('timer', $timer['id']),
                       $timer['name'] . sprintf(" (%s)", $timer['elapsed']),
                       'hermes-stop', null, '', null, '__noselection'
            );
        }

        $menu->add(Horde::url('search.php'), _("_Search"), 'hermes-search');

        if ($GLOBALS['conf']['time']['deliverables'] &&
            $GLOBALS['registry']->isAdmin(array('permission' => 'hermes:deliverables'))) {
            $menu->add(Horde::url('deliverables.php'),
                       _("_Deliverables"),
                       'hermes-deliverables');
        }

        if ($GLOBALS['conf']['invoices']['driver'] &&
            $GLOBALS['registry']->isAdmin(array('permission' => 'hermes:invoicing'))) {
            $menu->add(Horde::url('invoicing.php'),
                       _("_Invoicing"),
                       'hermes-invoices');
        }

        /* Administration. */
        if ($GLOBALS['registry']->isAdmin()) {
            $menu->add(Horde::url('admin.php'), _("_Admin"), 'hermes-admin');
        }
    }

    /**
     * Add additional items to the sidebar. This is for the Basic view. For the
     * Dynamic view @see Hermes_View_Sidebar
     *
     * @param Horde_View_Sidebar $sidebar  The sidebar object.
     */
    public function sidebar($sidebar)
    {
        $sidebar->addNewButton(
            _("_New Time"),
            Horde::url('entry.php')
        );
    }

    /* Topbar method. */

    /**
     */
    public function topbarCreate(
        Horde_Tree_Renderer_Base $tree, $parent = null, array $params = array())
    {
        switch ($params['id']) {
        case 'menu':
            $tree->addNode(array(
                'id' => $parent . '__add',
                'parent' => $parent,
                'label' => _("Enter Time"),
                'expanded' => false,
                'params' => array(
                    'icon' => Horde_Themes::img('hermes.png'),
                    'url' => Horde::url('entry.php')
                )
            ));

            $tree->addNode(array(
                'id' => $parent . '__search',
                'parent' => $parent,
                'label' => _("Search Time"),
                'expanded' => false,
                'params' => array(
                    'icon' => Horde_Themes::img('search.png'),
                    'url' => Horde::url('search.php')
                )
            ));
            break;

        case 'stopwatch':
            $tree->addNode(array(
                'id' => $parent . '__start',
                'parent' => $parent,
                'label' => _("Start Watch"),
                'expanded' => false,
                'params' => array(
                    'icon' => Horde_Themes::img('timer-start.png'),
                    'url' => '#',
                    'onclick' => Horde::popupJs(Horde::url('start.php'), array('height' => 200, 'width' => 410))
                )
            ));

            $timers = Hermes::listTimers();
            $entry = Horde::url('entry.php');
            foreach ($timers as $i => $timer) {
                $tree->addNode(array(
                    'id' => $parent . '__timer_' . $i,
                    'parent' => $parent,
                    'label' => $timer['name'] . sprintf(" (%s)", $timer['elapsed']),
                    'expanded' => false,
                    'params' => array(
                        'icon' => Horde_Themes::img('timer-stop.png'),
                        'url' => $entry->add('timer', $timer['id'])
                    )
                ));
            }
        }
    }

}
