/**
 * Provides the javascript for problem reporting.
 *
 * @copyright  2014-2015 Horde LLC
 * @license    LGPL-2 (http://www.horde.org/licenses/lgpl)
 */

var HordeProblem = {

    onSubmit: function(e)
    {
        if ($F('subject').empty()) {
            window.alert(this.summary_text);
            $('subject').focus();
            e.stop();
        } else if ($F('message').empty()) {
            window.alert(this.message_text);
            $('message').focus();
            e.stop();
        } else {
            $('actionID').setValue('send_problem_report');
        }
    }

};

$('problem-report').observe('click', HordeProblem.onSubmit.bindAsEventListener(HordeProblem));
