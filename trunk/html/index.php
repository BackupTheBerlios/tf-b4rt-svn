<?php
# really messy
# but have to do it slowly not to mess everything
if(isset($_GET['page'])) {
	switch($_GET['page']) {
		default:
			require_once("inc/index.php");
		break;
		case "index":
			require_once("inc/index.php");
		break;
	}
}
else {
# use "old" style
	require_once("inc/index.php");
}
?>