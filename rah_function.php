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
		static $serialized = array(), $whitelist = NULL;
		
		if($whitelist === NULL) {
			$whitelist = defined('rah_function_whitelist') ? 
				do_list(rah_function_whitelist) : array();
		}
		
		if(empty($atts['call'])) {
			trigger_error(gTxt('invalid_attribute_value', array('{name}' => 'call')));
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
		
		foreach($atts as $name => $value) {
			
			if(strpos($name, '_') !== 0 && $name != 'thing') {
				continue;
			}
			
			if($thing !== NULL && substr($name, -5) == 'thing') {
				$value = $atts[$name] = parse($thing);
				$thing = NULL;
			}
			
			$value = trim($value);
		
			if(strpos($name, '_serialized') === 0 && in_array($value, $serialized)) {
				$atts[$name] = unserialize($value);
			}
			
			elseif(strpos($name, '_bool') === 0) {
				$atts[$name] = $value && $value != 'FALSE';
			}
			
			elseif(strpos($name, '_int') === 0) {
				$atts[$name] = (int) $value;
			}
			
			elseif(strpos($name, '_null') === 0) {
				$atts[$name] = NULL;
			}
			
			elseif(strpos($name, '_array') === 0) {
				$atts[$name] = @json_decode($value);
			}
		}
		
		if($thing !== NULL) {
			array_unshift($atts, parse($thing));
		}
		
		foreach(do_list($function) as $index => $name) {
			
			$call = $name;
			
			if(strpos($call, '::')) {
				$call = explode('::', $call);
			}
			
			elseif(strpos($call, '->')) {
				$call = explode('->', $call);
				
				if(class_exists($call[0])) {
					$call[0] = new $call[0];
				}
			}
			
			if(!is_callable($call) || ($whitelist && !in_array($name, $whitelist))) {
				trigger_error(gTxt('invalid_attribute_value', array('{name}' => 'call')));
				return;
			}
			
			$atts = call_user_func_array($call, !$index ? $atts : array($atts));
		}
		
		if(!is_scalar($atts) && !is_array($atts)) {
			trigger_error(gTxt('rah_function_illegal_type', array('{type}' => gettype($atts))));
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