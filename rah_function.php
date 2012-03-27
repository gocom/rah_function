<?php

/**
 * Rah_function plugin for Textpattern CMS
 *
 * @author Jukka Svahn
 * @date 2009-
 * @license GNU GPLv2
 * @link http://rahforum.biz/plugins/rah_function
 *
 * Copyright (C) 2012 Jukka Svahn <http://rahforum.biz>
 * Licensed under GNU Genral Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

	function rah_function($atts, $thing=NULL) {
		
		if(empty($atts['call']) || !function_exists($atts['call']))
			return;

		global $prefs, $is_article_body, $thisarticle;
		
		if($is_article_body) {
			if(
				!$prefs['allow_article_php_scripting'] ||
				!has_privs('article.php', $thisarticle['authorid'])
			)
				return;
		}
		else if(!$prefs['allow_page_php_scripting'])
			return;

		$flags = $temp = array();
		$function = $atts['call'];
		unset($atts['call']);

		if($thing !== NULL) {
			if(isset($atts['thing'])) {
				$atts['thing'] = parse($thing);
			}
			
			else {
				$flags[] = 'parse($thing)';
			}
		}
		
		$i = 0;

		foreach($atts as $value) {
			$i++;
			$temp[$i] = $value;
			$flags[] = '$temp['.$i.']';
		}
		
		$flag = implode(',', $flags);
		eval('$out = '.$function.'('.$flag.');');
		
		if(!is_scalar($out) && !is_array($out)) {
			trigger_error('Returned invalid type, scalar or array required.');
			return;
		}
		
		if(is_bool($out)) {
			$out = $out ? 'TRUE' : 'FALSE';
		}
		
		elseif(is_array($out)) {
			$out = serialize($out);
		}
		
		return $out;
	}
?>