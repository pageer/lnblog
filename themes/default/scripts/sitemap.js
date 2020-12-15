function addLink() {
    var ret;
    var text = document.getElementById("linktext");
    var title = document.getElementById("linktitle");
    var target = document.getElementById("linktarget");
    ret = '<a title="'+title.value+'" href="'+target.value+'">'+text.value+"</a>\n";
    var outbox = document.getElementById("output");
    outbox.value += ret;
    text.value = '';
    title.value = '';
    target.value = '';
    text.focus();
    return true;
}

function clearBoxes() {
    var text = document.getElementById("linktext");
    var title = document.getElementById("linktitle");
    var target = document.getElementById("linktarget");
    text.value = '';
    title.value = '';
    target.value = '';
    return true;
}
