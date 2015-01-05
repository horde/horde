/**
 * Load any jQuery Mobile init code before the library is loaded.
 *
 * @copyright  2014-2015 Horde LLC
 * @license    LGPL-2.1 (http://www.horde.org/licenses/lgpl21)
 */

if (window.horde_jquerymobile_init) {
    $(window.document).bind("mobileinit", window.horde_jquerymobile_init);
    delete window.horde_jquerymobile_init;
}
