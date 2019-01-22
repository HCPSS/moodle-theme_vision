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

namespace theme_vision\output;

use theme_essential\output\core_renderer as base_renderer;
use html_writer;
use custom_menu;
use moodle_page;
use context_course;
use moodle_url;

class core_renderer extends base_renderer {
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
	 * The parent class contains this function, but marked it as private.
	 */
	protected function getfontawesomemarkup($theicon, $classes = array(), $attributes = array(), $content = '') {
        $classes[] = 'fa fa-'.$theicon;
        $attributes['aria-hidden'] = 'true';
        $attributes['class'] = implode(' ', $classes);
        return html_writer::tag('span', $content, $attributes);
    }

    /**
     * Outputs the courses menu
     * @return custom_menu object
     */
    public function custom_menu_courses() {
        global $CFG;

        $coursemenu = new custom_menu();

        $hasdisplaymycourses = \theme_essential\toolbox::get_setting('displaymycourses');
        if (isloggedin() && !isguestuser() && $hasdisplaymycourses) {
            $mycoursesorder = \theme_essential\toolbox::get_setting('mycoursesorder');
            if (!$mycoursesorder) {
                $mycoursesorder = 1;
            }

            $lateststring = '';
            if ($mycoursesorder == 3) {
                $lateststring = 'latest';
            }

            $mycoursetitle = \theme_essential\toolbox::get_setting('mycoursetitle');
            if ($mycoursetitle == 'module') {
                $branchtitle = get_string('my'.$lateststring.'modules', 'theme_essential');
            } else if ($mycoursetitle == 'unit') {
                $branchtitle = get_string('my'.$lateststring.'units', 'theme_essential');
            } else if ($mycoursetitle == 'class') {
                $branchtitle = get_string('my'.$lateststring.'classes', 'theme_essential');
            } else {
                $branchtitle = get_string('my'.$lateststring.'courses', 'theme_essential');
            }
            $branchlabel = $this->getfontawesomemarkup('briefcase').$branchtitle;
            $branchurl = new moodle_url('#');
            $branchsort = 200;

            $branch = $coursemenu->add($branchlabel, $branchurl, $branchtitle, $branchsort);

            $hometext = get_string('myhome');
            $homelabel = html_writer::tag('span', $this->getfontawesomemarkup('home').html_writer::tag('span', ' '.$hometext));
            $branch->add($homelabel, new moodle_url('/my/index.php'), $hometext);

            // Retrieve courses and add them to the menu when they are visible.
            $numcourses = 0;
            $hasdisplayhiddenmycourses = \theme_essential\toolbox::get_setting('displayhiddenmycourses');

            $courses = array();
            if (($mycoursesorder == 1) || ($mycoursesorder == 2)) {
                $direction = 'ASC';
                if ($mycoursesorder == 1) {
                    // Get 'My courses' sort preference from admin config.
                    if (!$sortorder = $CFG->navsortmycoursessort) {
                        $sortorder = 'sortorder';
                    }
                } else if ($mycoursesorder == 2) {
                    $sortorder = 'id';
                    $mycoursesorderidorder = \theme_essential\toolbox::get_setting('mycoursesorderidorder');
                    if ($mycoursesorderidorder == 2) {
                        $direction = 'DESC';
                    }
                }
                $courses = enrol_get_my_courses(null, $sortorder.' '.$direction);
            } else if ($mycoursesorder == 3) {
                /*
                 * To test:
                 * 1. As an administrator...
                 * 2. Create a test user to be a student.
                 * 3. Create a course with a start time before the current and enrol the student.
                 * 4. Log in as the student and access the course.
                 * 5. Log back in as an administrator and create a second course and enrol the student.
                 * 6. Log back in as the student and navigate to the dashboard.
                 * 7. Confirm that the second course is listed before the first on the menu.
                 */
                // Get the list of enrolled courses as before but as for us, ignore 'navsortmycoursessort'.
                $courses = enrol_get_my_courses(null, 'sortorder ASC');
                if ($courses) {
                    // We have something to work with.  Get the last accessed information for the user and populate.
                    global $DB, $USER;
                    $lastaccess = $DB->get_records('user_lastaccess', array('userid' => $USER->id), '', 'courseid, timeaccess');
                    if ($lastaccess) {
                        foreach ($courses as $course) {
                            if (!empty($lastaccess[$course->id])) {
                                $course->timeaccess = $lastaccess[$course->id]->timeaccess;
                            }
                        }
                    }
                    // Determine if we need to query the enrolment and user enrolment tables.
                    $enrolquery = false;
                    foreach ($courses as $course) {
                        if (empty($course->timeaccess)) {
                            $enrolquery = true;
                            break;
                        }
                    }
                    if ($enrolquery) {
                        // We do.
                        $params = array('userid' => $USER->id);
                        $sql = "SELECT ue.id, e.courseid, ue.timestart
                            FROM {enrol} e
                            JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = :userid)";
                        $enrolments = $DB->get_records_sql($sql, $params, 0, 0);
                        if ($enrolments) {
                            // Sort out any multiple enrolments on the same course.
                            $userenrolments = array();
                            foreach ($enrolments as $enrolment) {
                                if (!empty($userenrolments[$enrolment->courseid])) {
                                    if ($userenrolments[$enrolment->courseid] < $enrolment->timestart) {
                                        // Replace.
                                        $userenrolments[$enrolment->courseid] = $enrolment->timestart;
                                    }
                                } else {
                                    $userenrolments[$enrolment->courseid] = $enrolment->timestart;
                                }
                            }
                            // We don't need to worry about timeend etc. as our course list will be valid for the user from above.
                            foreach ($courses as $course) {
                                if (empty($course->timeaccess)) {
                                    $course->timestart = $userenrolments[$course->id];
                                }
                            }
                        }
                    }
                    uasort($courses, array($this, 'timeaccesscompare'));
                }
            }

            if ($courses) {
                $mycoursesmax = \theme_essential\toolbox::get_setting('mycoursesmax');
                if (!$mycoursesmax) {
                    $mycoursesmax = PHP_INT_MAX;
                }
                foreach ($courses as $course) {
                    if ($course->visible) {
                        $branchtitle = format_string($course->shortname);
                        $branchurl = new moodle_url('/course/view.php', array('id' => $course->id));
                        $enrolledclass = '';
                        if (!empty($course->timestart)) {
                            $enrolledclass .= ' class="onlyenrolled"';
                        }
						// HCPSS mod to change the graduation cap icons to the comment icon. This line is the only
						// reason this method is overridden.
                        $branchlabel = '<span'.$enrolledclass.'>'.$this->getfontawesomemarkup('comments').format_string($course->fullname).'</span>';
						// End HCPSS mod
                        $branch->add($branchlabel, $branchurl, $branchtitle);
                        $numcourses += 1;
                    } else if (has_capability('moodle/course:viewhiddencourses', context_course::instance($course->id)) && $hasdisplayhiddenmycourses) {
                        $branchtitle = format_string($course->shortname);
                        $enrolledclass = '';
                        if (!empty($course->timestart)) {
                            $enrolledclass .= ' onlyenrolled';
                        }
                        $branchlabel = '<span class="dimmed_text'.$enrolledclass.'">'.$this->getfontawesomemarkup('eye-slash').
                            format_string($course->fullname) . '</span>';
                        $branchurl = new moodle_url('/course/view.php', array('id' => $course->id));
                        $branch->add($branchlabel, $branchurl, $branchtitle);
                        $numcourses += 1;
                    }
                    if ($numcourses == $mycoursesmax) {
                        break;
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

	public function get_tile_file($filename) {
        global $CFG;
        $filename .= '.php';

		if (file_exists("$CFG->dirroot/theme/vision/layout/tiles/$filename")) {
			return "$CFG->dirroot/theme/vision/layout/tiles/$filename";
		} else if (file_exists("$CFG->dirroot/theme/essential/layout/tiles/$filename")) {
            return "$CFG->dirroot/theme/essential/layout/tiles/$filename";
        } else if (!empty($CFG->themedir) and file_exists("$CFG->themedir/essential/layout/tiles/$filename")) {
            return "$CFG->themedir/essential/layout/tiles/$filename";
        } else {
            return dirname(__FILE__) . "/$filename";
        }
    }

    /**
     * Render a social media icon.
     *
     * @param string $name
     * @param string $url
     * @return string
     */
    public function render_social_network($name, $url = null) {
        if (!$url) {
            $url = $this->get_setting(strtolower($name));
        }

        $classes = sprintf('socialicon %s', strtolower($name));

        $slug = strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $name));
        $slug = str_replace(' ', '', $slug);
        $src = sprintf('https://s3.amazonaws.com/hcpss.web.site/images/hub/%s.png', $slug);
        $image = html_writer::img($src, $name);

        $socialhtml = html_writer::start_tag('li');
        $socialhtml .= html_writer::link($url, $image, array(
            'class'   => $classes,
            'target' => '_blank',
            'rel'     => 'noopener noreferrer',
            'title'   => $name,
        ));
        $socialhtml .= html_writer::end_tag('li');

        return $socialhtml;
    }

    /**
     * Render all social media icons.
     *
     * @param type $socialnetwork
     * @return type
     */
    public function render_social_networks() {
        $output = '';

        $output .= $this->render_social_network('Workday', 'https://www.myworkday.com/hcpss/login.flex');
        $output .= $this->render_social_network('Synergy', 'https://sis.hcpss.org');
        $output .= $this->render_social_network('Canvas', 'https://hcpss.instructure.com');
        $output .= $this->render_social_network('Hoonuit', 'https://dw.hcpss.org');
        $output .= $this->render_social_network('Service Request', 'https://sr.hcpss.org');
        $output .= $this->render_social_network('Frontline', 'https://login.frontlineeducation.com/sso/hcpss');
        $output .= $this->render_social_network('BRAINSTORM!', 'https://docs.google.com/forms/d/e/1FAIpQLSeP2jexB3jKJqZU55qM6eRj1YyqF_qnqj41SG6g9B8FG-MQ_Q/viewform');
        $output .= $this->render_social_network('Website');

        return $output;
    }

    /**
     * This renders the breadcrumbs
     * @return string $breadcrumbs
     */
    public function navbar() {
        $breadcrumbstyle = \theme_essential\toolbox::get_setting('breadcrumbstyle');
        if ($breadcrumbstyle) {
            if ($breadcrumbstyle == '4') {
                $breadcrumbstyle = '1'; // Fancy style with no collapse.
            }

            $showcategories = true;
            if (($this->page->pagelayout == 'course') || ($this->page->pagelayout == 'incourse')) {
                $showcategories = \theme_essential\toolbox::get_setting('categoryincoursebreadcrumbfeature');
            }

            $breadcrumbs = html_writer::tag('span', get_string('pagepath'), array('class' => 'accesshide', 'id' => 'navbar-label'));
            $breadcrumbs .= html_writer::start_tag('nav', array('aria-labelledby' => 'navbar-label'));
            $breadcrumbs .= html_writer::start_tag('ul', array('class' => "breadcrumb style$breadcrumbstyle"));
            foreach ($this->page->navbar->get_items() as $item) {
                // Test for single space hide section name trick.
                if ((strlen($item->text) == 1) && ($item->text[0] == ' ')) {
                    continue;
                }
                if ((!$showcategories) && ($item->type == \navigation_node::TYPE_CATEGORY)) {
                    continue;
                }
                $item->hideicon = true;
                $breadcrumbs .= html_writer::tag('li', $this->render($item));
            }
            $breadcrumbs .= html_writer::end_tag('ul');
            $breadcrumbs .= html_writer::end_tag('nav');
        } else {
            $breadcrumbs = '';
        }
        return $breadcrumbs;
    }
}
