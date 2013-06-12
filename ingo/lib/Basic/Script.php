<?php
/**
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2002-2013 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Script page.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Jan Schneider <jan@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2013 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Basic_Script extends Ingo_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $injector, $notification, $session;

        /* Redirect if script updating is not available. */
        $script = $injector->getInstance('Ingo_Factory_Script');
        if (!$script->hasFeature('script_file')) {
            Ingo_Basic_Filters::url()->redirect();
        }

        /* Generate the script. */
        $scripts = array();
        foreach ($script->createAll() as $script) {
            $scripts = array_merge($scripts, $script->generate());
        }

        /* Activate/deactivate script if requested. */
        switch ($this->vars->actionID) {
        case 'action_activate':
            if (!empty($scripts)) {
                try {
                    Ingo::activateScripts($scripts, false);
                } catch (Ingo_Exception $e) {
                    $notification->push($e);
                }
            }
            break;

        case 'action_deactivate':
            try {
                Ingo::activateScripts('', true);
            } catch (Ingo_Exception $e) {
                $notification->push($e);
            }
            break;

        case 'show_active':
            $scripts = array();
            foreach ($session->get('ingo', 'backend/transport', Horde_Session::TYPE_ARRAY) as $transport) {
                try {
                    $backend = $injector->getInstance('Ingo_Factory_Transport')->create($transport);
                    if (method_exists($backend, 'getScript')) {
                        $scripts[] = $backend->getScript();
                    }
                } catch (Horde_Exception_NotFound $e) {
                } catch (Ingo_Exception $e) {
                    $notification->push($e);
                }
            }
            break;
        }

        /* Prepare the view. */
        $view = new Horde_View(array(
            'templatePath' => INGO_TEMPLATES . '/basic/script'
        ));
        $view->addHelper('Text');

        if (empty($scripts)) {
            $view->scriptexists = false;
        } else {
            $view->scriptexists = true;
            foreach ($scripts as &$script) {
                $script['lines'] = preg_split('(\r\n|\n|\r)', trim($script['script']));
                $script['width'] = strlen(count($script['lines']));
            }
        }
        $view->scripturl = self::url();
        $view->showactivate = ($this->vars->actionID != 'show_active');
        if ($view->scriptexists) {
            $view->scripts = $scripts;
        }

        $this->header = _("Filter Script Display");
        $this->output = $view->render('script');
    }

    /**
     */
    static public function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'script');
    }

}
