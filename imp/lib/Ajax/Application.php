<?php
/**
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
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
 * @copyright 2010-2014 Horde LLC
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

        $this->queue = $injector->getInstance('IMP_Ajax_Queue');

        switch ($registry->getView()) {
        case $registry::VIEW_BASIC:
            $this->addHandler('IMP_Ajax_Application_Handler_Mboxtoggle');
            $this->addHandler('IMP_Ajax_Application_Handler_Passphrase');
            $this->addHandler('IMP_Ajax_Application_Handler_Search');
            if ($injector->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_REMOTE)) {
                $this->addHandler('IMP_Ajax_Application_Handler_RemotePrefs');
            }
            break;

        case $registry::VIEW_DYNAMIC:
            $this->addHandler('IMP_Ajax_Application_Handler_Common');
            $this->addHandler('IMP_Ajax_Application_Handler_ComposeAttach');
            $this->addHandler('IMP_Ajax_Application_Handler_Draft');
            $this->addHandler('IMP_Ajax_Application_Handler_Dynamic');
            $this->addHandler('IMP_Ajax_Application_Handler_Mboxtoggle');
            $this->addHandler('IMP_Ajax_Application_Handler_Passphrase');
            $this->addHandler('IMP_Ajax_Application_Handler_Search');
            if ($injector->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_REMOTE)) {
                $this->addHandler('IMP_Ajax_Application_Handler_Remote');
                $this->addHandler('IMP_Ajax_Application_Handler_RemotePrefs');
            }
            break;

        case $registry::VIEW_SMARTMOBILE:
            $this->addHandler('IMP_Ajax_Application_Handler_Common');
            $this->addHandler('IMP_Ajax_Application_Handler_ComposeAttach');
            $this->addHandler('IMP_Ajax_Application_Handler_Draft')->disabled = array(
                'autoSaveDraft',
                'saveTemplate'
            );
            $this->addHandler('IMP_Ajax_Application_Handler_Smartmobile');
            break;
        }

        $this->addHandler('IMP_Ajax_Application_Handler_ImageUnblock');
        $this->addHandler('Horde_Core_Ajax_Application_Handler_Imple');
        $this->addHandler('Horde_Core_Ajax_Application_Handler_Prefs');

        /* Copy 'view' paramter to 'mailbox', because this is what
         * IMP_Indices_Mailbox expects. */
        if (isset($this->_vars->view)) {
            $this->_vars->mailbox = $this->_vars->view;
        }

        $this->indices = new IMP_Indices_Mailbox($this->_vars);

        /* Make sure the viewport entry is initialized. */
        $vp = isset($this->_vars->viewport)
            ? json_decode($this->_vars->viewport)
            : new stdClass;
        $this->_vars->viewport = new Horde_Support_ObjectStub($vp);

        /* GLOBAL TASKS */

        /* Check for global msgload task. */
        if (isset($this->_vars->msgload)) {
            $this->queue->message($this->indices->mailbox->fromBuids(array($this->_vars->msgload)), true, true);
        }

        /* Check for global poll task. */
        if (isset($this->_vars->poll)) {
            $poll = json_decode($this->_vars->poll);
            $this->queue->poll(
                empty($poll)
                    ? $injector->getInstance('IMP_Ftree')->poll->getPollList()
                    : IMP_Mailbox::formFrom($poll),
                true
            );
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

        /* Convert viewport to output format. */
        $name = 'imp:viewport';
        if (isset($this->tasks->$name)) {
            $this->tasks->$name = $this->tasks->$name->toObject();
        }

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
     * @return IMP_Ajax_Application_Viewport  Viewport data object.
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
            $search = json_decode($vp->search);
            $args += array(
                'search_buid' => isset($search->buid) ? $search->buid : null,
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
                $this->indices->mailbox->imp_imap->openMailbox($this->indices->mailbox, $rw ? Horde_Imap_Client::OPEN_READWRITE : Horde_Imap_Client::OPEN_AUTO);
            } catch (IMP_Imap_Exception $e) {
                $e->notify();
                return null;
            }
        }

        return ($this->indices->mailbox->cacheid_date != $this->_vars->viewport->cacheid);
    }

    /**
     * Setup environment for compose actions.
     *
     * Variables used:
     *   - bcc: (string) Bcc address to use.
     *   - bcc_ac: (string) Bcc address to use (autocompleter JSON data).
     *   - cc: (string) Cc address to use.
     *   - cc_ac: (string) Cc address to use (autocompleter JSON data).
     *   - composeCache: (string) The IMP_Compose cache identifier.
     *   - from: (string) From address to use.
     *   - from_ac: (string) From address to use (autocompleter JSON data).
     *   - identity: (integer) The identity to use
     *   - redirect_to: (string) To address to use (for redirect).
     *   - redirect_to_ac: (string) To address to use (for redirect)
     *                     (autocompleter JSON data).
     *   - to: (string) To address to use.
     *   - to_ac: (string) To address to use (autocompleter JSON data).
     *
     * @param string $action  AJAX action.
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

        $addr = $this->getAddrFields();

        $headers = array(
            /* Set up the From address based on the identity. */
            'from' => strval($identity->getFromLine(null, $this->_vars->from)),
            'to' => implode(',', $addr['to']['addr']),
            'cc' => implode(',', $addr['cc']['addr']),
            'bcc' => implode(',', $addr['bcc']['addr']),
            'redirect_to' => implode(',', $addr['redirect_to']['addr']),
            'subject' => $this->_vars->subject
        );

        $imp_compose = $injector->getInstance('IMP_Factory_Compose')->create($this->_vars->composeCache);

        $result = new stdClass;
        $result->action = $action;
        $result->success = 1;

        return array($result, $imp_compose, $headers, $identity);
    }

    /**
     * Return address field data from the browser form.
     *
     * @return array  Keys are header names, values are arrays with two keys:
     *   - addr: (array) List of addresses.
     *   - map: (boolean) If true, addr keys are autocomplete IDs.
     */
    public function getAddrFields()
    {
        $out = array();

        foreach (array('to', 'cc', 'bcc', 'redirect_to') as $val) {
            $data = $this->_vars->get($val);
            $data_ac = $this->_vars->get($val . '_ac');
            $tmp = array(
                'addr' => array(),
                'map' => false
            );

            if (strlen($data)) {
                if (strlen($data_ac)) {
                    $tmp['map'] = true;
                    foreach (json_decode($data_ac, true) as $val2) {
                        $tmp['addr'][$val2[1]] = $val2[0];
                    }
                } else {
                    $tmp['addr'][] = $data;
                }
            }

            $out[$val] = $tmp;
        }

        return $out;
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
            $this->addTask('viewport', $this->viewPortData(true));
        } elseif (($indices instanceof IMP_Indices_Mailbox) &&
                  ($force || $this->indices->mailbox->hideDeletedMsgs(true))) {
            $vp = new IMP_Ajax_Application_Viewport($this->indices->mailbox);
            $vp->disappear = $indices->buids[strval($this->indices->mailbox)];
            $this->addTask('viewport', $vp);
        }

        $this->queue->poll(array_keys($indices->indices()));
    }

}
