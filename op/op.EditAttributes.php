<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2010-2016 Uwe Steinmann
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

include("../inc/inc.Settings.php");
include("../inc/inc.Utils.php");
include("../inc/inc.LogInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassController.php");
include("../inc/inc.Authentication.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$controller = Controller::factory($tmp[1], array('dms'=>$dms, 'user'=>$user));

/* Check if the form data comes from a trusted request */
if(!checkFormKey('editattributes')) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_request_token"))),getMLText("invalid_request_token"));
}

$accessop = new SeedDMS_AccessOperation($dms, $user, $settings);
if (!$accessop->check_controller_access('EditAttributes', $_POST)) {
	UI::exitError(getMLText("folder_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("access_denied"));
}

if (!isset($_POST["documentid"]) || !is_numeric($_POST["documentid"]) || intval($_POST["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$documentid = $_POST["documentid"];
$document = $dms->getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$folder = $document->getFolder();

if ($document->getAccessMode($user, 'editDocumentContentAttributes') < M_READWRITE) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

$versionid = $_POST["version"];
$version = $document->getContentByVersion($versionid);

if (!is_object($version)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

/* Make a real copy of each attribute because setting a new attribute value
 * will just update the old attribute object in array attributes[] and hence
 * also update the old value
 */
$oldattributes = array();
foreach($version->getAttributes() as $ai=>$aa)
	$oldattributes[$ai] = clone $aa;

$attributes = $_POST["attributes"];
$controller->setParam('document', $document);
$controller->setParam('version', $version);
$controller->setParam('attributes', $attributes);
if(!$controller()) {
	$err = $controller->getErrorMsg();
	if(is_string($err))
		$errmsg = getMLText($err);
	elseif(is_array($err)) {
		$errmsg = getMLText($err[0], $err[1]);
	} else {
		$errmsg = $err;
	}
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())), $errmsg);
}

if($notifier) {
	$notifier->sendChangedVersionAttributesMail($version, $user, $oldattributes);
}

add_log_line("?documentid=".$documentid);

header("Location:../out/out.DocumentVersionDetail.php?documentid=".$documentid."&version=".$versionid);
