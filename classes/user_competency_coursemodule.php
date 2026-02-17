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
 * Class for user_competency_coursemodule persistence.
 *
 * @package    tool_cmcompetency
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cmcompetency;

use context_module;
use context_user;
use lang_string;
use core_competency\persistent;
use core_competency\competency;

/**
 * Class for loading/storing user_competency_coursemodule from the DB.
 *
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_competency_coursemodule extends persistent {
    /** Table name for user_competency_coursemodule persistency */
    const TABLE = 'tool_cmcompetency_usercompcm';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'userid' => [
                'type' => PARAM_INT,
            ],
            'cmid' => [
                'type' => PARAM_INT,
            ],
            'competencyid' => [
                'type' => PARAM_INT,
            ],
            'proficiency' => [
                'type'    => PARAM_BOOL,
                'default' => null,
                'null'    => NULL_ALLOWED,
            ],
            'grade' => [
                'type'    => PARAM_INT,
                'default' => null,
                'null'    => NULL_ALLOWED,
            ],
        ];
    }

    /**
     * Return the competency Object.
     *
     * @return competency Competency Object
     */
    public function get_competency() {
        return new competency($this->get('competencyid'));
    }

    /**
     * Get the context.
     *
     * @return context The context.
     */
    public function get_context() {
        return context_user::instance($this->get('userid'));
    }

    /**
     * Create a new user_competency_coursemodule object.
     *
     * Note, this is intended to be used to create a blank relation, for instance when
     * the record was not found in the database. This does not save the model.
     *
     * @param  int $userid The user ID.
     * @param  int $competencyid The competency ID.
     * @param  int $cmid The course module ID.
     * @return \tool_cmcompetency\user_competency_coursemodule
     */
    public static function create_relation($userid, $competencyid, $cmid) {
        $data = new \stdClass();
        $data->userid = $userid;
        $data->competencyid = $competencyid;
        $data->cmid = $cmid;

        $relation = new user_competency_coursemodule(0, $data);
        return $relation;
    }

    /**
     * Validate the user ID.
     *
     * @param int $value The value.
     * @return true|lang_string
     */
    protected function validate_userid($value) {
        global $DB;

        if (!$DB->record_exists('user', ['id' => $value])) {
            return new lang_string('invaliduserid', 'error');
        }

        return true;
    }

    /**
     * Validate the competency ID.
     *
     * @param int $value The value.
     * @return true|lang_string
     */
    protected function validate_competencyid($value) {
        if (!competency::record_exists($value)) {
            return new lang_string('errornocompetency', 'core_competency', $value);
        }

        return true;
    }

    /**
     * Validate course module ID.
     *
     * @param int $value The course module ID.
     * @return true|lang_string
     */
    protected function validate_cmid($value) {
        if (!context_module::instance($value, IGNORE_MISSING)) {
            return new lang_string('errorinvalidcoursemodule', 'tool_cmcompetency', $value);
        }

        return true;
    }

    /**
     * Validate the proficiency.
     *
     * @param int $value The value.
     * @return true|lang_string
     */
    protected function validate_proficiency($value) {
        $grade = $this->get('grade');

        if ($grade !== null && $value === null) {
            // We must set a proficiency when we set a grade.
            return new lang_string('invaliddata', 'error');
        } else if ($grade === null && $value !== null) {
            // We must not set a proficiency when we don't set a grade.
            return new lang_string('invaliddata', 'error');
        }

        return true;
    }

    /**
     * Validate the grade.
     *
     * @param int $value The value.
     * @return true|lang_string
     */
    protected function validate_grade($value) {
        if ($value !== null) {
            if ($value <= 0) {
                return new lang_string('invalidgrade', 'core_competency');
            }

            // Check if grade exist in the scale item values.
            $competency = $this->get_competency();
            if (!array_key_exists($value - 1, $competency->get_scale()->scale_items)) {
                return new lang_string('invalidgrade', 'core_competency');
            }
        }

        return true;
    }

    /**
     * Get multiple user_competency_coursemodule for a user.
     *
     * @param  int $userid
     * @param  int $cmid
     * @param  array  $competenciesorids Limit search to those competencies, or competency IDs.
     * @return \tool_cmcompetency\user_competency_coursemodule[]
     */
    public static function get_multiple($userid, $cmid, array $competenciesorids = []) {
        global $DB;

        $params = [];
        $params['userid'] = $userid;
        $params['cmid'] = $cmid;
        $sql = '1 = 1';

        if (!empty($competenciesorids)) {
            $test = reset($competenciesorids);
            if (is_number($test)) {
                $ids = $competenciesorids;
            } else {
                $ids = [];
                foreach ($competenciesorids as $comp) {
                    $ids[] = $comp->get('id');
                }
            }

            [$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $params += $inparams;
            $sql = "competencyid $insql";
        }

        // Order by ID to prevent random ordering.
        return self::get_records_select("userid = :userid AND cmid = :cmid AND $sql", $params, 'id ASC');
    }

    /**
     * Count the proficient competencies in this course module for one user.
     *
     * @param int $cmid The course module id
     * @param int $userid The user id
     * @return int
     */
    public static function count_proficient_competencies($cmid, $userid) {
        global $DB;

        $sql = 'SELECT COUNT(comp.id)
                  FROM {' . self::TABLE . '} usercmcomp
                  JOIN {' . \core_competency\course_module_competency::TABLE . '} cc
                    ON usercmcomp.competencyid = cc.competencyid AND cc.cmid = usercmcomp.cmid
                  JOIN {' . \core_competency\competency::TABLE . '} comp
                    ON usercmcomp.competencyid = comp.id
                 WHERE usercmcomp.cmid = ? AND usercmcomp.userid = ? AND usercmcomp.proficiency = ?';
        $params = [$cmid, $userid, true];

        $results = $DB->count_records_sql($sql, $params);

        return $results;
    }

    /**
     * Get the list of competencies that were completed the least times in a course.
     *
     * @param int $cmid
     * @param int $skip The number of competencies to skip
     * @param int $limit The max number of competencies to return
     * @return competency[]
     */
    public static function get_least_proficient_competencies_for_coursemodule($cmid, $skip = 0, $limit = 0) {
        global $DB;

        $fields = competency::get_sql_fields('c', 'c_');
        $params = ['cmid' => $cmid];
        $sql = 'SELECT ' . $fields . '
                  FROM (SELECT cc.competencyid, SUM(COALESCE(ucc.proficiency, 0)) AS timesproficient
                          FROM {' . \core_competency\course_module_competency::TABLE . '} cc
                     LEFT JOIN {' . self::TABLE . '} ucc
                                ON ucc.competencyid = cc.competencyid
                               AND ucc.cmid = cc.cmid
                         WHERE cc.cmid = :cmid
                      GROUP BY cc.competencyid
                     ) p
                  JOIN {' . \core_competency\competency::TABLE . '} c
                    ON c.id = p.competencyid
              ORDER BY p.timesproficient ASC, c.id DESC';

        raise_memory_limit(MEMORY_EXTRA);
        $results = $DB->get_records_sql($sql, $params, $skip, $limit);

        $comps = [];
        foreach ($results as $r) {
            $c = competency::extract_record($r, 'c_');
            $comps[] = new \core_competency\competency(0, $c);
        }
        return $comps;
    }
}
