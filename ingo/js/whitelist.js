/**
 * Provides the javascript for the whitelist view.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014-2015 Horde LLC
 * @license    ASL (http://www.horde.org/licenses/apache)
 */

var IngoWhitelist = {

    onDomLoad: function()
    {
        $('whitelist_return').observe('click', function(e) {
            document.location.href = this.filtersurl;
            e.stop();
        }.bind(this));
    }

};

document.observe('dom:loaded', IngoWhitelist.onDomLoad.bind(IngoWhitelist));
