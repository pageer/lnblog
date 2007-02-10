// This code is based on example AJAX code by Rasmus Lerdorf.

var http = lnblog.createRequestObject();

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

