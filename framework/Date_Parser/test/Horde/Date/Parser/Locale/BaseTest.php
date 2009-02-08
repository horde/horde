<?php
/**
 * @category   Horde
 * @package    Horde_Date
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Horde_Date
 * @subpackage UnitTests
 */
class Horde_Date_Parser_Locale_BaseTest extends PHPUnit_Framework_TestCase
{
    public function testToday()
    {
        var_dump((string)Horde_Date_Parser::parse('today at 11'));
        var_dump((string)Horde_Date_Parser::parse('tomorrow'));
        var_dump((string)Horde_Date_Parser::parse('may 27'));
        var_dump((string)Horde_Date_Parser::parse('thursday'));
        var_dump((string)Horde_Date_Parser::parse('next month'));
        var_dump((string)Horde_Date_Parser::parse('last week tuesday'));
        var_dump((string)Horde_Date_Parser::parse('3 years ago'));
        var_dump((string)Horde_Date_Parser::parse('6 in the morning'));
        var_dump((string)Horde_Date_Parser::parse('afternoon yesterday'));
        var_dump((string)Horde_Date_Parser::parse('3rd wednesday in november'));
        var_dump((string)Horde_Date_Parser::parse('4th day last week'));
    }

}
