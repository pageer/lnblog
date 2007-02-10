// This code is based on example AJAX code by Rasmus Lerdorf.

function createRequestObject() {
	var request;
	var browser = navigator.appName;
	if (browser == "Microsoft Internet Explorer") {
		request = new ActiveXObject("Microsoft.XMLHTTP");
	} else {
	request = new XMLHttpRequest();
	}
	return request;
}

var http = createRequestObject();

function sndReq(uri) {
	http.open('get', uri);
	http.onreadystatechange = handleResponse;
	http.send(null);
	document.getElementById('calendar').style.cursor = 'wait';
	return false;
}

function handleResponse() {
	if(http.readyState == 4){
		var response = http.responseText;
		document.getElementById('calendar').innerHTML = response;
	document.getElementById('calendar').style.cursor = 'default';
	}
}

