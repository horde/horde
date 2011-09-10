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
    }

};
