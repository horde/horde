<?php
/**
 * Hermes application interface.
 *
 * This file is responsible for initializing the Hermes application.
 *
 * Copyright 2001-2007 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2010 Alkaloid Networks (http://projects.alkaloid.net/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Robert E. Coyle <robertecoyle@hotmail.com>
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Hermes
 */

if (!defined('HERMES_BASE')) {
    define('HERMES_BASE', dirname(__FILE__). '/..');
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
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (2.0-git)';

    /**
     * Driver object for reading/writing time entries
     */
    static public $driver = null;

    /**
     * Initialization function.
     *
     * Global variables defined:
     */
    protected function _init()
    {
        try {
            $this->driver = Hermes::getDriver();
        } catch (Hermes_Exception $e) {
            $GLOBALS['notification']->push($e);
            return false;
        }
    }

    /**
     * Interface to define settable permissions within Horde
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

    /* Sidebar method. */

    /**
     * Add node(s) to the sidebar tree.
     *
     * @param Horde_Tree_Base $tree  Tree object.
     * @param string $parent         The current parent element.
     * @param array $params          Additional parameters.
     *
     * @throws Horde_Exception
     */
    public function sidebarCreate(Horde_Tree_Base $tree, $parent = null,
                                  array $params = array())
    {
        switch ($params['id']) {
        case 'menu':
            $tree->addNode(
                $parent . '__add',
                $parent,
                _("Enter Time"),
                1,
                false,
                array(
                    'icon' => 'hermes.png',
                    'url' => Horde::url('entry.php')
                )
            );

            $tree->addNode(
                $parent . '__search',
                $parent,
                _("Search Time"),
                1,
                false,
                array(
                    'icon' => 'search.png',
                    'url' => Horde::url('search.php')
                )
            );
            break;

        case 'stopwatch':
            Horde::addScriptFile('popup.js', 'horde');
            $entry = Horde::url('entry.php');

            $tree->addNode(
                $parent . '__start',
                $parent,
                _("Start Watch"),
                1,
                false,
                array(
                    'icon' => 'timer-start.png',
                    'onclick' => "popup('" . Horde::url('start.php') . "', 400, 100); return false;",
                    'url' => '#'
                )
            );

            if ($timers = @unserialize($GLOBALS['prefs']->getValue('running_timers', false))) {
                foreach ($timers as $i => $timer) {
                    $hours = round((float)(time() - $i) / 3600, 2);
                    $tree->addNode(
                        $parent . '__timer_' . $i,
                        $parent,
                        Horde_String::convertCharset($timer['name'], $prefs->getCharset(), 'UTF-8') . sprintf(" (%s)", $hours),
                        1,
                        false,
                        array(
                            'icon' => 'timer-stop.png',
                            'url' => $entry->add('timer', $i)
                        )
                    );
                }
            }
            break;
        }
    }

}
