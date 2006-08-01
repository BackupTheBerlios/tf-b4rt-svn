<?php
/* $Id: admin_default.php 102 2006-07-31 05:01:28Z msn_exploder $ */
$tmpl = new vlibTemplate("themes/".$cfg["default_theme"]."/tmpl/admin_default.tmpl");

$tmpl->setvar('DisplayHead', DisplayHead(_ADMINISTRATION));
$tmpl->setvar('displayMenu', displayMenu());
$tmpl->setvar('displayUserSection', displayUserSection());
$tmpl->setvar('displayActivity', displayActivity($min));
$tmpl->setvar('DisplayFoot', DisplayFoot(true,true));

$tmpl->pparse();
?>