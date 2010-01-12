function showFace(id)
{
    $('facediv' + id).addClassName('shown');
    $('facethumb' + id).style.border = '1px solid red';
    $('facedivname' + id).style.display = 'inline';
}
function hideFace(id)
{
    $('facediv' + id).removeClassName('shown');
    $('facethumb' + id).style.border = '1px solid black';
    $('facedivname' + id).style.display = 'none';
}
document.observe('dom:loaded', function() {
    Event.observe($('photodiv'), 'load', function() {
        $('faces-on-image').immediateDescendants().collect(function(element) {
            element.clonePosition($('photodiv'), {setWidth: false, setHeight: false});
        });
    });
});
