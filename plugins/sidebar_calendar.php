<?php
class SidebarCalendar extends Plugin {
	
	function SidebarCalendar($do_output=0) {
		global $SYSTEM;
		$this->plugin_desc = _("Provides a link calendar for the sidebar.");
		$this->plugin_version = "0.1.1";
		$this->addOption("caption", _("Title for calendar"), _("Calendar"));
		$this->addOption("show_all", _("Include link to show all entries"),
		                 false, "checkbox");
		
		$this->addOption('no_event',
			_('No event handlers - do output when plugin is created'),
			$SYSTEM->sys_ini->value("plugins","EventDefaultOff", 0), 
			'checkbox');
			
		$this->getConfig();
		
		if ( $this->no_event || 
		     $SYSTEM->sys_ini->value("plugins","EventForceOff", 0) ) {
			# If either of these is true, then don't set the event handler
			# and rely on explicit invocation for output.
		} else {
			$this->registerEventHandler("sidebar", "OnOutput", "put_calendar");
			$this->registerEventHandler("page", "OnOutput", "add_style");
		}
		
		if ($do_output) $this->output();
	}

	function add_style(&$param) {
		$param->addStylesheet("calendar.css");
	}

	function put_calendar() {
		$blog = NewBlog();
		if (! $blog->isBlog() ) return false;
	
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

		if ($this->caption) echo "<h3>".$this->caption."</h3>\n";
	
		$gets = '';
		if (count($_GET) > 0) {
			foreach ($_GET as $name=>$val) {
				$gets = $name."=".$val."&amp;";
			}
		}
	
		if ($month == "12") {
			$qsnext = array("year"=>$year+1, "month"=>"01");
			$qsprev = array("year"=>$year, "month"=>"11");
			$labelnext = fmtdate( (USE_STRFTIME?"%b":"M"), strtotime(($year+1)."-01-01") );
			$labelprev = fmtdate( (USE_STRFTIME?"%b":"M"), strtotime($year."-11-01") );

		} elseif ($month == "01") {
			$qsnext = array("year"=>$year, "month"=>"02");
			$qsprev = array("year"=>$year-1, "month"=>"12");
			$labelnext = fmtdate( (USE_STRFTIME?"%b":"M"), strtotime($year."-02-01") );
			$labelprev = fmtdate( (USE_STRFTIME?"%b":"M"), strtotime(($year - 1)."-12-01") );
		} else {
			$qsnext = array("year"=>$year, "month"=>sprintf("%02d", $month + 1));
			$qsprev = array("year"=>$year, "month"=>sprintf("%02d", $month - 1));
			$labelnext = fmtdate( (USE_STRFTIME?"%b":"M"), strtotime($year."-".($month + 1)."-01") );
			$labelprev = fmtdate( (USE_STRFTIME?"%b":"M"), strtotime($year."-".($month - 1)."-01") );
		}
		
		if (is_dir(mkpath(BLOG_ROOT,BLOG_ENTRY_PATH, $qsnext['year'], $qsnext['month']))) {
			$next_link = ' <a class="rlink" href="'.make_uri(false,$qsnext,false).
			             '">'.$labelnext.'&nbsp;&gt;&gt;</a>';
		} else {
			$next_link = "<span class=\"rlink\">$labelnext&nbsp;&gt;&gt;</span>";
		}
		
		#if (is_dir(mkpath(BLOG_ROOT,BLOG_ENTRY_PATH, $qsprev['year'], $qsprev['month']))) {
			$prev_link = '<a class="llink" href="'.make_uri(false,$qsprev,false).
			             '">&lt;&lt;&nbsp;'.$labelprev.'</a> ';
		#} else {
		#	$prev_link = "<spanvclass=\"llink\">&lt;&lt;&nbsp;$labelprev</span>";
		#}
		
		#echo '<div class="panel">';
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
				$cls = ($week[$i] == date('j') ? ' class="today"' : '');
				if (isset($links[$week[$i]])) {
					echo '<td'.$cls.'>'.$links[$week[$i]].'</td>';
				} else {
					echo '<td'.$cls.'>'.$week[$i].'</td>';
				}
			}
			echo "</tr>";
		}
		echo "</table>";
		echo '<p class="calendar">'.
		     '<a style="margin-right: 5%" '.
		     'href="'.$blog->getURL().BLOG_ENTRY_PATH.'/">'._('Archives').'</a> ';
		if ($this->show_all) {
			echo '<a style="margin-left: 5%" '.
			     'href="'.$blog->getURL().BLOG_ENTRY_PATH.'/all.php">'.
			     _('Show all').'</a>';
		}
		echo '</p>';;
	}
	
}

global $PLUGIN_MANAGER;
if (! $PLUGIN_MANAGER->plugin_config->value('recent', 'creator_output', 0)) {
	$sb =& new SidebarCalendar();
}

?>
