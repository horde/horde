<?php
/**
 * @category   Horde
 * @package    Text_Textile
 * @subpackage UnitTests
 */

/** Horde_Text_Textile_TestCase */
require_once dirname(__FILE__) . '/TestCase.php';

/**
 * These tests correspond to "5. Lists" from http://hobix.com/textile/.
 *
 * @category   Horde
 * @package    Text_Textile
 * @subpackage UnitTests
 */
class Horde_Text_Textile_ListsTest extends Horde_Text_Textile_TestCase {

    public function testNumericList()
    {
        $this->assertTransforms('# A first item
# A second item
# A third',
                       "\t<ol>
\t\t<li>A first item</li>
\t\t<li>A second item</li>
\t\t<li>A third</li>
\t</ol>");

        $this->assertTransforms('# Fuel could be:
## Coal
## Gasoline
## Electricity
# Humans need only:
## Water
## Protein',
                       "\t<ol>
\t\t<li>Fuel could be:
\t\t<ol>
\t\t\t<li>Coal</li>
\t\t\t<li>Gasoline</li>
\t\t\t<li>Electricity</li>
\t\t</ol></li>
\t\t<li>Humans need only:
\t\t<ol>
\t\t\t<li>Water</li>
\t\t\t<li>Protein</li>
\t\t</ol></li>
\t</ol>");
    }

    public function testBulletedLists()
    {
        $this->assertTransforms('* A first item
* A second item
* A third',
                       "\t<ul>
\t\t<li>A first item</li>
\t\t<li>A second item</li>
\t\t<li>A third</li>
\t</ul>");

        $this->assertTransforms('* Fuel could be:
** Coal
** Gasoline
** Electricity
* Humans need only:
** Water
** Protein',
                       "\t<ul>
\t\t<li>Fuel could be:
\t\t<ul>
\t\t\t<li>Coal</li>
\t\t\t<li>Gasoline</li>
\t\t\t<li>Electricity</li>
\t\t</ul></li>
\t\t<li>Humans need only:
\t\t<ul>
\t\t\t<li>Water</li>
\t\t\t<li>Protein</li>
\t\t</ul></li>
\t</ul>");
    }

}
