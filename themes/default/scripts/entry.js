function comm_del(ctrl, label) {
	var res = window.confirm(strings.get('entry_deleteConfirm', label));
	if (res) {
		if (ctrl.href.lastIndexOf('?') >= 0) {
			ctrl.href=ctrl.href+'&conf=yes';
		} else {
			ctrl.href=ctrl.href+'?conf=yes';
		}
	}
	return res;
}

function mark_all() {
	var boxes = document.getElementsByTagName('input');
	for (i = 0; i < boxes.length; i++) {
		if (boxes[i].class == 'markbox') {
			boxes[i].checked = !boxes[i].checked;
		}
	}
}

function mark_type(itemtype) {
	var boxes = document.getElementsByTagName('input');
	var str = '^'+itemtype;
	var re = new RegExp(str);
	for (i = 0; i < boxes.length; i++) {
		if (boxes[i].class = 'markbox' && 
		    re.test(boxes[i].id) ) {
			boxes[i].checked = !boxes[i].checked;
		}
	}
}

var http = new LnBlogAJAX();

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
			dellinks[i].onclick = reply_delete;
		}
	}
}

lnblog.addEvent(window, 'load', attachDeleteHandlers);
