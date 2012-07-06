<?php
/**
 * Hermes application interface.
 *
 * This file is responsible for initializing the Hermes application.
 *
 * Copyright 2001-2007 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2010 Alkaloid Networks (http://projects.alkaloid.net/)
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Robert E. Coyle <robertecoyle@hotmail.com>
 * @author  Ben Klang <ben@alkaloid.net>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
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
    public $version = 'H5 (2.0-git)';

    /**
     */
    protected function _bootstrap()
    {
        $GLOBALS['injector']->bindFactory('Hermes_Driver', 'Hermes_Factory_Driver', 'create');
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
            )
        );
    }

    /**
     */
    public function menu($menu)
    {
        $menu->add(Horde::url('time.php'), _("My _Time"), 'hermes.png', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
        $menu->add(Horde::url('entry.php'), _("_New Time"), 'hermes.png', null, null, null, Horde_Util::getFormData('id') ? '__noselection' : null);
        $menu->add(Horde::url('search.php'), _("_Search"), 'search.png');

        if ($GLOBALS['conf']['time']['deliverables'] &&
            $GLOBALS['registry']->isAdmin(array('permission' => 'hermes:deliverables'))) {
            $menu->add(Horde::url('deliverables.php'), _("_Deliverables"), 'hermes.png');
        }

        if ($GLOBALS['conf']['invoices']['driver'] &&
            $GLOBALS['registry']->isAdmin(array('permission' => 'hermes:invoicing'))) {
            $menu->add(Horde::url('invoicing.php'), _("_Invoicing"), 'invoices.png');
        }

        /* Administration. */
        if ($GLOBALS['registry']->isAdmin()) {
            $menu->add(Horde::url('admin.php'), _("_Admin"), 'hermes.png');
        }
    }

    /* Topbar method. */

    /**
     */
    public function topbarCreate(Horde_Tree_Renderer_Base $tree, $parent = null,
                                 array $params = array())
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
                    'url' => htmlspecialchars('javascript:' . Horde::popupJs(Horde::url('start.php'), array('height' => 200, 'width' => 400)))
                )
            ));

            if ($timers = @unserialize($GLOBALS['prefs']->getValue('running_timers'))) {
                $entry = Horde::url('entry.php');
                foreach ($timers as $i => $timer) {
                    $hours = round((float)(time() - $i) / 3600, 2);
                    $tree->addNode(array(
                        'id' => $parent . '__timer_' . $i,
                        'parent' => $parent,
                        'label' => $timer['name'] . sprintf(" (%s)", $hours),
                        'expanded' => false,
                        'params' => array(
                            'icon' => Horde_Themes::img('timer-stop.png'),
                            'url' => $entry->add('timer', $i)
                        )
                    ));
                }
            }
            break;
        }
    }

}
