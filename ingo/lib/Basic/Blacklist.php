<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Blacklist page.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Basic_Blacklist extends Ingo_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $injector, $notification, $page_output, $prefs, $session;

        /* Redirect if blacklist is not available. */
        if (!in_array(Ingo_Storage::ACTION_BLACKLIST, $session->get('ingo', 'script_categories'))) {
            $notification->push(_("Blacklist is not supported in the current filtering driver."), 'horde.error');
            Ingo_Basic_Filters::url()->redirect();
        }

        $ingo_script = $injector->getInstance('Ingo_Factory_Script')->create(Ingo::RULE_BLACKLIST);
        $ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();
        $blacklist_folder = $folder = null;

        $flagonly = ($ingo_script && in_array(Ingo_Storage::ACTION_FLAGONLY, $ingo_script->availableActions()));

        /* Perform requested actions. */
        switch ($this->vars->actionID) {
        case 'rule_update':
            switch ($this->vars->action) {
            case 'delete':
                $folder = '';
                break;

            case 'mark':
                $folder = Ingo::BLACKLIST_MARKER;
                break;

            case 'folder':
                $folder = Ingo::validateFolder($this->vars, 'actionvalue');
                break;
            }

            if (!$flagonly && ($folder == Ingo::BLACKLIST_MARKER)) {
                $notification->push("Not supported by this script generator.", 'horde.error');
            } else {
                try {
                    $blacklist = Ingo::updateListFilter($this->vars->blacklist, Ingo_Storage::ACTION_BLACKLIST);
                    $blacklist->setBlacklistFolder($folder);
                    $ingo_storage->store($blacklist);
                    $notification->push(_("Changes saved."), 'horde.success');
                    if ($prefs->getValue('auto_update')) {
                        Ingo::updateScript();
                    }
                } catch (Ingo_Exception $e) {
                    $notification->push($e->getMessage(), $e->getCode());
                }

                /* Update the timestamp for the rules. */
                $session->set('ingo', 'change', time());
            }
            break;
        }

        /* Get the blacklist object. */
        if (!isset($blacklist)) {
            try {
                $blacklist = $ingo_storage->retrieve(Ingo_Storage::ACTION_BLACKLIST);
            } catch (Ingo_Exception $e) {
                $notification->push($e);
                $blacklist = new Ingo_Storage_Blacklist();
            }
        }

        /* Create the folder listing. */
        if (!isset($blacklist_folder)) {
            $blacklist_folder = $blacklist->getBlacklistFolder();
        }
        $folder_list = Ingo::flistSelect($blacklist_folder, 'actionvalue');

        /* Get the blacklist rule. */
        $bl_rule = $ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS)->findRule(Ingo_Storage::ACTION_BLACKLIST);

        /* Prepare the view. */
        $view = new Horde_View(array(
            'templatePath' => INGO_TEMPLATES . '/basic/blacklist'
        ));
        $view->addHelper('Horde_Core_View_Helper_Help');
        $view->addHelper('Horde_Core_View_Helper_Label');
        $view->addHelper('FormTag');
        $view->addHelper('Tag');
        $view->addHelper('Text');

        $view->blacklist = implode("\n", $blacklist->getBlacklist());
        $view->disabled = !empty($bl_rule['disable']);
        $view->flagonly = $flagonly;
        $view->folder = $blacklist_folder;
        $view->folderlist = $folder_list;
        $view->formurl = self::url();

        $page_output->addScriptFile('blacklist.js');
        $page_output->addInlineJsVars(array(
            'IngoBlacklist.filtersurl' => strval(Ingo_Basic_Filters::url()->setRaw(true))
        ));

        $this->header = _("Blacklist Edit");
        $this->output = $view->render('blacklist');
    }

    /**
     */
    static public function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'blacklist');
    }

}
