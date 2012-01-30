<?php	##################
	#
	#	rah_function-plugin for Textpattern
	#	version 0.3
	#	by Jukka Svahn
	#	http://rahforum.biz
	#
	#	Copyright (C) 2011 Jukka Svahn <http://rahforum.biz>
	#	Licensed under GNU Genral Public License version 2
	#	http://www.gnu.org/licenses/gpl-2.0.html
	#
	##################

	function rah_function($atts,$thing=NULL) {
		
		if(!isset($atts['call']) || !$atts['call'] || !function_exists($atts['call']))
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

		$flags = array();
		$function = $atts['call'];
		unset($atts['call']);

		if($thing !== NULL) {
			if(isset($atts['thing']))
				$atts['thing'] = parse($thing);
			else
				$flags[] = 'parse($thing)';
		}

		foreach($atts as $key => $att) 
			$flags[] = '$atts["'.$key.'"]';
		$flag = implode(',',$flags);
		$php = '$out = '.$function.'('.$flag.');';
		eval($php);
		return $out;
	}
?>