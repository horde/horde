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
 * Whitelist page.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Brent J. Nordquist <bjn@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2013 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Basic_Whitelist extends Ingo_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $injector, $notification, $page_output, $prefs, $session;

        /* Redirect if whitelist not available. */
        if (!in_array(Ingo_Storage::ACTION_WHITELIST, $session->get('ingo', 'script_categories'))) {
            $notification->push(_("Whitelist is not supported in the current filtering driver."), 'horde.error');
            Ingo_Basic_Filters::url()->redirect();
        }

        $ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();
        $whitelist = $ingo_storage->retrieve(Ingo_Storage::ACTION_WHITELIST);

        /* Perform requested actions. */
        switch ($this->vars->actionID) {
        case 'rule_update':
            try {
                Ingo::updateListFilter($this->vars->whitelist, Ingo_Storage::ACTION_WHITELIST);
                $notification->push(_("Changes saved."), 'horde.success');
                if ($prefs->getValue('auto_update')) {
                    Ingo::updateScript();
                }

                /* Update the timestamp for the rules. */
                $session->set('ingo', 'change', time());
            } catch (Ingo_Exception $e) {
                $notification->push($e);
            }
            break;
        }

        /* Get the whitelist rule. */
        $wl_rule = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS)->findRule(Ingo_Storage::ACTION_WHITELIST);

        /* Prepare the view. */
        $view = new Horde_View(array(
            'templatePath' => INGO_TEMPLATES . '/basic/whitelist'
        ));
        $view->addHelper('Horde_Core_View_Helper_Help');
        $view->addHelper('Horde_Core_View_Helper_Label');
        $view->addHelper('Text');

        $view->disabled = !empty($wl_rule['disable']);
        $view->formurl = self::url();
        $view->whitelist = implode("\n", $whitelist->getWhitelist());

        $page_output->addScriptFile('whitelist.js');
        $page_output->addInlineJsVars(array(
            'IngoWhitelist.filtersurl' => strval(Ingo_Basic_Filters::url()->setRaw(true))
        ));

        $this->title = _("Whitelist Edit");
        $this->output = $view->render('whitelist');
    }

    /**
     */
    static public function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'whitelist');
    }

}
