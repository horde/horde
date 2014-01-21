<?php
/**
 * Javascript minification tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    JSMin
 * @package    JavascriptMinify_Jsmin
 * @subpackage UnitTests
 */

class Horde_JavascriptMinify_Jsmin_JsminTest extends PHPUnit_Framework_TestCase
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

        $jsmin = new Horde_JavascriptMinify_Jsmin($javascript);
        $this->assertEquals(
            "function foo(bar)\n{if(bar==2){return true;}else{return false;}}",
            $jsmin->minify()
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

        $jsmin = new Horde_JavascriptMinify_Jsmin($javascript);
        $this->assertEquals(
            "var is={ie:navigator.appName=='Microsoft Internet Explorer',java:navigator.javaEnabled(),ns:navigator.appName=='Netscape',ua:navigator.userAgent.toLowerCase(),version:parseFloat(navigator.appVersion.substr(21))||parseFloat(navigator.appVersion),win:navigator.platform=='Win32'}
is.mac=is.ua.indexOf('mac')>=0;if(is.ua.indexOf('opera')>=0){is.ie=is.ns=false;is.opera=true;}
if(is.ua.indexOf('gecko')>=0){is.ie=is.ns=false;is.gecko=true;}",
             $jsmin->minify()
        );
    }

    public function testBug12787()
    {
        $js = "function foo(a) { return/\//.test(a); }";
        $jsmin = new Horde_JavascriptMinify_Jsmin($js);

        $this->assertEquals(
            'function foo(a){return/\//.test(a);}',
             $jsmin->minify()
         );

        $js2 = 'var a = 0, b = c / 100 | 0;';
        $jsmin2 = new Horde_JavascriptMinify_Jsmin($js2);

        $this->assertNotEquals(
            $js2,
            $jsmin2->minify()
        );
    }

}
