<?php

/* $Id$ */

/*******************************************************************************

 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html

*******************************************************************************/

class dir
{
	var $name;
	var $subdirs;
	var $files;
	var $num;
	var $prio;

	function dir($name,$num,$prio) {
		$this->name = $name;
		$this->num = $num;
		$this->prio = $prio;
		$this->files = array();
		$this->subdirs = array();
	}

	function &addFile($file) {
		$this->files[] =& $file;
		return $file;
	}

	function &addDir($dir) {
		$this->subdirs[] =& $dir;
		return $dir;
	}

	// code changed to support php4
	// thx to Mistar Muffin
	function &findDir($name) {
		foreach (array_keys($this->subdirs) as $v) {
			$dir =& $this->subdirs[$v];
			if($dir->name == $name)
				return $dir;
		}
		$retVal = false;
		return $retVal;
	}

	function draw($parent) {
		$draw = ("d.add(".$this->num.",".$parent.",\"".$this->name."\",".$this->prio.",0);\n");
		foreach($this->subdirs as $v)
			$draw .= $v->draw($this->num);
		foreach($this->files as $v) {
			if(is_object($v))
			  $draw .= ("d.add(".$v->num.",".$this->num.",\"".$v->name."\",".$v->prio.",".$v->size.");\n");
		}
		return $draw;
	}

}

class file {

	var $name;
	var $prio;
	var $size;
	var $num;

	function file($name,$num,$size,$prio) {
		$this->name = $name;
		$this->num	= $num;
		$this->size = $size;
		$this->prio = $prio;
	}
}

function showMetaInfo($torrent, $allowSave=false) {
	global $cfg;
	if (empty($torrent)) {
		$showMetaInfo = $cfg['_NORECORDSFOUND'];
	} elseif ($cfg["enable_file_priority"]) {
		$prioFileName = $cfg["transfer_file_path"].getAliasName($torrent).".prio";
		require_once('inc/classes/BDecode.php');
		$showMetaInfo = '<link rel="StyleSheet" href="themes/'.$cfg["theme"].'/css/dtree.css" type="text/css" /><script type="text/javascript" src="themes/'.$cfg["theme"].'/scripts/dtree.js"></script>';
		$ftorrent=$cfg["transfer_file_path"].$torrent;
		$fp = fopen($ftorrent, "rd");
		$alltorrent = fread($fp, filesize($ftorrent));
		fclose($fp);
		$btmeta = BDecode($alltorrent);
		$torrent_size = $btmeta["info"]["piece length"] * (strlen($btmeta["info"]["pieces"]) / 20);
		if (array_key_exists('files',$btmeta['info']))
			$dirnum = count($btmeta['info']['files']);
		else
			$dirnum = 0;
		if ( is_readable($prioFileName)) {
			$prio = split(',',file_get_contents($prioFileName));
			$prio = array_splice($prio,1);
		} else {
			$prio = array();
			for($i=0;$i<$dirnum;$i++)
				$prio[$i] = -1;
		}
		$tree = new dir("/",$dirnum,isset($prio[$dirnum])?$prio[$dirnum]:-1);
		if (array_key_exists('files',$btmeta['info'])) {
			foreach( $btmeta['info']['files'] as $filenum => $file) {
				$depth = count($file['path']);
				$branch =& $tree;
				for($i=0; $i < $depth; $i++) {
					if ($i != $depth-1) {
						$d =& $branch->findDir($file['path'][$i]);
						if($d) {
							$branch =& $d;
						} else {
							$dirnum++;
							$d =& $branch->addDir(new dir($file['path'][$i], $dirnum, (isset($prio[$dirnum])?$prio[$dirnum]:-1)));
							$branch =& $d;
						}
					} else {
						$branch->addFile(new file($file['path'][$i]." (".$file['length'].")",$filenum,$file['length'],$prio[$filenum]));
					}
				}
			}
		}
		$showMetaInfo .= "<table><tr>";
		$showMetaInfo .= "<tr><td width=\"110\">Metainfo File:</td><td>".$torrent."</td></tr>";
		$showMetaInfo .= "<tr><td>Directory Name:</td><td>".$btmeta['info']['name']."</td></tr>";
		$showMetaInfo .= "<tr><td>Announce URL:</td><td>".$btmeta['announce']."</td></tr>";
		if(array_key_exists('comment',$btmeta))
			$showMetaInfo .= "<tr><td valign=\"top\">Comment:</td><td>".$btmeta['comment']."</td></tr>";
		$showMetaInfo .= "<tr><td>Created:</td><td>".date("F j, Y, g:i a",$btmeta['creation date'])."</td></tr>";
		$showMetaInfo .= "<tr><td>Torrent Size:</td><td>".$torrent_size." (".formatBytesTokBMBGBTB($torrent_size).")</td></tr>";
		$showMetaInfo .= "<tr><td>Chunk size:</td><td>".$btmeta['info']['piece length']." (".formatBytesTokBMBGBTB($btmeta['info']['piece length']).")</td></tr>";
		if (array_key_exists('files',$btmeta['info'])) {
			$showMetaInfo .= "<tr><td>Selected size:</td><td id=\"sel\">0</td></tr>";
			$showMetaInfo .= "</table><br>\n";
			if ($allowSave) {
				$showMetaInfo .= "<form name=\"priority\" action=\"index.php?iid=index\" method=\"POST\" >";
				$showMetaInfo .= "<input type=\"hidden\" name=\"torrent\" value=\"".$torrent."\" >";
				$showMetaInfo .= "<input type=\"hidden\" name=\"setPriorityOnly\" value=\"true\" >";
			}
			$showMetaInfo .= "<script type=\"text/javascript\">\n";
			$showMetaInfo .= "var sel = 0;\n";
			$showMetaInfo .= "d = new dTree('d');\n";
			$showMetaInfo .= $tree->draw(-1);
			$showMetaInfo .= "document.write(d);\n";
			$showMetaInfo .= "sel = getSizes();\n";
			$showMetaInfo .= "drawSel();\n";
			$showMetaInfo .= "</script>\n";
			$showMetaInfo .= "<input type=\"hidden\" name=\"filecount\" value=\"".count($btmeta['info']['files'])."\">";
			$showMetaInfo .= "<input type=\"hidden\" name=\"count\" value=\"".$dirnum."\">";
			$showMetaInfo .= "<br>";
			if ($allowSave) {
				$showMetaInfo .= '<input type="submit" value="Save" >';
				$showMetaInfo .= "<br>";
			}
			$showMetaInfo .= "</form>";
		} else {
			$showMetaInfo .= "</table><br>";
			$showMetaInfo .= $btmeta['info']['name'].$torrent_size." (".formatBytesTokBMBGBTB($torrent_size).")";
		}
	} else {
		$result = getTorrentMetaInfo($torrent);
		$showMetaInfo = "<pre>".$result."</pre>";
	}
	return $showMetaInfo;
}
?>