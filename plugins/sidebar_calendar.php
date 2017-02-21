<?php
# Plugin: SidebarCalendar
# This plugin adds a calendar to the sidebar with links to the days that have entries.
# It allows the user to navigate between months, updating the calendar via AJAX calls.

$do_output = false;
if (isset($_GET['month']) && isset($_GET['plugin']) && defined('PLUGIN_DO_OUTPUT')) {
	$do_output = true;
}

# Add this really massive if statements to that we don't end up declaring the 
# same class twice, i.e. if the page is called directly, this class will be defined 
# when it first loads and then again when the plugins are loaded.
if (! class_exists("SidebarCalendar")):  # Start massive if statement

class SidebarCalendar extends Plugin {
	
	function __construct($do_output=0) {
		$this->plugin_desc = _("Provides a link calendar for the sidebar.");
		$this->plugin_version = "0.1.2";
		$this->addOption("caption", _("Title for calendar"), _("Calendar"));
		$this->addOption("show_all", _("Include link to show all entries"),
		                 false, "checkbox");
		
		$this->addOption('no_event',
			_('No event handlers - do output when plugin is created'),
			System::instance()->sys_ini->value("plugins","EventDefaultOff", 0), 
			'checkbox');
			
		parent::__construct();
		
		if ( $this->no_event || 
		     System::instance()->sys_ini->value("plugins","EventForceOff", 0) ) {
			# If either of these is true, then don't set the event handler
			# and rely on explicit invocation for output.
		} else {
			$this->registerEventHandler("sidebar", "OnOutput", "put_calendar");
		}
		$this->registerEventHandler("page", "OnOutput", "add_style");
		$this->registerEventHandler('page', 'OnOutput', 'link_ajax_js');
		
		if ($do_output) $this->put_calendar();
	}

	function get_date_vars() {
		$blog = NewBlog();
		if (! $blog->isBlog() ) return false;

		$year = GET("year");
		$month = sprintf("%02d", GET("month"));
		$day = GET("day") ? GET('day') : 1;
		if ($year && $month) {
			$days = date("t", strtotime($year."-".$month."-01"));
		} elseif (preg_match("/.*".BLOG_ENTRY_PATH."\/(\d{4})\/(\d{2})\/.*/", 
		                      current_uri() )) {	
			$year = preg_replace("/.*".BLOG_ENTRY_PATH."\/(\d{4})\/(\d{2})\/.*/",
			                     "$1", current_uri() );
			$month = preg_replace("/.*".BLOG_ENTRY_PATH."\/(\d{4})\/(\d{2})\/.*/",
			                      "$2", current_uri() );
			$days = date("t", strtotime($year."-".$month."-01"));
		} else {
			$year = date("Y");
			$month = date("m");
			$days = date("t");
		}
		
		return array($year, $month, $day);
	}

	function self_uri($arr=false) {
		if (! $arr) {
			$arr = array();
		}
		$arr['plugin'] = 'sidebar_calendar';
		return make_uri('index.php', $arr);
		
		$ret = localpath_to_uri(__FILE__);
		$urlinfo = parse_url($ret);
		if ( isset($urlinfo['host']) && 
			 SERVER("SERVER_NAME") != $urlinfo['host'] ) {
			$blog = NewBlog();
			$ret = $blog->uri('plugin',
			                  str_replace(".php", "", basename(__FILE__)), 
			                  $arr);
		} else {
			$ret = make_uri($ret, $arr);
		}
	
		return $ret;
	}

	function add_style(&$param) {
		$param->addStylesheet("calendar.css");
	}

	function make_calendar_array($year, $month, $day) {
		
		$blog = NewBlog();
		
		$intl_ts = mktime(0, 0, 0, $month, $day, $year);
		$first_ts = mktime(0, 0, 0, $month, 1, $year);
		
		$num_days = date('t', $intl_ts);
		$first_day = date('w', $first_ts);
		
		$days = array();
		
		# Start with the days from the previous month.
		$prev_days = date('t', $first_ts - 86400);
		$tmp_month = date('m', $first_ts - 86400);
		$tmp_year = date('Y', $first_ts - 86400);
		for ($i = $prev_days - $first_day + 1; $i <= $prev_days; $i++) {
			$days[] = array('day'=>$i, 'month'=>$tmp_month, 'year'=>$tmp_year,
			                'count'=>$blog->getDayCount($tmp_year, $tmp_month, $i),
			                'uri'=>$blog->uri('showday', $tmp_year, $tmp_month, $i));
		}
		
		# Get an array of the days in the month and the number of entries for
		# each day.
		for ($i = 1; $i <= $num_days; $i++) {
			$days[] = array('day'=>$i, 'month'=>$month, 'year'=>$year,
			                'count'=>$blog->getDayCount($year, $month, $i),
			                'uri'=>$blog->uri('showday', $year, $month, $i));
		}
		
		# Now fill out the calendar with the ending and starting days of the 
		# previous and next months.
		$last_day = date('w', mktime(0,0,0, $month, $num_days, $year) + 86400);
		$tmp_month = date('m', mktime(0,0,0, $month, $num_days, $year) + 86400);
		$tmp_year = date('Y', mktime(0,0,0, $month, $num_days, $year) + 86400);
		for ($i = 1; $i <= 7 - $last_day; $i++) {
			$days[] = array('day'=>$i, 'month'=>$tmp_month, 'year'=>$tmp_year,
			                'count'=>$blog->getDayCount($tmp_year, $tmp_month, $i),
			                'uri'=>$blog->uri('showday', $tmp_year, $tmp_month, $i));
		}
		
		return $days;
	}
	
	function get_calendar_string($year, $month, $day) {
		$ret = "<table>\n<tr>\n";
		
		$tmp_ts = mktime(0, 0, 0, $month, $day, $year);
		$tmp_ts -= ( date('w', $tmp_ts) * 86400);
		for ($i = 0; $i < 7; $i++) {
			$ret .= "<th>";
			$ret .= fmtdate('%a', $tmp_ts + $i*96400);
			$ret .= "</th>";
		}
		$ret .= "</tr>\n";
		
		$days = $this->make_calendar_array($year, $month, $day);
		$count = 0;
			
		$this_date= date('Y').'-'.date('m').'-'.date('j');

		for ($i = 0; $i < count($days); $i++) {
			if ($i % 7 == 0) $ret .= "<tr>";
			
			$ret .= '<td';
			if ($days[$i]['year'].'-'.$days[$i]['month'].'-'.$days[$i]['day'] == $this_date) {
				$ret .= ' class="today"';
			}

			if ($days[$i]['count']) {
				$ret .= ' title="'.$days[$i]['count'].' entries">';
				$ret .= '<a href="'.$days[$i]['uri'].'">'.$days[$i]['day'].'</a>';
			} else {
				$ret .= '>';
				$ret .= $days[$i]['day'];
			}
			$ret .= "</td>";
			
			if ($i % 7 == 6) $ret .= "</tr>\n";
		}
		
		$ret .= "</table>\n";
		
		return $ret;
	}
	
	function buildOutput($nodiv=false) {
		$blog = NewBlog();
		if (! $blog->isBlog() ) return false;

		list($year, $month, $day) = $this->get_date_vars();

		$content = '';
		
		$date_ts = mktime(0, 0, 0, $month, $day, $year);
		
		$content .= '<p class="calendar">'."\n";

		$content .= '<a class="calendar-nav-link" href="'.
					$this->self_uri( array('blog'=>$blog->blogid,
		                                   'month'=>($month > 1 ? $month-1 : 12),
		                                   'year'=>($month > 1 ? $year : $year-1))).
		            '">&lt;&lt;</a> ';
		
		$months = $blog->getMonthList($year);
		if (calendar_binsearch_monthlist($months, $year, $month, 0, count($months))) {
			$content .= '<a href="'.$blog->uri('listmonth', $year, $month).'">'.
			            fmtdate("%B", $date_ts)."</a> ".
			            '<a href="'.$blog->uri('listyear', $year).'">'.
			            fmtdate("%Y", $date_ts).'</a>';
		} else {
			$content .= fmtdate("%B", $date_ts)." ";
			if ($months) {
				$content .= '<a href="'.$blog->uri('listyear', $year).'">'.fmtdate("%Y", $date_ts).'</a>';
			} else {
				$content .= fmtdate('%Y', $date_ts);
			}
		}

		$content .= ' <a class="calendar-nav-link" href="'.
		            $this->self_uri(array('blog'=>$blog->blogid,
		                                  'month'=>($month < 12 ? $month+1 : 1),
		                                  'year'=>($month < 12 ? $year : $year+1))).
		            '">&gt;&gt;</a>';

		$content .= "</p>\n";

		$content .= $this->get_calendar_string($year, $month, 1);

		$content .= '<p class="calendar">'.
		            '<a style="margin-right: 5%" '.
		            'href="'.$blog->uri('archives').'">'._('Archives').'</a> ';
		if ($this->show_all) {
			$content .= '<a style="margin-left: 5%" '.
			            'href="'.$blog->uri('listall').'">'.
			            _('Show all').'</a>';
		}

		$content .= '</p>';

		if ($nodiv !== true) {
			$tpl = NewTemplate("sidebar_panel_tpl.php");
			if ($this->caption) $tpl->set('PANEL_TITLE', $this->caption);
			$tpl->set('PANEL_ID', "calendar");
			$tpl->set('PANEL_CLASS', "panel");
			$tpl->set('PANEL_CONTENT', $content);
			$content = $tpl->process();
		}

		return $content;

	}
	
	function put_calendar($nodiv=false) {
		echo $this->buildOutput($nodiv);
	}
	
	function show_page() {
		# Disable IE's page caching to avoid screwing up the request.
		header( "Expires",  "Mon, 26 Jul 1997 05:00:00 GMT" );
		header( "Last-Modified", gmdate( "D, d M Y H:i:s" )." GMT" ); 
		header( "Cache-Control", "no-cache, must-revalidate" ); 
		header( "Pragma", "no-cache" );

		$this->put_calendar(true);
	}

	function link_ajax_js() {
		$blog = NewBlog();
		ob_start();
		?>
		$(document).ready(function () {
			var bindLinks = function (scope) {
				var link = scope.href;
				$('#calendar').css('cursor', 'wait');
				$('#calendar').load(link, function (data) {
					$('#calendar').css('cursor', 'default');
					$('.calendar-nav-link').click(function () {
						return bindLinks(this);
					});
				});
				return false;
			};
			$('.calendar-nav-link').click(function () {
				return bindLinks(this);
			});
		});
		<?php
		$script = ob_get_clean();
		Page::instance()->addInlineScript($script);
	}

}

# Search for a year and month in the array returned by the blog 
# class's getMonthList() method.  Note that this array is sorted
# in reverse chronological order.
function calendar_binsearch_monthlist(&$arr, $year, $month, $start, $len) {
	
	$search_date = $year.$month;

	if ($len <= 0) return false;

	$n = (int)($len / 2);
	$tmpdate = $arr[$start+$n]['year'].$arr[$start+$n]['month'];
	if ($tmpdate == $search_date) {
		return $arr[$start+$n];
	} elseif ($tmpdate < $search_date) {
		return calendar_binsearch_monthlist($arr, $year, $month, $start, $n);
	} else {
		return calendar_binsearch_monthlist($arr, $year, $month, $start+$n, $n-1);
	}
}

if (! PluginManager::instance()->plugin_config->value('sidebarcalendar', 'creator_output', 0)) {
	$sbc = new SidebarCalendar();
}

endif; # End massive if statement

if ($do_output) {
	$sbc = new SidebarCalendar();
	$sbc->put_calendar(true);
} else {
	if (! PluginManager::instance()->plugin_config->value('sidebarcalendar', 'creator_output', 0)) {
		$sbc = new SidebarCalendar();
	}
}
?>