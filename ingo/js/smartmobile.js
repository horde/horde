/**
 * jQuery Mobile UI Ingo application logic.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @copyright  2014-2015 Horde LLC
 * @license    ASL (http://www.horde.org/licenses/apache)
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
                rule: purl.params.uid
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

        $('#ingo-rule-label').text(r.label);
        if (r.descrip) {
            $('#ingo-rule-description').text(r.descrip);
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
