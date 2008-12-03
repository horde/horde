<?php
/**
 * News
 *
 * Copyright 2006 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: pdf.php 183 2008-01-06 17:39:50Z duck $
 *
 * @author Duck <duck@obala.net>
 * @package News
 */

define('NEWS_BASE', dirname(__FILE__));
require_once NEWS_BASE . '/lib/base.php';
require_once 'File/PDF.php';

$id = Util::getFormData('id');
$row = $news->get($id);

// check if the news eyists
if ($row instanceof PEAR_Error) {
    $notification->push($row->getMessage(), 'horde.error');
    header('Location: ' . Horde::applicationUrl('browse.php'));
    exit;
}

/* Set up the PDF object. */
$pdf = File_PDF::factory(array('orientation' => 'P', 'unit' => 'mm', 'format' => 'A4'));
$pdf->setMargins(5, 5, 20);

/* Enable automatic page breaks. */
$pdf->setAutoPageBreak(true, 50);

/* Start the document. */
$pdf->open();

/* Start a page. */
$pdf->addPage();

/* Write the header in Times 24 Bold. */
@$pdf->setFont('timesce', '', 20);
$pdf->multiCell(0, 20, $row['title']);
$pdf->newLine(1);

/* News data */
$news_url = Util::addParameter(Horde::applicationUrl('news.php', true), 'id', $id);
$body = _("On") . ': ' . $news->dateFormat($row['publish']) . "\n"
       . _("Link") . ': ' . $news_url . "\n"
      . iconv('utf-8', 'cp1250', strip_tags($row['content']));

/* Write the note body in Times 14. */
$pdf->setFont('timesce', '', 12);
$pdf->write(12, $body);

/* Output the generated PDF. */
$browser->downloadHeaders($id . '.pdf', 'application/pdf');
echo $pdf->getOutput();
