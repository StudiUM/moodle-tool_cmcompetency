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
 * This page contains navigation hooks for course module competency.
 *
 * @package    tool_cmcompetency
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function tool_cmcompetency_extend_navigation_course($navigation, $course, $context) {
    global $PAGE, $USER;
    if (is_enrolled($context, $USER->id, 'moodle/competency:coursecompetencygradable')) {

        $params = ['courseid' => $course->id];
        if ($PAGE->cm && $PAGE->cm->id) {
            $params['id'] = $PAGE->cm->id;
        }
        $path = new moodle_url("/admin/tool/cmcompetency/userreport.php", $params);
        $node = navigation_node::create(get_string('competencycmmenu', 'tool_cmcompetency'),
                $path, navigation_node::TYPE_COURSE, 'cmp-md', 'cmcompetency', new pix_icon('i/competencies', ''));
        if ($node->check_if_active(URL_MATCH_BASE)) {
            $node->make_active();
        }
        $node->set_force_into_more_menu(true);
        $node->set_show_in_secondary_navigation(true);
        $navigation->add_node($node);
    }
}

/**
 * Serve the manage editor form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function tool_cmcompetency_output_fragment_grade_cm($args) {
    global $CFG, $DB;

    require_once($CFG->libdir.'/formslib.php');
    require_once($CFG->dirroot . '/admin/tool/cmcompetency/classes/form/grade_cm.php');
    $args = (object) $args;
    $contextcm = context::instance_by_id($args->contextid);
    $editoroptions = \tool_cmcompetency\form\grade_cm::build_editor_options($contextcm);

    $formoptions = ['editoroptions' => $editoroptions,
            'contextid' => $args->contextid,
            'cangrade' => $args->canGrade,
            'ratingoptions' => $args->ratingOptions,
            'showapplygroup' => $args->showapplygroup];
    $mform = new \tool_cmcompetency\form\grade_cm(
            null,
            $formoptions,
            'post',
            '',
            ['id' => 'competency_grading_form'],
            true,
            []
        );

    return $mform->render();
}
