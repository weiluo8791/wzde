<div class="footer">
<p class="copyright">Version <?php echo VERSION ?><br></p>
<p class="copyright">Page rendered in <strong><?php echo $WZDE['TIME']?></strong> seconds</p>
</div>
<script type="text/javascript">
//called after ready() on window.onload and we want to set the timer to 1 second so datatable nodes can be rendered
//scroll to position of the current site
window.addEventListener('load', function() {
    setTimeout("$('#wzdeSiteTable_wrapper div.dataTables_scrollBody').scrollTo('#' + default_site + '_rowID',500);", 1000);
}, false);

</script> 
</body>
</html>
