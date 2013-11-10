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

// Asynchronous Post Methods

// relies on SwissRootPath, defined in ui.js

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
    this.post = function (data) {
        var fd = new FormData()
        for (i in data)
            fd.append(i, data[i])
        self.xhr.open("POST", SwissRootPath+"async.php", true)
        self.xhr.send(fd)
    }
    this.addEventListener = function(eventType, callback) { self.xhr.upload.addEventListener(eventType, callback) }
}
