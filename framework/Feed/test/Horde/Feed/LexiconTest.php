<?php
/**
 * @category Horde
 * @package Feed
 * @subpackage UnitTests
 */
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
