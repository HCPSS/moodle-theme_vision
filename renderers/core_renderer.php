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
 * @package     theme_essential
 * @copyright   2013 Julian Ridden
 * @copyright   2014 Gareth J Barnard, David Bezemer
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_vision_core_renderer extends theme_essential_core_renderer {
    /**
     * Outputs the page's footer
     * @return string HTML fragment
     */
    public function footer() {
        global $CFG;

        $output = $this->container_end_all(true);

        $footer = $this->opencontainers->pop('header/footer');

        // Provide some performance info if required
        $performanceinfo = '';
        if (defined('MDL_PERF') || (!empty($CFG->perfdebug) and $CFG->perfdebug > 7)) {
            $perf = get_performance_info();
            if (defined('MDL_PERFTOLOG') && !function_exists('register_shutdown_function')) {
                error_log("PERF: " . $perf['txt']);
            }
            if (defined('MDL_PERFTOFOOT') || debugging() || $CFG->perfdebug > 7) {
                $performanceinfo = $this->performance_output($perf, $this->get_setting('perfinfo'));
            }
        }

        $footer = str_replace($this->unique_performance_info_token, $performanceinfo, $footer);
        $footer = str_replace($this->unique_end_html_token, $this->page->requires->get_end_code(), $footer);
        $this->page->set_state(moodle_page::STATE_DONE);
        //$info = '<!-- Essential theme version: '.$this->page->theme->settings->version.', developed, enhanced and maintained by Gareth J Barnard: about.me/gjbarnard -->';
        
        // Hide the login block unless it is specifically requested
        if(!isset($_GET['bypass'])){
            $footer .= '<style> .block_login{ display: none; } </style>';
        }
        
        return $output . $footer;
    }
    
    /**
     * Outputs the courses menu
     * @return custom_menu object
     */
    public function custom_menu_courses() {
        global $CFG;

        $coursemenu = new custom_menu();

        $hasdisplaymycourses = $this->get_setting('displaymycourses');
        if (isloggedin() && !isguestuser() && $hasdisplaymycourses) {
            $mycoursetitle = $this->get_setting('mycoursetitle');
            if ($mycoursetitle == 'module') {
                $branchtitle = get_string('mymodules', 'theme_essential');
            } else if ($mycoursetitle == 'unit') {
                $branchtitle = get_string('myunits', 'theme_essential');
            } else if ($mycoursetitle == 'class') {
                $branchtitle = get_string('myclasses', 'theme_essential');
            } else {
                $branchtitle = get_string('mycourses', 'theme_essential');
            }
            $branchlabel = '<i class="fa fa-briefcase"></i>' . $branchtitle;
            $branchurl = new moodle_url('');
            $branchsort = 200;

            $branch = $coursemenu->add($branchlabel, $branchurl, $branchtitle, $branchsort);

            $hometext = get_string('myhome');
            $homelabel = html_writer::tag('i', '', array('class' => 'fa fa-home')).html_writer::tag('span', ' '.$hometext);
            $branch->add($homelabel, new moodle_url('/my/index.php'), $hometext);

            // Get 'My courses' sort preference from admin config.
            if (!$sortorder = $CFG->navsortmycoursessort) {
                $sortorder = 'sortorder';
            }

            // Retrieve courses and add them to the menu when they are visible
            $numcourses = 0;
            if ($courses = enrol_get_my_courses(NULL, $sortorder . ' ASC')) {
                foreach ($courses as $course) {
                    if ($course->visible) {
                        // HCPSS Mod: Change fa-graduation-cap to fa-comments
                        $branch->add('<i class="fa fa-comments"></i>' . format_string($course->fullname), new moodle_url('/course/view.php?id=' . $course->id), format_string($course->shortname));
                        $numcourses += 1;
                    } else if (has_capability('moodle/course:viewhiddencourses', context_system::instance())) {
                        $branchtitle = format_string($course->shortname);
                        $branchlabel = '<span class="dimmed_text"><i class="fa fa-eye-slash"></i>' . format_string($course->fullname) . '</span>';
                        $branchurl = new moodle_url('/course/view.php', array('id' =>$course->id));
                        $branch->add($branchlabel, $branchurl, $branchtitle);
                        $numcourses += 1;
                    }
                }
            }
            if ($numcourses == 0 || empty($courses)) {
                $noenrolments = get_string('noenrolments', 'theme_essential');
                $branch->add('<em>' . $noenrolments . '</em>', new moodle_url('#'), $noenrolments);
            }
        }
        return $this->render_custom_menu($coursemenu);
    }

    /**
     * Outputs the user menu.
     * @return custom_menu object
     */
    public function custom_menu_user() {
        // die if executed during install
        if (during_initial_install()) {
            return false;
        }

        global $USER, $CFG, $DB, $SESSION;
        $loginurl = get_login_url();

        $usermenu = html_writer::start_tag('ul', array('class' => 'nav'));
        $usermenu .= html_writer::start_tag('li', array('class' => 'dropdown'));

        if (!isloggedin()) {
            if ($this->page->pagelayout != 'login') {
                $userpic = '<em><i class="fa fa-sign-in"></i>' . get_string('login') . '</em>';
                $usermenu .= html_writer::link($loginurl, $userpic, array('class' => 'loginurl'));
            }
        } else if (isguestuser()) {
            $userurl = new moodle_url('#');
            $userpic = parent::user_picture($USER, array('link' => false));
            $caret = '<i class="fa fa-caret-right"></i>';
            $userclass = array('class' => 'dropdown-toggle', 'data-toggle' => 'dropdown');
            $usermenu .= html_writer::link($userurl, $userpic . get_string('guest') . $caret, $userclass);

            // Render direct logout link
            $usermenu .= html_writer::start_tag('ul', array('class' => 'dropdown-menu pull-right'));
            $branchlabel = '<em><i class="fa fa-sign-out"></i>' . get_string('logout') . '</em>';
            $branchurl = new moodle_url('/login/logout.php?sesskey=' . sesskey());
            $usermenu .= html_writer::tag('li', html_writer::link($branchurl, $branchlabel));

            // Render Help Link
            $usermenu .= $this->theme_essential_render_helplink();

            $usermenu .= html_writer::end_tag('ul');

        } else {
            $course = $this->page->course;
            $context = context_course::instance($course->id);

            // Output Profile link
            $userurl = new moodle_url('#');
            $userpic = parent::user_picture($USER, array('link' => false));
            $caret = '<i class="fa fa-caret-right"></i>';
            $userclass = array('class' => 'dropdown-toggle', 'data-toggle' => 'dropdown');

            $usermenu .= html_writer::link($userurl, $userpic . $USER->firstname . $caret, $userclass);

            // Start dropdown menu items
            $usermenu .= html_writer::start_tag('ul', array('class' => 'dropdown-menu pull-right'));

            if (\core\session\manager::is_loggedinas()) {
                $realuser = \core\session\manager::get_realuser();
                $branchlabel = '<em><i class="fa fa-key"></i>' . fullname($realuser, true) . get_string('loggedinas', 'theme_essential') . fullname($USER, true) . '</em>';
                $branchurl = new moodle_url('/user/profile.php', array('id' => $USER->id));
                $usermenu .= html_writer::tag('li', html_writer::link($branchurl, $branchlabel));
            } else {
                $branchlabel = '<em><i class="fa fa-user"></i>' . fullname($USER, true) . '</em>';
                $branchurl = new moodle_url('/user/profile.php', array('id' => $USER->id));
                $usermenu .= html_writer::tag('li', html_writer::link($branchurl, $branchlabel));
            }

            if (is_mnet_remote_user($USER) && $idprovider = $DB->get_record('mnet_host', array('id' => $USER->mnethostid))) {
                $branchlabel = '<em><i class="fa fa-users"></i>' . get_string('loggedinfrom', 'theme_essential') . $idprovider->name . '</em>';
                $branchurl = new moodle_url($idprovider->wwwroot);
                $usermenu .= html_writer::tag('li', html_writer::link($branchurl, $branchlabel));
            }

            if (is_role_switched($course->id)) { // Has switched roles
                $branchlabel = '<em><i class="fa fa-users"></i>' . get_string('switchrolereturn') . '</em>';
                $branchurl = new moodle_url('/course/switchrole.php', array('id' => $course->id, 'sesskey' => sesskey(), 'switchrole' => 0, 'returnurl' => $this->page->url->out_as_local_url(false)));
                $usermenu .= html_writer::tag('li', html_writer::link($branchurl, $branchlabel));
            }

            // Add preferences submenu
            $usermenu .= $this->theme_essential_render_preferences($context);

            $usermenu .= html_writer::empty_tag('hr', array('class' => 'sep'));
            
            // Check if messaging is enabled.
            if (!empty($CFG->messaging)) {
                $branchlabel = '<em><i class="fa fa-envelope"></i>' . get_string('pluginname', 'block_messages') . '</em>';
                $branchurl = new moodle_url('/message/index.php');
                $usermenu .= html_writer::tag('li', html_writer::link($branchurl, $branchlabel));
            }

            // Check if user is allowed to view discussions
            if (has_capability('mod/forum:viewdiscussion', $context)) {
                $branchlabel = '<em><i class="fa fa-list-alt"></i>' . get_string('forumposts', 'mod_forum') . '</em>';
                $branchurl = new moodle_url('/mod/forum/user.php', array('id' => $USER->id));
                $usermenu .= html_writer::tag('li', html_writer::link($branchurl, $branchlabel));

                $branchlabel = '<em><i class="fa fa-list"></i>' . get_string('discussions', 'mod_forum') . '</em>';
                $branchurl = new moodle_url('/mod/forum/user.php', array('id' => $USER->id, 'mode' => 'discussions'));
                $usermenu .= html_writer::tag('li', html_writer::link($branchurl, $branchlabel));

                $usermenu .= html_writer::empty_tag('hr', array('class' => 'sep'));
            }
            
            // Render direct logout link
            $branchlabel = '<em><i class="fa fa-sign-out"></i>' . get_string('logout') . '</em>';
            $branchurl = new moodle_url('/login/logout.php?sesskey=' . sesskey());
            $usermenu .= html_writer::tag('li', html_writer::link($branchurl, $branchlabel));

            $usermenu .= html_writer::end_tag('ul');
        }

        $usermenu .= html_writer::end_tag('li');
        $usermenu .= html_writer::end_tag('ul');

        return $usermenu;
    }


    /**
     * Renders preferences submenu
     *
     * @param integer $context
     * @return string $preferences
     */
    protected function theme_essential_render_preferences($context) {
        global $USER, $CFG;
        $label = '<em><i class="fa fa-cog"></i>' . get_string('preferences') . '</em>';
        $preferences = html_writer::start_tag('li', array('class' => 'dropdown-submenu preferences'));
        $preferences .= html_writer::link(new moodle_url('#'), $label, array('class' => 'dropdown-toggle', 'data-toggle' => 'dropdown'));
        $preferences .= html_writer::start_tag('ul', array('class' => 'dropdown-menu'));
        // Check if user is allowed to edit profile
        if (has_capability('moodle/user:editownprofile', $context)) {
            $branchlabel = '<em><i class="fa fa-user"></i>' . get_string('editmyprofile') . '</em>';
            $branchurl = new moodle_url('/user/edit.php', array('id' => $USER->id));
            $preferences .= html_writer::tag('li', html_writer::link($branchurl, $branchlabel));
        }
        
        $preferences .= html_writer::end_tag('ul');
        $preferences .= html_writer::end_tag('li');
        return $preferences;
    }


    public function render_social_network($socialnetwork) {
        if ($this->get_setting($socialnetwork)) {
            $icon = $socialnetwork;
            if ($socialnetwork === 'googleplus') {
                $icon = 'pencil';
            } else if ($socialnetwork === 'website') {
                $icon = 'fire';
            } else if ($socialnetwork === 'ios') {
                $icon = 'apple';
            } else if ($socialnetwork === 'winphone') {
                $icon = 'windows';
            }
            
            $socialhtml = '';
            if ($socialnetwork == 'twitter') {
                // Twitter is the first icon and we want insert some before it
            
            	// Workday
            	$socialhtml .= html_writer::start_tag('li');
            	$socialhtml .= html_writer::start_tag('button', array('type' => "button",
            			'class' => 'socialicon workday',
            			'onclick' => "window.open('https://www.myworkday.com/hcpss/login.flex')",
            			'title' => 'Workday',
            	));
            	$socialhtml .= html_writer::start_tag('i', array('class' => 'fa fa-workday fa-inverse'));
            	$socialhtml .= html_writer::end_tag('i');
            	$socialhtml .= html_writer::start_span('sr-only') . html_writer::end_span();
            	$socialhtml .= html_writer::end_tag('button');
            	$socialhtml .= html_writer::end_tag('li');
            	
                // Synergy
                $socialhtml .= html_writer::start_tag('li');
                $socialhtml .= html_writer::start_tag('button', array('type' => "button",
                    'class' => 'socialicon synergy',
                    'onclick' => "window.open('https://sis.hcpss.org')",
                    'title' => 'Synergy',
                ));
                $socialhtml .= html_writer::start_tag('i', array('class' => 'fa fa-synergy fa-inverse'));
                $socialhtml .= html_writer::end_tag('i');
                $socialhtml .= html_writer::start_span('sr-only') . html_writer::end_span();
                $socialhtml .= html_writer::end_tag('button');
                $socialhtml .= html_writer::end_tag('li');
                
                // Canvas
                $socialhtml .= html_writer::start_tag('li');
                $socialhtml .= html_writer::start_tag('button', array('type' => "button",
                    'class' => 'socialicon canvas',
                    'onclick' => "window.open('https://hcpss.instructure.com')",
                    'title' => 'Canvas',
                    'style' => 'background-image: url("https://s3.amazonaws.com/hcpss.web.site/images/hub/canvasicon.png"); background-position: center;',
                ));
                $socialhtml .= html_writer::start_tag('i', array('class' => 'fa fa-canvas fa-inverse'));
                $socialhtml .= html_writer::end_tag('i');
                $socialhtml .= html_writer::start_span('sr-only') . html_writer::end_span();
                $socialhtml .= html_writer::end_tag('button');
                $socialhtml .= html_writer::end_tag('li');
            }
                
            if ($socialnetwork == 'website') {
                // The theme has no setting for Vimeo, we want to add it
                // before the website (where youtube used to be)
                $socialhtml .= html_writer::start_tag('li');
                $socialhtml .= html_writer::start_tag('button', array('type' => "button",
                    'class' => 'socialicon vimeo',
                    'onclick' => "window.open('https://vimeo.com/hcpss/')",
                    'title' => 'Vimeo',
                ));
                $socialhtml .= html_writer::start_tag('i', array('class' => 'fa fa-vimeo fa-inverse'));
                $socialhtml .= html_writer::end_tag('i');
                $socialhtml .= html_writer::start_span('sr-only') . html_writer::end_span();
                $socialhtml .= html_writer::end_tag('button');
                $socialhtml .= html_writer::end_tag('li');
            }
            
            $socialhtml .= html_writer::start_tag('li');
            $socialhtml .= html_writer::start_tag('button', array('type' => "button",
                'class' => 'socialicon ' . $socialnetwork,
                'onclick' => "window.open('" . $this->get_setting($socialnetwork) . "')",
                'title' => get_string($socialnetwork, 'theme_essential'),
            ));
            $socialhtml .= html_writer::start_tag('i', array('class' => 'fa fa-' . $icon . ' fa-inverse'));
            $socialhtml .= html_writer::end_tag('i');
            $socialhtml .= html_writer::start_span('sr-only') . html_writer::end_span();
            $socialhtml .= html_writer::end_tag('button');
            $socialhtml .= html_writer::end_tag('li');
            
            return $socialhtml;
        } else {
            return false;
        }
    }
}
