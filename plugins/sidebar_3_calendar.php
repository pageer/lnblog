<?php
class SidebarCalendar extends Plugin {
	
	function SidebarCalendar() {
		$this->plugin_desc = _("Provides a link calendar for the sidebar.");
		$this->plugin_version = "0.1.0";
	}

	function add_style(&$param) {
		$param->addStylesheet("calendar.css");
	}

	function put_calendar() {
		$blog = NewBlog();
		if (! $blog->blogExists() ) return false;
	
		$year = GET("year");
		$month = GET("month");
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

		$ts = strtotime($year."-".$month."-01");
		
		# Populate an array of weeks.  Each week is a 7 element array 
		# containing the day of the month.
		$weeks = array();
		$curr_week = array_fill(0, 7, '');
		$curr_day = date("w", $ts);
		for ($i=1; $i<=$days; $i++) {
			$curr_week[$curr_day] = $i;
			if ($curr_day == 6) {
				
				$weeks[] = $curr_week;
				$curr_week = array_fill(0, 7, '');
				
				# Pick a Sunday to use to get day names.
				if (! isset($first_sunday)) $first_sunday = $i + 1;
			}
			$curr_day = ($curr_day + 1) % 7;
		}
		$weeks[] = $curr_week;
		
		# Generate a list of day names.
		$week_names = array();
		for ($i=$first_sunday; $i < $first_sunday + 7; $i++) {
			$week_names[] = "<th>".fmtdate(USE_STRFTIME?"%a":"D",
			                  strtotime("$year-$month-".sprintf("%02d", $i)))."</th>";
		}

		$links = array($days);
		$entries = $blog->getMonth($year, $month);
		foreach ($entries as $ent) {
			$day = date("j", $ent->post_ts);
			if (! isset($links[intval($day)]) ) {
				$links[intval($day)] = '<a href="'.$blog->getURL().
					BLOG_ENTRY_PATH."/$year/$month/day.php?day=$day\">$day</a>";
			}
		}

		echo "<h3>"._("Calendar")."</h3>";
		#echo "<p class=\"calendar\">";
		#echo "</p>";
	
		$gets = '';
		if (count($_GET) > 0) {
			foreach ($_GET as $name=>$val) {
				$gets = $name."=".$val."&amp;";
			}
		}
	
		if ($month == "12") {
			$next_link = '<a style="margin-left: 10%" '.
			             'href="?'.$gets.'year='.($year+1).'&amp;month=01">'.
			             fmtdate( (USE_STRFTIME?"%b":"M"), 
							          strtotime(($year+1)."-01-01") ).'&nbsp;&gt;&gt;</a>';
			$prev_link = '<a style="margin-right: 10%" '.
			             'href="?'.$gets.'year='.$year.'&amp;month=11">&lt;&lt;&nbsp;'.
			             fmtdate( (USE_STRFTIME?"%b":"M"), 
							          strtotime($year."-11-01") ).'</a>';
		} elseif ($month == "01") {
			$next_link = '<a style="margin-left: 10%" '.
			             'href="?'.$gets.'year='.$year.'&amp;month=02">'.
			             fmtdate( (USE_STRFTIME?"%b":"M"), 
							          strtotime($year."-02-01") ).'&nbsp;&gt;&gt;</a>';
			$prev_link = '<a style="margin-right: 10%" '.
			             'href="?'.$gets.'year='.($year - 1).'&amp;month=12">&lt;&lt;&nbsp;'.
			             fmtdate( (USE_STRFTIME?"%b":"M"), 
							          strtotime(($year - 1)."-12-01") ).'</a>';
		} else {
			$next_link = '<a style="margin-left: 10%" '.
			             'href="?'.$gets.'year='.$year.'&amp;month='.
			             sprintf("%02d", $month + 1).'">'.
			             fmtdate( (USE_STRFTIME?"%b":"M"), 
							          strtotime($year."-".($month + 1)."-01") ).'&nbsp;&gt;&gt;</a>';
			$prev_link = '<a style="margin-right: 10%" '.
			             'href="?'.$gets.'year='.$year.'&amp;month='.
			             sprintf("%02s", $month - 1).'">&lt;&lt;&nbsp;'.
			             fmtdate( (USE_STRFTIME?"%b":"M"), 
							          strtotime($year."-".($month - 1)."-01") ).'</a>';
		}
		echo '<p class="calendar">';
		echo $prev_link;
		if ( is_dir( mkpath(BLOG_ROOT,BLOG_ENTRY_PATH,$year,$month) ) ) {
			echo "<a href=\"".$blog->getURL().BLOG_ENTRY_PATH.
			     "/$year/$month/\">".fmtdate((USE_STRFTIME?"%B":"F"), $ts)."</a>";
		} else {
			echo fmtdate((USE_STRFTIME?"%B":"F"), $ts);
		}
		echo "&nbsp;";
		if ( is_dir( mkpath(BLOG_ROOT,BLOG_ENTRY_PATH,$year) ) ) {
			echo '<a href="'.$blog->getURL().BLOG_ENTRY_PATH.
			     "/$year/\">".fmtdate((USE_STRFTIME?"%Y":"Y"), $ts)."</a>";
		} else {
			echo fmtdate((USE_STRFTIME?"%Y":"Y"), $ts);
		}
		echo $next_link;
		echo '</p>';

		
		echo "<table class=\"calendar\">\n";
		echo "<tr>";
		foreach ($week_names as $name) echo $name;
		echo "</tr>";
		
		foreach ($weeks as $week) {
			echo "<tr>\n";
			for ($i=0; $i<=6; $i++) {
				if (isset($links[$week[$i]])) {
					echo '<td>'.$links[$week[$i]].'</td>';
				} else {
					echo '<td>'.$week[$i].'</td>';
				}
			}
			echo "</tr>";
		}
		echo "</table>";
		echo '<p class="calendar">'.
		     '<a style="margin-right: 20%" '.
			     'href="'.$blog->getURL().BLOG_ENTRY_PATH.'/">'._('Archives').'</a>'.
		     '<a href="'.$blog->getURL().BLOG_ENTRY_PATH.'/all.php">'._('Show all').'</a>'.
		     '</p>';
		echo "";
	}
	
}

$sb = new SidebarCalendar();
$sb->registerEventHandler("sidebar", "OnOutput", "put_calendar");
$sb->registerEventHandler("page", "OnOutput", "add_style");
?>
