<?php
/**
 * Search page for minimal view.
 *
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Minimal_Search extends IMP_Minimal_Base
{
    /**
     * URL Parameters:
     *   - a: (string) Action ID.
     *   - checkbox: TODO
     *   - mt: TODO
     *   - p: (integer) Page.
     *   - search: (sring) The search string
     *   - start: (integer) Start.
     */
    protected function _init()
    {
        $this->title = sprintf(_("Search %s"), $this->indicees->mailbox->display);

        $this->view->mailbox = $this->indices->mailbox->form_to;
        $this->view->menu = $this->getMenu('search');
        $this->view->title = $this->title;
        $this->view->url = IMP_Minimal_Mailbox::url();

        $this->_pages[] = 'search';
        $this->_pages[] = 'menu';
    }

    /**
     * @param array $opts  Options:
     *   - mailbox: (string) The mailbox to search. Defaults to INBOX.
     */
    static public function url(array $opts = array())
    {
        $opts = array_merge(array('mailbox' => 'INBOX'), $opts);

        return IMP_Mailbox::get($opts['mailbox'])->url('minimal')->add('page', 'search');
    }

}
