<?php
/**
 * News
 *
 * $Id: pdf.php 1191 2009-01-21 16:45:21Z duck $
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */

$no_compress = true;
require_once dirname(__FILE__) . '/lib/base.php';

$id = Horde_Util::getFormData('id');
$row = $news->get($id);

// Check if the news exists
if ($row instanceof PEAR_Error) {
    $notification->push($row);
    Horde::url('browse.php')->redirect();
}

// Set up the PDF object.
$pdf = new Horde_Pdf_Writer();

$pdf->setInfo('title', $row['title']);
$pdf->setInfo('author', $row['user']);
$pdf->setInfo('CreationDate', 'D:' . date('Ymdhis'));

$pdf->open();
$pdf->addPage();
$pdf->setAutoPageBreak(true);
$pdf->setFont('Arial', '', 12);

if ($row['picture']) {
    $file = $conf['vfs']['params']['vfsroot'] . '/'
            . News::VFS_PATH . '/images/news/big/'
            . $id . '.' . $conf['images']['image_type'];
    try {
        $pdf->image($file, 120, 20);
    } catch (Horde_Pdf_Exception $e) {
        Horde::logMessage($e, 'INFO');
    }
}

$pdf->setFillColor('rgb', 200/255, 220/255, 255/255);
$pdf->cell(0, 6, $row['title'], 0, 1, 'L', 1);
$pdf->newLine(4);

$pdf->write(12, _("On") . ': ' . News::dateFormat($row['publish']) . "\n");
$pdf->write(12, _("Link") . ': ' . News::getUrlFor('news', $id, true) . "\n\n", News::getUrlFor('news', $id, true));
$pdf->multiCell(0, 12, Horde_String::convertCharset(strip_tags($row['content']), $GLOBALS['registry']->getCharset(), 'UTF-8'));

$browser->downloadHeaders($id . '.pdf', 'application/pdf');
echo $pdf->getOutput();
