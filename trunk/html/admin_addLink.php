<?php
if(!empty($newLink)){
	if(strpos($newLink, "http://" ) !== 0 && strpos($newLink, "https://" ) !== 0 && strpos($newLink, "ftp://" ) !== 0){
		$newLink = "http://".$newLink;
	}
	empty($newSite) && $newSite = $newLink;
	//addNewLink($newLink);
	//AuditAction($cfg["constants"]["admin"], "New "._LINKS_MENU.": ".$newLink);
	addNewLink($newLink,$newSite);
	AuditAction($cfg["constants"]["admin"], "New "._LINKS_MENU.": ".$newSite." [".$newLink."]");
}
header("location: admin.php?op=editLinks");
?>