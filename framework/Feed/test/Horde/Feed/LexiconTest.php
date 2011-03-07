<?php
/**
 * @category Horde
 * @package Feed
 * @subpackage UnitTests
 */

/** Horde_Feed_TestCase */
require_once dirname(__FILE__) . '/TestCase.php';

class Horde_Feed_LexiconTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getLexicon
     */
    public function testParse($file)
    {
        try {
            $feed = Horde_Feed::readFile($file);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->assertGreaterThan(0, count($feed));
    }

    public static function getLexicon()
    {
        $files = array();
        foreach (new DirectoryIterator(dirname(__FILE__) . '/fixtures/lexicon') as $file) {
            if ($file->isFile()) {
                $files[] = array($file->getPathname());
            }
        }

        return $files;
    }

}
