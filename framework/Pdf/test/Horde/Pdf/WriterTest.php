<?php
/**
 * Horde_Pdf test suite
 *
 * @license    http://www.horde.org/licenses/lgpl21
 * @category   Horde
 * @package    Pdf
 * @subpackage UnitTests
 */

/**
 * Horde_Pdf_test suite
 *
 * @category   Horde
 * @package    Pdf
 * @subpackage UnitTests
 */
class Horde_Pdf_WriterTest extends PHPUnit_Framework_TestCase
{
    public function testFactoryWithOptions()
    {
        $options = array('orientation' => 'L', 'unit' => 'pt', 'format' => 'A3');
        $pdf = new Horde_Pdf_Writer($options);

        $this->assertEquals('L',     $pdf->getDefaultOrientation());
        $this->assertEquals(841.89,  $pdf->getFormatHeight());
        $this->assertEquals(1190.55, $pdf->getFormatWidth());
    }

    public function testFactoryWithDefaults()
    {
        $pdf = new Horde_Pdf_Writer();

        $this->assertEquals('P',     $pdf->getDefaultOrientation());
        $this->assertTrue(abs($pdf->getScale() - 2.8346456692913) < 0.000001);
        $this->assertEquals(595.28,  $pdf->getFormatHeight());
        $this->assertEquals(841.89,  $pdf->getFormatWidth());
    }

    public function testHelloWorldUncompressed()
    {
        $pdf = new Horde_Pdf_Writer(array('orientation' => 'P', 'format' => 'A4'));
        $pdf->setInfo('CreationDate', $this->fixtureCreationDate());
        $pdf->open();
        $pdf->setCompression(false);
        $pdf->addPage();
        $pdf->setFont('Courier', '', 8);
        $pdf->text(100, 100, 'First page');
        $pdf->setFontSize(20);
        $pdf->text(100, 200, 'HELLO WORLD!');
        $pdf->addPage();
        $pdf->setFont('Arial', 'BI', 12);
        $pdf->text(100, 100, 'Second page');
        $actual = $pdf->getOutput();

        $expected = $this->fixture('hello_world_uncompressed');
        $this->assertEquals($expected, $actual);
    }

    public function testHelloWorldCompressed()
    {
        $pdf = new Horde_Pdf_Writer(array('orientation' => 'P', 'format' => 'A4'));
        $pdf->setInfo('CreationDate', $this->fixtureCreationDate());
        $pdf->open();
        $pdf->setCompression(false);
        $pdf->addPage();
        $pdf->setFont('Courier', '', 8);
        $pdf->text(100, 100, 'First page');
        $pdf->setFontSize(20);
        $pdf->text(100, 200, 'HELLO WORLD!');
        $pdf->addPage();
        $pdf->setFont('Arial', 'BI', 12);
        $pdf->text(100, 100, 'Second page');
        $actual = $pdf->getOutput();

        $expected = $this->fixture('hello_world_compressed');
        $this->assertEquals($expected, $actual);
    }

    public function testAutoBreak()
    {
        $pdf = new Horde_Pdf_Writer(array('format' => array(50, 50), 'unit' => 'pt'));
        $pdf->setInfo('CreationDate', $this->fixtureCreationDate());
        $pdf->setCompression(false);
        $pdf->setMargins(0, 0);

        $pdf->setAutoPageBreak(true);
        $pdf->open();
        $pdf->addPage();
        $pdf->setFont('Courier', '', 10);
        $pdf->write(10, "Hello\nHello\nHello\nHello\nHello\nHello\nHello\n");
        $actual = $pdf->getOutput();

        $expected = $this->fixture('auto_break');
        $this->assertEquals($expected, $actual);
    }


    public function testChangePage()
    {
        $pdf = new Horde_Pdf_Writer(array('format' => array(80, 80), 'unit' => 'pt'));
        $pdf->setInfo('CreationDate', $this->fixtureCreationDate());
        $pdf->setCompression(false);
        $pdf->setMargins(0, 0);
        $pdf->open();

        // first page
        $pdf->addPage();

        $pdf->setFont('Courier', '', 10);
        $pdf->write(10, "Hello");

        // second page
        $pdf->addPage();

        // back to first page again
        $pdf->setPage(1);
        $pdf->write(10, "Goodbye");

        // back to second page
        $pdf->setPage(2);

        $expected = $this->fixture('change_page');
        $this->assertEquals($expected, $pdf->getOutput());
    }

    public function testTextColor()
    {
        $pdf = new Horde_Pdf_Writer();
        $pdf->setInfo('CreationDate', $this->fixtureCreationDate());
        $pdf->setCompression(false);
        $pdf->open();
        $pdf->addPage();
        $pdf->setFont('Helvetica', 'B', 48);
        $pdf->setDrawColor('rgb', 50, 0, 0);
        $pdf->setTextColor('rgb', 0, 50, 0);
        $pdf->setFillColor('rgb', 0, 0, 50);
        $pdf->cell(0, 50, 'Hello Colors', 1, 0, 'C', 1);
        $actual = $pdf->getOutput();

        $expected = $this->fixture('text_color');
        $this->assertEquals($expected, $actual);
    }

    public function testTextColorUsingHex()
    {
        $pdf = new Horde_Pdf_Writer();
        $pdf->setInfo('timestamp', $this->fixtureCreationDate());
        $pdf->setCompression(false);
        $pdf->open();
        $pdf->addPage();
        $pdf->setFont('Helvetica', 'B', 48);

        $pdf->setDrawColor('hex', '#F00');
        $pdf->setTextColor('hex', '#0F0');
        $pdf->setFillColor('hex', '#00F');

        $this->assertEquals('1.000 0.000 0.000 RG', $pdf->getDrawColor());
        $this->assertEquals('0.000 1.000 0.000 rg', $pdf->getTextColor());
        $this->assertEquals('0.000 0.000 1.000 rg', $pdf->getFillColor());
    }

    public function testUnderline()
    {
        $pdf = new Horde_Pdf_Writer(array('orientation' => 'P', 'format' => 'A4'));
        $pdf->setInfo('CreationDate', $this->fixtureCreationDate());
        $pdf->open();
        $pdf->setCompression(false);
        $pdf->addPage();
        $pdf->setFont('Helvetica', 'U', 12);
        $pdf->write(15, "Underlined\n");
        $pdf->write(15, 'Horde', 'http://www.horde.org');
        $actual = $pdf->getOutput();

        $expected = $this->fixture('underline');
        $this->assertEquals($expected, $actual);
    }

    /**
     * PEAR Bug #12310
     */
    public function testHeaderFooterStyles()
    {
        $pdf = new HeaderFooterStylesPdf(array(
            'orientation' => 'P',
            'unit' => 'mm',
            'format' => 'A4',
        ));
        $pdf->setCompression(false);
        $pdf->setInfo('title', '20000 Leagues Under the Seas');
        $pdf->setInfo('author', 'Jules Verne');
        $pdf->setInfo('CreationDate', $this->fixtureCreationDate());
        $pdf->printChapter(1, 'A RUNAWAY REEF', '20k_c1.txt');
        $pdf->printChapter(2, 'THE PROS AND CONS', '20k_c2.txt');
        $actual = $pdf->getOutput();

        $expected = $this->fixture('header_footer_styles');
        $this->assertEquals($expected, $actual);
    }

    /**
     * Horde Bug #5964
     */
    public function testLinks()
    {
        $pdf = new Horde_Pdf_Writer(array('orientation' => 'P', 'format' => 'A4'));
        $pdf->setInfo('CreationDate', $this->fixtureCreationDate());
        $pdf->open();
        $pdf->setCompression(false);
        $pdf->addPage();
        $pdf->setFont('Helvetica', 'U', 12);
        $pdf->write(15, 'Horde', 'http://www.horde.org');
        $pdf->write(15, "\n");
        $link = $pdf->addLink();
        $pdf->write(15, 'here', $link);
        $pdf->addPage();
        $pdf->setLink($link);
        $pdf->image(__DIR__ . '/fixtures/horde-power1.png', 15, 15, 0, 0, '', 'http://pear.horde.org/');
        $actual = $pdf->getOutput();

        $expected = $this->fixture('links');
        $this->assertEquals($expected, $actual);
    }

    /**
     * PEAR Bug #12310
     */
    public function testCourierStyle()
    {
        $pdf = new Horde_Pdf_Writer();
        $pdf->setFont('courier', 'B', 10);
    }

    // Test Helpers

    protected function fixture($name)
    {
        $filename = __DIR__ . "/fixtures/{$name}.pdf";
        $fixture = file_get_contents($filename);

        $this->assertInternalType('string', $fixture);
        return $fixture;
    }

    protected function fixtureCreationDate()
    {
        return 'D:20071105152947';
    }

}

class HeaderFooterStylesPdf extends Horde_Pdf_Writer
{
    public function header()
    {
        $this->setFont('Arial', 'B', 15);
        $w = $this->getStringWidth($this->_info['title']) + 6;
        $this->setX((210 - $w) / 2);
        $this->setDrawColor('rgb', 0/255, 80/255, 180/255);
        $this->setFillColor('rgb', 230/255, 230/255, 0/255);
        $this->setTextColor('rgb', 220/255, 50/255, 50/255);
        $this->setLineWidth(1);
        $this->cell($w, 9, $this->_info['title'], 1, 1, 'C', 1);
        $this->newLine(10);
    }

    public function footer()
    {
        $this->setY(-15);
        $this->setFont('Arial', 'I', 8);
        $this->setTextColor('gray', 128/255);
        $this->cell(0, 10, 'Page ' . $this->getPageNo(), 0, 0, 'C');
    }

    public function chapterTitle($num, $label)
    {
        $this->setFont('Arial', '', 12);
        $this->setFillColor('rgb', 200/255, 220/255, 255/255);
        $this->cell(0, 6, "Chapter $num : $label", 0, 1, 'L', 1);
        $this->newLine(4);
    }

    public function chapterBody($file)
    {
        $filename = __DIR__ . "/fixtures/$file";
        $text = file_get_contents($filename);
        $this->setFont('Times', '', 12);
        $this->multiCell(0, 5, $text);
        $this->newLine();
        $this->setFont('', 'I');
        $this->cell(0, 5, '(end of extract)');
    }

    public function printChapter($num, $title, $file)
    {
        $this->addPage();
        $this->chapterTitle($num, $title);
        $this->chapterBody($file);
    }

}
