/**
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 */

var IngoRule = {

    delete_condition: function(num)
    {
        $('actionID').setValue('rule_delete');
        $('conditionnumber').setValue(num);
        $('rule').submit();
        return true;
    },

    onDomLoad: function()
    {
        $('all', 'any').invoke('observe', 'click', function(e) {
            e.stop();
            $('rule').submit();
        });

        $('rule').on('change', 'SELECT', function(e) {
            e.stop();
            $('rule').submit();
        });

        $('rule_save').observe('click', function(e) {
            e.stop();
            $('actionID').setValue('rule_save');
            $('rule').submit();
        });

        $('rule_cancel').observe('click', function(e) {
            e.stop();
            document.location.href = this.filtersurl;
        }.bind(this));
    }

};

document.observe('dom:loaded', IngoRule.onDomLoad.bind(IngoRule));
