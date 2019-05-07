<?php
global $WZDE;
$WZDE['TIME_END'] = microtime(true);
$WZDE['TIME'] = $WZDE['TIME_END'] - $WZDE['TIME_START'];
?>
<!-- CDN -->
<script type="text/javascript" charset="utf8" src="//code.jquery.com/jquery-1.11.3.min.js"></script>     
<script type="text/javascript" charset="utf8" src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script type="text/javascript" charset="utf8" src="//malsup.github.io/jquery.blockUI.js"></script>
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8" src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.3/moment.min.js"></script>
<script type="text/javascript" charset="utf8" src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.min.js"></script>

<!-- Local -->
<script type="text/javascript" charset="utf8" src="js/systemStatus.js"></script>
<script type="text/javascript" charset="utf8" src="js/functions.js"></script>
<script type="text/javascript" charset="utf8" src="js/<?php echo $WZDE['ID']; ?>.js"></script>
<script type="text/javascript" charset="utf8" src="js/ga.js"></script>

<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="//www.googletagmanager.com/gtag/js?id=UA-22228657-9"></script>

<!-- gtag JS -->
<script src="js/ga.js"></script>	

<div class="footer">
<p class="copyright">Version <?php echo VERSION ?><br></p>
<p class="copyright">Page rendered in <strong><?php echo $WZDE['TIME']?></strong> seconds</p>
</div>

</body>
</html>
