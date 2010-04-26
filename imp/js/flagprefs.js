/**
 * Provides the javascript for managing message flags.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

var ImpFlagPrefs = {
    // Variables set by other code: confirm_delete, new_prompt

    addFlag: function()
    {
        var category = window.prompt(this.new_prompt, '');
        if (category) {
            this._sendData('add', category);
        }
    },

    _sendData: function(a, d)
    {
        $('flag_action').setValue(a)
        $('flag_data').setValue(d);
        $('prefs').submit();
    },

    clickHandler: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element(), elt2;

        while (Object.isElement(elt)) {
            if (elt.hasClassName('flagcolorpicker')) {
                elt2 = elt.previous('INPUT');
                new ColorPicker({
                    color: $F(elt2),
                    draggable: true,
                    offsetParent: elt,
                    resizable: true,
                    update: [
                        [ elt2, 'value' ],
                        [ elt2, 'background' ],
                        [ elt.previous('DIV.flagUser'), 'background' ]
                    ]
                });
                e.stop();
                return;
            }

            if (elt.hasClassName('flagdelete')) {
                if (window.confirm(this.confirm_delete)) {
                    this._sendData('delete', elt.previous('INPUT').readAttribute('id'));
                }
                e.stop();
                return;
            }

            switch (elt.readAttribute('id')) {
            case 'new_button':
                this.addFlag();
                break;
            }

            elt = elt.up();
        }
    },

    resetHandler: function()
    {
        $('prefs').getInputs('text').each(function(i) {
            if (i.readAttribute('id').startsWith('color_')) {
                i.setStyle({ backgroundColor: $F(i) });
            }
        });
    }

};

document.observe('dom:loaded', function() {
    var fp = ImpFlagPrefs;
    document.observe('click', fp.clickHandler.bindAsEventListener(fp));
    $('prefs').observe('reset', function() { fp.resetHandler.defer(); });
});
