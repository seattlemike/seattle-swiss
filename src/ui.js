function buildNode(elType, elClass, elText) {
    var el = document.createElement(elType)
    el.className = elClass
    if (elText != undefined)
        el.appendChild(document.createTextNode(elText))
    return el
}

function makeTop(el) {
    var elements = document.getElementsByTagName("*")
    var idx = 0;
    for(var i=0; i<elements.length; i++)
        if (parseInt(elements[i].style.zIndex) > idx)
            idx=parseInt(elements[i].style.zIndex)

    el.style.zIndex = idx;
}

function Dialog( titleText="", okfn = function() {} ) {
    var self = this

    this.minWidth = 0
    this.overlay = buildNode("div", "window-overlay")
    this.dialog = buildNode("div", "dialog")
    this.buttonBox = buildNode("div","dialog-buttons")
    this.cancelBtn = buildNode("div", "button", "Cancel")
    this.okBtn = buildNode("div", "button rt-button", "Ok")

    this.cancelBtn.onclick = function() { self.hide(); } 
    this.okBtn.onclick = function () { okfn(); self.hide() }

    this.buttonBox.appendChild(this.cancelBtn)
    this.buttonBox.appendChild(this.okBtn)

    if (titleText) {
        this.titleBox = buildNode("div","dialog-text", titleText)
        this.dialog.appendChild(this.titleBox)
    }
    this.dialog.appendChild(this.buttonBox)

    this.show = function() { 
        document.body.appendChild(self.overlay); 
        makeTop(self.overlay)
        document.body.appendChild(self.dialog); 
        makeTop(self.dialog)
        if (self.dialog.offsetWidth-34 < self.minWidth)  
            self.setWidth(self.minWidth)
    }
    this.hide = function() { document.body.removeChild(self.dialog);  document.body.removeChild(self.overlay) }
    this.insert = function(node) { self.dialog.insertBefore(node, self.buttonBox); return node }
    this.setWidth = function(w) { self.dialog.style.width =  w+"px"; self.dialog.style.marginLeft = (Math.round(-1 * w/2)) + "px" }
    this.display = function(css) { self.dialog.style.position = css; }
}

function delTournament() {
    d = new Dialog("Delete this tournamnent?",
                    function () { var form = document.getElementById("delForm"); if (form) form.submit() })
    d.show()
}
