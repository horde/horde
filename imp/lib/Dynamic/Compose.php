<?php
/**
 * Compose page for dynamic view.
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
class IMP_Dynamic_Compose extends IMP_Dynamic_Base
{
    /**
     * URL parameters:
     *   - bcc: BCC addresses.
     *   - bcc_json: JSON encoded addresses to send to. Overwrites 'bcc'.
     *   - body: Message body text.
     *   - cc: CC addresses.
     *   - cc_json: JSON encoded addresses to send to. Overwrites 'cc'.
     *   - identity: Force message to use this identity by default.
     *   - subject: Subject to use.
     *   - type: redirect, reply, reply_auto, reply_all, reply_list,
     *           forward_attach, forward_auto, forward_body, forward_both,
     *           forward_redirect, resume, new, editasnew, template,
     *           template_edit, template_new
     *   - to: Addresses to send to.
     *   - to_json: JSON encoded addresses to send to. Overwrites 'to'.
     *   - uids: UIDs of message to forward (only used when forwarding a
     *           message).
     */
    protected function _init()
    {
        global $injector, $notification, $page_output, $prefs, $session;

        /* The headers of the message. */
        $args = IMP::getComposeArgs($this->vars);
        $header = array();
        foreach (array('to', 'cc', 'bcc', 'subject') as $val) {
            if (isset($args[$val])) {
                $header[$val] = $args[$val];
            }
        }

        /* Check for JSON encoded information. */
        foreach (array('to', 'cc', 'bcc') as $val) {
            $alist = $injector->getInstance('IMP_Dynamic_AddressList');
            $var_name = $val . '_json';
            if (isset($this->vars->$var_name)) {
                $header[$val] = strval($alist->parseAddressList($this->vars->$var_name));
            }
        }

        $identity = $injector->getInstance('IMP_Identity');
        if (!$prefs->isLocked('default_identity') &&
            isset($this->vars->identity)) {
            $identity->setDefault($this->vars->identity);
        }

        /* Init objects. */
        $imp_compose = $injector->getInstance('IMP_Factory_Compose')->create();
        $imp_ui = new IMP_Ui_Compose();
        $compose_ajax = new IMP_Ajax_Application_Compose($imp_compose, $this->vars->type);

        $compose_opts = array(
            'title' => _("New Message")
        );

        switch ($this->vars->type) {
        case 'reply':
        case 'reply_all':
        case 'reply_auto':
        case 'reply_list':
            try {
                $contents = $imp_ui->getContents($this->vars);
            } catch (IMP_Compose_Exception $e) {
                $notification->push($e, 'horde.error');
                break;
            }

            $result = $imp_compose->replyMessage($compose_ajax->reply_map[$this->vars->type], $contents, array(
                'to' => isset($header['to']) ? $header['to'] : null
            ));

            $onload = $compose_ajax->getResponse($result);

            switch ($result['type']) {
            case IMP_Compose::REPLY_SENDER:
                $compose_opts['title'] = _("Reply");
                break;

            case IMP_Compose::REPLY_ALL:
                $compose_opts['title'] = _("Reply to All");
                break;

            case IMP_Compose::REPLY_LIST:
                $compose_opts['title'] = _("Reply to List");
                break;
            }
            $compose_opts['title'] .= ': ' . $result['headers']['subject'];

            $show_editor = ($result['format'] == 'html');
            break;

        case 'forward_attach':
        case 'forward_auto':
        case 'forward_body':
        case 'forward_both':
            $indices = $this->vars->uids
                ? new IMP_Indices_Form($this->vars->uids)
                : null;

            if ($indices && (count($indices) > 1)) {
                if (!in_array($this->vars->type, array('forward_attach', 'forward_auto'))) {
                    $notification->push(_("Multiple messages can only be forwarded as attachments."), 'horde.warning');
                }

                try {
                    $subject = $compose_opts['title'] = $imp_compose->attachImapMessage($indices);
                } catch (IMP_Compose_Exception $e) {
                    $notification->push($e, 'horde.error');
                    break;
                }

                $show_editor = ($prefs->getValue('compose_html') && $session->get('imp', 'rteavail'));

                $onload = $compose_ajax->getBaseResponse();
                if ($show_editor) {
                    $onload->format = 'html';
                }
                $onload->opts->atc = $compose_ajax->getAttachmentInfo(IMP_Compose::FORWARD_ATTACH);
                $onload->header['subject'] = $subject;
            } else {
                try {
                    $contents = $imp_ui->getContents($this->vars);
                } catch (IMP_Compose_Exception $e) {
                    $notification->push($e, 'horde.error');
                    break;
                }

                $result = $imp_compose->forwardMessage($compose_ajax->forward_map[$this->vars->type], $contents);
                $onload = $compose_ajax->getResponse($result);

                if (in_array($result['type'], array(IMP_Compose::FORWARD_ATTACH, IMP_Compose::FORWARD_BOTH))) {
                    $compose_opts['fwdattach'] = true;
                }
                $compose_opts['title'] = $result['headers']['title'];

                $show_editor = ($result['format'] == 'html');
            }
            break;

        case 'forward_redirect':
            try {
                $imp_compose->redirectMessage($imp_ui->getIndices($this->vars));
                $compose_opts['title'] = _("Redirect");
            } catch (IMP_Compose_Exception $e) {
                $notification->push($e, 'horde.error');
            }
            $onload = $compose_ajax->getBaseResponse();
            break;

        case 'editasnew':
        case 'resume':
        case 'template':
        case 'template_edit':
            try {
                $indices_ob = IMP::mailbox()->getIndicesOb(IMP::uid());

                switch ($this->vars->type) {
                case 'editasnew':
                    $result = $imp_compose->editAsNew($indices_ob);
                    break;

                case 'resume':
                    $result = $imp_compose->resumeDraft($indices_ob);
                    break;

                case 'template':
                    $result = $imp_compose->useTemplate($indices_ob);
                    $compose_opts['template'] = true;
                    break;

                case 'template_edit':
                    $result = $imp_compose->editTemplate($indices_ob);
                    $compose_opts['template'] = true;
                    break;
                }

                $onload = $compose_ajax->getResponse($result);
                $onload->header = array_merge($header, $onload->header);

                if (in_array($result['type'], array(IMP_Compose::FORWARD_ATTACH, IMP_Compose::FORWARD_BOTH))) {
                    $compose_opts['fwdattach'] = true;
                }
                $show_editor = ($result['format'] == 'html');
            } catch (IMP_Compose_Exception $e) {
                $notification->push($e);
            }
            break;

        case 'new':
        default:
            $show_editor = ($prefs->getValue('compose_html') && $session->get('imp', 'rteavail'));

            $onload = $compose_ajax->getBaseResponse();
            $onload->body = strval($this->vars->body);
            $onload->header = $header;
            if ($show_editor) {
                $onload->format = 'html';
            }
            break;
        }

        /* Attach spellchecker & auto completer. */
        if ($this->vars->type == 'forward_redirect') {
            $compose_opts['redirect'] = true;
            $imp_ui->attachAutoCompleter(array('redirect_to'));
        } else {
            $acomplete = array('to', 'redirect_to');
            foreach (array('cc', 'bcc') as $val) {
                if ($prefs->getValue('compose_' . $val)) {
                    $acomplete[] = $val;
                }
            }
            $imp_ui->attachAutoCompleter($acomplete);
            $imp_ui->attachSpellChecker();
        }

        $page_output->addInlineJsVars(array(
            'DimpCompose.onload_show' => $onload
        ));

        $this->title = $compose_opts['title'];
        $this->view->compose = $injector->getInstance('IMP_Dynamic_Compose_Common')->compose($this, $compose_opts);

        Horde::startBuffer();
        IMP::status();
        $this->view->status = Horde::endBuffer();

        $this->_pages[] = 'compose-base';
    }

    /**
     */
    static public function url(array $opts = array())
    {
        return Horde::url('dynamic.php')->add('page', 'compose');
    }

}
