importPackage(java.io);

_indexOf = function(item, list) {
	for (var i = list.length; i >= 0; --i) {
		if (list[i] == item) {
			return i;
		}
	}
	return -1;
};

var f = new File(arguments[0]);
var fis = new FileInputStream(f);
var bis = new BufferedInputStream(fis);
var dis = new DataInputStream(bis);
var _k = [];
var _v = [];
var n = 0;
var res = 0;
while (dis.available() != 0) {
	cl = dis.readLine();
	n = parseInt(cl);
	var _in = _indexOf(n, _k);
	if (_in == -1) {
		_in = _k.length;
		_k[_in] = n;
		_v[_in] = 1;
	}
	for (var i = 0; i < _k.length; i++) {
		if ((_k[i] < n) && (_v[i] >= _v[_in])) {
			_v[_in] = _v[i] + 1;
		}
	}
}
for (var i = 0; i < _v.length; i++) {
	if (_v[i] > res) {
		res = _v[i];
	}
}
print(res);
fis.close();
bis.close();
dis.close();
