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

Txp::get('\Textpattern\Tag\Registry')
    ->register('rah_function', 'rah_function')
    ->register('rah_function', 'rah_fn');
