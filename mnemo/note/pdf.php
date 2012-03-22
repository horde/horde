<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @package Mnemo
 */
require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('mnemo');

/* Check if a passphrase has been sent. */
$passphrase = Horde_Util::getFormData('memo_passphrase');

/* We can either have a UID or a memo id and a notepad. Check for UID
 * first. */
if ($uid = Horde_Util::getFormData('uid')) {
    $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create();
    try {
        $note = $storage->getByUID($uid, $passphrase);
    } catch (Mnemo_Exception $e) {
        Horde::url('list.php', true)->redirect();
    }
    $note_id = $note['memo_id'];
    $notelist_id = $note['memolist_id'];
} else {
    /* If we aren't provided with a memo and memolist, redirect to
     * list.php. */
    $note_id = Horde_Util::getFormData('note');
    $notelist_id = Horde_Util::getFormData('notepad');
    if (!isset($note_id) || !$notelist_id) {
        Horde::url('list.php', true)->redirect();
    }

    /* Get the current memo. */
    $note = Mnemo::getMemo($notelist_id, $note_id, $passphrase);
}
try {
    $share = $GLOBALS['mnemo_shares']->getShare($notelist_id);
} catch (Horde_Share_Exception $e) {
    $notification->push(sprintf(_("There was an error viewing this notepad: %s"), $e->getMessage()), 'horde.error');
    Horde::url('list.php', true)->redirect();
}
if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
    $notification->push(sprintf(_("You do not have permission to view the notepad %s."), $share->get('name')), 'horde.error');
    Horde::url('list.php', true)->redirect();
}

/* If the requested note doesn't exist, display an error message. */
if (!$note || !isset($note['memo_id'])) {
    $notification->push(_("Note not found."), 'horde.error');
    Horde::url('list.php', true)->redirect();
}

/* Let's assume that the note content can be converted to ISO-8859-1 if this
 * is the current language's charset, as long as we don't have UTF-8 support
 * in Horde_Pdf. */
if ($GLOBALS['registry']->getLanguageCharset() == 'ISO-8859-1') {
    $note = Horde_String::convertCharset($note, 'UTF-8', 'ISO-8859-1');
}

/* Set up the PDF object. */
$pdf = new Horde_Pdf_Writer(array('format' => 'Letter', 'unit' => 'pt'));
$pdf->setMargins(50, 50);

/* Enable automatic page breaks. */
$pdf->setAutoPageBreak(true, 50);

/* Start the document. */
$pdf->open();

/* Start a page. */
$pdf->addPage();

/* Write the header in Times 24 Bold. */
$pdf->setFont('Times', 'B', 24);
$pdf->multiCell(0, 24, $note['desc'], 'B', 1);
$pdf->newLine(20);

/* Write the note body in Times 14. */
$pdf->setFont('Times', '', 14);
$pdf->write(14, $note['body']);

/* Output the generated PDF. */
$browser->downloadHeaders($note['desc'] . '.pdf', 'application/pdf');
echo $pdf->getOutput();
