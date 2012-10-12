<?php
/**
 * Defines the AJAX interface for IMP.
 *
 * Global tasks:
 *   - msgload: (string) Indices of the messages to load in the background
 *              (IMAP sequence string; mailboxes are base64url encoded).
 *   - poll: (string) The list of mailboxes to process (JSON encoded
 *           array; mailboxes are base64url encoded). If an empty array, polls
 *           all mailboxes.
 *
 * Global parameters (in viewport parameter):
 *   - force: (integer) If set, always return viewport information if changed.
 *                     changed.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     * The mailbox (view) we are dealing with on the browser.
     *
     * @var IMP_Mailbox
     */
    public $mbox;

    /**
     * Queue object.
     *
     * @var IMP_Ajax_Queue
     */
    public $queue;

    /**
     */
    protected function _init()
    {
        global $injector, $registry;

        switch ($registry->getView()) {
        case $registry::VIEW_DYNAMIC:
            $this->addHandler('IMP_Ajax_Application_Handler_Dynamic');
            $this->addHandler('IMP_Ajax_Application_Handler_Common');
            $this->addHandler('IMP_Ajax_Application_Handler_Passphrase');
            $this->addHandler('IMP_Ajax_Application_Handler_Search');
            break;

        case $registry::VIEW_SMARTMOBILE:
            $this->addHandler('IMP_Ajax_Application_Handler_Smartmobile');
            $this->addHandler('IMP_Ajax_Application_Handler_Common');
            break;

        case $registry::VIEW_TRADITIONAL:
            $this->addHandler('IMP_Ajax_Application_Handler_Passphrase');
            $this->addHandler('IMP_Ajax_Application_Handler_Search');
            break;
        }

        $this->addHandler('IMP_Ajax_Application_Handler_ImageUnblock');
        $this->addHandler('Horde_Core_Ajax_Application_Handler_Imple');

        $this->queue = $injector->getInstance('IMP_Ajax_Queue');

        /* Bug #10462: 'view' POST parameter is base64url encoded to
         * workaround suhosin. */
        if (isset($this->_vars->view)) {
            $this->mbox = IMP_Mailbox::formFrom($this->_vars->view);
        }

        /* Make sure the viewport entry is initialized. */
        $vp = isset($this->_vars->viewport)
            ? Horde_Serialize::unserialize($this->_vars->viewport, Horde_Serialize::JSON)
            : new stdClass;
        $this->_vars->viewport = new Horde_Support_ObjectStub($vp);

        /* GLOBAL TASKS */

        /* Check for global msgload task. */
        if (isset($this->_vars->msgload)) {
            $indices = new IMP_Indices_Form($this->_vars->msgload);
            foreach ($indices as $ob) {
                foreach ($ob->uids as $val) {
                    $this->queue->message($ob->mbox, $val, true, true);
                }
            }
        }

        /* Check for global poll task. */
        if (isset($this->_vars->poll)) {
            $poll = Horde_Serialize::unserialize($this->_vars->poll, Horde_Serialize::JSON);
            if (empty($poll)) {
                $this->queue->poll($injector->getInstance('IMP_Imap_Tree')->getPollList());
            } else {
                $this->queue->poll(IMP_Mailbox::formFrom($poll));
            }
        }
    }

    /**
     */
    public function send()
    {
        $this->getTasks();
        parent::send();
    }

    /**
     * Get the list of tasks.
     *
     * @return array  Task list.
     */
    public function getTasks()
    {
        $this->queue->add($this);
        return $this->tasks;
    }

    /* Shared code between handlers. */

    /**
     * Get forward compose data.
     *
     * See the list of variables needed for checkUidvalidity(). Additional
     * variables used:
     *   - dataonly: (boolean) Only return data information (DEFAULT: false).
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - type: (string) Forward type.
     *   - uid: (string) Indices of the messages to forward (IMAP sequence
     *          string; mailboxes are base64url encoded).
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - body: (string) The body text of the message.
     *   - format: (string) Either 'text' or 'html'.
     *   - header: (array) The headers of the message.
     *   - identity: (integer) The identity ID to use for this message.
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - opts: (array) Additional options needed for DimpCompose.fillForm().
     *   - type: (string) The input 'type' value.
     */
    public function getForwardData()
    {
        global $notification;

        /* Can't open session read-only since we need to store the message
         * cache id. */

        try {
            $compose = $this->initCompose();

            $fwd_msg = $compose->compose->forwardMessage($compose->ajax->forward_map[$this->_vars->type], $compose->contents);

            if ($this->_vars->dataonly) {
                $result = $compose->ajax->getBaseResponse();
                $result->body = $fwd_msg['body'];
                $result->format = $fwd_msg['format'];
                $result->opts->atc = $compose->ajax->getAttachmentInfo($fwd_msg['type']);
            } else {
                $result = $compose->ajax->getResponse($fwd_msg);
            }
        } catch (Horde_Exception $e) {
            $notification->push($e);
            $this->checkUidvalidity();
            $result = false;
        }

        return $result;
    }

    /**
     * Initialize the objects needed to compose.
     *
     * @return object  Object with the following properties:
     *   - ajax: IMP_Ajax_Application_Compose object
     *   - compose: IMP_Compose object
     *   - contents: IMP_Contents object
     */
    public function initCompose()
    {
        global $injector;

        $ob = new stdClass;

        $ob->compose = $injector->getInstance('IMP_Factory_Compose')->create($this->_vars->imp_compose);
        $ob->ajax = new IMP_Ajax_Application_Compose($ob->compose, $this->_vars->type);

        if (!($ob->contents = $ob->compose->getContentsOb())) {
            $ob->contents = $this->_vars->uid
                ? $injector->getInstance('IMP_Factory_Contents')->create(new IMP_Indices_Form($this->_vars->uid))
                : null;
        }

        return $ob;
    }

    /**
     * Check the UID validity of the mailbox.
     *
     * See the list of variables needed for viewPortData().
     */
    public function checkUidvalidity()
    {
        try {
            $this->mbox->uidvalid;
        } catch (IMP_Exception $e) {
            $this->addTask('viewport', $this->viewPortData(true));
        }
    }

    /**
     * Generate the information necessary for a ViewPort request from/to the
     * browser.
     *
     * Variables used (contained in 'viewport' object):
     *   - applyfilter
     *   - cache
     *   - cacheid
     *   - delhide
     *   - initial
     *   - qsearch
     *   - qsearchfield
     *   - qsearchfilter
     *   - qsearchflag
     *   - qsearchflagnot
     *   - qsearchmbox
     *   - rangeslice
     *   - requestid
     *   - sortby
     *   - sortdir
     *
     * @param boolean $change  True if cache information has changed.
     *
     * @return array  See IMP_Ajax_Application_ListMessages::listMessages().
     */
    public function viewPortData($change)
    {
        $args = array(
            'change' => $change,
            'mbox' => strval($this->mbox)
        );

        $params = array(
            'applyfilter', 'cache', 'cacheid', 'delhide', 'initial', 'qsearch',
            'qsearchfield', 'qsearchfilter', 'qsearchflag', 'qsearchflagnot',
            'qsearchmbox', 'rangeslice', 'requestid', 'sortby', 'sortdir'
        );

        $vp = $this->_vars->viewport;

        foreach ($params as $val) {
            $args[$val] = $vp->$val;
        }

        if ($vp->search || $args['initial']) {
            $args += array(
                'after' => intval($vp->after),
                'before' => intval($vp->before)
            );
        }

        if ($vp->search) {
            $search = Horde_Serialize::unserialize($vp->search, Horde_Serialize::JSON);
            $args += array(
                'search_uid' => isset($search->uid) ? $search->uid : null,
                'search_unseen' => isset($search->unseen) ? $search->unseen : null
            );
        } else {
            list($slice_start, $slice_end) = explode(':', $vp->slice, 2);
            $args += array(
                'slice_start' => intval($slice_start),
                'slice_end' => intval($slice_end)
            );
        }

        return $GLOBALS['injector']->getInstance('IMP_Ajax_Application_ListMessages')->listMessages($args);
    }

    /**
     * Generate data necessary to display a message.
     *
     * See the list of variables needed for changed() and
     * checkUidvalidity().  Additional variables used:
     *   - peek: (integer) If set, don't set seen flag.
     *   - preview: (integer) If set, return preview data. Otherwise, return
     *              full data.
     *   - uid: (string) Index of the message to display (IMAP sequence
     *          string; mailbox is base64url encoded) - must be single index.
     *
     * @return object  Object with the following entries:
     *   - error: (string) On error, the error string.
     *   - errortype: (string) On error, the error type.
     *   - mbox: (string) The message mailbox (base64url encoded).
     *   - uid: (string) The message UID.
     *   - view: (string) The view ID.
     */
    public function showMessage()
    {
        $indices = new IMP_Indices_Form($this->_vars->uid);
        list($mbox, $uid) = $indices->getSingle();

        if (!$uid) {
            throw new IMP_Exception(_("Requested message not found."));
        }

        $result = new stdClass;
        $result->mbox = $mbox->form_to;
        $result->uid = $uid;
        $result->view = $this->_vars->view;

        try {
            $change = $this->changed(true);
            if (is_null($change)) {
                throw new IMP_Exception(_("Could not open mailbox."));
            }

            /* Explicitly load the message here; non-existent messages are
             * ignored when the Ajax queue is processed. */
            $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create($indices);

            $this->queue->message($mbox, $uid, $this->_vars->preview, $this->_vars->peek);
        } catch (Exception $e) {
            $result->error = $e->getMessage();
            $result->errortype = 'horde.error';

            $change = true;
        }

        if ($this->_vars->preview || $this->_vars->viewport->force) {
            if ($change) {
                $this->addTask('viewport', $this->viewPortData(true));
            } elseif ($this->mbox->cacheid_date != $this->_vars->viewport->cacheid) {
                /* Cache ID has changed due to viewing this message. So update
                 * the cacheid in the ViewPort. */
                $this->addTask('viewport', $this->viewPortOb());
            }

            if ($this->_vars->preview) {
                $this->queue->poll($mbox);
            }
        }

        return $result;
    }

    /**
     * Determine if the cache information has changed.
     *
     * Variables used:
     *   - cacheid: (string) The browser (ViewPort) cache identifier.
     *   - forceUpdate: (integer) If 1, forces an update.
     *
     * @param boolean $rw  Open mailbox as READ+WRITE?
     *
     * @return boolean  True if the server state differs from the browser
     *                  state.
     */
    public function changed($rw = null)
    {
        /* Forced viewport return. */
        if ($this->_vars->viewport->force) {
            return true;
        }

        /* Only update search mailboxes on forced refreshes. */
        if ($this->mbox->search) {
            return !empty($this->_vars->forceUpdate);
        }

        /* We know we are going to be dealing with this mailbox, so select it
         * on the IMAP server (saves some STATUS calls). */
        if (!is_null($rw)) {
            try {
                $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->openMailbox($this->mbox, $rw ? Horde_Imap_Client::OPEN_READWRITE : Horde_Imap_Client::OPEN_AUTO);
            } catch (IMP_Imap_Exception $e) {
                $e->notify();
                return null;
            }
        }

        return ($this->mbox->cacheid_date != $this->_vars->viewport->cacheid);
    }

    /**
     * Return a basic ViewPort object.
     *
     * @param IMP_Mailbox $mbox  The mailbox view of the ViewPort request.
     *                           Defaults to current view.
     *
     * @return object  The ViewPort data object.
     */
    public function viewPortOb($mbox = null)
    {
        if (is_null($mbox)) {
            $mbox = $this->mbox;
        }

        $vp = new stdClass;
        $vp->cacheid = $mbox->cacheid_date;
        $vp->view = $mbox->form_to;

        return $vp;
    }

    /**
     * Setup environment for compose actions.
     *
     * Variables used:
     *   - composeCache: (string) The IMP_Compose cache identifier.
     *   - from: (string) From address to use.
     *   - identity: (integer) The identity to use
     *
     * @return array  An array with the following values:
     *   - (object) AJAX base return object (with action and success
     *     parameters defined).
     *   - (IMP_Compose) The IMP_Compose object for the message.
     *   - (array) The list of headers for the object.
     *   - (Horde_Prefs_Identity) The identity used for the composition.
     *
     * @throws Horde_Exception
     */
    public function composeSetup()
    {
        global $injector, $prefs;

        /* Set up identity. */
        $identity = $injector->getInstance('IMP_Identity');
        if (isset($this->_vars->identity) &&
            !$prefs->isLocked('default_identity')) {
            $identity->setDefault($this->_vars->identity);
        }

        /* Set up the From address based on the identity. */
        $headers = array(
            'from' => strval($identity->getFromLine(null, $this->_vars->from))
        );

        $headers['to'] = $this->_vars->to;
        if ($prefs->getValue('compose_cc')) {
            $headers['cc'] = $this->_vars->cc;
        }
        if ($prefs->getValue('compose_bcc')) {
            $headers['bcc'] = $this->_vars->bcc;
        }
        $headers['subject'] = $this->_vars->subject;

        $imp_compose = $injector->getInstance('IMP_Factory_Compose')->create($this->_vars->composeCache);

        $result = new stdClass;
        $result->action = $this->_action;
        $result->success = 1;

        return array($result, $imp_compose, $headers, $identity);
    }

    /**
     * Processes delete message requests.
     * See the list of variables needed for viewPortData().
     *
     * @param IMP_Indices $indices  An indices object.
     * @param boolean $changed      If true, add full ViewPort information.
     * @param boolean $force        If true, forces addition of disappear
     *                              information.
     */
    public function deleteMsgs(IMP_Indices $indices, $changed, $force = false)
    {
        /* Check if we need to update thread information. */
        if (!$changed) {
            $changed = ($this->mbox->getSort()->sortby == Horde_Imap_Client::SORT_THREAD);
        }

        if ($changed) {
            $vp = $this->viewPortData(true);
            $this->addTask('viewport', $vp);
        } elseif ($force || $this->mbox->hideDeletedMsgs(true)) {
            $vp = $this->viewPortOb();

            if ($this->mbox->search) {
                $disappear = array();
                foreach ($indices as $val) {
                    foreach ($val->uids as $val2) {
                        $disappear[] = IMP_Ajax_Application_ListMessages::searchUid($val->mbox, $val2);
                    }
                }
            } else {
                $disappear = end($indices->getSingle(true));
            }
            $vp->disappear = $disappear;

            $this->addTask('viewport', $vp);
        }

        $this->queue->poll(array_keys($indices->indices()));
    }

}
