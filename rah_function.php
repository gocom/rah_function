<?php

/**
 * Rah_function plugin for Textpattern CMS.
 *
 * @author  Jukka Svahn
 * @license GNU GPLv2
 * @link    http://rahforum.biz/plugins/rah_function
 *
 * Copyright (C) 2013 Jukka Svahn http://rahforum.biz
 * Licensed under GNU Genral Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

function rah_function($atts, $thing = null)
{
    global $is_article_body, $thisarticle, $variable;
    static $whitelist = null;

    extract(lAtts(array(
        'call'    => null,
        '_is'     => null,
        '_assign' => null,
    ), $atts, 0));

    if ($whitelist === null) {
        $whitelist = defined('rah_function_whitelist') ? 
            do_list(rah_function_whitelist) : array();
    }

    if (!$call) {
        trigger_error(gTxt('invalid_attribute_value', array('{name}' => 'call')));
        return;
    }

    if ($is_article_body) {
        if (!get_pref('allow_article_php_scripting')) {
            trigger_error(gTxt('php_code_disabled_article'));
            return;
        }

        if (!has_privs('article.php', $thisarticle['authorid'])) {
            trigger_error(gTxt('php_code_forbidden_user'));
            return;
        }
    } else if (!get_pref('allow_page_php_scripting')) {
        trigger_error(gTxt('php_code_disabled_page'));
        return;
    }

    unset($atts['call'], $atts['_is'], $atts['_assign']);

    foreach ($atts as $name => $value) {
        if (strpos($name, '_') !== 0 && $name != 'thing') {
            continue;
        }

        if ($thing !== null && substr($name, -5) == 'thing' && $_is === null) {
            $value = $atts[$name] = parse($thing);
            $thing = null;
        }

        $value = trim($value);

        if (strpos($name, '_bool') === 0) {
            $atts[$name] = $value && $value != 'FALSE';
        } elseif (strpos($name, '_int') === 0) {
            $atts[$name] = (int) $value;
        } elseif (strpos($name, '_null') === 0) {
            $atts[$name] = null;
        } elseif (strpos($name, '_array') === 0) {
            $atts[$name] = $value === '' ? array() : @json_decode($value, true);

            if (!is_array($atts[$name])) {
                trigger_error(gTxt('invalid_attribute_value', array('{name}' => $name)));
                return;
            }
        } elseif (strpos($name, '_constant') === 0) {
            if (!defined($value)) {
                trigger_error(gTxt('invalid_attribute_value', array('{name}' => $name)));
                return;
            }

            $atts[$name] = constant($value);
        }
    }

    if ($thing !== null && $_is === null) {
        array_unshift($atts, parse($thing));
    }

    foreach (do_list($call) as $index => $name) {    
        $f = $name;

        if (strpos($f, '::')) {
            $f = explode('::', $f);
        } elseif (strpos($f, '->')) {
            $f = explode('->', $f);

            if (class_exists($f[0])) {
                $f[0] = new $f[0];
            }
        }

        if (!is_callable($f) || ($whitelist && !in_array($name, $whitelist))) {
            trigger_error(gTxt('invalid_attribute_value', array('{name}' => 'call')));
            return;
        }

        $atts = call_user_func_array($f, !$index ? $atts : array($atts));
    }

    if (!is_scalar($atts) && !is_array($atts)) {
        trigger_error(gTxt('rah_function_illegal_type', array('{type}' => gettype($atts))));
        $atts = '';
    } else if (is_bool($atts)) {
        $atts = $atts ? 'TRUE' : 'FALSE';
    } else if (is_array($atts)) {
        if ($_assign) {
            foreach (do_list($_assign) as $name) {
                $variable[$name] = (string) current($atts);
                next($atts);
            }

            $_assign = null;
        }

        $atts = json_encode($atts);
    }

    if ($_assign) {
        $variable[$_assign] = $atts;
    }

    if ($_is !== null && $thing !== null) {
        return parse(EvalElse($thing, $atts === $_is));
    }

    return $atts;
}
