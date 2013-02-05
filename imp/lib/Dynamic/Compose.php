<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Compose page for dynamic view.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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
        $compose_ajax = new IMP_Ajax_Application_Compose($imp_compose, $this->vars->type);

        $ajax_queue = $injector->getInstance('IMP_Ajax_Queue');
        $ajax_queue->compose($imp_compose);

        $compose_opts = array(
            'title' => _("New Message")
        );

        switch ($this->vars->type) {
        case 'reply':
        case 'reply_all':
        case 'reply_auto':
        case 'reply_list':
            try {
                $contents = $this->_getContents();
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
            if (count($this->indices) > 1) {
                if (!in_array($this->vars->type, array('forward_attach', 'forward_auto'))) {
                    $notification->push(_("Multiple messages can only be forwarded as attachments."), 'horde.warning');
                }

                try {
                    $subject = $compose_opts['title'] = $imp_compose->attachImapMessage($this->indices);
                } catch (IMP_Compose_Exception $e) {
                    $notification->push($e, 'horde.error');
                    break;
                }

                $show_editor = ($prefs->getValue('compose_html') && $session->get('imp', 'rteavail'));

                $onload = $compose_ajax->getBaseResponse();
                if ($show_editor) {
                    $onload->format = 'html';
                }

                $onload->header['subject'] = $subject;
            } else {
                try {
                    $contents = $this->_getContents();
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

            $ajax_queue->attachment($imp_compose, IMP_Compose::FORWARD_ATTACH);
            break;

        case 'forward_redirect':
            try {
                $imp_compose->redirectMessage($this->indices);
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
                switch ($this->vars->type) {
                case 'editasnew':
                    $result = $imp_compose->editAsNew($this->indices);
                    break;

                case 'resume':
                    $result = $imp_compose->resumeDraft($this->indices);
                    $compose_opts['resume'] = true;
                    break;

                case 'template':
                    $result = $imp_compose->useTemplate($this->indices);
                    break;

                case 'template_edit':
                    $result = $imp_compose->editTemplate($this->indices);
                    $compose_opts['template'] = true;
                    break;
                }

                $onload = $compose_ajax->getResponse($result);
                $onload->header = array_merge($header, $onload->header);

                $ajax_queue->attachment($imp_compose, $result['type']);

                if (in_array($result['type'], array(IMP_Compose::FORWARD_ATTACH, IMP_Compose::FORWARD_BOTH))) {
                    $compose_opts['fwdattach'] = true;
                }
                $show_editor = ($result['format'] == 'html');
            } catch (IMP_Compose_Exception $e) {
                $notification->push($e);
            }
            break;

        case 'new':
        case 'template_new':
        default:
            $show_editor = ($prefs->getValue('compose_html') && $session->get('imp', 'rteavail'));

            $onload = $compose_ajax->getBaseResponse();
            $onload->body = isset($args['body'])
                ? strval($args['body'])
                : '';
            $onload->header = $header;
            if ($show_editor) {
                $onload->format = 'html';
            }

            if ($this->vars->type == 'template_new') {
                $compose_opts['template'] = true;
            }
            break;
        }

        /* Attach spellchecker & auto completer. */
        $imp_ui = new IMP_Compose_Ui();
        if ($this->vars->type == 'forward_redirect') {
            $compose_opts['redirect'] = true;
            $imp_ui->attachAutoCompleter(array('redirect_to'));
        } else {
            $imp_ui->attachAutoCompleter(array('to', 'cc', 'bcc', 'redirect_to'));
            $imp_ui->attachSpellChecker();
        }


        $page_output->addInlineJsVars(array(
            'DimpCompose.onload_show' => $onload,
            'DimpCompose.tasks' => $injector->getInstance('Horde_Core_Factory_Ajax')->create('imp', $this->vars)->getTasks()
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

    /**
     * Create the IMP_Contents objects needed to create a message.
     *
     * @param Horde_Variables $vars  The variables object.
     *
     * @return IMP_Contents  The IMP_Contents object.
     * @throws IMP_Exception
     */
    protected function _getContents()
    {
        $ob = null;

        if (!is_null($this->indices)) {
            try {
                return $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create($this->indices);
            } catch (Horde_Exception $e) {}
        }

        $this->vars->buid = null;
        $this->vars->type = 'new';

        throw new IMP_Exception(_("Could not retrieve message data from the mail server."));
    }

}
