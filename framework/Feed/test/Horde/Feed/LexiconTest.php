<?php
/**
 * @category Horde
 * @package Feed
 * @subpackage UnitTests
 */

/** Setup testing */
require_once __DIR__ . '/Autoload.php';

class Horde_Feed_LexiconTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getLexicon
     */
    public function testParse($file)
    {
        $feed = Horde_Feed::readFile($file);
        $this->assertGreaterThan(0, count($feed));
    }

    public static function getLexicon()
    {
        $files = array();
        foreach (new DirectoryIterator(__DIR__ . '/fixtures/lexicon') as $file) {
            if ($file->isFile()) {
                $files[] = array($file->getPathname());
            }
        }

        return $files;
    }

}
