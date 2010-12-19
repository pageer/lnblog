<?php

class LBParser {

	function LBParser($str='') {
		$this->data = $str;
		$this->curr = 0;
		$this->data_stack = array();
		$this->data_size = 0;
		$this->tag_stack = array();
		$this->tag_size = 0;
		$this->output = '';

		# List of valid LBCode tags.
		# Note that we omit the [*] tag - we'll let the list and numlist 
		# handlers decide what to do with that.
		$this->LBCODE_TAG_LIST = array('url','img','ab','ac','quote','b','i','u',
		                               'q', 'list', 'numlist','code','t',
		                               'img-right', 'img-left','h');

		$this->options = array('default_header_level'=>'3',
		                       'list_separator'=>'[*]');
	}

	function setOption($key, $val) {
		$this->options[$key] = $val;
	}

	function findTag() {

		$start_pos = $this->curr;
		
		do {
			# Get the beginning and ending position.
			$next_tag_start = strpos($this->data, '[', $start_pos);
			$next_tag_end = strpos($this->data, ']', $next_tag_start);

			$ret = array();
			$ret['start'] = $next_tag_start;
			$ret['end'] = $next_tag_end;
			$ret['close'] = false;

			$data = substr($this->data, 
						   $next_tag_start + 1, 
						   $next_tag_end - $next_tag_start - 1);
			if (substr($data, 0, 1) == '/') {
				$ret['close'] = true;
				$data = substr($data, 1);
			}
			
			$eq_pos = strpos($data, '=');
			
			if ($eq_pos !== false) {
				$ret['tag'] = substr($data, 0, $eq_pos);
				$ret['attr'] = substr($data, $eq_pos + 1);
			} else {
				$ret['tag'] = $data;
				$ret['attr'] = '';
			}

			if (in_array($ret['tag'], $this->LBCODE_TAG_LIST) ) {
				return $ret;
			} else {
				$start_pos = $next_tag_end + 1;
			}

		} while ($next_tag_start !== false && $next_tag_end !== false);
		
		return false;
	}

	function handleTag($tag) {
		if (! $tag) return false;

		$pre_data = substr($this->data, 
		                   $this->curr, 
		                   $tag['start'] - $this->curr);
		$this->curr = $tag['end'] + 1;
		
		if ($tag['close']) {
			# If the tag on top of the stack matches this closing tag, then the
			# pre-data is our tag content.
			#echo "<p>".$this->tag_size.": ".count($this->tag_stack)."</p>";
			if ( $this->tag_size > 0 && 
			     $this->tag_stack[$this->tag_size - 1]['tag'] == $tag['tag'] ) {
				$open = array_pop($this->tag_stack);
				$this->tag_size -= 1;
				
				$ret = $this->translate($open, $pre_data);

				# If there are tags on the stack, the push the result onto the 
				# data stack.  Otherwise, add it to the output.
				if ($this->tag_size > 0) {
					$this->data_size = array_push($this->data_stack, $ret);
				} else {
					$this->output .= $ret;
				}

			# If there ARE NO opening tags to match, just dump everything into
			# the outputand let the user sort it out.
			} elseif ($this->tag_size <= 0) {
				$this->output .= $this->handlePlainText($pre_data.'[/'.$tag['tag'].']');

			# If the closing tag doesn't match what's on top of the stack....
			# Let the user figure it out?
			} else {
				$this->output .= $this->handlePlainText($pre_data.'[/'.$tag['tag'].']');
			}
		} else {
			# If there are no tags on the stack, we're in the outer text, so just
			# handle it rather than putting it on the stack.
			if ($this->tag_size == 0) {
				$this->output .= $this->handlePlainText($pre_data);
			} else {
				$this->data_size = array_push($this->data_stack, $pre_data);
			}
			$this->tag_size = array_push($this->tag_stack , $tag);
		}
	}

	function translate($tag, $data) {
		switch ($tag['tag']) {
			case 'h':
				$end = 'h'.$this->options['default_header_level'].'>';
				return '</p><'.$end.$data.'</'.$end."<p>\n";
			case 'url':
				return '<a href="'.$tag['attr'].'">'.$data.'</a>';
			case 'img-right':
				$float = 'style="float:right; clear:none;" ';
			case 'img-left':
				$float = 'style="float:left; clear:none;" ';
			case 'img':
				return '<img src="'.$tag['attr'].'" '.
				             'alt="'.$data.'" '.
				             'title="'.$data.'" '.
				             (isset($float) ? $float : '').'/>';
			case 'ab':
				return '<abbr title="'.$tag['attr'].'">'.$data.'</abbr>';
			case 'ac':
				return '<acronym title="'.$tag['attr'].'">'.$data.'</acronym>';
			case 'quote':
				if ($ret['attr']) $cite_text = ' cite="'.$tag['attr'].'"';
				else $cite_text = '';
				return "</p>\n<blockquote$cite_text>\n<p>$data</p>\n</blockquote>\n<p>";
			case 'b': return "<strong>$data</strong>";
			case 'i': return "<em>$data</em>";
			case 'u': return "<span style=\"text-decoration:underline\">$data</span>";
			case 'q':
				if ($ret['attr']) $cite_text = ' cite="'.$tag['attr'].'"';
				else $cite_text = '';
				return "<quote$cite_text>$data</quote>";
			case 't': return "<tt>$data</tt>";
			case 'code': return "<code style=\"white-space:pre\">$data</code>";
			case 'numlist':
			case 'list':
				if ($tag['tag'] == 'list') 	$htmltag = 'ul';
				else $htmltag = 'ol';
				$items = explode($this->options['list_separator'], $data);
				$ret = "</p>\n<$htmltag>\n";
				foreach ($items as $i) $ret .= "<li>$i</li>\n";
				$ret .= "</$htmltag>\n<p>"; 
				return $ret;
		}
	}

	function handlePlainText($data) {
		#$lines = explode("\n\n", $data);
		#for ($i=0; $i < count($lines); $i++) {
		#	$lines[$i] = str_replace("\n", "<br />\n", $lines[$i]);
		#}
		#return implode("</p>\n<p>", $lines);
		#$ret = str_replace(array("\n", "\r\n"), "<br />\n", $data);
		#$ret = str_replace(array("<br />\n<br />\n", "<br />\r\n<br />\r\n"),
		#                   "</p>\n<p>", $ret);
		#return $ret;
		return $data;
	}

	function parse() {

		echo "Parse: {$this->data}\n";
		while ( ($tag = $this->findTag()) !== false) {
			$this->handleTag($tag);
			print_r($tag);
		}
		if ($this->curr < strlen($this->data)) {
			$this->output .= $this->handlePlainText(substr($this->data, $this->curr));
		}
		return $this->output;
	}

}
