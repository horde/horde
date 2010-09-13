<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @package Jonah
 */
class Jonah_View_StoryPdf extends Jonah_View
{
    public function run()
    {
        extract($this->_params, EXTR_REFS);

        $driver = $GLOBALS['injector']->getInstance('Jonah_Driver');
        if (!$story_id) {
            try {
                $story_id = $GLOBALS['injector']->getInstance('Jonah_Driver')->getLatestStoryId($channel_id);
            } catch (Exception $e) {
                $this->_exit($e->getMessage());
            }
        }
        try {
            $story = $driver->getStory($channel_id, $story_id, !$browser->isRobot());
        } catch (Exception $e) {
            $this->_exit($e->getMessage());
        }

        // Convert the body from HTML to text if necessary.
        if (!empty($story['body_type']) && $story['body_type'] == 'richtext') {
            $story['body'] = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($story['body'], 'html2text');
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
        if (!empty($story['published_date'])) {
            $pdf->setFont('Times', 'B', 14);
            $pdf->cell(0, 14, $story['published_date'], 0, 1);
            $pdf->newLine(10);
        }

        // Write the header in Times 24 Bold.
        $pdf->setFont('Times', 'B', 24);
        $pdf->multiCell(0, 24, $story['title'], 'B', 1);
        $pdf->newLine(20);

        // Write the story body in Times 14.
        $pdf->setFont('Times', '', 14);
        $pdf->write(14, $story['body']);

        // Output the generated PDF.
        $browser->downloadHeaders($story['title'] . '.pdf', 'application/pdf');
        echo $pdf->getOutput();
    }

}