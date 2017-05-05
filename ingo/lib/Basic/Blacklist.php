<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Blacklist page.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2017 Horde LLC
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

        $this->_assertCategory(Ingo_Storage::ACTION_BLACKLIST, _("Blacklist"));

        $ingo_script = $injector->getInstance('Ingo_Factory_Script')->create(Ingo::RULE_BLACKLIST);
        $ingo_storage = $injector->getInstance('Ingo_Factory_Storage')->create();
        $flagonly = ($ingo_script && in_array(Ingo_Storage::ACTION_FLAGONLY, $ingo_script->availableActions()));

        /* Token checking & perform requested actions. */
        switch ($this->_checkToken(array('rule_update'))) {
        case 'rule_update':
            switch ($this->vars->action) {
            case 'delete':
                $folder = '';
                break;

            case 'mark':
                $folder = Ingo::BLACKLIST_MARKER;
                break;

            case 'folder':
                $folder = $this->validateMbox('actionvalue');
                break;

            default:
                $folder = null;
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
                    Ingo_Script_Util::update();
                } catch (Ingo_Exception $e) {
                    $notification->push($e->getMessage(), $e->getCode());
                }
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
        $blacklist_folder = $blacklist->getBlacklistFolder();
        $folder_list = Ingo_Flist::select($blacklist_folder, 'actionvalue');

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

        $features = new Ingo_Form_Base(
            null,
            '',
            null,
            $ingo_script->availableCategoryFeatures(Ingo_Storage::ACTION_BLACKLIST)
        );

        $view->actiondelete = $features->hasFeature('actiondelete');
        $view->actionmark = $features->hasFeature('actionmark');
        $view->actionfolder = $features->hasFeature('actionfolder');
        $view->actions = $view->actiondelete || $view->actionmark || $view->actionfolder;

        $view->blacklist = implode("\n", $blacklist->getBlacklist());
        $view->disabled = !empty($bl_rule['disable']);
        $view->flagonly = $flagonly;
        $view->folder = $blacklist_folder;
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
    static public function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'blacklist');
    }

}
