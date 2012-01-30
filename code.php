<?php	##################
	#
	#	rah_function-plugin for Textpattern
	#	version 0.2
	#	by Jukka Svahn
	#	http://rahforum.biz
	#
	###################

	function rah_function($atts,$thing='') {
		if(!isset($atts['call']))
			return '';

		global $prefs,$is_article_body;

		if(!empty($is_article_body)) {
			if(empty($prefs['allow_article_php_scripting']))
				return '';
			global $thisarticle;
			if(!has_privs('article.php', $thisarticle['authorid']))
				return '';
		} else 
			if(empty($prefs['allow_page_php_scripting']))
				return '';

		$flags = array();
		$function = $atts['call'];
		unset($atts['call']);

		if($thing) {
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
	}?>