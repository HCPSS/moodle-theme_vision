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
 * This is built using the bootstrapbase template to allow for new theme's using
 * Moodle's new Bootstrap theme engine
 *
 * @package     theme_vision
 * @copyright   2015 Howard County Public School System
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$THEME->name = 'vision';

// The only thing you need to change in this file when copying it to
// create a new theme is the name above. You also need to change the name
// in version.php and lang/en/theme_essential.php as well.

$THEME->doctype = 'html5';
$THEME->yuicssmodules = array();
$THEME->parents = array('essential');

$THEME->sheets[] = 'moodle-rtl';
$THEME->sheets[] = 'bootstrap-pix';
$THEME->sheets[] = 'moodle-pix';
$THEME->sheets[] = 'essential-pix';
$THEME->sheets[] = 'essential-settings';
$THEME->sheets[] = 'fontawesome';
$THEME->sheets[] = 'vision';
$THEME->sheets[] = 'print';

if ((get_config('theme_essential', 'enablealternativethemecolors1')) ||
    (get_config('theme_essential', 'enablealternativethemecolors2')) ||
    (get_config('theme_essential', 'enablealternativethemecolors3'))
) {
    $THEME->sheets[] = 'essential-alternative';
}

$THEME->sheets[] = 'custom';

$THEME->supportscssoptimisation = false;

if (floatval($CFG->version) >= 2013111803.02) { // 2.6.3+ (Build: 20140522) which has MDL-43995 integrated into it.
    $THEME->enable_dock = true;
    $THEME->javascripts_footer[] = 'dock';
}

$THEME->editor_sheets = array('editor');

$THEME->plugins_exclude_sheets = array('mod' => array('quiz'));

$addregions = array();
if (get_config('theme_essential', 'frontpagemiddleblocks') > 0) {
    $addregions = array('home-left', 'home-middle', 'home-right');
}

$THEME->layouts['incourse'] = array(
    'file' => 'columns3.php',
    'regions' => array('side-pre', 'side-post', 'footer-left', 'footer-middle', 'footer-right'),
    'defaultregion' => 'side-pre',
);

$THEME->javascripts_footer[] = 'coloursswitcher';

$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->csspostprocess = 'theme_vision_process_css';

$THEME->lessfile = 'vision';
