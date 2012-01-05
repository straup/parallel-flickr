sharkify_add = function(){
	var s = [[222,123],[387,220],[303,136],[250,186]];
	var i = Math.ceil(Math.random()*s.length);
	var a = typeof(window.innerHeight) == 'number';
	var b = document.documentElement && document.documentElement.clientHeight;
	var h = a ? window.innerHeight : b ? document.documentElement.clientHeight : document.body.clientHeight;
	var w = a ? window.innerWidth  : b ? document.documentElement.clientWidth  : document.body.clientWidth;
	var d = document.createElement('div');
	d.style.position = 'fixed';
	d.style.left = (Math.random()*(w-s[i-1][0]))+'px';
	d.style.top  = (Math.random()*(h-s[i-1][1]))+'px';
	d.style.zIndex = 10;
	var m = document.createElement('img');
	m.onclick=sharkify_add;
	m.style.cursor='pointer';
	m.src=abs_root_url+'images/shark_'+i+'.gif';
	var body = document.getElementsByTagName('body')[0];
	body.appendChild(d);
	d.appendChild(m);
}