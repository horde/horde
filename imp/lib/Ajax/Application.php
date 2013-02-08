<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Defines the AJAX interface for IMP.
 *
 * Global tasks:
 *   - msgload: (string) BUID of a message to load in the background (mailbox
 *              is located in 'mailbox' parameter).
 *   - poll: (string) The list of mailboxes to process (JSON encoded
 *           array; mailboxes are base64url encoded). If an empty array, polls
 *           all mailboxes.
 *
 * Global parameters (in viewport parameter):
 *   - force: (integer) If set, always return viewport information if changed.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     * The IMP_Indices_Mailbox object based on form data.
     *
     * @var IMP_Indices_Mailbox
     */
    public $indices;

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
        case $registry::VIEW_BASIC:
            $this->addHandler('IMP_Ajax_Application_Handler_Mboxtoggle');
            $this->addHandler('IMP_Ajax_Application_Handler_Passphrase');
            $this->addHandler('IMP_Ajax_Application_Handler_Search');
            break;

        case $registry::VIEW_DYNAMIC:
            $this->addHandler('IMP_Ajax_Application_Handler_Dynamic');
            $this->addHandler('IMP_Ajax_Application_Handler_Common');
            $this->addHandler('IMP_Ajax_Application_Handler_Mboxtoggle');
            $this->addHandler('IMP_Ajax_Application_Handler_Passphrase');
            $this->addHandler('IMP_Ajax_Application_Handler_Search');
            break;

        case $registry::VIEW_SMARTMOBILE:
            $this->addHandler('IMP_Ajax_Application_Handler_Smartmobile');
            $this->addHandler('IMP_Ajax_Application_Handler_Common');
            break;
        }

        $this->addHandler('IMP_Ajax_Application_Handler_ImageUnblock');
        $this->addHandler('Horde_Core_Ajax_Application_Handler_Imple');

        $this->queue = $injector->getInstance('IMP_Ajax_Queue');

        /* Copy 'view' paramter to 'mailbox', because this is what
         * IMP_Indices_Mailbox expects. */
        if (isset($this->_vars->view)) {
            $this->_vars->mailbox = $this->_vars->view;
        }

        $this->indices = new IMP_Indices_Mailbox($this->_vars);

        /* Make sure the viewport entry is initialized. */
        $vp = isset($this->_vars->viewport)
            ? Horde_Serialize::unserialize($this->_vars->viewport, Horde_Serialize::JSON)
            : new stdClass;
        $this->_vars->viewport = new Horde_Support_ObjectStub($vp);

        /* GLOBAL TASKS */

        /* Check for global msgload task. */
        if (isset($this->_vars->msgload)) {
            $this->queue->message($this->indices->mailbox->fromBuids(array($this->_vars->msgload)), true, true);
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

        if (!($ob->contents = $ob->compose->getContentsOb()) &&
            count($this->indices)) {
            $ob->contents = $injector->getInstance('IMP_Factory_Contents')->create($this->indices);
        }

        $this->queue->compose($ob->compose);

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
            $this->indices->mailbox->uidvalid;
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
            'mbox' => strval($this->indices->mailbox)
        );

        $params = array(
            'applyfilter', 'cache', 'cacheid', 'delhide', 'initial', 'qsearch',
            'qsearchfield', 'qsearchfilter', 'qsearchflag', 'qsearchflagnot',
            'qsearchmbox', 'rangeslice', 'sortby', 'sortdir'
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
        if ($this->indices->mailbox->search) {
            return !empty($this->_vars->forceUpdate);
        }

        if (!$this->_vars->viewport->cacheid) {
            return false;
        }

        /* We know we are going to be dealing with this mailbox, so select it
         * on the IMAP server (saves some STATUS calls). */
        if (!is_null($rw)) {
            try {
                $GLOBALS['injector']->getInstance('IMP_Imap')->openMailbox($this->indices->mailbox, $rw ? Horde_Imap_Client::OPEN_READWRITE : Horde_Imap_Client::OPEN_AUTO);
            } catch (IMP_Imap_Exception $e) {
                $e->notify();
                return null;
            }
        }

        return ($this->indices->mailbox->cacheid_date != $this->_vars->viewport->cacheid);
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
            $mbox = $this->indices->mailbox;
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
     @param string $action  AJAX action.
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
    public function composeSetup($action)
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
            'from' => strval($identity->getFromLine(null, $this->_vars->from)),
            'to' => $this->_vars->to,
            'cc' => $this->_vars->cc,
            'bcc' => $this->_vars->bcc,
            'subject' => $this->_vars->subject
        );

        $imp_compose = $injector->getInstance('IMP_Factory_Compose')->create($this->_vars->composeCache);

        $result = new stdClass;
        $result->action = $action;
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
            $changed = ($this->indices->mailbox->getSort()->sortby == Horde_Imap_Client::SORT_THREAD);
        }

        if ($changed) {
            $vp = $this->viewPortData(true);
            $this->addTask('viewport', $vp);
        } elseif (($indices instanceof IMP_Indices_Mailbox) &&
                  ($force || $this->indices->mailbox->hideDeletedMsgs(true))) {
            $vp = $this->viewPortOb();
            $vp->disappear = $indices->buids[strval($this->indices->mailbox)];
            $this->addTask('viewport', $vp);
        }

        $this->queue->poll(array_keys($indices->indices()));
    }

    /**
     * Explicitly call an action.
     *
     * @todo Move to Horde_Core_Ajax_Application
     *
     * @param string $action  The action to call.
     *
     * @return mixed  The response from the called action.
     */
    public function callAction($action)
    {
        foreach ($this->_handlers as $ob) {
            if ($ob->has($action)) {
                return call_user_func(array($ob, $action));
            }
        }
    }

}
