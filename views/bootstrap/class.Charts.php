<?php
/**
 * Implementation of Charts view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class which outputs the html page for Charts view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Charts extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		$data = $this->params['data'];
		$type = $this->params['type'];

		header('Content-Type: application/javascript; charset=UTF-8');
?>
	$("<div id='tooltip'></div>").css({
		position: "absolute",
		display: "none",
		padding: "5px",
		color: "white",
		"background-color": "#000",
		"border-radius": "5px",
		opacity: 0.80
	}).appendTo("body");

<?php
if(in_array($type, array('docspermonth'))) {
?>
	var data = [
<?php
	if($data) {
		foreach($data as $i=>$rec) {
			$key = mktime(12, 0, 0, substr($rec['key'], 5, 2), 1, substr($rec['key'], 0, 4)) * 1000;
			echo '["'.$rec['key'].'",'.$rec['total'].'],'."\n";
		}
	}
?>
	];
	$.plot("#chart", [data], {
		xaxis: {
			mode: "categories",
			tickLength: 0,
		},
		series: {
			bars: {
				show: true,
				align: "center",
				barWidth: 0.8,
			},
		},
		grid: {
			hoverable: true,
			clickable: true
		}
	});

	$("#chart").bind("plothover", function (event, pos, item) {
		if(item) {
			var x = item.datapoint[0];//.toFixed(2),
					y = item.datapoint[1];//.toFixed(2);
			$("#tooltip").html(item.series.xaxis.ticks[x].label + ": " + y)
				.css({top: pos.pageY-35, left: pos.pageX+5})
				.fadeIn(200);
		} else {
			$("#tooltip").hide();
		}
	});
<?php
} elseif(in_array($type, array('sizepermonth'))) {
?>
	var data = [
<?php
	if($data) {
		foreach($data as $i=>$rec) {
			$key = mktime(12, 0, 0, substr($rec['key'], 5, 2), 1, substr($rec['key'], 0, 4)) * 1000;
			echo '["'.$rec['key'].'",'.$rec['total'].'],'."\n";
		}
	}
?>
	];
	$.plot("#chart", [data], {
		xaxis: {
			mode: "categories",
			tickLength: 0,
		},
		series: {
			bars: {
				show: true,
				align: "center",
				barWidth: 0.8,
			},
		},
		grid: {
			hoverable: true,
			clickable: true
		}
	});

	$("#chart").bind("plothover", function (event, pos, item) {
		if(item) {
			var x = item.datapoint[0];//.toFixed(2),
					y = item.datapoint[1];//.toFixed(2);
			$("#tooltip").html(item.series.xaxis.ticks[x].label + ": " + formatFileSize(y, false, 2))
				.css({top: pos.pageY-35, left: pos.pageX+5})
				.fadeIn(200);
		} else {
			$("#tooltip").hide();
		}
	});
<?php
} elseif(in_array($type, array('docsaccumulated'))) {
?>
	var data = [
<?php
	if($data) {
		foreach($data as $rec) {
			echo '['.htmlspecialchars($rec['key']).','.$rec['total'].'],'."\n";
		}
	}
?>
	];
	var plot = $.plot("#chart", [data], {
		xaxis: { mode: "time" },
		series: {
			lines: {
				show: true
			},
			points: {
				show: true
			}
		},
		grid: {
			hoverable: true,
			clickable: true
		}
	});

	$("#chart").bind("plothover", function (event, pos, item) {
		if(item) {
			var x = item.datapoint[0];//.toFixed(2),
					y = item.datapoint[1];//.toFixed(2);
			$("#tooltip").html($.plot.formatDate(new Date(x), '%e. %b %Y') + ": " + y)
				.css({top: pos.pageY-35, left: pos.pageX+5})
				.fadeIn(200);
		} else {
			$("#tooltip").hide();
		}
	});
<?php
} else {
?>
	var data = [
<?php
	if($data) {
		foreach($data as $rec) {
			echo '{ label: "'.htmlspecialchars($rec['key']).'", data: [[1,'.$rec['total'].']]},'."\n";
		}
	}
?>
	];
$(document).ready( function() {
	$.plot('#chart', data, {
		series: {
			pie: { 
				show: true,
				radius: 1,
				label: {
					show: true,
					radius: 2/3,
					formatter: labelFormatter,
					threshold: 0.1,
					background: {
						opacity: 0.8
					}
				}
			}
		},
		grid: {
			hoverable: true,
			clickable: true
		},
		legend: {
			show: true,
			container: '#legend'
		}
	});

	$("#chart").bind("plothover", function (event, pos, item) {
		if(item) {
			var x = item.series.data[0][0];//.toFixed(2),
					y = item.series.data[0][1];//.toFixed(2);

			$("#tooltip").html(item.series.label + ": " + y + " (" + Math.round(item.series.percent) + "%)")
				.css({top: pos.pageY-35, left: pos.pageX+5})
				.fadeIn(200);
		} else {
			$("#tooltip").hide();
		}
	});
	function labelFormatter(label, series) {
		return "<div style='font-size:8pt; line-height: 14px; text-align:center; padding:2px; color:black; background: white; border-radius: 5px;'>" + label + "<br/>" + series.data[0][1] + " (" + Math.round(series.percent) + "%)</div>";
	}
});
<?php
}
	} /* }}} */

	/**
	 * Check if it makes sense to show the chart
	 *
	 * e.g. it doesn't make sense to show the documents by category if
	 * there are no categories.
	 *
	 * @param string $type
	 * @return boolean
	 */
	private function showChart($type) { /* {{{ */
		$dms = $this->params['dms'];
		if($type == 'docspercategory') {
			if($cats = $dms->getDocumentCategories())
				return true;
			else
				return false;
		}
		return true;
	} /* }}} */

	protected function getSearchUrl($type, $item) {
		$dms = $this->params['dms'];
		$settings = $this->params['settings'];

		$searchurl = null;
		switch($type) {
		case 'docsperuser':
		case 'sizeperuser':
			$searchurl = $settings->_httpRoot.'out/out.Search.php?fullsearch=0&query=&mode=1&orderby=&resultmode=1&owner%5B%5D='.$item['res'];
			break;
		case 'foldersperuser':
			$searchurl = $settings->_httpRoot.'out/out.Search.php?fullsearch=0&query=&mode=1&orderby=&resultmode=2&owner%5B%5D='.$item['res'];
			break;
		case 'docspercategory':
			$searchurl = $settings->_httpRoot.'out/out.Search.php?fullsearch=0&query=&mode=1&orderby=&resultmode=1&category%5B%5D='.$item['res'];
			break;
		case 'docsperstatus':
			$searchurl = $settings->_httpRoot.'out/out.Search.php?fullsearch=0&query=&mode=1&orderby=&resultmode=1&status%5B%5D='.$item['res'];
			break;
		case 'docspermonth':
		case 'sizepermonth':
			$startmonth = $item['key'].'-01';
			$endmonth = date('Y-m-t', strtotime($startmonth));
			$searchurl = $settings->_httpRoot.'out/out.Search.php?fullsearch=0&query=&mode=1&orderby=&resultmode=1&created%5Bfrom%5D='.$startmonth.'&created%5Bto%5D='.$endmonth;
			break;
		case 'docspermimetype':
			$searchurl = $settings->_httpRoot.'out/out.Search.php?fullsearch=0&query=&mode=1&orderby=&resultmode=1&mimetype%5B%5D='.urlencode($item['key']);
			break;
		case 'docsaccumulated':
			$start = date('Y-m-d', $item['key']/1000);
			$end = date('Y-m-d', $item['key']/1000 + 86400);
			$searchurl = $settings->_httpRoot.'out/out.Search.php?fullsearch=0&query=&mode=1&orderby=&resultmode=1&created%5Bfrom%5D='.$start.'&created%5Bto%5D='.$end;
			break;
		}
		return $searchurl;
	}

	public function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$data = $this->params['data'];
		$type = $this->params['type'];
		$quota = $this->params['quota'];

		$this->htmlAddHeader(
			'<script type="text/javascript" src="../styles/bootstrap/flot/jquery.flot.min.js"></script>'."\n".
			'<script type="text/javascript" src="../styles/bootstrap/flot/jquery.flot.pie.min.js"></script>'."\n".
			'<script type="text/javascript" src="../styles/bootstrap/flot/jquery.flot.categories.min.js"></script>'."\n".
			'<script type="text/javascript" src="../styles/bootstrap/flot/jquery.flot.time.min.js"></script>'."\n");

		$this->htmlStartPage(getMLText("folders_and_documents_statistic"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

		$this->rowStart();
		$this->columnStart(3);
		$this->contentHeading(getMLText("chart_selection"));
		$this->contentContainerStart();
		foreach(array('docsperuser', 'foldersperuser', 'sizeperuser', 'sizepermonth','docspermimetype', 'docspercategory', 'docsperstatus', 'docspermonth', 'docsaccumulated') as $atype) {
			if($this->showChart($atype))
				echo "<div><a href=\"?type=".$atype."\">".getMLText('chart_'.$atype.'_title')."</a></div>\n";
		}
		$this->contentContainerEnd();
		$this->columnEnd();

		if(in_array($type, array('sizepermonth', 'docspermonth', 'docsaccumulated'))) {
			$this->columnStart(9);
		} else {
			$this->columnStart(6);
		}
		$this->contentHeading(getMLText('chart_'.$type.'_title'));
		$this->contentContainerStart();
?>
<div id="chart" style="height: 400px;" class="chart"></div>
<?php
		$this->contentContainerEnd();
		echo "<table class=\"table table-condensed table-sm table-hover\">";
		echo "<tr>";
		echo "<th>".getMLText('chart_'.$type.'_title')."</th>";
		echo "<th>".getMLText('total')."</th>";
		$types = array('docspermonth', 'docsaccumulated');
		if($quota)
			$types[] = 'sizeperuser';
		if(in_array($type, $types))
			echo "<th></th>";
		echo "<th>Search</th>";
		echo "</tr>";
		$total = 0;
		switch($type) {
		case 'docspermonth':
		case 'docsperuser':
		case 'foldersperuser':
		case 'docspermimetype':
		case 'docspercategory':
		case 'docsperstatus':
			$oldtotal = 0;
			foreach($data as $item) {
				echo "<tr>";
				echo "<td>".htmlspecialchars($item['key'])."</td>";
				echo "<td>".$item['total']."</td>";
				if(in_array($type, array('docspermonth')))
					echo "<td>".sprintf('%+d', $item['total']-$oldtotal)."</td>";
				$searchurl = $this->getSearchUrl($type, $item);
				if($searchurl)
					echo "<td><a href=\"".$searchurl."\"><i class=\"fa fa-search\"></i></a></td>";
				else
					echo "<td></td>";
				echo "</tr>";
				$oldtotal = $item['total'];
				$total += $item['total'];
			}
			echo "<tr><th></th><th>".$total."<th></tr>";
			break;
		case 'docsaccumulated':
			$oldtotal = 0;
			foreach($data as $item) {
				echo "<tr>";
				echo "<td>".getReadableDate($item['key']/1000)."</td>";
				echo "<td>".$item['total']."</td>";
				echo "<td>".sprintf('%+d', $item['total']-$oldtotal)."</td>";
				$searchurl = $this->getSearchUrl($type, $item);
				if($searchurl)
					echo "<td><a href=\"".$searchurl."\"><i class=\"fa fa-search\"></i></a></td>";
				else
					echo "<td></td>";
				echo "</tr>";
				$oldtotal = $item['total'];
				$total += $item['total'];
			}
			break;
		case 'sizeperuser':
			foreach($data as $item) {
				$currUser = $dms->getUser($item['res']);
				echo "<tr><td>".htmlspecialchars($item['key'])."</td>";
				echo "<td>".SeedDMS_Core_File::format_filesize((int) $item['total'])."</td>";
				if($quota) {
					echo "<td width=\"100\">";
					$qt = $currUser->getQuota() ? $currUser->getQuota() : $quota;
					echo $this->getProgressBar($currUser->getUsedDiskSpace(), $qt);
					echo "</td>";
				}
				$searchurl = $this->getSearchUrl($type, $item);
				if($searchurl)
					echo "<td><a href=\"".$searchurl."\"><i class=\"fa fa-search\"></i></a></td>";
				else
					echo "<td></td>";
				echo "</tr>";
				$total += $item['total'];
			}
			echo "<tr><th></th><th>".SeedDMS_Core_File::format_filesize($total)."<th></tr>";
			break;
		case 'sizepermonth':
			foreach($data as $item) {
				echo "<tr><td>".htmlspecialchars($item['key'])."</td><td>".SeedDMS_Core_File::format_filesize((int) $item['total'])."</td>";
				$searchurl = $this->getSearchUrl($type, $item);
				if($searchurl)
					echo "<td><a href=\"".$searchurl."\"><i class=\"fa fa-search\"></i></a></td>";
				else
					echo "<td></td>";
				echo "</tr>";
				$total += $item['total'];
			}
			echo "<tr><th></th><th>".SeedDMS_Core_File::format_filesize($total)."<th></tr>";
			break;
		}
		echo "</table>";
		$this->columnEnd();

		if(!in_array($type, array('sizepermonth', 'docspermonth', 'docsaccumulated'))) {
			$this->columnStart(3);
			$this->contentHeading(getMLText('legend'));
			$this->contentContainerStart('', 'legend');
			$this->contentContainerEnd();
			$this->columnEnd();
		}

		$this->rowEnd();
		$this->contentContainerEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
