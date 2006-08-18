<script type="text/javascript" language="JavaScript">
	var popUpWin=0;
	function renameFolder(name_file) {
		if(popUpWin) {
			if(!popUpWin.closed) popUpWin.close();
		}
		popUpWin = open(name_file,'_blank','toolbar=no,location=0,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=600,height=430')
	}
</script>
<?php
if ($cfg["enable_rename"] == 1) {
	echo "<a href=\"JavaScript:renameFolder('renameFolder.php?dir=".urlencode($dir)."&file=".urlencode($entry)."&start=true')\"><img src=\"images/rename.gif\" width=16 height=16 title=\""._DIR_REN_LINK."\" border=0></a>";
}
?>