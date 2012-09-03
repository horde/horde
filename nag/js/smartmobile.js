/**
 * jQuery Mobile UI application logic.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */
var NagMobile = {

    toggleComplete: function()
    {
        var elt = $(this);

        HordeMobile.doAction(
            'smartmobileToggle',
            {
                task: elt.data('task'),
                tasklist: elt.data('tasklist')
            },
            function(r) { NagMobile.toggleCompleteCallback(r, elt); }
        );
    },

    toggleCompleteCallback: function(r, elt)
    {
        if (r.data == 'complete') {
            if (NagConf.showCompleted == 'incomplete' ||
                NagConf.showCompleted == 'future-incomplete') {
                // Hide the task
                elt.closest('li').remove();
            } else {
                elt.data('icon', 'check');
                elt.find('span.ui-icon').removeClass('ui-icon-nag-unchecked').addClass('ui-icon-check');
            }
        } else {
            if (NagMobile.showCompleted == 'complete') {
                // Hide the task
                elt.closest('li').remove();
            } else {
                elt.data('icon', 'nag-unchecked');
                elt.find('span.ui-icon').removeClass('ui-icon-check').addClass('ui-icon-nag-unchecked');
            }
        }
    },

    onDocumentReady: function()
    {
        $('.toggleable').click(NagMobile.toggleComplete);
    }

};

$(NagMobile.onDocumentReady);
