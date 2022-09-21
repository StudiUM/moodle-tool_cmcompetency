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
 * Class for exporting user competency data with all the evidence
 *
 * @package    tool_cmcompetency
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cmcompetency\external;

use renderer_base;
use stdClass;
use core_comment\external\comment_area_exporter;
use core_competency\external\evidence_exporter;
use tool_lp\external\competency_summary_exporter;
use core_user\external\user_summary_exporter;
use core_competency\user_competency;

/**
 * Class for exporting user competency data with additional related data.
 *
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class uc_cm_summary_exporter extends \core\external\exporter {

    /**
     * Returns a list of objects that are related to this persistent.
     *
     * Only objects listed here can be cached in this object.
     *
     * The class name can be suffixed:
     * - with [] to indicate an array of values.
     * - with ? to indicate that 'null' is allowed.
     *
     * @return array of 'propertyname' => array('type' => classname, 'required' => true)
     */
    protected static function define_related() {
        // We cache the context so it does not need to be retrieved from the framework every time.
        return array('competency' => '\\core_competency\\competency',
                     'relatedcompetencies' => '\\core_competency\\competency[]',
                     'user' => '\\stdClass',
                     'usercompetencycoursemodule' => '\\tool_cmcompetency\\user_competency_coursemodule?',
                     'evidence' => '\\core_competency\\evidence[]');
    }

    /**
     * Return the list of additional properties used only for display.
     *
     * @return array other properties
     */
    protected static function define_other_properties() {
        return array(
            'showrelatedcompetencies' => array(
                'type' => PARAM_BOOL
            ),
            'cangrade' => array(
                'type' => PARAM_BOOL
            ),
            'competency' => array(
                'type' => competency_summary_exporter::read_properties_definition()
            ),
            'user' => array(
                'type' => user_summary_exporter::read_properties_definition(),
            ),
            'usercompetencycm' => array(
                'type' => user_competency_cm_exporter::read_properties_definition(),
                'optional' => true
            ),
            'evidence' => array(
                'type' => evidence_exporter::read_properties_definition(),
                'multiple' => true
            ),
            'commentarea' => array(
                'type' => comment_area_exporter::read_properties_definition(),
                'optional' => true
            ),
        );
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        global $DB;
        $result = new stdClass();
        $result->showrelatedcompetencies = true;

        $competency = $this->related['competency'];
        $exporter = new competency_summary_exporter(null, array(
            'competency' => $competency,
            'context' => $competency->get_context(),
            'framework' => $competency->get_framework(),
            'linkedcourses' => array(),
            'relatedcompetencies' => $this->related['relatedcompetencies']
        ));
        $result->competency = $exporter->export($output);

        $result->cangrade = false;
        if ($this->related['user']) {
            $result->cangrade = user_competency::can_grade_user($this->related['user']->id);
            $exporter = new user_summary_exporter($this->related['user']);
            $result->user = $exporter->export($output);
        }
        $related = array('scale' => $competency->get_scale());

        if ($this->related['usercompetencycoursemodule']) {
            $exporter = new user_competency_cm_exporter($this->related['usercompetencycoursemodule'], $related);
            $result->usercompetencycm = $exporter->export($output);
        }

        $allevidence = array();
        $usercache = array();
        $scale = $competency->get_scale();

        $result->evidence = array();
        if (count($this->related['evidence'])) {
            foreach ($this->related['evidence'] as $evidence) {
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

            foreach ($this->related['evidence'] as $evidence) {
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
            $result->evidence = $allevidence;
        }

        return (array) $result;
    }
}
