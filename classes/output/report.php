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
 * Class containing data for course module competency page.
 *
 * @package    tool_cmcompetency
 * @copyright  2019 Université de Montréal
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cmcompetency\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use stdClass;
use core_competency\api as core_competency_api;
use core_competency\external\performance_helper;
use core_user;
use tool_cmcompetency\api as tool_cmcompetency_api;
use tool_cmcompetency\external\user_competency_cm_exporter;
use tool_lp\external\competency_summary_exporter;
use core_competency\external\evidence_exporter;
use tool_lp\external\course_competency_statistics_exporter;
use tool_cmcompetency\coursemodule_competency_statistics;


/**
 * Class containing data for course module competency page.
 *
 * @copyright  2019 Université de Montréal
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report implements renderable, templatable {

    /** @var int $cmid */
    protected $cmid;
    /** @var int $userid */
    protected $userid;

    /**
     * Construct this renderable.
     *
     * @param int $cmid The course module id
     */
    public function __construct($cmid) {
        global $USER;
        $this->cmid = $cmid;
        $this->userid = $USER->id;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB;
        $data = new stdClass();
        $data->cmid = $this->cmid;

        $user = core_user::get_user($this->userid);

        $data->usercompetencies = array();
        $cmcompetencies = core_competency_api::list_course_module_competencies($this->cmid);
        $usercompetencycoursesmodules = tool_cmcompetency_api::list_user_competencies_in_coursemodule($this->cmid, $user->id);
        $cmcompetencystatistics = new coursemodule_competency_statistics($this->cmid);

        $helper = new performance_helper();
        foreach ($usercompetencycoursesmodules as $usercompetencycm) {
            $onerow = new stdClass();
            $competency = null;
            foreach ($cmcompetencies as $cmcompetency) {
                if ($cmcompetency['competency']->get('id') == $usercompetencycm->get('competencyid')) {
                    $competency = $cmcompetency['competency'];
                    break;
                }
            }
            if (!$competency) {
                continue;
            }

            $framework = $helper->get_framework_from_competency($competency);
            $scale = $helper->get_scale_from_competency($competency);

            $exporter = new user_competency_cm_exporter($usercompetencycm, array('scale' => $scale));
            $record = $exporter->export($output);
            $onerow->usercompetencycoursemodule = $record;
            $exporter = new competency_summary_exporter(null, array(
                'competency' => $competency,
                'framework' => $framework,
                'context' => $framework->get_context(),
                'relatedcompetencies' => array(),
                'linkedcourses' => array()
            ));
            $onerow->competency = $exporter->export($output);

            $evidences = tool_cmcompetency_api::list_evidence_in_coursemodule($this->userid, $this->cmid, $competency->get('id'));
            $allevidence = array();
            $usercache = array();
            $onerow->evidence = array();
            if (count($evidences)) {
                foreach ($evidences as $evidence) {
                    $actionuserid = $evidence->get('actionuserid');
                    if (!empty($actionuserid)) {
                        $usercache[$evidence->get('actionuserid')] = true;
                    }
                }
                $users = array();
                if (!empty($usercache)) {
                    list($sql, $params) = $DB->get_in_or_equal(array_keys($usercache));
                    $users = $DB->get_records_select('user', 'id ' . $sql, $params);
                }

                foreach ($users as $user) {
                    $usercache[$user->id] = $user;
                }

                foreach ($evidences as $evidence) {
                    $actionuserid = $evidence->get('actionuserid');
                    $related = array(
                        'scale' => $scale,
                        'usercompetency' => null,
                        'usercompetencyplan' => null,
                        'context' => $evidence->get_context()
                    );
                    $related['actionuser'] = !empty($actionuserid) ? $usercache[$actionuserid] : null;
                    $exporter = new evidence_exporter($evidence, $related);
                    $allevidence[] = $exporter->export($output);
                }
                $onerow->evidence = $allevidence;
            }
            array_push($data->usercompetencies, $onerow);
        }
        $data->hascompetencies = (empty($data->usercompetencies)) ? false : true;
        $related = array('context' => \context_module::instance($this->cmid));
        $exporter = new course_competency_statistics_exporter($cmcompetencystatistics, $related);
        $data->statistics = $exporter->export($output);
        return $data;
    }
}
