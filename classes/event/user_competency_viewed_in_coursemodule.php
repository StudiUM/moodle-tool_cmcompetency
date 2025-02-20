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
 * User competency viewed in course module event.
 *
 * @package    tool_cmcompetency
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_cmcompetency\event;

use core\event\base;
use tool_cmcompetency\user_competency_coursemodule;

/**
 * User competency viewed in course module event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - int competencyid: id of competency.
 * }
 *
 * @package    tool_cmcompetency
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_competency_viewed_in_coursemodule extends base {

    /**
     * Convenience method to instantiate the event in course module.
     *
     * @param user_competency_coursemodule $usercompetencycm The user competency for the course module.
     * @return self
     */
    public static function create_from_user_competency_viewed_in_coursemodule(user_competency_coursemodule $usercompetencycm) {
        if (!$usercompetencycm->get('id')) {
            throw new \coding_exception('The user competency course ID must be set.');
        }
        $cmid = $usercompetencycm->get('cmid');
        $params = [
            'objectid'      => $usercompetencycm->get('id'),
            'relateduserid' => $usercompetencycm->get('userid'),
            'other'         => [
                'competencyid' => $usercompetencycm->get('competencyid'),
                'cmid'         => $cmid,
            ],
        ];
        $cmrecord = get_coursemodule_from_id(null, $cmid);
        $context = \context_course::instance($cmrecord->course);
        $params['contextid'] = $context->id;
        $params['courseid'] = $cmrecord->course;

        $event = static::create($params);
        $event->add_record_snapshot(user_competency_coursemodule::TABLE, $usercompetencycm->to_record());
        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' viewed the user competency with id '$this->objectid' "
                . "in course module with id '" . $this->other['cmid'] . "'";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventusercompetencyviewedincoursemodule', 'tool_cmcompetency');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url|null
     */
    public function get_url() {
        // Implement this when creating pages related to cmcompetency.
        return null;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = user_competency_coursemodule::TABLE;
    }

    /**
     * Get_objectid_mapping method.
     *
     * @return string the name of the restore mapping the objectid links to
     */
    public static function get_objectid_mapping() {
        return base::NOT_MAPPED;
    }

    /**
     * Custom validation.
     *
     * Throw \coding_exception notice in case of any problems.
     */
    protected function validate_data() {
        if (!isset($this->other) || !isset($this->other['competencyid'])) {
            throw new \coding_exception('The \'competencyid\' value must be set.');
        }

        if (!$this->courseid) {
            throw new \coding_exception('The \'courseid\' value must be set.');
        }

        if (!$this->relateduserid) {
            throw new \coding_exception('The \'relateduserid\' value must be set.');
        }

        if (!isset($this->other['cmid'])) {
            throw new \coding_exception('The \'cmid\' value must be set.');
        }
    }

}
