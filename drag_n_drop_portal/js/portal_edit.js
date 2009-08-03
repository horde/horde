
var _widgets_blocks = new Array();
var _layout_params = new Array();

function onOverWidget(portal, widget) {
    widget.getElement().insertBefore($('control_buttons'), widget.getElement().firstChild);
    $('control_buttons').show();
}

function onOutWidget(portal, widget) {
    $('control_buttons').hide();
}

function minimizeWidget(element) {
    var widget = $(element).up(".widget").widget;
    id = widget._getId().substr(7);
    if ($('content_widget_' + id).style.display == 'none') {
        $('content_widget_' + id).style.display = 'block';
        element.id = 'minimize_button';
    } else {
        $('content_widget_' + id).style.display = 'none';
        element.id = 'maximize_button';
    }
}

function removeWidget(element) {
    var widget = $(element).up(".widget").widget;

    if (confirm(confirm_remove)) {
        document.body.appendChild($('control_buttons').hide())
        portal.remove(widget);
    }
}

function listWidgets() {

    RedBox.loading();

    // load edit options
    new Ajax.Request(list_url, {
                method: 'get',
                onSuccess: function(transport) {
                    RedBox.showHtml('<div id="RB_info">' + transport.responseText + '</div>');
                },
                onFailure: function(transport) {
                    RedBox.close();
                }
            });
}

function addWidget() {

    // Add widget
    select = $('block_selection');
    title = select.options[select.selectedIndex].text;

    widget = new Xilinus.Widget();
    widget.setTitle(title);
    widget.setContent(title);
    portal.add(widget, parseInt($F('block_column')));

    _widgets_blocks[_widgets_blocks.length] = select.value;
    _layout_params[_widgets_blocks.length] = new Array();

    // Edit wiget
    editWidget(widget);

    cancelRedBox();
}

function reloadWidget(element) {

    if ($(element).id == 'reload_button') {
        var widget = $(element).up(".widget").widget;
    } else {
        var widget = element;
    }

    var id = widget._getId().substr(7);

    new Ajax.Request(load_url, {
                parameters: getAjaxParameters(element),
                method: 'get',
                onSuccess: function(transport) {
                    block_data = transport.responseText.evalJSON(true);
                    widget.setTitle(block_data['title']);
                    widget.setContent(block_data['content']);
                    _widgets_blocks[widget._getId().substr(7)] = block_used;
                },
                onFailure: function(transport) {
                    alert('Someting gone wrong.');
                }
            });
}

function editWidget(element) {

    if ($(element).id == 'reload_button') {
        var widget = $(element).up(".widget").widget;
    } else {
        var widget = element;
    }

    new Ajax.Request(edit_url, {
                parameters: getAjaxParameters(element),
                method: 'get',
                onSuccess: function(transport) {
                    RedBox.showHtml('<div id="RB_info">' + transport.responseText + '</div>');
                },
                onFailure: function(transport) {
                    RedBox.close();
                }
            });

}

function getAjaxParameters(element) {

    if ($(element).id == 'reload_button' || $(element).id == 'edit_button') {
        var widget = $(element).up(".widget").widget;
    } else {
        var widget = element;
    }

    var id = widget._getId().substr(7);

    parameters = 'block=' + _widgets_blocks[id];
    parameters = parameters + '&widget=' + widget._getId();

    p = _layout_params[id];
    for (a in p) {
        if (typeof(p[a]) != 'string') {
            break;
        }
        parameters = parameters + '&defaults[' + a + ']=' + p[a];
    }

    return parameters;
}

function setParams() {

    widget_name = '';
    params = new Array();
    inputs = $('blockform').getElements();
    inputs.each(function(item) {
        name = item.name.substr(0, 6);
        if (name == 'params') {
            pos = item.name.indexOf(']', 7);
            param_name = item.name.substr(7, pos - 7);
            if (item.type == 'checkbox') {
                params[param_name] = item.checked;
            } else {
                params[param_name] = item.value;
            }
        }
        if (name == 'widget') {
            widget_name = item.value;
        }
    });

    _layout_params[widget_name.substr(7)] = params;

    var widget = $(widget_name).widget;
    reloadWidget(widget);

    cancelRedBox();
}

function noParams(widget_name, msg) {

    // alert(msg);

    var widget = $(widget_name).widget;
    reloadWidget(widget);

    cancelRedBox();
}

function cancelRedBox() {
    RedBox.close();
    return false;
}

function savePortal() {

    parameters = portal.serialize();

    for (var i = 0; i < _layout_params.length; i++) {
        parameters = parameters + '&params[' + i + '][type]=' + _widgets_blocks[i];
        p = _layout_params[i];
        for (a in p) {
            if (typeof(p[a]) != 'string') {
                break;
            }
            parameters = parameters + '&params[' + i + '][' + a + ']=' + p[a];
        }
    }

    new Ajax.Request(save_url, {
                parameters: parameters,
                method: 'post'
            });
}
