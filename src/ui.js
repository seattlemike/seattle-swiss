/*  
    Copyright 2011, 2012, 2013 Mike Bell and Paul Danos

    This file is part of 20Swiss.
    
    20Swiss is free software: you can redistribute it and/or modify it under the
    terms of the GNU Affero General Public License as published by the Free
    Software Foundation, either version 3 of the License, or (at your option)
    any later version.

    20Swiss is distributed in the hope that it will be useful, but WITHOUT ANY
    WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
    FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for
    more details.

    You should have received a copy of the GNU Affero General Public License
    along with 20Swiss.  If not, see <http://www.gnu.org/licenses/>. 
*/

// Common UI

function buildSyncForm(fData) {
    var form = document.createElement("form")
    form.method="post"
    form.action="/sync.php"
    form.style="display: none;"
    for(var key in fData) {
        form.appendChild(buildInput(key, fData[key]))
        form.lastChild.setAttribute("type", "hidden")
    }
    document.body.appendChild(form)
    return form
}

// public:  index, state, next(), setState(s), setIndex(i)
function Toggle(states) {
    var self = this
    this.callback = function(s) { }
    this.setIndex = function(i) {
        self.index = i
        self.state = states[i]
        self.callback(states[i]) 
    }
    this.next = function () { self.setIndex((self.index + 1) % states.length) }
    this.setState = function(s) { self.setIndex(states.indexOf(s)) }
    this.setIndex(0)
}

function ToggleText(node, labels) {
    var self = new Toggle(labels)
    node.onclick = self.next
    self.callback = function(s) {
        node.removeChild(node.firstChild)
        node.appendChild(document.createTextNode(s))
    }
    return self
}

function ToggleClass(node, labels) {
    var self = new Toggle(labels)
    node.onclick = self.next
    self.callback = function(s) { node.className = s }
    return self
}

function buildInput(elName, elValue) {
    var el = document.createElement("input")
    el.name = elName
    el.value = elValue
    return el
}

function buildNode(elType, elClass, elText) {
    var el = document.createElement(elType)
    if (elClass)
        el.className = elClass
    if (elText != undefined)
        el.appendChild(document.createTextNode(elText))
    return el
}

function removeNode(n) { n.parentNode.removeChild(n) }

function removeChildren(node) {
    while (node.hasChildNodes())
        node.removeChild(node.firstChild);
}

function newNodeText(node, text) {
    removeChildren(node)
    node.appendChild(document.createTextNode(text))
}

function toggleHide(id) {
    var el = document.getElementById(id)
    if (el.style.display == "none")
        el.style.display = ""
    else el.style.display = "none"
}

// Modal Dialog

function makeTop(el) {
    var elements = document.getElementsByTagName("*")
    var idx = 0;
    for(var i=0; i<elements.length; i++)
        if (parseInt(elements[i].style.zIndex) > idx)
            idx=parseInt(elements[i].style.zIndex)

    el.style.zIndex = idx;
}

function Dialog( titleText, okfn) {
    var self = this
    if (! okfn) 
        okfn = function () {}

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

        // center dialog
        var newTop = (window.innerHeight-self.dialog.offsetHeight)/2
        if (newTop < 0) {
            newTop = 0
            self.dialog.style.position = "absolute" // if taller than innerHeight, better allow scrolling
        }
        self.dialog.style.top = newTop+"px"
        var newLeft = (window.innerWidth-self.dialog.offsetWidth)/2
        self.dialog.style.left= newLeft+"px"

        self.dialog.scrollIntoView()
    }
    this.hide = function() { document.body.removeChild(self.dialog);  document.body.removeChild(self.overlay) }
    this.insert = function(node) { self.dialog.insertBefore(node, self.buttonBox); return node }
    this.setWidth = function(w) { self.dialog.style.width =  w+"px"; self.dialog.style.marginLeft = (Math.round(-1 * w/2)) + "px" }
    this.display = function(css) { self.dialog.style.position = css }
    this.setTitle = function(text) { self.titleBox.innerHTML=text }
}
