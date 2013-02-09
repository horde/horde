<?php
/**
 * Copyright 2009-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2009-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Basic (simple) search script for basic view.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2009-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Basic_Searchbasic extends IMP_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $injector, $notification;

        if (!$this->indices->mailbox->access_search) {
            $notification->push(_("Searching is not available."), 'horde.error');
            $this->indices->mailbox->url('mailbox')->redirect();
        }

        $imp_flags = $injector->getInstance('IMP_Flags');
        $imp_search = $injector->getInstance('IMP_Search');

        /* If search_basic is set, we are processing the search query. */
        if ($this->vars->search_basic) {
            $c_list = array();

            if ($this->vars->search_criteria_text) {
                switch ($this->vars->search_criteria) {
                case 'from':
                case 'subject':
                    $c_list[] = new IMP_Search_Element_Header(
                        $this->vars->search_criteria_text,
                        $this->vars->search_criteria,
                        $this->vars->search_criteria_not
                    );
                    break;

                case 'recip':
                    $c_list[] = new IMP_Search_Element_Recipient(
                        $this->vars->search_criteria_text,
                        $this->vars->search_criteria_not
                    );
                    break;

                case 'body':
                case 'text':
                    $c_list[] = new IMP_Search_Element_Text(
                        $this->vars->search_criteria_text,
                        ($this->vars->search_criteria == 'body'),
                        $this->vars->search_criteria_not
                    );
                    break;
                }
            }

            if ($this->vars->search_criteria_flag) {
                $formdata = $imp_flags->parseFormId($this->vars->search_criteria_flag);
                $c_list[] = new IMP_Search_Element_Flag(
                    $formdata['flag'],
                    ($formdata['set'] && !$this->vars->search_criteria_flag_not)
                );
            }

            if (empty($c_list)) {
                $notification->push(_("No search criteria specified."), 'horde.error');
            } else {
                /* Store the search in the session. */
                $q_ob = $imp_search->createQuery($c_list, array(
                    'id' => IMP_Search::BASIC_SEARCH,
                    'mboxes' => array($this->indices->mailbox),
                    'type' => IMP_Search::CREATE_QUERY
                ));

                /* Redirect to the mailbox screen. */
                IMP_Mailbox::get($q_ob)->url('mailbox')->redirect();
            }
        }

        $flist = $imp_flags->getList(array(
            'imap' => true,
            'mailbox' => $this->indices->mailbox
        ));
        $flag_set = array();
        foreach ($flist as $val) {
            $flag_set[] = array(
                'val' => $val->form_set,
                'label' => $val->label
            );
        }

        /* Prepare the search template. */
        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/basic/search'
        ));
        $view->addHelper('FormTag');
        $view->addHelper('Tag');

        $view->action = self::url();
        $view->advsearch = Horde::link($this->indices->mailbox->url(IMP_Basic_Search::url()));
        $view->mbox = $this->indices->mailbox->form_to;
        $view->search_title = sprintf(_("Search %s"), $this->indices->mailbox->display_html);
        $view->flist = $flag_set;

        $this->title = _("Search");
        $this->output = $view->render('search-basic');
    }

    /**
     */
    static public function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'searchbasic');
    }

}
