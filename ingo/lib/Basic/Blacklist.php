<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Blacklist page.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Basic_Blacklist extends Ingo_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $injector, $notification, $page_output;

        $this->_assertCategory('Ingo_Rule_System_Blacklist', _("Blacklist"));

        $ingo_script = $injector->getInstance('Ingo_Factory_Script')->create(Ingo::RULE_BLACKLIST);
        $ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();
        $flagonly = ($ingo_script && in_array('Ingo_Rule_User_FlagOnly', $ingo_script->availableActions()));

        /* Token checking & perform requested actions. */
        switch ($this->_checkToken(array('rule_update'))) {
        case 'rule_update':
            switch ($this->vars->action) {
            case 'delete':
                $folder = '';
                break;

            case 'mark':
                $folder = Ingo_Rule_System_Blacklist::DELETE_MARKER;
                break;

            case 'folder':
                $folder = $this->validateMbox('actionvalue');
                break;

            default:
                $folder = null;
                break;
            }

            if (!$flagonly &&
                ($folder == Ingo_Rule_System_Blacklist::DELETE_MARKER)) {
                $notification->push("Not supported by this script generator.", 'horde.error');
            } else {
                try {
                    $bl = $ingo_storage->getSystemRule('Ingo_Rule_System_Blacklist');
                    $bl->addresses = $this->vars->blacklist;
                    $bl->mailbox = $folder;
                    $ingo_storage->updateRule($bl);
                    $notification->push(_("Changes saved."), 'horde.success');
                    Ingo_Script_Util::update();
                } catch (Ingo_Exception $e) {
                    $notification->push($e, $e->getCode());
                }
            }
            break;
        }

        /* Get the blacklist object. */
        $bl = $ingo_storage->getSystemRule('Ingo_Rule_System_Blacklist');

        /* Create the folder listing. */
        $folder_list = Ingo_Flist::select($bl->mailbox, 'actionvalue');

        /* Prepare the view. */
        $view = new Horde_View(array(
            'templatePath' => INGO_TEMPLATES . '/basic/blacklist'
        ));
        $view->addHelper('Horde_Core_View_Helper_Help');
        $view->addHelper('Horde_Core_View_Helper_Label');
        $view->addHelper('FormTag');
        $view->addHelper('Tag');
        $view->addHelper('Text');

        $view->blacklist = implode("\n", $bl->addresses);
        $view->disabled = $bl->disable;
        $view->flagonly = $flagonly;
        $view->folder = $bl->mailbox;
        $view->folderlist = $folder_list;
        $view->formurl = $this->_addToken(self::url());

        $page_output->addScriptFile('blacklist.js');
        $page_output->addInlineJsVars(array(
            'IngoBlacklist.filtersurl' => strval(Ingo_Basic_Filters::url()->setRaw(true))
        ));

        $this->header = _("Blacklist Edit");
        $this->output = $view->render('blacklist');
    }

    /**
     */
    public static function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'blacklist');
    }

}
