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
		
		global $prefs, $is_article_body, $thisarticle;
		static $serialized = array();
		
		if(empty($atts['call'])) {
			trigger_error(gTxt('rah_function_call_attribute_required'));
			return;
		}
		
		if($is_article_body) {
			if(
				!$prefs['allow_article_php_scripting'] ||
				!has_privs('article.php', $thisarticle['authorid'])
			) {
				return;
			}
		}
		
		elseif(!$prefs['allow_page_php_scripting']) {
			return;
		}

		$function = $atts['call'];
		unset($atts['call']);

		if($thing !== NULL) {
			if(isset($atts['thing'])) {
				$atts['thing'] = parse($thing);
			}
			else {
				array_unshift($atts, parse($thing));
			}
		}
		
		foreach($atts as $name => $value) {
		
			if(strpos($name, '_serialized') === 0 && in_array($value, $serialized)) {
				$atts[$name] = unserialize($value);
			}
			
			elseif(strpos($name, '_bool') === 0) {
				$atts[$name] = $value && $value != 'FALSE';
			}
			
			elseif(strpos($name, '_int') === 0) {
				$atts[$name] = (int) $value;
			}
		}
		
		foreach(do_list($function) as $call) {
			
			if(!function_exists($call)) {
				trigger_error(gTxt('invalid_attribute_value', array('{name}' => $call)));
				return;
			}
			
			$atts = call_user_func_array($call, is_array($atts) ? $atts : array($atts));
		}
		
		if(!is_scalar($atts) && !is_array($atts)) {
			trigger_error('Returned invalid type, scalar or array required');
			return;
		}
		
		if(is_bool($atts)) {
			$atts = $atts ? 'TRUE' : 'FALSE';
		}
		
		elseif(is_array($atts)) {
			$atts = serialize($atts);
			$serialized[] = $atts;
		}
		
		return $atts;
	}
?>