<?php

/** StandardPage:: */
require_once WICKED_BASE . '/lib/Page/StandardPage.php';

/**
 * Wicked AttachedFiles class.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Wicked
 */
class AttachedFiles extends Wicked_Page {

    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    var $supportedModes = array(
        Wicked::MODE_CONTENT => true,
        Wicked::MODE_EDIT => true,
        Wicked::MODE_REMOVE => true,
        Wicked::MODE_DISPLAY => true);

    /**
     * The page for which we'd like to manipulate attachments.
     *
     * @var string
     */
    var $_referrer = null;

    /**
     * Constructor.
     */
    function AttachedFiles($referrer)
    {
        $this->_referrer = $referrer;
    }

    /**
     * Returns the current user's permissions for the referring page.
     *
     * @return integer  The permissions bitmask.
     */
    function getPermissions()
    {
        return parent::getPermissions($this->referrer());
    }

    /**
     * Returns this page rendered in Content mode.
     *
     * @return string  The page content, or PEAR_Error.
     */
    function content()
    {
        global $wicked, $notification;

        if (!$wicked->pageExists($this->referrer())) {
            $error = sprintf(_("Referrer \"%s\" does not exist."),
                             $this->referrer());
            $notification->push($error, 'horde.error');
            return PEAR::raiseError($error);
        }

        $referrer_id = $wicked->getPageId($this->referrer());

        $attachments = $wicked->getAttachedFiles($referrer_id, true);
        if (is_a($attachments, 'PEAR_Error')) {
            return $attachments;
        }

        foreach ($attachments as $idx => $attach) {
            $attachments[$idx]['date'] = date('M j, Y g:ia',
                                              $attach['attachment_created']);

            $attachments[$idx]['url'] = Horde::downloadUrl(
                $attach['attachment_name'],
                array('page' => $referrer_id,
                      'file' => $attach['attachment_name'],
                      'version' => $attach['attachment_majorversion'] . '.'
                                   . $attach['attachment_minorversion']));

            $attachments[$idx]['delete_form'] = $this->allows(Wicked::MODE_REMOVE);

            $this->_page['change_author'] = $attachments[$idx]['change_author'];
            $attachments[$idx]['change_author'] = $this->author();
        }

        return $attachments;
    }

    /**
     * Returns this page rendered in Display mode.
     *
     * @return mixed  Returns true or PEAR_Error.
     */
    function display()
    {
        global $registry, $wicked, $notification, $conf;

        $attachments = $this->content();
        if (is_a($attachments, 'PEAR_Error')) {
            $notification->push(sprintf(_("Error retrieving attachments: %s"),
                                        $attachments->getMessage()),
                                'horde.error');
            return $attachments;
        }

        $template = $GLOBALS['injector']->createInstance('Horde_Template');

        $template->setOption('gettext', true);
        $template->set('pageName', $this->pageName());
        $template->set('formAction', Wicked::url('AttachedFiles'));
        $template->set('deleteButton', Horde_Themes::img('delete.png'));
        $template->set('referrerLink', Wicked::url($this->referrer()));

        $refreshIcon = Horde::link($this->pageUrl())
            . Horde::img('reload.png',
                         sprintf(_("Reload \"%s\""), $this->pageTitle()))
            . '</a>';
        $template->set('refreshIcon', $refreshIcon);
        $template->set('attachments', $attachments, true);

        /* Get an array of unique filenames for the update form. */
        $files = array();
        foreach ($attachments as $attachment) {
            $files[$attachment['attachment_name']] = true;
        }
        $files = array_keys($files);
        sort($files);
        $template->set('files', $files);
        $template->set('canUpdate',
                       $this->allows(Wicked::MODE_EDIT) && count($files),
                       true);
        $template->set('canAttach', $this->allows(Wicked::MODE_EDIT), true);
        if ($conf['wicked']['require_change_log']) {
            $template->set('requireChangelog', true, true);
        } else {
            $template->set('requireChangelog', false, true);
        }

        $requiredMarker = Horde::img('required.png', '*');
        $template->set('requiredMarker', $requiredMarker);
        $template->set('referrer', $this->referrer());
        $template->set('formInput', Horde_Util::formInput());

        Horde::addScriptFile('stripe.js', 'horde', true);
        echo $template->fetch(WICKED_TEMPLATES . '/display/AttachedFiles.html');
        return true;
    }

    function pageName()
    {
        return 'AttachedFiles';
    }

    function pageTitle()
    {
        return sprintf(_("Attached Files: %s"), $this->referrer());
    }

    function referrer()
    {
        return $this->_referrer;
    }

    /**
     * Retrieves the form fields and processes the attachment.
     */
    function handleAction()
    {
        global $notification, $wicked, $registry, $conf;

        // Only allow POST commands.
        $cmd = Horde_Util::getPost('cmd');
        $version = Horde_Util::getFormData('version');
        $is_update = (bool)Horde_Util::getFormData('is_update');
        $filename = Horde_Util::getFormData('filename');
        $change_log = Horde_Util::getFormData('change_log');

        // See if we're supposed to delete an attachment.
        if ($cmd == 'delete' && $filename && $version) {
            if (!$this->allows(Wicked::MODE_REMOVE)) {
                $notification->push(_("You do not have permission to delete attachments from this page."), 'horde.error');
                return;
            }

            $result = $wicked->removeAttachment(
                $wicked->getPageId($this->referrer()),
                $filename, $version);
            if (is_a($result, 'PEAR_Error')) {
                $notification->push($result->getMessage(), 'horde.error');
            } else {
                $notification->push(
                    sprintf(_("Successfully deleted version %s of \"%s\" from \"%s\""),
                            $version, $filename, $this->referrer()),
                    'horde.success');
            }
            return;
        }

        if (empty($filename)) {
            $filename = Horde_Util::dispelMagicQuotes($_FILES['attachment_file']['name']);
        }

        try {
            $GLOBALS['browser']->wasFileUploaded('attachment_file', _("attachment"));
        } catch (Horde_Browser_Exception $e) {
            $notification->push($e, 'horde.error');
            return;
        }

        if (strpos($filename, ' ') !== false) {
            $notification->push(
                _("Attachments with spaces can't be embedded into a page."),
                'horde.warning');
        }

        $data = file_get_contents($_FILES['attachment_file']['tmp_name']);
        if ($data === false) {
            $notification->push(_("Can't read uploaded file."), 'horde.error');
            return;
        }

        if (!$this->allows(Wicked::MODE_EDIT)) {
            $notification->push(
                sprintf(_("You do not have permission to edit \"%s\""),
                        $this->referrer()),
                'horde.error');
            return;
        }

        if ($conf['wicked']['require_change_log'] && empty($change_log)) {
            $notification->push(
                _("You must enter a change description to attach this file."),
                'horde.error');
            return;
        }

        $referrer_id = $wicked->getPageId($this->referrer());
        $attachments = $wicked->getAttachedFiles($referrer_id);
        if (is_a($attachments, 'PEAR_Error')) {
            $notification->push(sprintf(_("Error retrieving attachments: %s"),
                                        $attachments->getMessage()),
                                'horde.error');
            return;
        }

        $found = false;
        foreach ($attachments as $attach) {
            if ($filename == $attach['attachment_name']) {
                $found = true;
                break;
            }
        }

        $minor_change = false;
        if ($is_update) {
            if (!$found) {
                $notification->push(
                    sprintf(_("Can't update \"%s\": no such attachment."),
                            $filename),
                    'horde.error');
                return;
            }
            $minor_change = Horde_Util::getFormData('minor_change');
        } else {
            if ($found) {
                $notification->push(
                    sprintf(_("There is already an attachment named \"%s\"."),
                            $filename),
                    'horde.error');
                return;
            }
        }

        $file = array('page_id'         => $referrer_id,
                      'attachment_name' => $filename,
                      'minor'           => $minor_change,
                      'change_log'      => $change_log);

        $result = $wicked->attachFile($file, $data);
        if (is_a($result, 'PEAR_Error')) {
            $notification->push($result, 'horde.error');
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        if ($is_update) {
            $message = sprintf(_("Updated attachment \"%s\" on page \"%s\"."),
                               $filename, $this->referrer());
        } else {
            $message = sprintf(_("New attachment \"%s\" to page \"%s\"."),
                               $filename, $this->referrer());
        }
        $notification->push($message, 'horde.success');

        $url = Wicked::url($this->referrer(), true, -1);
        Wicked::mail($message . ' ' . _("View page: ") . $url . "\n",
                     array('Subject' => '[' . $registry->get('name')
                           . '] attachment: ' . $this->referrer() . ', '
                           . $filename));
    }

}
