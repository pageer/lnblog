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
