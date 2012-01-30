<?php	##################
	#
	#	rah_function-plugin for Textpattern
	#	version 0.1
	#	by Jukka Svahn
	#	http://rahforum.biz
	#
	###################

	function rah_function($atts,$thing='') {
		if(!isset($atts['call']))
			return '';
		$flags = array();
		$function = $atts['call'];
		unset($atts['call']);
		if($thing)
			$flags[] = 'parse($thing)';
		foreach($atts as $key => $att) 
			$flags[] = '$atts["'.$key.'"]';
		$flag = implode(',',$flags);
		$php = '$out = '.$function.'('.$flag.');';
		eval($php);
		return $out;
	} ?>