<?php
/**
 * @category   Horde
 * @package    Horde_Text_Textile
 * @subpackage UnitTests
 */

/** Horde_Text_Textile_TestCase */
require_once dirname(__FILE__) . '/TestCase.php';

/**
 * These tests correspond to "6. Tables" from http://hobix.com/textile/.
 *
 * @category   Horde
 * @package    Horde_Text_Textile
 * @subpackage UnitTests
 */
class Horde_Text_Textile_TablesTest extends Horde_Text_Textile_TestCase {

    public function testSimpleTable()
    {
        $text = '| name | age | sex |
| joan | 24 | f |
| archie | 29 | m |
| bella | 45 | f |';
        $html = "\t<table>
\t\t<tr>
\t\t\t<td> name </td>
\t\t\t<td> age </td>
\t\t\t<td> sex </td>
\t\t</tr>
\t\t<tr>
\t\t\t<td> joan </td>
\t\t\t<td> 24 </td>
\t\t\t<td> f </td>
\t\t</tr>
\t\t<tr>
\t\t\t<td> archie </td>
\t\t\t<td> 29 </td>
\t\t\t<td> m </td>
\t\t</tr>
\t\t<tr>
\t\t\t<td> bella </td>
\t\t\t<td> 45 </td>
\t\t\t<td> f </td>
\t\t</tr>
\t</table>";
        $this->assertTransforms($text, $html);
    }

    public function testSimpleTableHeaders()
    {
        $text = '|_. name |_. age |_. sex |
| joan | 24 | f |
| archie | 29 | m |
| bella | 45 | f |';
        $html = "\t<table>
\t\t<tr>
\t\t\t<th>name </th>
\t\t\t<th>age </th>
\t\t\t<th>sex </th>
\t\t</tr>
\t\t<tr>
\t\t\t<td> joan </td>
\t\t\t<td> 24 </td>
\t\t\t<td> f </td>
\t\t</tr>
\t\t<tr>
\t\t\t<td> archie </td>
\t\t\t<td> 29 </td>
\t\t\t<td> m </td>
\t\t</tr>
\t\t<tr>
\t\t\t<td> bella </td>
\t\t\t<td> 45 </td>
\t\t\t<td> f </td>
\t\t</tr>
\t</table>";
        $this->assertTransforms($text, $html);
    }

    public function testCellAttributes()
    {
        $text = '|_. attribute list |
|<. align left |
|>. align right|
|=. center |
|<>. justify |
|^. valign top |
|~. bottom |';
        $html = "\t<table>
\t\t<tr>
\t\t\t<th>attribute list </th>
\t\t</tr>
\t\t<tr>
\t\t\t<td style=\"text-align:left;\">align left </td>
\t\t</tr>
\t\t<tr>
\t\t\t<td style=\"text-align:right;\">align right</td>
\t\t</tr>
\t\t<tr>
\t\t\t<td style=\"text-align:center;\">center </td>
\t\t</tr>
\t\t<tr>
\t\t\t<td style=\"text-align:justify;\">justify </td>
\t\t</tr>
\t\t<tr>
\t\t\t<td style=\"vertical-align:top;\">valign top </td>
\t\t</tr>
\t\t<tr>
\t\t\t<td style=\"vertical-align:bottom;\">bottom </td>
\t\t</tr>
\t</table>";
        $this->assertTransforms($text, $html, 'Cell alignment');

        $text = '|\2. spans two cols |
| col 1 | col 2 |';
        $html = "\t<table>
\t\t<tr>
\t\t\t<td colspan=\"2\">spans two cols </td>
\t\t</tr>
\t\t<tr>
\t\t\t<td> col 1 </td>
\t\t\t<td> col 2 </td>
\t\t</tr>
\t</table>";
        $this->assertTransforms($text, $html, 'Colspan');

        $text = '|/3. spans 3 rows | a |
| b |
| c |';
        $html = "\t<table>
\t\t<tr>
\t\t\t<td rowspan=\"3\">spans 3 rows </td>
\t\t\t<td> a </td>
\t\t</tr>
\t\t<tr>
\t\t\t<td> b </td>
\t\t</tr>
\t\t<tr>
\t\t\t<td> c </td>
\t\t</tr>
\t</table>";
        $this->assertTransforms($text, $html, 'Rowspan');

        $text = '|{background:#ddd}. Grey cell|';
        $html = "\t<table>
\t\t<tr>
\t\t\t<td style=\"background:#ddd;\">Grey cell</td>
\t\t</tr>
\t</table>";
        $this->assertTransforms($text, $html, 'Block attributes on cells');
    }

    public function testTableRowAttributes()
    {
        $text = 'table{border:1px solid black}.
|This|is|a|row|
|This|is|a|row|';
        $html = "\t<table style=\"border:1px solid black;\">
\t\t<tr>
\t\t\t<td>This</td>
\t\t\t<td>is</td>
\t\t\t<td>a</td>
\t\t\t<td>row</td>
\t\t</tr>
\t\t<tr>
\t\t\t<td>This</td>
\t\t\t<td>is</td>
\t\t\t<td>a</td>
\t\t\t<td>row</td>
\t\t</tr>
\t</table>";
        $this->assertTransforms($text, $html, 'Table-wide attributes');

        $text = '|This|is|a|row|
{background:#ddd}. |This|is|grey|row|';
        $html = "\t<table>
\t\t<tr>
\t\t\t<td>This</td>
\t\t\t<td>is</td>
\t\t\t<td>a</td>
\t\t\t<td>row</td>
\t\t</tr>
\t\t<tr style=\"background:#ddd;\">
\t\t\t<td>This</td>
\t\t\t<td>is</td>
\t\t\t<td>grey</td>
\t\t\t<td>row</td>
\t\t</tr>
\t</table>";
        $this->assertTransforms($text, $html, 'Row attributes');
    }

}
