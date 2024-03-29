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
 * Class for exporting user competency data with all the evidence in a course module
 *
 * @package    tool_cmcompetency
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cmcompetency\external;
defined('MOODLE_INTERNAL') || die();

use context_module;
use core_competency\user_competency;
use core_course\external\course_module_summary_exporter;
use tool_cmcompetency\external\uc_cm_summary_exporter;
use renderer_base;
use stdClass;

require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Class for exporting user competency data with additional related data in a course module.
 *
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_competency_summary_in_coursemodule_exporter extends \core\external\exporter {

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
        return ['competency'                      => '\\core_competency\\competency',
                     'relatedcompetencies'        => '\\core_competency\\competency[]',
                     'user'                       => '\\stdClass',
                     'course'                     => '\\stdClass',
                     'usercompetencycoursemodule' => '\\tool_cmcompetency\\user_competency_coursemodule?',
                     'evidence'                   => '\\core_competency\\evidence[]',
                     'scale'                      => '\\grade_scale', ];
    }

    /**
     * Return the list of additional properties used only for display.
     *
     * @return array other properties
     */
    protected static function define_other_properties() {
        return [
            'usercompetencysummary' => [
                'type' => uc_cm_summary_exporter::read_properties_definition(),
            ],
            'coursemodule' => [
                'type' => course_module_summary_exporter::read_properties_definition(),
            ],
            'showapplygroup' => [
                'type' => PARAM_BOOL,
            ],
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        $related = $this->related;
        $result = new stdClass();

        $exporter = new uc_cm_summary_exporter(null, $related);
        $result->usercompetencysummary = $exporter->export($output);
        $result->usercompetencysummary->cangrade = user_competency::can_grade_user_in_course($this->related['user']->id,
            $this->related['course']->id);

        $cmid = $this->related['usercompetencycoursemodule']->get('cmid');
        $modinfo = get_fast_modinfo($this->related['course']);
        $cm = $modinfo->get_cm($cmid);
        $cmexporter = new course_module_summary_exporter(null, ['cm' => $cm]);
        $result->coursemodule = $cmexporter->export($output);

        $result->showapplygroup = false;
        if ($cm->modname == 'assign') {
            $context = context_module::instance($cm->id);
            $assign = new \assign($context, null, null);

            if ($assign->get_instance()->teamsubmission) {
                $result->showapplygroup = true;
            }
        }

        return (array) $result;
    }
}
