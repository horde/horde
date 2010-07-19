<?php
/**
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

function _exit($message)
{
    $GLOBALS['notification']->push(sprintf(_("Error fetching story: %s"), $message), 'horde.error');
    require JONAH_TEMPLATES . '/common-header.inc';
    $GLOBALS['notification']->notify(array('listeners' => 'status'));
    require $GLOBALS['registry']->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

require_once dirname(__FILE__) . '/../lib/Application.php';
$jonah = Horde_Registry::appInit('jonah', array(
    'authentication' => 'none',
    'session_control' => 'readonly'
));

$news = Jonah_News::factory();

$channel_id = Horde_Util::getFormData('channel_id');
$story_id = Horde_Util::getFormData('story_id');
if (!$story_id) {
    $story_id = $news->getLatestStoryId($channel_id);
    if (is_a($story_id, 'PEAR_Error')) {
        _exit($story_id->getMessage());
    }
}

$story = $news->getStory($channel_id, $story_id, !$browser->isRobot());
if (is_a($story, 'PEAR_Error')) {
    _exit($story->getMessage());
} elseif (empty($story['story_body']) && !empty($story['story_url'])) {
    _exit(_("Cannot generate PDFs of remote stories."));
}

// Convert the body from HTML to text if necessary.
if (!empty($story['story_body_type']) && $story['story_body_type'] == 'richtext') {
    $story['story_body'] = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($story['story_body'], 'html2text');
}

// Set up the PDF object.
$pdf = File_PDF::factory(array('format' => 'Letter', 'unit' => 'pt'));
$pdf->setMargins(50, 50);

// Enable automatic page breaks.
$pdf->setAutoPageBreak(true, 50);

// Start the document.
$pdf->open();

// Start a page.
$pdf->addPage();

// Publication date.
if (!empty($story['story_published_date'])) {
    $pdf->setFont('Times', 'B', 14);
    $pdf->cell(0, 14, $story['story_published_date'], 0, 1);
    $pdf->newLine(10);
}

// Write the header in Times 24 Bold.
$pdf->setFont('Times', 'B', 24);
$pdf->multiCell(0, 24, $story['story_title'], 'B', 1);
$pdf->newLine(20);

// Write the story body in Times 14.
$pdf->setFont('Times', '', 14);
$pdf->write(14, $story['story_body']);

// Output the generated PDF.
$browser->downloadHeaders($story['story_title'] . '.pdf', 'application/pdf');
echo $pdf->getOutput();
