<?php
/**
 * Copyright 2002-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
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
 * @copyright 2002-2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Basic_Whitelist extends Ingo_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $injector, $notification, $page_output;

        $this->_assertCategory('Ingo_Rule_System_Whitelist', _("Whitelist"));

        $ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();
        $whitelist = $ingo_storage->getSystemRule('Ingo_Rule_System_Whitelist');

        /* Token checking & perform requested actions. */
        switch ($this->_checkToken(array('rule_update'))) {
        case 'rule_update':
            try {
                $whitelist->addresses = $this->vars->whitelist;
                $ingo_storage->updateRule($whitelist);
                $notification->push(_("Changes saved."), 'horde.success');

                $injector->getInstance('Ingo_Factory_Script')->activateAll();
            } catch (Ingo_Exception $e) {
                $notification->push($e);
            }
            break;
        }

        /* Prepare the view. */
        $view = new Horde_View(array(
            'templatePath' => INGO_TEMPLATES . '/basic/whitelist'
        ));
        $view->addHelper('Horde_Core_View_Helper_Help');
        $view->addHelper('Horde_Core_View_Helper_Label');
        $view->addHelper('Text');

        $view->disabled = $whitelist->disable;
        $view->formurl = $this->_addToken(self::url());
        $view->whitelist = implode("\n", $whitelist->addresses);

        $page_output->addScriptFile('whitelist.js');
        $page_output->addInlineJsVars(array(
            'IngoWhitelist.filtersurl' => strval(Ingo_Basic_Filters::url()->setRaw(true))
        ));

        $this->title = _("Whitelist Edit");
        $this->output = $view->render('whitelist');
    }

    /**
     */
    public static function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'whitelist');
    }

}
