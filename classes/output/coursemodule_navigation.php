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
 * Course module navigation in competencies in the course module.
 *
 * @package    tool_cmcompetency
 * @copyright  2019 Université de Montréal
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cmcompetency\output;

use renderable;
use renderer_base;
use templatable;
use context_course;
use core_user\external\user_summary_exporter;
use stdClass;

/**
 * Course module navigation in competencies in the course module.
 *
 * @package    tool_cmcompetency
 * @copyright  2019 Université de Montréal
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursemodule_navigation implements renderable, templatable {

    /** @var stdClass $course */
    protected $course;

    /** @var stdClass $cm */
    protected $cm;

    /** @var string $baseurl */
    protected $baseurl;

    /** @var int $cmiddefault */
    protected $cmiddefault;

    /**
     * Construct.
     *
     * @param stdClass $cm current
     * @param stdClass $course
     * @param string $baseurl
     * @param int $cmiddefault
     */
    public function __construct($cm, $course, $baseurl, $cmiddefault) {
        $this->course = $course;
        $this->cm = $cm;
        $this->baseurl = $baseurl;
        $this->cmiddefault = $cmiddefault;
    }

    /**
     * Export the data.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->baseurl = $this->baseurl;
        $data->cmid = $this->cm->id;
        $data->courseid = $this->course->id;
        $cmids = \tool_cmcompetency\api::get_list_course_modules_with_competencies($this->course->id,
                $this->cm->id, $this->cmiddefault);
        $data->coursemodules = array_values($cmids);
        $data->hascoursemodules = empty($data->coursemodules) ? false : true;
        return $data;
    }
}
