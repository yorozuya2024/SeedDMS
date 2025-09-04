<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005 Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
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

if (!isset($settings))
	require_once("../inc/inc.Settings.php");
require_once("inc/inc.Utils.php");
require_once("inc/inc.LogInit.php");
require_once("inc/inc.Language.php");
require_once("inc/inc.Init.php");
require_once("inc/inc.Extension.php");
require_once("inc/inc.DBInit.php");
require_once("inc/inc.ClassUI.php");
require_once("inc/inc.Authentication.php");

function getTime() { /* {{{ */
	if (function_exists('microtime')) {
		$tm = microtime();
		$tm = explode(' ', $tm);
		return (float) sprintf('%f', $tm[1] + $tm[0]);
	}
	return time();
} /* }}} */

$get = $_GET;

// Redirect to the search page if the navigation search button has been
// selected without supplying any search terms.
if (isset($get["navBar"])) {
	if (!isset($get["folderid"]) || !is_numeric($get["folderid"]) || intval($get["folderid"])<1) {
		$folderid=$settings->_rootFolderID;
	} else {
		$folderid = $get["folderid"];
	}
}

$includecontent = false;
if (isset($get["includecontent"]) && $get["includecontent"])
	$includecontent = true;

$skipdefaultcols = false;
if (isset($get["skipdefaultcols"]) && $get["skipdefaultcols"])
	$skipdefaultcols = true;

$exportoptions = [];
if (isset($get["export_options"]) && $get["export_options"])
	$exportoptions = $get["export_options"];

$newowner = null;
if (isset($get["newowner"]) && is_numeric($get["newowner"]) && $get['newowner'] > 0) {
	$newowner = $dms->getUser((int) $get['newowner']);
}

$newreviewer = null;
if (isset($get["newreviewer"]) && is_numeric($get["newreviewer"]) && $get['newreviewer'] > 0) {
	$newreviewer = $dms->getUser((int) $get['newreviewer']);
}

$newapprover = null;
if (isset($get["newapprover"]) && is_numeric($get["newapprover"]) && $get['newapprover'] > 0) {
	$newapprover = $dms->getUser((int) $get['newapprover']);
}

$changecategory = null;
if (isset($get["changecategory"]) && is_numeric($get["changecategory"]) && $get['changecategory'] > 0) {
	$changecategory = $dms->getDocumentCategory((int) $get['changecategory']);
}
$removecategory = 0;
if (isset($get["removecategory"]) && is_numeric($get["removecategory"]) && $get['removecategory'] > 0) {
	$removecategory = (int) $get['removecategory'];
}

/* Creation date {{{ */
$createstartts = null;
$createstartdate = null;
$createendts = null;
$createenddate = null;
$created['from'] = null;
$created['to'] = null;
if(!empty($get["created"]["from"])) {
	$createstartts = makeTsFromDate($get["created"]["from"]);
	$createstartdate = array('year'=>(int)date('Y', $createstartts), 'month'=>(int)date('m', $createstartts), 'day'=>(int)date('d', $createstartts), 'hour'=>0, 'minute'=>0, 'second'=>0);
	if (!checkdate($createstartdate['month'], $createstartdate['day'], $createstartdate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_create_date_end"));
	}
	$created['from'] = $createstartts;
}
if(!empty($get["created"]["to"])) {
	$createendts = makeTsFromDate($get["created"]["to"]);
	$createenddate = array('year'=>(int)date('Y', $createendts), 'month'=>(int)date('m', $createendts), 'day'=>(int)date('d', $createendts), 'hour'=>23, 'minute'=>59, 'second'=>59);
	if (!checkdate($createenddate['month'], $createenddate['day'], $createenddate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_create_date_end"));
	}
	$created['to'] = $createendts;
}
/* }}} */

/* Modification date {{{ */
$modifystartts = null;
$modifystartdate = null;
$modifyendts = null;
$modifyenddate = null;
$modified['from'] = null;
$modified['to'] = null;
if(!empty($get["modified"]["from"])) {
	$modifystartts = makeTsFromDate($get["modified"]["from"]);
	$modifystartdate = array('year'=>(int)date('Y', $modifystartts), 'month'=>(int)date('m', $modifystartts), 'day'=>(int)date('d', $modifystartts), 'hour'=>0, 'minute'=>0, 'second'=>0);
	if (!checkdate($modifystartdate['month'], $modifystartdate['day'], $modifystartdate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_modification_date_end"));
	}
	$modified['from'] = $modifystartts;
}
if(!empty($get["modified"]["to"])) {
	$modifyendts = makeTsFromDate($get["modified"]["to"]);
	$modifyenddate = array('year'=>(int)date('Y', $modifyendts), 'month'=>(int)date('m', $modifyendts), 'day'=>(int)date('d', $modifyendts), 'hour'=>23, 'minute'=>59, 'second'=>59);
	if (!checkdate($modifyenddate['month'], $modifyenddate['day'], $modifyenddate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_modification_date_end"));
	}
	$modified['to'] = $modifyendts;
}
/* }}} */

/* Filesize {{{ */
$filesizestart = 0;
$filesizeend = 0;
$filesize['from'] = null;
$filesize['to'] = null;
if(!empty($get["filesize"]["from"])) {
	$filesizestart = $get["filesize"]["from"];
	$filesize['from'] = $get["filesize"]["from"];
}
if(!empty($get["filesize"]["to"])) {
	$filesizeend = $get["filesize"]["to"];
	$filesize['to'] = $get["filesize"]["to"];
}
/* }}} */

// Check to see if the search has been restricted to a particular
// document owner.
// $get['owner'] can be a name of an array of names or ids {{{
$owner = [];
$ownernames = []; // Needed by fulltext search
$ownerobjs = []; // Needed by database search
if(!empty($get["owner"])) {
	$owner = $get['owner'];
	if (!is_array($get['owner'])) {
		if(is_numeric($get['owner']))
			$o = $dms->getUser($get['owner']);
		else
			$o = $dms->getUserByLogin($get['owner']);
		if($o) {
			$ownernames[] = $o->getLogin();
			$ownerobjs[] = $o;
		}
	} else {
		foreach($get["owner"] as $l) {
			if($l) {
				if(is_numeric($l))
					$o = $dms->getUser($l);
				else
					$o = $dms->getUserByLogin($l);
				if($o) {
					$ownernames[] = $o->getLogin();
					$ownerobjs[] = $o;
				}
			}
		}
	}
} /* }}} */

// category {{{
$categories = array();
$categorynames = array();
$category = array();
if(isset($get['category']) && $get['category']) {
	$category = $get['category'];
	foreach($get['category'] as $catid) {
		if($catid) {
			if(is_numeric($catid)) {
				if($cat = $dms->getDocumentCategory($catid)) {
					$categories[] = $cat;
					$categorynames[] = $cat->getName();
				}
			} else {
				$categorynames[] = $catid;
			}
		}
	}
} /* }}} */

if (isset($get["orderby"]) && is_string($get["orderby"])) {
	$orderby = $get["orderby"];
} else {
	$orderby = "";
}

$terms = [];
$limit = (isset($get["limit"]) && is_numeric($get["limit"])) ? (int) $get['limit'] : 20;
$fullsearch = ((!isset($get["fullsearch"]) && $settings->_defaultSearchMethod == 'fulltext') || !empty($get["fullsearch"])) && $settings->_enableFullSearch;
$facetsearch = !empty($get["facetsearch"]) && $settings->_enableFullSearch;

if (isset($get["query"]) && is_string($get["query"])) {
	$query = $get["query"];
} else {
	$query = "";
}

// Check to see if the search has been restricted to a particular
// mimetype. {{{
$mimetype = [];
if (isset($get["mimetype"])) {
	if (!is_array($get['mimetype'])) {
		if(!empty($get['mimetype']))
			$mimetype[] = $get['mimetype'];
	} else {
		foreach($get["mimetype"] as $l) {
			if($l)
				$mimetype[] = $l;
		}
	}
} /* }}} */

// status
$status = isset($get['status']) ? $get['status'] : array();

// Get the page number to display. If the result set contains more than
// 25 entries, it is displayed across multiple pages.
//
// This requires that a page number variable be used to track which page the
// user is interested in, and an extra clause on the select statement.
//
// Default page to display is always one.
$pageNumber=1;
if (isset($get["pg"])) {
	if (is_numeric($get["pg"]) && $get["pg"]>0) {
		$pageNumber = (int) $get["pg"];
	}
	elseif (!strcasecmp($get["pg"], "all")) {
		$pageNumber = "all";
	}
}

if($fullsearch) {
	// Search in Fulltext {{{

	// record_type
	if(isset($get['record_type']))
		$record_type = $get['record_type'];
	else
		$record_type = array();

	if (isset($get["attributes"]))
		$attributes = $get["attributes"];
	else
		$attributes = array();

	foreach($attributes as $an=>&$av) {
		if(substr($an, 0, 5) == 'attr_') {
			$tmp = explode('_', $an);
			if($attrdef = $dms->getAttributeDefinition($tmp[1])) {
				switch($attrdef->getType()) {
				/* Turn dates into timestamps */
				case SeedDMS_Core_AttributeDefinition::type_date:
					foreach(['from', 'to'] as $kk)
						if(!empty($av[$kk])) {
							if(!is_numeric($av[$kk])) {
								$av[$kk] = makeTsFromDate($av[$kk]);
							}
						}
					break;
				}
			}
		}
	}

	/* Create $order array for fulltext search */
	$order = ['by'=>'', 'dir'=>''];
	switch($orderby) {
	case 'dd':
		$order = ['by'=>'created', 'dir'=>'desc'];
		break;
	case 'd':
		$order = ['by'=>'created', 'dir'=>'asc'];
		break;
	case 'nd':
		$order = ['by'=>'title', 'dir'=>'desc'];
		break;
	case 'n':
		$order = ['by'=>'title', 'dir'=>'asc'];
		break;
	case 'id':
		$order = ['by'=>'id', 'dir'=>'desc'];
		break;
	case 'i':
		$order = ['by'=>'id', 'dir'=>'asc'];
		break;
	default:
		$order = ['by'=>'', 'dir'=>''];
	}

	//print_r($attributes);exit;
	// Check to see if the search has been restricted to a particular sub-tree in
	// the folder hierarchy.
	$startFolder = null;
	if (isset($get["folderfullsearchid"]) && is_numeric($get["folderfullsearchid"]) && $get["folderfullsearchid"]>0) {
		$targetid = $get["folderfullsearchid"];
		$startFolder = $dms->getFolder($targetid);
		if (!is_object($startFolder)) {
			UI::exitError(getMLText("search"),getMLText("invalid_folder_id"));
		}
	}

	$rootFolder = $dms->getFolder($settings->_rootFolderID);

	if($settings->_fullSearchEngine == 'lucene') {
		Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');
	}

	if(strlen($query) < 4 && strpos($query, '*')) {
		$session->setSplashMsg(array('type'=>'error', 'msg'=>getMLText('splash_invalid_searchterm')));
		$dcount = 0;
		$fcount = 0;
		$totalPages = 0;
		$entries = array();
		$searchTime = 0;
	} else {
		$startTime = getTime();
//		$limit = 20;
		$total = 0;
		$index = $fulltextservice->Indexer();
		if($index) {
			if(!empty($settings->_suggestTerms) && !empty($get['query'])) {
				$st = preg_split("/[\s,]+/", trim($get['query']));
				if($lastterm = end($st))
					$terms = $index->terms($lastterm, $settings->_suggestTerms);
			}
			$lucenesearch = $fulltextservice->Search();
			$searchresult = $lucenesearch->search($query,
				array(
					'record_type'=>$record_type,
					'owner'=>$ownernames,
					'status'=>$status,
					'category'=>$categorynames,
					'user'=>$user->isAdmin() ? [] : [$user->getLogin()],
					'mimetype'=>$mimetype,
					'startFolder'=>$startFolder,
					'rootFolder'=>$rootFolder,
					'created_start'=>$created['from'],
					'created_end'=>$created['to'],
					'modified_start'=>$modified['from'],
					'modified_end'=>$modified['to'],
					'filesize_start'=>$filesize['from'],
					'filesize_end'=>$filesize['to'],
					'attributes'=>$attributes
				), ($pageNumber == 'all' ? array() : array('limit'=>$limit, 'offset'=>$limit * ($pageNumber-1))), $order);
			if($searchresult === false) {
				$session->setSplashMsg(array('type'=>'error', 'msg'=>getMLText('splash_invalid_searchterm')));
				$dcount = 0;
				$fcount = 0;
				$totalPages = 0;
				$entries = array();
				$facets = array();
				$stats = array();
				$searchTime = 0;
			} else {
				$entries = array();
				$facets = $searchresult['facets'];
				$stats = $searchresult['stats'] ?? null;
				$dcount = 0;
				$fcount = 0;
				if($searchresult['hits']) {
					foreach($searchresult['hits'] as $hit) {
						if($hit['document_id'][0] == 'D') {
							if($tmp = $dms->getDocument(substr($hit['document_id'], 1))) {
//								if($tmp->getAccessMode($user) >= M_READ) {
									$tmp->verifyLastestContentExpriry();
									$entries[] = $tmp;
									$dcount++;
//								}
							}
						} elseif($hit['document_id'][0] == 'F') {
							if($tmp = $dms->getFolder(substr($hit['document_id'], 1))) {
//								if($tmp->getAccessMode($user) >= M_READ) {
									$entries[] = $tmp;
									$fcount++;
//								}
							}
						}
					}
					if(isset($facets['record_type'])) {
						$fcount = isset($facets['record_type']['folder']) ? $facets['record_type']['folder'] : 0;
						$dcount = isset($facets['record_type']['document']) ? $facets['record_type']['document'] : 0 ;
					}
				}
				if(/* $pageNumber != 'all' && */$searchresult['count'] > $limit) {
					$totalPages = (int) ($searchresult['count']/$limit);
					if($searchresult['count']%$limit)
						$totalPages++;
//					if($limit > 0)
//						$entries = array_slice($entries, ($pageNumber-1)*$limit, $limit);
				} else {
					$totalPages = 1;
				}
				$total = $searchresult['count'];
			}
			$searchTime = getTime() - $startTime;
			$searchTime = round($searchTime, 2);
		} else {
			$session->setSplashMsg(array('type'=>'error', 'msg'=>getMLText('splash_invalid_search_service')));
			$dcount = 0;
			$fcount = 0;
			$totalPages = 0;
			$entries = array();
			$facets = array();
			$stats = array();
			$searchTime = 0;
		}
	}
	$reception = array();
	// }}}
} else {
	// Search in Database {{{

	/* Select if only documents (0x01), only folders (0x02) or both (0x03)
	 * are found
	 */
	$resultmode = 0x03;
	if (isset($get["resultmode"]) && is_numeric($get["resultmode"])) {
			$resultmode = $get['resultmode'];
	}

	$mode = "AND";
	if (isset($get["mode"]) && is_numeric($get["mode"]) && $get["mode"]==0) {
			$mode = "OR";
	}

	$searchin = array();
	if (isset($get['searchin']) && is_array($get["searchin"])) {
		foreach ($get["searchin"] as $si) {
			if (isset($si) && is_numeric($si)) {
				switch ($si) {
					case 1: // keywords
					case 2: // name
					case 3: // comment
					case 4: // attributes
					case 5: // id
						$searchin[$si] = $si;
						break;
				}
			}
		}
	}

	// if none is checkd search all
	if (count($searchin)==0) $searchin=array(1, 2, 3, 4, 5);

	// Check to see if the search has been restricted to a particular sub-tree in
	// the folder hierarchy.
	if (isset($get["targetid"]) && is_numeric($get["targetid"]) && $get["targetid"]>0) {
		$targetid = $get["targetid"];
		$startFolder = $dms->getFolder($targetid);
	}
	else {
		$startFolder = $dms->getRootFolder();
	}
	if (!is_object($startFolder)) {
		UI::exitError(getMLText("search"),getMLText("invalid_folder_id"));
	}

	/* Revision date {{{ */
	$revisionstartts = null;
	$revisionstartdate = array();
	$revisionendts = null;
	$revisionenddate = array();
	$revised['from'] = null;
	$revised['to'] = null;
	if(!empty($get["revisiondatestart"])) {
		$revisionstartts = makeTsFromDate($get["revisiondatestart"]);
		$revisionstartdate = array('year'=>(int)date('Y', $revisionstartts), 'month'=>(int)date('m', $revisionstartts), 'day'=>(int)date('d', $revisionstartts), 'hour'=>0, 'minute'=>0, 'second'=>0);
		if (!checkdate($revisionstartdate['month'], $revisionstartdate['day'], $revisionstartdate['year'])) {
			UI::exitError(getMLText("search"),getMLText("invalid_revision_date_start"));
		}
		$revised['from'] = $revisionstartts;
	}
	if(!empty($get["revisiondateend"])) {
		$revisionendts = makeTsFromDate($get["revisiondateend"]);
		$revisionenddate = array('year'=>(int)date('Y', $revisionendts), 'month'=>(int)date('m', $revisionendts), 'day'=>(int)date('d', $revisionendts), 'hour'=>23, 'minute'=>59, 'second'=>59);
		if (!checkdate($revisionenddate['month'], $revisionenddate['day'], $revisionenddate['year'])) {
			UI::exitError(getMLText("search"),getMLText("invalid_revision_date_end"));
		}
		$revised['to'] = $revisionendts;
	}
	/* }}} */

	/* Status date {{{ */
	$statusstartdate = array();
	$statusenddate = array();
	if(!empty($get["statusdatestart"])) {
		$statusstartts = makeTsFromDate($get["statusdatestart"]);
		$statusstartdate = array('year'=>(int)date('Y', $statusstartts), 'month'=>(int)date('m', $statusstartts), 'day'=>(int)date('d', $statusstartts), 'hour'=>0, 'minute'=>0, 'second'=>0);
	}
	if ($statusstartdate && !checkdate($statusstartdate['month'], $statusstartdate['day'], $statusstartdate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_status_date_start"));
	}
	if(!empty($get["statusdateend"])) {
		$statusendts = makeTsFromDate($get["statusdateend"]);
		$statusenddate = array('year'=>(int)date('Y', $statusendts), 'month'=>(int)date('m', $statusendts), 'day'=>(int)date('d', $statusendts), 'hour'=>23, 'minute'=>59, 'second'=>59);
	}
	if ($statusenddate && !checkdate($statusenddate['month'], $statusenddate['day'], $statusenddate['year'])) {
		UI::exitError(getMLText("search"),getMLText("invalid_status_date_end"));
	}
	/* }}} */

	/* Expiration date {{{ */
	$expstartdate = array();
	$expenddate = array();
	if(!empty($get["expirationstart"])) {
		$expstartts = makeTsFromDate($get["expirationstart"]);
		$expstartdate = array('year'=>(int)date('Y', $expstartts), 'month'=>(int)date('m', $expstartts), 'day'=>(int)date('d', $expstartts), 'hour'=>0, 'minute'=>0, 'second'=>0);
		if (!checkdate($expstartdate['month'], $expstartdate['day'], $expstartdate['year'])) {
			UI::exitError(getMLText("search"),getMLText("invalid_expiration_date_start"));
		}
	}
	if(!empty($get["expirationend"])) {
		$expendts = makeTsFromDate($get["expirationend"]);
		$expenddate = array('year'=>(int)date('Y', $expendts), 'month'=>(int)date('m', $expendts), 'day'=>(int)date('d', $expendts), 'hour'=>23, 'minute'=>59, 'second'=>59);
		if (!checkdate($expenddate['month'], $expenddate['day'], $expenddate['year'])) {
			UI::exitError(getMLText("search"),getMLText("invalid_expiration_date_end"));
		}
	}
	/* }}} */

	$reception = array();
	if (isset($get["reception"])){
		$reception = $get["reception"];
	}

	/* Do not search for folders if result shall be filtered by status.
	 * If this is not done, unexplainable results will be delivered.
	 * e.g. a search for expired documents of a given user will list
	 * also all folders of that user because the status doesn't apply
	 * to folders.
	 */
//	if($status)
//		$resultmode = 0x01;

	if (isset($get["attributes"]))
		$attributes = $get["attributes"];
	else
		$attributes = array();

	foreach($attributes as $attrdefid=>$attribute) {
		if($attribute) {
			if($attrdef = $dms->getAttributeDefinition($attrdefid)) {
				if($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_date) {
					if(is_array($attribute)) {
						if(!empty($attributes[$attrdefid]['from']))
							$attributes[$attrdefid]['from'] = date('Y-m-d', makeTsFromDate($attribute['from']));
						if(!empty($attributes[$attrdefid]['to']))
							$attributes[$attrdefid]['to'] = date('Y-m-d', makeTsFromDate($attribute['to']));
					} else {
						$attributes[$attrdefid] = date('Y-m-d', makeTsFromDate($attribute));
					}
				}
			}
		}
	}

	// ---------------- Start searching -----------------------------------------
	$startTime = getTime();
	$resArr = $dms->search(array(
		'query'=>$query,
		'limit'=>0,
		'offset'=>0,
		'logicalmode'=>$mode,
		'searchin'=>$searchin,
		'startFolder'=>$startFolder,
		'owner'=>$ownerobjs,
		'status'=>$status,
		'mimetype'=>$mimetype,
		'creationstartdate'=>$created['from'],
		'creationenddate'=>$created['to'],
		'modificationstartdate'=>$modified['from'],
		'modificationenddate'=>$modified['to'],
		'filesizestart'=>$filesize['from'],
		'filesizeend'=>$filesize['to'],
		'categories'=>$categories,
		'attributes'=>$attributes,
		'mode'=>$resultmode,
		'expirationstartdate'=>$expstartdate ? $expstartdate : array(),
		'expirationenddate'=>$expenddate ? $expenddate : array(),
		'revisionstartdate'=>$revisionstartdate ? $revisionstartdate : array(),
		'revisionenddate'=>$revisionenddate ? $revisionenddate : array(),
		'reception'=>$reception,
		'statusstartdate'=>$statusstartdate ? $statusstartdate : array(),
		'statusenddate'=>$statusenddate ? $statusenddate : array(),
		'orderby'=>$orderby
	));
	$total = $resArr['totalDocs'] + $resArr['totalFolders'];
	$searchTime = getTime() - $startTime;
	$searchTime = round($searchTime, 2);

	$entries = array();
	$fcount = 0;
//	if(!isset($get['action']) || $get['action'] != 'export') {
		if($resArr['folders']) {
			foreach ($resArr['folders'] as $entry) {
				if ($entry->getAccessMode($user) >= M_READ) {
					$entries[] = $entry;
					$fcount++;
				}
			}
		}
//	}
	$dcount = 0;
	if($resArr['docs']) {
		foreach ($resArr['docs'] as $entry) {
			if ($entry->getAccessMode($user) >= M_READ) {
				if($entry->getLatestContent()) {
					$entry->verifyLastestContentExpriry();
					$entries[] = $entry;
					$dcount++;
				}
			}
		}
	}
	$totalPages = 1;
	if ((!isset($get['action']) || $get['action'] != 'export') /*&& (!isset($get["pg"]) || strcasecmp($get["pg"], "all"))*/) {
		$totalPages = (int) (count($entries)/$limit);
		if(count($entries)%$limit)
			$totalPages++;
		if($pageNumber != 'all')
			$entries = array_slice($entries, ($pageNumber-1)*$limit, $limit);
	} else
		$totalPages = 1;
	$facets = array();
	$stats = array();
// }}}
}

// -------------- Output results --------------------------------------------

if($settings->_showSingleSearchHit && count($entries) == 1) {
	$entry = $entries[0];
	if($entry->isType('document')) {
		header('Location: ../out/out.ViewDocument.php?documentid='.$entry->getID());
		exit;
	} elseif($entry->isType('folder')) {
		header('Location: ../out/out.ViewFolder.php?folderid='.$entry->getID());
		exit;
	}
} else {
	$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
	$view = UI::factory($theme, $tmp[1], array('dms'=>$dms, 'user'=>$user));
	$accessop = new SeedDMS_AccessOperation($dms, $user, $settings);
	if($view) {
		$view->setParam('facets', $facets);
		$view->setParam('stats', $stats);
		$view->setParam('accessobject', $accessop);
		$view->setParam('query', $query);
		$view->setParam('includecontent', $includecontent);
		$view->setParam('skipdefaultcols', $skipdefaultcols);
		$view->setParam('exportoptions', $exportoptions);
		$view->setParam('marks', isset($get['marks']) ? $get['marks'] : array());
		$view->setParam('newowner', $newowner);
		$view->setParam('newreviewer', $newreviewer);
		$view->setParam('newapprover', $newapprover);
		$view->setParam('changecategory', $changecategory);
		$view->setParam('removecategory', $removecategory);
		$view->setParam('searchhits', $entries);
		$view->setParam('terms', $terms);
		$view->setParam('totalpages', $totalPages);
		$view->setParam('pagenumber', $pageNumber);
		$view->setParam('limit', $limit);
		$view->setParam('searchtime', $searchTime);
		$view->setParam('urlparams', $get);
		$view->setParam('cachedir', $settings->_cacheDir);
		$view->setParam('onepage', $settings->_onePageMode); // do most navigation by reloading areas of pages with ajax
		$view->setParam('showtree', showtree());
		$view->setParam('enableRecursiveCount', $settings->_enableRecursiveCount);
		$view->setParam('maxRecursiveCount', $settings->_maxRecursiveCount);
		$view->setParam('total', $total);
		$view->setParam('totaldocs', $dcount /*resArr['totalDocs']*/);
		$view->setParam('totalfolders', $fcount /*resArr['totalFolders']*/);
		$view->setParam('fullsearch', $fullsearch);
		$view->setParam('facetsearch', $facetsearch);
		$view->setParam('mode', isset($mode) ? $mode : '');
		$view->setParam('orderby', isset($orderby) ? $orderby : '');
		$view->setParam('defaultsearchmethod', !empty($get["fullsearch"]) || $settings->_defaultSearchMethod);
		$view->setParam('resultmode', isset($resultmode) ? $resultmode : '');
		$view->setParam('searchin', isset($searchin) ? $searchin : array());
		$view->setParam('startfolder', isset($startFolder) ? $startFolder : null);
		$view->setParam('owner', $owner);
		$view->setParam('createstartdate', $created['from']);
		$view->setParam('createenddate', $created['to']);
		$view->setParam('created', $created);
		$view->setParam('revisionstartdate', !empty($revisionstartdate) ? getReadableDate($revisionstartts) : '');
		$view->setParam('revisionenddate', !empty($revisionenddate) ? getReadableDate($revisionendts) : '');
		$view->setParam('modifystartdate', $modified['from']);
		$view->setParam('modifyenddate', $modified['to']);
		$view->setParam('modified', $modified);
		$view->setParam('filesizestart', $filesize['from']);
		$view->setParam('filesizeend', $filesize['to']);
		$view->setParam('filesize', $filesize);
		$view->setParam('expstartdate', !empty($expstartdate) ? getReadableDate($expstartts) : '');
		$view->setParam('expenddate', !empty($expenddate) ? getReadableDate($expendts) : '');
		$view->setParam('statusstartdate', !empty($statusstartdate) ? getReadableDate($statusstartts) : '');
		$view->setParam('statusenddate', !empty($statusenddate) ? getReadableDate($statusendts) : '');
		$view->setParam('status', $status);
		$view->setParam('recordtype', isset($record_type) ? $record_type : null);
		$view->setParam('categories', isset($categories) ? $categories : '');
		$view->setParam('category', $category);
		$view->setParam('mimetype', isset($mimetype) ? $mimetype : '');
		$view->setParam('attributes', isset($attributes) ? $attributes : '');
		$attrdefs = $dms->getAllAttributeDefinitions(array(SeedDMS_Core_AttributeDefinition::objtype_document, SeedDMS_Core_AttributeDefinition::objtype_documentcontent, SeedDMS_Core_AttributeDefinition::objtype_folder, SeedDMS_Core_AttributeDefinition::objtype_all));
		$view->setParam('attrdefs', $attrdefs);
		$allCats = $dms->getDocumentCategories();
		$view->setParam('allcategories', $allCats);
		$allUsers = $dms->getAllUsers($settings->_sortUsersInList);
		$view->setParam('allusers', $allUsers);
		$view->setParam('workflowmode', $settings->_workflowMode);
		$view->setParam('enablefullsearch', $settings->_enableFullSearch);
		$view->setParam('previewWidthList', $settings->_previewWidthList);
		$view->setParam('convertToPdf', $settings->_convertToPdf);
		$view->setParam('previewConverters', isset($settings->_converters['preview']) ? $settings->_converters['preview'] : array());
		$view->setParam('conversionmgr', $conversionmgr);
		$view->setParam('timeout', $settings->_cmdTimeout);
		$view->setParam('xsendfile', $settings->_enableXsendfile);
		$view->setParam('reception', $reception);
		$view->setParam('showsinglesearchhit', $settings->_showSingleSearchHit);
		$view($get);
		exit;
	}
}
