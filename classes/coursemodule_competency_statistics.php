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
 * Course module competency statistics class
 *
 * @package    tool_cmcompetency
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_cmcompetency;

use tool_cmcompetency\api;

/**
 * Course module competency statistics class.
 *
 * @package    tool_cmcompetency
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursemodule_competency_statistics {

    /** @var $competencycount The number of competencies in the course module */
    public $competencycount = 0;

    /** @var $proficientcompetencycount The number of proficient competencies for the current user */
    public $proficientcompetencycount = 0;

    /** @var $leastproficientcompetencies The competencies in this course module that were proficient the least times */
    public $leastproficientcompetencies = [];

    /**
     * Return the custom definition of the properties of this model.
     *
     * @param int $cmid The course module we want to generate statistics for.
     */
    public function __construct($cmid) {
        global $USER;

        $this->competencycount = api::count_competencies_in_coursemodule($cmid);
        $this->proficientcompetencycount = api::count_proficient_competencies_in_coursemodule_for_user($cmid, $USER->id);
        $this->leastproficientcompetencies = [];
    }
}
