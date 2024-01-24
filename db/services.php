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
 * Course module competency webservice functions.
 *
 *
 * @package    tool_cmcompetency
 * @author     Issam Taboubi
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'tool_cmcompetency_data_for_user_competency_summary_in_coursemodule' => [
        'classname'    => 'tool_cmcompetency\external',
        'methodname'   => 'data_for_user_competency_summary_in_coursemodule',
        'classpath'    => '',
        'description'  => 'Load a summary of a user competency in course module.',
        'type'         => 'read',
        'capabilities' => 'moodle/competency:coursecompetencyview',
        'ajax'         => true,
        'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'tool_cmcompetency_grade_competency_in_coursemodule' => [
        'classname'    => 'tool_cmcompetency\external',
        'methodname'   => 'grade_competency_in_coursemodule',
        'classpath'    => '',
        'description'  => 'Grade a competency from the course module page.',
        'type'         => 'write',
        'capabilities' => 'moodle/competency:competencygrade',
        'ajax'         => true,
        'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'tool_cmcompetency_user_competency_viewed_in_coursemodule' => [
        'classname'    => 'tool_cmcompetency\external',
        'methodname'   => 'user_competency_viewed_in_coursemodule',
        'classpath'    => '',
        'description'  => 'Log the user competency viewed in course module event',
        'type'         => 'write',
        'capabilities' => 'moodle/competency:usercompetencyview',
        'ajax'         => true,
        'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    // Course module competency related functions.
];
