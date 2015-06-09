<?php

function theme_vision_print_single_section_page(&$that, &$courserenderer, $course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
    global $PAGE;

    $modinfo = get_fast_modinfo($course);
    $course = course_get_format($course)->get_course();

    // Can we view the section in question?
    if (!($sectioninfo = $modinfo->get_section_info($displaysection))) {
        // This section doesn't exist
        print_error('unknowncoursesection', 'error', null, $course->fullname);
        return false;
    }

    if (!$sectioninfo->uservisible) {
        if (!$course->hiddensections) {
            echo $that->start_section_list();
            echo $that->section_hidden($displaysection);
            echo $that->end_section_list();
        }
        // Can't view this section.
        return false;
    }

    // Copy activity clipboard..
    echo $that->course_activity_clipboard($course, $displaysection);
    $thissection = $modinfo->get_section_info(0);
    if ($thissection->summary or ! empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {
        echo $that->start_section_list();
        echo $that->section_header($thissection, $course, true, $displaysection);
        echo $courserenderer->course_section_cm_list($course, $thissection, $displaysection);
        echo $courserenderer->course_section_add_cm_control($course, 0, $displaysection);
        echo $that->section_footer();
        echo $that->end_section_list();
    }

    // Start single-section div
    echo html_writer::start_tag('div', array('class' => 'single-section'));

    // The requested section page.
    $thissection = $modinfo->get_section_info($displaysection);

    // Title with section navigation links.
    $sectionnavlinks = $that->get_nav_links($course, $modinfo->get_section_info_all(), $displaysection);

    // Define the Section Title
    $sectiontitle = '';
    $sectiontitle .= html_writer::start_tag('div', array('class' => 'section-title'));
    // Title attributes
    $titleattr = 'title';
    if (!$thissection->visible) {
        $titleattr .= ' dimmed_text';
    }
    $sectiontitle .= html_writer::start_tag('h3', array('class' => $titleattr));
    $sectiontitle .= get_section_name($course, $displaysection);
    $sectiontitle .= html_writer::end_tag('h3');
    $sectiontitle .= html_writer::end_tag('div');

    // Output the Section Title.
    echo $sectiontitle;

    // Now the list of sections..
    echo $that->start_section_list();

    echo $that->section_header($thissection, $course, true, $displaysection);

    // Show completion help icon.
    $completioninfo = new completion_info($course);
    echo $completioninfo->display_help_icon();

    echo $courserenderer->course_section_cm_list($course, $thissection, $displaysection);
    echo $courserenderer->course_section_add_cm_control($course, $displaysection, $displaysection);
    echo $that->section_footer();
    echo $that->end_section_list();

    // Close single-section div.
    echo html_writer::end_tag('div');

    // Construct navigation links
    $sectionnav = html_writer::start_tag('nav', array('class' => 'section-navigation'));
    $sectionnav .= $sectionnavlinks['previous'];
    $sectionnav .= $sectionnavlinks['next'];
    $sectionnav .= html_writer::empty_tag('br', array('style' => 'clear:both'));
    $sectionnav .= html_writer::end_tag('nav');
    $sectionnav .= html_writer::tag('div', '', array('class' => 'bor'));

    // Output Section Navigation
    echo $sectionnav;
}

function theme_vision_process_css($css, $theme) {
    global $CFG;

    // Change to 'true' if you want to use Essential's settings after removing the '$THEME->parents_exclude_sheets' in config.php.
    $usingessentialsettings = true;
    
    if ($usingessentialsettings) {
        require_once($CFG->dirroot . '/theme/essential/lib.php');        
        $essentialtheme = theme_config::load('essential');        
        $css = theme_essential_process_css($css, $essentialtheme);
    } else {
        // Set FontAwesome font loading path as we have not excluded the Essential 'style/fontawesome.css' file.
        $css = theme_vision_set_fontwww($css);
    }

    // If you have your own settings, then add them here.

    // Finally return processed CSS
    return $css;
}

function theme_vision_set_fontwww($css) {
    global $CFG;
    $fontwww = preg_replace("(https?:)", "", $CFG->wwwroot . '/theme/essential/fonts/');

    $tag = '[[setting:fontwww]]';

    if (theme_essential_get_setting('bootstrapcdn')) {
        $css = str_replace($tag, '//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/fonts/', $css);
    } else {
        $css = str_replace($tag, $fontwww, $css);
    }
    return $css;
}

function theme_vision_page_init(moodle_page $page) {
    global $CFG;
    
    require_once($CFG->dirroot . '/theme/essential/lib.php');
    theme_essential_page_init($page);
}
