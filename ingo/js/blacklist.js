/**
 * Provides the javascript for the blacklist view.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014-2015 Horde LLC
 * @license    ASL (http://www.horde.org/licenses/apache)
 */

var IngoBlacklist = {

    // Vars used and defaulting to null/false:
    //   filtersurl

    onDomLoad: function()
    {
        $('actionvalue').observe('change', function(e) {
            if ($F(e.element())) {
                $('action_folder').setValue(1);
            }
        });

        $('blacklist_return').observe('click', function(e) {
            document.location.href = this.filtersurl;
            e.stop();
        }.bind(this));
    }
};

document.observe('dom:loaded', IngoBlacklist.onDomLoad.bind(IngoBlacklist));
