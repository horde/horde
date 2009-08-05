/**
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
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
