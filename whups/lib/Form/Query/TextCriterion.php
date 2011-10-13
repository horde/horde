<?php
/**
 * @package Whups
 */
class Whups_Form_Query_TextCriterion extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct(
            $vars,
            $vars->get('edit') ? _("Edit Text Criterion") : _("Add Text Criterion"),
            'Whups_Form_Query_TextCriterion');

        $this->addHidden('', 'edit', 'boolean', false);
        $this->addVariable(_("Text"), 'text', 'text', true);
        $this->addVariable(
            _("Match Operator"), 'operator', 'enum', true, false, null,
            array(Whups_Query::textOperators()));
        $this->addVariable(_("Search Summary"), 'summary', 'boolean', false);
        $this->addVariable(_("Search Comments"), 'comments', 'boolean', false);
    }

    public function execute(&$vars)
    {
        $path = $vars->get('path');
        $text = $vars->get('text');
        $operator = $vars->get('operator');
        $summary = $vars->get('summary');
        $comments = $vars->get('comments');

        if ($summary && $comments) {
            $path = $GLOBALS['whups_query']->insertBranch($path, Whups_Query::TYPE_OR);
        }

        if ($summary) {
            $GLOBALS['whups_query']->insertCriterion(
                $path, Whups_Query::CRITERION_SUMMARY, null, $operator, $text);
        }

        if ($comments) {
            $GLOBALS['whups_query']->insertCriterion(
                $path, Whups_Query::CRITERION_COMMENT, null, $operator, $text);
        }

        $this->unsetVars($vars);
    }

}