<?php
if(!empty($newLink)){
	if(strpos($newLink, "http://" ) !== 0 && strpos($newLink, "https://" ) !== 0 && strpos($newLink, "ftp://" ) !== 0){
		$newLink = "http://".$newLink;
	}
	empty($newSite) && $newSite = $newLink;
	$oldLink=getLink($lid);
	$oldSite=getSite($lid);
	alterLink($lid,$newLink,$newSite);
	AuditAction($cfg["constants"]["admin"], "Change Link: ".$oldSite." [".$oldLink."] -> ".$newSite." [".$newLink."]");
}
header("location: admin.php?op=editLinks");
?>