// Asynchronous Post Methods

function asyncPost(onSuccess) {
    var self = this
    this.xhr = new XMLHttpRequest
    this.xhr.onreadystatechange = function() { 
        if (this.readyState == 4) {
            if (self.onFinished) self.onFinished() 
            if (self.xhr.status == 200)
                self.handleResponse(self.xhr.responseText)
            else 
                alert("Status: "+self.xhr.status+"\nText: "+self.xhr.responseText)
        }
    }
    this.handleResponse = function(response) {
        if (self.onComplete) self.onComplete(self.xhr.responseText) 
        try {
            var response = JSON.parse(self.xhr.responseText)
            if (response.success) {
                if (self.onSuccess) self.onSuccess(response)
            }
            else 
                alert("Error: "+response.errno+", "+response.msg)
        }
        catch (e) {
            alert("JSON Parse Error: "+e+"\n"+self.xhr.responseText)
        }
    }
    this.onSuccess = onSuccess
    this.post = function (formdata) {
        self.xhr.open("POST", "/async.php", true)
        self.xhr.send(formdata);
    }
    this.addEventListener = function(eventType, callback) { self.xhr.upload.addEventListener(eventType, callback) }
}

// Async-post: update image details (just img-text?)
function updateImg(entry) {
    var async = new asyncPost()
    var entry = entry;
    async.onSuccess = function() {
        // store the sql entry in the data-entry field as a JSON object
        var node = entry.node
        delete entry.node  // but don't include a reference to the parent element
        node.setAttribute("data-entry", JSON.stringify(entry))

        // if we've got media_text, render the 'name' accordingly
        if (entry.media_text)
            node.lastChild.innerHTML = entry.media_text+" ["+entry.media_filename+"]"
        else
            node.lastChild.innerHTML = entry.media_filename
    }

    var fd = new FormData()
    fd.append("case", "updateImg")
    fd.append("img_id", entry.media_id)
    fd.append("img_text", entry.media_text)
    async.post(fd)
}

// Async-post: delete img
function deleteImg(entry) {
    var async = new asyncPost()
    var node = entry.node
    async.onSuccess = function() { node.parentNode.removeChild(node); parsePostText(document.forms['entry.form']) }

    var fd = new FormData()
    fd.append("case", "deleteImg")
    fd.append("img_id", entry.media_id)
    async.post(fd)
}

// Async-post: set status to entry.post_status
function postStatus( entry, node ) {
    var async = new asyncPost()
    async.onSuccess = function() {
        if (entry.post_status == 1) statusText = "Published"
        else if (entry.post_status == 2) statusText = "Draft"
        node.setAttribute("data-status", JSON.stringify(entry))
        node.removeChild(node.firstChild)
        node.appendChild(document.createTextNode("Status: "+statusText));
        //node.firstChild.removeChild(node.firstChild.firstChild)
        //node.firstChild.appendChild(document.createTextNode("Status: "+statusText))
    }

    var fd = new FormData()
    fd.append("case", "setStatus")
    fd.append("post_id", entry.post_id)
    fd.append("post_status", entry.post_status)
    async.post(fd);
}

// Async-post: save post
function savePost( form , background = false) {
    if (! background)
        form.post_fulltext.value = removeAsides(parsePostText(form))
    this.async = new asyncPost( function () { document.getElementById('saveBtn').className = "button disabled" } )
    var fd = new FormData(form)
    fd.append("case", "savePost")
    async.post(fd)
}

// Async-post: delete post
function deletePost( id ) {
    var self=this
    this.async = new asyncPost()
    this.async.onSuccess = function() { location.href="admin_home.php" }

    this.fd = new FormData()
    this.fd.append("case", "deletePost")
    this.fd.append("id", id)

    var dialog = new Dialog("Are you sure you want to DELETE this post?", function () { self.async.post(self.fd) } )
    dialog.show();
}

// toggle entry.post_status
function toggleStatus( node ) {
    var entry = JSON.parse(node.getAttribute("data-status"))
    entry.post_status = 3 - entry.post_status // toggle 1 <=> 2
    if (entry.post_status == 1) {
        var dialog = new Dialog("Do you want to publish this post?", function () { postStatus(entry, node) })
        dialog.show()
    }
    else {
        postStatus(entry, node)
    }
}

// edit img-details modal
function imgDetails (imgNode) {
    var entry = JSON.parse(imgNode.getAttribute("data-entry"))
    entry.node = imgNode;
    var dialog = new imgDialog(entry)
    dialog.show()
}

// upload images from files!
function handleImgUpload(files) {
    if (!files.length) {
        new Dialog("No files selected!")
    } else {
        for (var i = 0; i < files.length; i++) {
            var upMeter = new imgUpload(files[i].name)
            upMeter.moveTo(document.getElementById("imgUpload-pending"))
            SendFile(upMeter, files[i]);
        }
    }
}

// Async-post: upload/add image files - track progress with meter!
function SendFile(upMeter, file) {
    var self = this
    this.node = upMeter.node
    this.meter = upMeter.meter

    this.upload = new asyncPost()
    this.upload.onFinished = function() {
        self.node.removeChild(self.meter)
        self.node.parentNode.removeChild(self.node)
    }
    this.upload.onSuccess = function(response) {
        var doneList = document.getElementById("imgUpload-complete")
        doneList.appendChild(self.node)
        self.node.firstChild.className = "link"
        self.node.firstChild.onclick = function () { imgDetails(this.parentNode) }
        self.node.setAttribute("data-entry", JSON.stringify(response['entry']))
    }
    this.upload.addEventListener("progress", 
        function(e) {
            if (e.lengthComputable) {
                var percentage = Math.round((e.loaded * 100) / e.total)
                self.meter.firstChild.style.width = percentage+"%";
            }
        }, false)

    var fd = new FormData()
    fd.append("case", "image")
    fd.append("entry_id", document.forms["entry.form"]["post_id"].value);
    fd.append("img_file", file)
    this.upload.post(fd);
}
