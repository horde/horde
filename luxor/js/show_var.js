function show_var(varname)
{
    if (document.styleSheets[0].addRule) {
        document.styleSheets[0].addRule('.' + varname, 'background: yellow;', 45);
    } else {
        document.styleSheets[0].insertRule('.' + varname + ' { background: yellow; }', 45);
    }
}

function unshow_var(varname)
{
    if (document.styleSheets[0].removeRule) {
        document.styleSheets[0].removeRule(45);
    } else {
        document.styleSheets[0].deleteRule(45);
    }
}
