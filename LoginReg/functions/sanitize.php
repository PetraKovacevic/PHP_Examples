<?php
// This function is used to secure the application
// against XSS vulnerabilities 
function escape($string){
	return htmlentities($string, ENT_QUOTES, 'UTF-8');
}

// In essence, you should use mysql_real_escape_string
// prior to database insertion (to prevent SQL injection)
// and then htmlentities, etc. at the point of output.
?>