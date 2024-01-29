(function () {
  var h = "", s, g, c, i, t, u;
  if (window.getSelection) {
    s = window.getSelection();
    if (s.rangeCount) {
      c = document.createElement("div");
      for (i = 0; i < s.rangeCount; ++i) {
        c.appendChild(s.getRangeAt(i).cloneContents());
      }
      h = c.innerHTML
    }
  } else if ((s = document.selection) && s.type == "Text") {
    h = s.createRange().htmlText;
  }

  t = document.title;
  u = document.URL;
  void(window.open("###url###?t=" + encodeURIComponent(t) + "&u=" + encodeURIComponent(u) + "&d=" + encodeURIComponent(h), '', 'width=700,height=500,left=0,top=0,resizable=yes,toolbar=no,location=no,scrollbars=yes,status=no,menubar=no'));
})();
