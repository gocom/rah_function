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
		static $whitelist = NULL;
		
		extract(lAtts(array(
			'call' => NULL,
			'_is' => NULL,
		), $atts, 0));
		
		if($whitelist === NULL) {
			$whitelist = defined('rah_function_whitelist') ? 
				do_list(rah_function_whitelist) : array();
		}
		
		if(!$call) {
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

		unset($atts['call'], $atts['_is']);
		
		foreach($atts as $name => $value) {
			
			if(strpos($name, '_') !== 0 && $name != 'thing') {
				continue;
			}
			
			if($thing !== NULL && substr($name, -5) == 'thing' && $_is === NULL) {
				$value = $atts[$name] = parse($thing);
				$thing = NULL;
			}
			
			$value = trim($value);
			
			if(strpos($name, '_bool') === 0) {
				$atts[$name] = $value && $value != 'FALSE';
			}
			
			elseif(strpos($name, '_int') === 0) {
				$atts[$name] = (int) $value;
			}
			
			elseif(strpos($name, '_null') === 0) {
				$atts[$name] = NULL;
			}
			
			elseif(strpos($name, '_array') === 0) {
				
				$atts[$name] = $value === '' ? array() : @json_decode($value, true);
				
				if(!is_array($atts[$name])) {
					trigger_error(gTxt('invalid_attribute_value', array('{name}' => $name)));
					return;
				}
			}
		}
		
		if($thing !== NULL && $_is === NULL) {
			array_unshift($atts, parse($thing));
		}
		
		foreach(do_list($call) as $index => $name) {
			
			$f = $name;
			
			if(strpos($f, '::')) {
				$f = explode('::', $f);
			}
			
			elseif(strpos($f, '->')) {
				$f = explode('->', $f);
				
				if(class_exists($f[0])) {
					$f[0] = new $f[0];
				}
			}
			
			if(!is_callable($f) || ($whitelist && !in_array($name, $whitelist))) {
				trigger_error(gTxt('invalid_attribute_value', array('{name}' => 'call')));
				return;
			}
			
			$atts = call_user_func_array($f, !$index ? $atts : array($atts));
		}
		
		if(!is_scalar($atts) && !is_array($atts)) {
			trigger_error(gTxt('rah_function_illegal_type', array('{type}' => gettype($atts))));
			$atts = '';
		}
		
		elseif(is_bool($atts)) {
			$atts = $atts ? 'TRUE' : 'FALSE';
		}
		
		elseif(is_array($atts)) {
			$atts = json_encode($atts);
		}
		
		if($_is !== NULL && $thing !== NULL) {
			return parse(EvalElse($thing, $atts === $_is));
		}
		
		return $atts;
	}
?>