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

use core_competency\user_competency;
use tool_cmcompetency\external\uc_cm_summary_exporter;
use renderer_base;
use stdClass;

/**
 * Class for exporting user competency data with additional related data in a course module.
 *
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_competency_summary_in_coursemodule_exporter extends \core\external\exporter {

    protected static function define_related() {
        // We cache the context so it does not need to be retrieved from the framework every time.
        return array('competency' => '\\core_competency\\competency',
                     'relatedcompetencies' => '\\core_competency\\competency[]',
                     'user' => '\\stdClass',
                     'course' => '\\stdClass',
                     'usercompetencycoursemodule' => '\\tool_cmcompetency\\user_competency_coursemodule?',
                     'evidence' => '\\core_competency\\evidence[]',
                     'scale' => '\\grade_scale');
    }

    protected static function define_other_properties() {
        return array(
            'usercompetencysummary' => array(
                'type' => uc_cm_summary_exporter::read_properties_definition()
            )
        );
    }

    protected function get_other_values(renderer_base $output) {
        $related = $this->related;
        $result = new stdClass();

        $exporter = new uc_cm_summary_exporter(null, $related);
        $result->usercompetencysummary = $exporter->export($output);
        $result->usercompetencysummary->cangrade = user_competency::can_grade_user_in_course($this->related['user']->id,
            $this->related['course']->id);

        return (array) $result;
    }
}