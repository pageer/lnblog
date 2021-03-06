/*global strings, lnblog */
function comm_del() {
    /*jshint regexp:false */
    var item = this.href.replace(/^.*delete=(.+)$/, "$1");
    var res = window.confirm(strings.get('entry_deleteConfirm', item));
    if (res) {
        if (this.href.lastIndexOf('?') >= 0) {
            this.href=this.href+'&conf=yes';
        } else {
            this.href=this.href+'?conf=yes';
        }
    }
    return res;
}

function mark_all() {
    var boxes = document.getElementsByTagName('input');
    for (var i = 0; i < boxes.length; i++) {
        if (boxes[i]['class'] == 'markbox') {
            boxes[i].checked = !boxes[i].checked;
        }
    }
}

function mark_type(itemtype) {
    var boxes = document.getElementsByTagName('input');
    var str = '^'+itemtype;
    var re = new RegExp(str);
    for (var i = 0; i < boxes.length; i++) {
        if (boxes[i]['class'] == 'markbox' && re.test(boxes[i].id) ) {
            boxes[i].checked = !boxes[i].checked;
        }
    }
}

function reply_delete () {
    var topli = this.parentNode.parentNode.parentNode;
    var boxes = topli.getElementsByTagName('input');
    for (var i = 0; i < boxes.length; i++) {
        if (boxes[i].getAttribute('type') == 'hidden') {
            //window.alert(boxes[i].getAttribute('value'));
            
        }
    }
    return false;
}

function attachDeleteHandlers() {
    var dellinks = document.getElementsByTagName('a');
    for (var i = 0; i < dellinks.length; i++) {
        if (dellinks[i].getAttribute('class') == 'deletelink') {
            //lnblog.addEvent(dellinks[i], 'click', reply_delete);
            //dellinks[i].addEventListener('click', reply_delete, true);
            dellinks[i].onclick = comm_del; //reply_delete;
        }
    }
}

lnblog.addEvent(window, 'load', attachDeleteHandlers);

// Global to track "dirty" status of form.
var current_text_content = '';
var original_text_content = '';

// Window unload hanlder for comment box.
$(window).on(
    'beforeunload', function windowUnload() {
    if (current_text_content !== original_text_content) {
        return strings.editor_leavePrompt;
    }
    }
);

$(document).ready(
    function documentReady() {
    $('#commentform #data').on(
        'change', function commentChange() {
        current_text_content = $(this).val();
        }
    );
    
    $('#commentform').on(
        'submit', function commentSubmit() {
        current_text_content = original_text_content;
        }
    );
    }
);