function sbarToggle()
{
    var body = $(document.body), pref_value;

    if (body.hasClassName('rightPanel')) {
        pref_value = 0;
        body.removeClassName('rightPanel');
    } else {
        pref_value = 1;
        body.addClassName('rightPanel');
    }

    new Ajax.Request(Nag.conf.URI_AJAX + 'setPrefValue', { parameters: { pref: 'show_panel', value: pref_value } });
}

document.observe('dom:loaded', function() {
    $$('#pageControlsInner .tasklist-info').invoke('observe', 'click', function() {
        RedBox.loading();
        var tasklist_id = this.previous().down('.checkbox').value;
        new Ajax.Request(Nag.conf.tasklist_info_url, {
            parameters: { t: tasklist_id },
            method: 'get',
            onSuccess: function(transport) {
                RedBox.showHtml('<div id="RB_info">' + transport.responseText + '<input type="button" class="button" onclick="RedBox.close();" value="' + Nag.text.close + '" /><' + '/div>');
            },
            onFailure: function(transport) {
                RedBox.close();
            }
        });
    });
});
