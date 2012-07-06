/**
 * Provides the javascript for managing message flags.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
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
        var elt = e.element(), elt2;

        if (elt.readAttribute('id') == 'new_button') {
            this.addFlag();
        } else if (elt.hasClassName('flagcolorpicker')) {
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
            e.memo.stop();
        } else if (elt.hasClassName('flagdelete')) {
            if (window.confirm(this.confirm_delete)) {
                this._sendData('delete', elt.previous('INPUT').readAttribute('id'));
            }
            e.memo.stop();
        }
    },

    resetHandler: function()
    {
        $('prefs').getInputs('text').each(function(i) {
            if (i.readAttribute('id').startsWith('color_')) {
                i.setStyle({ backgroundColor: $F(i) });
            }
        });
    },

    onDomLoad: function()
    {
        HordeCore.initHandler('click');
        $('prefs').observe('reset', function() {
            this.resetHandler.defer();
        }.bind(this));
    }

};

document.observe('dom:loaded', ImpFlagPrefs.onDomLoad.bind(ImpFlagPrefs));
document.observe('HordeCore:click', ImpFlagPrefs.clickHandler.bindAsEventListener(ImpFlagPrefs));
