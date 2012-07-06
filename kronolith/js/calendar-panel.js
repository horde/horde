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

    new Ajax.Request(KronolithVar.URI_AJAX + 'setPrefValue', { parameters: { pref: 'show_panel', value: pref_value } });
}

document.observe('dom:loaded', function() {
    $$('#pageControlsInner .checkbox').invoke('observe', 'click', function() {
        Views.invalidate();
        ShowView(KronolithView, { date: KronolithDate.getFullYear() + (KronolithDate.getMonth() + 1).toPaddedString(2) + KronolithDate.getDate().toPaddedString(2), toggle_calendar: this.value }, false);
    });

    $$('#pageControlsInner .calendar-info').invoke('observe', 'click', function() {
        RedBox.loading();
        var calendar_id = this.up().select('.checkbox').first().value;
        new Ajax.Request(KronolithVar.calendar_info_url, {
            parameters: { c: calendar_id },
            method: 'get',
            onSuccess: function(transport) {
                RedBox.showHtml('<div id="RB_info">' + transport.responseText + '<input type="button" class="button" onclick="RedBox.close();" value="' + KronolithText.close + '" /></div>');
            },
            onFailure: function(transport) {
                RedBox.close();
            }
        });
    });
});
document.observe('click', function(e) {
    if (!e.isRightClick() && e.element().up().match('SPAN.toggleTags')) {
        e.element().up().up().select('.toggleTags').invoke('toggle');
    }
});