/**
 * Code for the list view.
 *
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @package Mnemo
 * @author  Jan Schneider <jan@horde.org>
 */

var Mnemo_List = {
    // Externally set properties:
    //  ajaxUrl
    sortCallback: function(column, sortDown)
    {
        new Ajax.Request(
            this.ajaxUrl,
            { parameters: { pref: 'sortby', value: column.substring(1) } }
        );
        new Ajax.Request(
            this.ajaxUrl,
            { parameters: { pref: 'sortdir', value: sortDown } }
        );
    },

    onDomLoad: function()
    {
        if ($('quicksearchL')) {
            $('quicksearchL').observe(
                'click',
                function(e) {
                    $('quicksearchL').hide();
                    $('quicksearch').show();
                    $('quicksearchT').focus();
                    e.stop();
                }.bindAsEventListener()
            );
            $('quicksearchX').observe(
                'click',
                function(e) {
                    $('quicksearch').hide();
                    $('quicksearchT').value = '';
                    QuickFinder.filter($('quicksearchT'));
                    $('quicksearchL').show();
                    e.stop();
                }.bindAsEventListener()
            );
        }
    }
};

function table_sortCallback(tableId, column, sortDown)
{
    Mnemo_List.sortCallback(column, sortDown);
}

document.observe('dom:loaded', Mnemo_List.onDomLoad.bind(Mnemo_List));
