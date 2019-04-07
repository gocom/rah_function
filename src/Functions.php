<?php

/*
 * rah_function - Every PHP function is a Textpattern CMS tag
 * https://github.com/gocom/rah_function
 *
 * Copyright (C) 2019 Jukka Svahn
 *
 * This file is part of rah_function.
 *
 * rah_function is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * rah_function is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with rah_function. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Calls the specified PHP function.
 *
 * @param  array       $atts  Attributes
 * @param  string|null $thing Contained statement
 * @return string User markup
 */
function rah_function($atts, $thing = null)
{
    global $is_article_body, $thisarticle, $variable;
    static $whitelist = null;

    extract(lAtts([
        'call' => null,
        '_is' => null,
        '_assign' => null,
    ], $atts, 0));

    if ($whitelist === null) {
        $whitelist = defined('rah_function_whitelist') ?
            do_list(rah_function_whitelist) : [];
    }

    if (!$call) {
        trigger_error(gTxt('invalid_attribute_value', ['{name}' => 'call']));
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
    } elseif (!get_pref('allow_page_php_scripting')) {
        trigger_error(gTxt('php_code_disabled_page'));
        return;
    }

    unset($atts['call'], $atts['_is'], $atts['_assign']);

    foreach ($atts as $name => $value) {
        if (strpos($name, '_') !== 0 && $name !== 'thing') {
            continue;
        }

        if ($thing !== null && substr($name, -5) === 'thing' && $_is === null) {
            $value = $atts[$name] = parse($thing);
            $thing = null;
        }

        $value = trim($value);

        if (strpos($name, '_bool') === 0) {
            $atts[$name] = $value && $value !== 'FALSE';
        } elseif (strpos($name, '_int') === 0) {
            $atts[$name] = (int) $value;
        } elseif (strpos($name, '_null') === 0) {
            $atts[$name] = null;
        } elseif (strpos($name, '_array') === 0) {
            $atts[$name] = $value === '' ? [] : @json_decode($value, true);

            if (!is_array($atts[$name])) {
                trigger_error(gTxt('invalid_attribute_value', ['{name}' => $name]));
                return;
            }
        } elseif (strpos($name, '_constant') === 0) {
            if (!defined($value)) {
                trigger_error(gTxt('invalid_attribute_value', ['{name}' => $name]));
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
            trigger_error(gTxt('invalid_attribute_value', ['{name}' => 'call']));
            return;
        }

        $atts = call_user_func_array($f, !$index ? $atts : [$atts]);
    }

    if (!is_scalar($atts) && !is_array($atts)) {
        trigger_error(gTxt('rah_function_illegal_type', ['{type}' => gettype($atts)]));
        $atts = '';
    } elseif (is_bool($atts)) {
        $atts = $atts ? 'TRUE' : 'FALSE';
    } elseif (is_array($atts)) {
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
