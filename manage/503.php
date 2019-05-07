<?php
header("HTTP/1.1 503 Service Temporarily Unavailable");
header("STatus: Service Temporarily Unavailable");
header("Retry-After: 7200");


?>
<!DOCTYPE html>
<html>
<head>
<title>WZDE is Temporarily Unavailable.</title>
<link rel="STYLESHEET" type="text/css" href="css/main.css">
<body>

<!-- Header Element -->
<div class="title_area">
    <table class="title_area"><tr>
    <td class="title"><p class="title">WZDE is Temporarily Unavailable.</p></td>
    <td class="subtitle">
        <div>
        <a href="https://staff.meditech.com/en/d/wzdedocumentation/homepage.htm" target="_blank">
        <img src="/documentation.png" style="vertical-align:text-top" width="22" height="22" border="0" alt="WZDE Documentation"></a>
        <span class="subtitle">Web Zone Development Environment (WZDE)</span>
        </div>
        <p class="subtitle">Advanced Technology Web</p>
    </td>
    </tr></table>
</div>

<div class="manage-downtime">
<h2>WZDE Manage is Temporarily Unavailable due to service maintenance.</h2>
<p>We expect to have the site back soon. Please try again later.</p>
<p id="manage-downtime-links">
    <a href="https://staff.meditech.com">Staff</a> |
    <a href="https://staff.meditech.com/en/d/atsc/">Corporate Solutions</a>
</p>
</div>

<!-- Footer Element -->
<div class="footer">
<p class="copyright">Version 1.0<br></p>
</div>

</body>
</html>