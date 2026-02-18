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
 * User competency page class.
 *
 * @package    tool_cmcompetency
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cmcompetency\output;

use renderable;
use renderer_base;
use templatable;
use tool_cmcompetency\api;
use core_competency\api as core_api;
use tool_cmcompetency\external\user_competency_summary_in_coursemodule_exporter;

/**
 * User competency page class.
 *
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_competency_summary_in_coursemodule implements renderable, templatable {
    /** @var userid */
    protected $userid;

    /** @var competencyid */
    protected $competencyid;

    /** @var cmid */
    protected $cmid;

    /**
     * Construct.
     *
     * @param int $userid
     * @param int $competencyid
     * @param int $cmid
     */
    public function __construct($userid, $competencyid, $cmid) {
        $this->userid = $userid;
        $this->competencyid = $competencyid;
        $this->cmid = $cmid;
    }

    /**
     * Export the data.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB;

        $usercompetencycm = api::get_user_competency_in_coursemodule($this->cmid, $this->userid, $this->competencyid);
        $competency = $usercompetencycm->get_competency();
        if (empty($usercompetencycm) || empty($competency)) {
            throw new \invalid_parameter_exception('Invalid params. The competency does not belong to the course module.');
        }

        $relatedcompetencies = core_api::list_related_competencies($competency->get('id'));
        $user = $DB->get_record('user', ['id' => $this->userid]);
        $evidence = api::list_evidence_in_coursemodule($this->userid, $this->cmid, $this->competencyid);
        $cm = get_coursemodule_from_id('', $this->cmid, 0, true, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course]);

        $params = [
            'competency'                 => $competency,
            'usercompetencycoursemodule' => $usercompetencycm,
            'evidence'                   => $evidence,
            'user'                       => $user,
            'course'                     => $course,
            'scale'                      => $competency->get_scale(),
            'relatedcompetencies'        => $relatedcompetencies,
        ];
        $exporter = new user_competency_summary_in_coursemodule_exporter(null, $params);
        $data = $exporter->export($output);

        return $data;
    }
}
