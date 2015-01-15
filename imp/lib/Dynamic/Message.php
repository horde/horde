<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Message page for dynamic view.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Dynamic_Message extends IMP_Dynamic_Base
{
    /**
     * @throws IMP_Exception
     */
    protected function _init()
    {
        global $conf, $injector, $notification, $page_output;

        if (!$this->indices) {
            throw new IMP_Exception(_("No message index given."));
        }

        $page_output->addScriptFile('message.js');
        $page_output->addScriptFile('external/CustomElements.js');
        $page_output->addScriptFile('external/time-elements.js');
        $page_output->addScriptFile('textarearesize.js', 'horde');
        $page_output->addScriptFile('toggle_quotes.js', 'horde');

        $page_output->addScriptPackage('IMP_Script_Package_Imp');

        $page_output->addThemeStylesheet('message.css');
        $page_output->addThemeStylesheet('message_view.css');

        $js_vars = array();

        switch ($this->vars->actionID) {
        case 'strip_attachment':
            try {
                $this->indices = new IMP_Indices_Mailbox(
                    $this->indices->mailbox,
                    $this->indices->stripPart($this->vars->id)
                );
                $js_vars['-ImpMessage.strip'] = 1;
                $notification->push(_("Attachment successfully stripped."), 'horde.success');
            } catch (IMP_Exception $e) {
                $notification->push($e);
            }
            break;
        }

        try {
            $show_msg = new IMP_Contents_Message($this->indices);
            $msg_res = $show_msg->showMessage();
        } catch (IMP_Exception $e) {
            $notification->notify(array(
                'listeners' => array('status', 'audio')
            ));
            echo Horde::wrapInlineScript(array(
                'parent.close()'
            ));
            exit;
        }

        /* Add 'maillog' and 'poll' data to the AJAX queue. */
        $ajax_queue = $injector->getInstance('IMP_Ajax_Queue');
        $ajax_queue->maillog($this->indices);
        $ajax_queue->poll($this->indices->mailbox);

        list(,$buid) = $this->indices->buids->getSingle();

        /* Need to be dynamically added, since formatting needs to be applied
         * via javascript. */
        foreach (array('from', 'to', 'cc', 'bcc') as $val) {
            if ($tmp = $show_msg->getAddressHeader($val)) {
                $js_vars['ImpMessage.' . $val] = $tmp;
            }
        }

        if (isset($msg_res['log'])) {
            $js_vars['ImpMessage.log'] = $msg_res['log'];
        }

        $list_info = $show_msg->contents->getListInformation();
        if (!empty($list_info['exists'])) {
            $js_vars['ImpMessage.reply_list'] = true;
            $this->view->listinfo = Horde::popupJs(
                IMP_Basic_Listinfo::url(array(
                    'buid' => $buid,
                    'mailbox' => $this->indices->mailbox
                )), array(
                    'urlencode' => true
                )
            );
        }
        $js_vars['ImpMessage.buid'] = $buid;
        $js_vars['ImpMessage.mbox'] = $this->indices->mailbox->form_to;
        if (isset($msg_res['atc'])) {
            $js_vars['ImpMessage.msg_atc'] = $msg_res['atc'];
            $this->js_text += array(
                'atc_downloadall' => _("Download All (%s)")
            );
        }
        if (isset($msg_res['md'])) {
            $js_vars['ImpMessage.msg_md'] = $msg_res['md'];
        }
        $js_vars['ImpMessage.tasks'] = $injector->getInstance('Horde_Core_Factory_Ajax')->create('imp', $this->vars)->getTasks();

        $page_output->addInlineJsVars($js_vars);
        if (isset($msg_res['js'])) {
            $page_output->addInlineScript(array_filter($msg_res['js']), true);
        }

        $this->_pages[] = 'message';

        $subject = $show_msg->getSubject();
        $this->view->subject = isset($subject['subjectlink'])
            ? $subject['subjectlink']
            : $subject['subject'];
        $this->title = $subject['title'];

        /* Determine if compose mode is disabled. */
        if (IMP_Compose::canCompose()) {
            $this->view->qreply = $injector
                ->getInstance('IMP_Dynamic_Compose_Common')
                ->compose(
                    $this,
                    array(
                        'title' => _("Message") . ': ' . $subject['subject']
                    )
                );

            $this->_pages[] = 'qreply';

            $this->js_conf['qreply'] = 1;
        }

        $page_output->noDnsPrefetch();

        $this->view->show_delete = $this->indices->mailbox->access_deletemsgs;

        list($real_mbox,) = $this->indices->getSingle();
        $this->view->show_innocent = $real_mbox->innocent_show;
        $this->view->show_spam = $real_mbox->spam_show;

        $this->view->show_view_all = empty($msg_res['onepart']);
        $this->view->show_view_source = !empty($conf['user']['allow_view_source']);

        $this->view->save_as = $show_msg->getSaveAs();

        if ($date = $show_msg->getDateOb()) {
            $this->view->datestamp = $date->format($date::DATE_ISO_8601);
            $this->view->fulldate = $date->format($date::DATE_FORCE);
            $this->view->localdate = $date->format($date::DATE_LOCAL);
            $this->view->addHelper('Text');
        }

        if ($this->view->user_hdrs = $show_msg->getUserHeaders()) {
            $this->view->addHelper('Text');
        }

        $this->view->msgtext = $msg_res['msgtext'];

        Horde::startBuffer();
        $notification->notify(array(
            'listeners' => array('status', 'audio')
        ));
        $this->view->status = Horde::endBuffer();

        $this->view->title = $this->title;
    }

    /**
     */
    public static function url(array $opts = array())
    {
        return Horde::url('dynamic.php')->add('page', 'message');
    }

}
