<?php 

/*  
    Copyright 2011, 2012 Mike Bell and Paul Danos

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

$header_text = "Two Oh Swiss";
include("header.php"); 
disp_header();
disp_topbar();
disp_titlebar();

?>

<div class='con'>
    <div class='centerBox'>
        <div class='mainBox'>
            <div class='header lline'>(Jul 15, 2013) Note</div><p>We're transition to a new, better version of the software here.  Things look a lot prettier, but not everything works yet.  That should get better in the next week or two.</p>
        </div>
        <div class='mainBox'>
            <div class='item'><a class="button" href="/about/">About</a></div>
            <div class='item'><a class="button" href="/public/">Tournaments</a></div>
            <div class='item'><a class="button" href="/login/">Login</a></div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>
