<?
foreach ($_POST as $key => $value) {
	if ($key != "searchEngine")
		$settings[$key] = $value;
}
saveSettings($settings);
AuditAction($cfg["constants"]["admin"], " Updating TorrentFlux Search Settings");
$searchEngine = getRequestVar('searchEngine');
if (empty($searchEngine)) $searchEngine = $cfg["searchEngine"];
header("location: admin.php?op=searchSettings&searchEngine=".$searchEngine);
?>
