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
