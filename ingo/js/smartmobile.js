/**
 * jQuery Mobile UI Ingo application logic.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */
var IngoMobile = {

    /**
     * Event handler for the pagebeforechange event that implements loading of
     * deep-linked pages.
     *
     * @param object e     Event object.
     * @param object data  Event data.
     */
    toPage: function(e, data)
    {
        switch (data.options.parsedUrl.view) {
        case 'rule':
            IngoMobile.rule(data);
            e.preventDefault();
            break;
        }
    },

    /**
     * View a rule.
     *
     * @param object data  Page change data object.
     */
    rule: function(data)
    {
        var purl = data.options.parsedUrl;

        HordeMobile.changePage('rule', data);

        HordeMobile.doAction(
            'smartmobileRule',
            {
                rule: purl.params.rulenum
            },
            IngoMobile.ruleLoaded
        );
    },

    /**
     * Callback method after a rule has been loaded.
     *
     * @param object r  The Ajax response object.
     */
    ruleLoaded: function(r)
    {
        if (r.error) {
            HordeMobile.changePage('rules');
            return;
        }

        $('#ingo-rule-label').html(r.label);
        if (r.descrip) {
            $('#ingo-rule-description').html(r.descrip);
        } else {
            $('#ingo-rule-description').text(Ingo.text.no_descrip);
        }
    },

    /**
     * Event handler for the document-ready event, responsible for the initial
     * setup.
     */
    onDocumentReady: function()
    {
        $(document).bind('pagebeforechange', IngoMobile.toPage);
    }

};

// JQuery Mobile setup
$(IngoMobile.onDocumentReady);
