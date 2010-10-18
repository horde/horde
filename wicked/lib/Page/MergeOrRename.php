<?php

require_once WICKED_BASE . '/lib/Page/StandardPage.php';

/**
 * Special page for merging or renaming pages.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jason M. Felice <eraserhd@speakeasy.net>
 * @package Wicked
 */
class MergeOrRename extends Wicked_Page {

    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    var $supportedModes = array(
        Wicked::MODE_EDIT => true,
        Wicked::MODE_DISPLAY => true);

    /**
     * The page that we're displaying similar pages to.
     *
     * @var string
     */
    var $_referrer = null;

    /**
     * Validation errors.
     *
     * @var string
     */
    var $_errors = array();

    function MergeOrRename($referrer)
    {
        $this->_referrer = $referrer;
    }

    /**
     * Returns if the page allows a mode. Access rights and user state
     * are taken into consideration.
     *
     * @see $supportedModes
     *
     * @param integer $mode  The mode to check for.
     *
     * @return boolean  True if the mode is allowed.
     */
    function allows($mode)
    {
        if ($mode == Wicked::MODE_EDIT) {
            if (!parent::allows(Wicked::MODE_REMOVE)) {
                return false;
            }
            $page = Wicked_Page::getPage($this->referrer());
            if ($page->isLocked(Wicked::lockUser())) {
                return false;
            }
        }
        return parent::allows($mode);
    }

    /**
     * Retrieve this user's permissions for the referring page.
     *
     * @return integer  The permissions bitmask.
     */
    function getPermissions()
    {
        return parent::getPermissions($this->referrer());
    }

    /**
     * Renders this page in display mode.
     *
     * @throws Wicked_Exception
     */
    function display()
    {
        global $wicked, $registry, $notification;

        $template = $GLOBALS['injector']->createInstance('Horde_Template');
        $template->setOption('gettext', true);

        $referrer = $this->referrer();
        $template->set('pageName', 'MergeOrRename');
        $template->set('formAction', Wicked::url('MergeOrRename'));
        $template->set('referrer', $referrer);

        $template->set('referrerLink', Wicked::url($referrer));

        $requiredMarker = Horde::img('required.png', '*');
        $template->set('requiredMarker', $requiredMarker);

        $references = $wicked->getBackLinks($referrer);

        foreach ($references as $key => $page) {
            $references[$key]['page_url'] = htmlspecialchars(Wicked::url($page['page_name']));
            $references[$key]['page_name'] = htmlspecialchars($page['page_name']);

            // Since the page name can have [ and ] and other special
            // characters in it, and we don't want the browser or PHP decoding
            // it, we encode it in quoted printable for the checkbox names.
            $references[$key]['checkbox'] = preg_replace('/([^a-zA-Z_0-9 ])/e', '"=" . str_pad(dechex(ord(\'\\1\')), 2, \'0\', STR_PAD_LEFT)', $page['page_name']);
        }

        $template->set('references', $references);
        $template->set('referenceCount', sprintf(_("This page is referenced from %d other page(s)."), count($references)));
        $template->set('formInput', Horde_Util::formInput());

        // Propogate any validation errors.
        foreach (array('new_name', 'collision') as $elt) {
            if (!isset($this->_errors[$elt])) {
                $this->_errors[$elt] = '';
            }
        }
        $template->set('errors', $this->_errors);

        $template->set('new_name', Horde_Util::getFormData('new_name'));

        Horde::addScriptFile('stripe.js', 'horde', true);
        echo $template->fetch(WICKED_TEMPLATES . '/display/MergeOrRename.html');
        return true;
    }

    function pageName()
    {
        return 'MergeOrRename';
    }

    function pageTitle()
    {
        return sprintf(_("Merge/Rename: %s"), $this->referrer());
    }

    function referrer()
    {
        return $this->_referrer;
    }

    /**
     * Retrieve the form fields and process the merge or rename.
     */
    function handleAction()
    {
        global $wicked, $notification, $registry;

        if (Horde_Util::getFormData('submit') == _("Cancel")) {
            Wicked::url($this->referrer(), true)->redirect();
        }

        $referrer = $this->referrer();

        $new_name = Horde_Util::getFormData('new_name');
        if (empty($new_name)) {
            $this->_errors['new_name'] = _("This is a required field.");
        } elseif ($new_name == $referrer) {
            $this->_errors['new_name'] = _("New name is the same as old name.");
        }
        $collision = Horde_Util::getFormData('collision');
        if (empty($collision)) {
            $this->_errors['collision'] = _("This is a required field.");
        }

        if (count($this->_errors)) {
            return;
        }

        $sourcePage = Wicked_Page::getPage($referrer);
        if (!$this->allows(Wicked::MODE_EDIT)) {
            throw new Wicked_Exception(sprintf(_("You do not have permission to edit \"%s\""),
                                               $referrer));
        }

        $destPage = Wicked_Page::getPage($new_name);
        if (!is_a($destPage, 'AddPage')) {
            // Destination page exists.
            if ($collision != 'merge') {
                // We don't want to overwrite.
                throw new Wicked_Exception(sprintf(_("Page \"%s\" already exists."),
                                                   $new_name));
            }
            if (!$destPage->allows(Wicked::MODE_EDIT)) {
                throw new Wicked_Exception(sprintf(_("You do not have permission to edit \"%s\""),
                                            $new_name));
            }

            // Merge the two pages.
            $newText = $destPage->getText() . "\n----\n" . $sourcePage->getText();
            $changelog = sprintf(_("Merged from %s"), $referrer);
            $wicked->updateText($new_name, $newText, $changelog, true);
            $wicked->removeAllVersions($referrer);

            $notification->push(sprintf(_("Merged \"%s\" into \"%s\"."), $referrer, $new_name), 'horde.success');

            $url = Wicked::url($new_name, true, -1);
            $message = sprintf(_("Merged \"%s\" into \"%s\". New page: %s\n"), $referrer, $new_name, $url);
            Wicked::mail($message, array(
                'Subject' => '[' . $registry->get('name') . '] merged: ' . $referrer . ', ' . $new_name));
        } else {
            // Rename the page.
            $wicked->renamePage($referrer, $new_name);
            $notification->push(sprintf(_("Renamed \"%s\" to \"%s\"."), $referrer, $new_name), 'horde.success');

            $url = Wicked::url($new_name, true, -1);
            $message = sprintf(_("Renamed \"%s\" to \"%s\". New page: %s\n"), $referrer, $new_name, $url);
            Wicked::mail($message, array(
                'Subject' => '[' . $registry->get('name') . '] renamed: ' . $referrer . ', ' . $new_name));
        }

        $wikiWord = '/^' . Wicked::REGEXP_WIKIWORD . '$/';

        // We don't check permissions on these pages since we want references
        // to be fixed even if the user doing the editing couldn't fix that
        // page, and fixing references is likely to never be a destructive
        // action, and the user can't supply their own data for it.
        $references = Horde_Util::getFormData('ref', array());
        foreach ($references as $name => $value) {
            $page_name = quoted_printable_decode($name);

            // Fix up for self-references.
            if ($page_name == $referrer) {
                $page_name = $new_name;
            }

            try {
                $refPage = $wicked->retrieveByName($page_name);
            } catch (Wicked_Exception $e) {
                $notification->push(sprintf(_("Error retrieving %s: %s"),
                                            $page_name, $e->getMessage()),
                                    'horde.error');
                continue;
            }

            $changelog = sprintf(_("Changed references from %s to %s"),
                                 $referrer, $new_name);

            if (preg_match($wikiWord, $new_name)) {
                $replaceWith = $new_name;
            } else {
                $replaceWith = '((' . $new_name . '))';
            }

            $from = array('/\(\(' . preg_quote($referrer, '/') . '\)\)/');
            $to = array($replaceWith);

            // If this works as a bare wiki word, replace that, too.
            if (preg_match($wikiWord, $referrer)) {
                $from[] = '/\b' . preg_quote($referrer, '/') . '\b/';
                $to[] = $replaceWith;
            }

            $newText = preg_replace($from, $to, $refPage['page_text']);
            $wicked->updateText($page_name, $newText, $changelog, true);
        }

        Wicked::url($new_name, true)->redirect();
    }

}
