function comm_del(ctrl, label) {
	var res = window.confirm("Really delete "+label+"?");
	if (res) {
		if (ctrl.href.lastIndexOf('?') >= 0) {
			ctrl.href=ctrl.href+'&conf=yes';
		} else {
			ctrl.href=ctrl.href+'?conf=yes';
		}
	}
	return res;
}
