<?php
/**
 * Javascript minification tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Text_Filter
 * @subpackage UnitTests
 */

class Horde_Text_Filter_JsminTest extends PHPUnit_Framework_TestCase
{
    public function testJsmin()
    {
        $javascript = <<<EOT
function foo(bar)
{
    if (bar == 2) {
        return true;
    } else {
        return false;
    }
}
EOT;

        $this->assertEquals(
            "function foo(bar)\n{if(bar==2){return true;}else{return false;}}",
             Horde_Text_Filter::filter($javascript, 'JavascriptMinify')
        );
    }

    // Example taken from jsmin.c source
    public function testJsmin2()
    {
        $javascript = <<<EOT
var is = {
    ie:      navigator.appName == 'Microsoft Internet Explorer',
    java:    navigator.javaEnabled(),
    ns:      navigator.appName == 'Netscape',
    ua:      navigator.userAgent.toLowerCase(),
    version: parseFloat(navigator.appVersion.substr(21)) ||
             parseFloat(navigator.appVersion),
    win:     navigator.platform == 'Win32'
}

is.mac = is.ua.indexOf('mac') >= 0;

if (is.ua.indexOf('opera') >= 0) {
    is.ie = is.ns = false;
    is.opera = true;
}

if (is.ua.indexOf('gecko') >= 0) {
    is.ie = is.ns = false;
    is.gecko = true;
}
EOT;
        $this->assertEquals(
            "var is={ie:navigator.appName=='Microsoft Internet Explorer',java:navigator.javaEnabled(),ns:navigator.appName=='Netscape',ua:navigator.userAgent.toLowerCase(),version:parseFloat(navigator.appVersion.substr(21))||parseFloat(navigator.appVersion),win:navigator.platform=='Win32'}
is.mac=is.ua.indexOf('mac')>=0;if(is.ua.indexOf('opera')>=0){is.ie=is.ns=false;is.opera=true;}
if(is.ua.indexOf('gecko')>=0){is.ie=is.ns=false;is.gecko=true;}",
             Horde_Text_Filter::filter($javascript, 'JavascriptMinify')
        );
    }

    public function testBug12787()
    {
        $js = "function foo(a) { return/\//.test(a); }";

        $this->assertEquals(
            'function foo(a){return/\//.test(a);}',
             Horde_Text_Filter::filter($js, 'JavascriptMinify')
         );
    }

}
