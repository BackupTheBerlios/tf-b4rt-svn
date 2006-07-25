<?php
// "Backup Database SQLITE HACK"
//$file = $cfg["db_name"]."_".date("Ymd").".tar.gz";
$file = $cfg["db_name"]."_".$cfg["db_type"]."_".date("Ymd").".tar.gz";
$back_file = $cfg["torrent_file_path"].$file;
$sql_file = $cfg["torrent_file_path"].$cfg["db_name"].".sql";
$sCommand = "";
switch($cfg["db_type"]) {
	case "mysql":
		$sCommand = "mysqldump -h ".$cfg["db_host"]." -u ".$cfg["db_user"]." --password=".$cfg["db_pass"]." --all -f ".$cfg["db_name"]." > ".$sql_file;
		break;
	case "sqlite":
		$sCommand = "sqlite ".$cfg["db_host"]." .dump > ".$sql_file;
		break;
	default:
		// no support for backup-on-demand.
		$sCommand = "";
		break;
}
// "Backup Database SQLITE HACK"
if($sCommand != "") {
	shell_exec($sCommand);
	shell_exec("tar -czvf ".$back_file." ".$sql_file);
	// Get the file size
	$file_size = filesize($back_file);
	// open the file to read
	$fo = fopen($back_file, 'r');
	$fr = fread($fo, $file_size);
	fclose($fo);
	// Set the headers
	header("Content-type: APPLICATION/OCTET-STREAM");
	header("Content-Length: ".$file_size.";");
	header("Content-Disposition: attachement; filename=".$file);
	// send the tar baby
	echo $fr;
	// Cleanup
	shell_exec("rm ".$sql_file);
	shell_exec("rm ".$back_file);
	AuditAction($cfg["constants"]["admin"], _BACKUP_MENU.": ".$file);
}
?>