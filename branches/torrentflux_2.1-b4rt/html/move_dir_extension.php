<script type="text/javascript" language="JavaScript">
	var popUpWin=0;
	function moveFile(name_file) {
		if(popUpWin) {
			if(!popUpWin.closed) popUpWin.close();
		}
		popUpWin = open(name_file,'_blank','toolbar=no,location=0,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=600,height=430');
	}
</script>
<?php
if ($cfg["enable_move"] == 1) {
	echo "<a href=\"JavaScript:moveFile('move.php?path=".urlencode($dir.$entry)."&start=true')\"><img src=\"images/_move.gif\" width=16 height=16 title=\""._DIR_MOVE_LINK."\" border=0></a>";
}
?>