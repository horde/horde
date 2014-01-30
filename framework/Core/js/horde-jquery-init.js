/**
 * Load any jQuery Mobile init code before the library is loaded.
 */

if (window.horde_jquerymobile_init) {
    $(window.document).bind("mobileinit", window.horde_jquerymobile_init);
    delete window.horde_jquerymobile_init;
}
