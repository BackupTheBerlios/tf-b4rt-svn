<?php
if (!isset($lid) && !isset($direction)&& $direction !== "up" && $direction !== "down" ) {
	header("location: admin.php?op=editLinks");
}
$idx=getLinkSortOrder($lid);
$position=array("up"=>-1, "down"=>1);
$new_idx=$idx+$position[$direction];
$sql="UPDATE tf_links SET sort_order=$idx WHERE sort_order=$new_idx";
$db->Execute($sql);
showError($db, $sql);
$sql="UPDATE tf_links SET sort_order=$new_idx WHERE lid=$lid";
$db->Execute($sql);
showError($db, $sql);
header("Location: admin.php?op=editLinks");
?>