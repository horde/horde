var WickedHistory = {
    onClick: function(e)
    {
        var elm = e.findElement();
        if (elm.tagName == 'INPUT' && elm.type == 'submit') {
            var value = $('wicked-diff')
                .getInputs('radio', 'v1')
                .find(function(radio) { return radio.checked; });
            if ($F(elm) == $F(value)) {
                e.stop();
            }
            $('wicked-diff-v2').setValue($F(elm));
        }
    }
};

$('wicked-diff').observe('click', WickedHistory.onClick);
