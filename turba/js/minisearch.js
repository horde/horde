/**
 * Provides the javascript for the minisearch portal block.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 */

var TurbaMinisearch = {

    mini_search: function()
    {
        $('turba_minisearch_searching', 'turba_minisearch_close', 'turba_minisearch_iframe').invoke('show');
    },

    hide_mini_search: function(e)
    {
        $('turba_minisearch_searching', 'turba_minisearch_close').invoke('hide');
        var d = $('turba_minisearch_iframe').hide().contentWindow.document;
        d.open();
        d.close();

        e.stop();
    },

    onDomLoad: function()
    {
        $('turba_minisearch').observe('submit', this.mini_search.bind(this));
        $('turba_minisearch_close').observe('click', this.hide_mini_search.bindAsEventListener(this));
    }

};

document.observe('dom:loaded', TurbaMinisearch.onDomLoad.bind(TurbaMinisearch));
