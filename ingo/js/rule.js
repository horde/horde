/**
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var IngoRule = {

    delete_condition: function(num)
    {
        document.rule.actionID.value = 'rule_delete';
        document.rule.conditionnumber.value = num;
        document.rule.submit();
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
