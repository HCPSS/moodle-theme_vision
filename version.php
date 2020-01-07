<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Essentials is a basic child theme of Essential to help you as a theme
 * developer create your own child theme of Essential.
 *
 * @package     theme_vision
 * @copyright   2015 Howard County Public Schools
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version = 2020010700; // YYYYMMDDVV.
$plugin->maturity = MATURITY_STABLE; // this version's maturity level.
$plugin->release = '2.0.6';
$plugin->requires  = 2016052301.00; // 3.1.1 (Build: 20160711).
$plugin->component = 'theme_vision';
$plugin->dependencies = array(
    'theme_essential'  => 2016061704
);
