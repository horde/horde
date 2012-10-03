<?php
/**
 * Message part display page for minimal view.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl21 GPL
 * @package  IMP
 */
class IMP_Minimal_Messagepart extends IMP_Minimal_Base
{
    /**
     * URL Parameters:
     *   - atc
     *   - id
     */
    protected function _init()
    {
        global $injector;

        /* Make sure we have a valid index. */
        $imp_mailbox = IMP::mailbox()->getListOb(IMP::mailbox(true)->getIndicesOb(IMP::uid()));
        if (!$imp_mailbox->isValidIndex()) {
            IMP_Minimal_Mailbox::url()->add('a', 'm')->redirect();
        }

        $index_ob = $imp_mailbox->getIMAPIndex();
        $mailbox = $index_ob['mailbox'];
        $uid = $index_ob['uid'];

        /* Parse the message. */
        try {
            $imp_contents = $injector->getInstance('IMP_Factory_Contents')->create(new IMP_Indices($imp_mailbox));
        } catch (IMP_Exception $e) {
            IMP_Minimal_Mailbox::url(array('mailbox' => $mailbox))->add('a', 'm')->redirect();
        }

        if (isset($this->vars->atc)) {
            $summary = $imp_contents->getSummary($this->vars->atc, IMP_Contents::SUMMARY_SIZE | IMP_Contents::SUMMARY_DESCRIP | IMP_Contents::SUMMARY_DOWNLOAD);

            $this->title = _("Download Attachment");

            $this->view->descrip = $summary['description_raw'];
            $this->view->download = $summary['download_url'];
            $this->view->size = $summary['size'];
            $this->view->type = $summary['type'];
        } else {
            $this->title = _("View Attachment");

            $data = $imp_contents->renderMIMEPart($this->vars->id, $imp_contents->canDisplay($this->vars->id, IMP_Contents::RENDER_INLINE));
            $this->view->data = isset($data[$this->vars->id])
                ? $data[$this->vars->id]['data']
                : _("This part is empty.");
        }

        $this->view->self_link = IMP_Minimal_Message::url(array('mailbox' => $mailbox, 'uid' => $uid));
        $this->view->title = $this->title;

        $this->_pages[] = 'messagepart';
    }

    /**
     * @param array $opts  Options:
     *   - mailbox: (string) Mailbox of message.
     *   - uid: (string) UID of message.
     */
    static public function url(array $opts = array())
    {
        return IMP::mailbox()->url('minimal.php', $opts['uid'], $opts['mailbox'])->add('page', 'messagepart');
    }

}
