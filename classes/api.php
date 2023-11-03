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
 * Class for doing things related to course module competencies.
 *
 * @package    tool_cmcompetency
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cmcompetency;
defined('MOODLE_INTERNAL') || die();

use core_competency\api as core_api;
use core_competency\user_competency;
use core_competency\competency;
use core_competency\evidence;
use core_competency\course_module_competency;
use tool_cmcompetency\event\user_competency_rated_in_coursemodule as cmcompetency_rated_event;
use tool_cmcompetency\event\user_competency_viewed_in_coursemodule as cmcompetency_viewed_event;
use stdClass;
use cm_info;
use context_course;
use context_module;
use context_user;
use coding_exception;
use require_login_exception;
use required_capability_exception;

require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Class for doing things with tool cmcompetency.
 *
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {

    /** @var boolean showrichtexteditor  **/
    static protected $showrichtexteditor = true;

    /**
     * Check if course module competency grading is enabled.
     */
    public static function show_richtext_editor() {
        return self::$showrichtexteditor;
    }

    /**
     * List all the course module using a competency.
     *
     * @param int $competencyid The id of the competency to check.
     * @return array Array of course module ids.
     */
    public static function list_coursesmodules_using_competency($competencyid) {
        core_api::require_enabled();

        $courses = \core_competency\course_competency::list_courses($competencyid);
        $result = array();

        // Now check permissions on each course.
        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            $capabilities = array('moodle/competency:coursecompetencyview', 'moodle/competency:coursecompetencymanage');
            if (!has_any_capability($capabilities, $context)) {
                continue;
            }
            $cmids = \core_competency\course_module_competency::list_course_modules($competencyid, $course->id);
            $result = array_merge($result, $cmids);
        }

        return $result;
    }

    /**
     * Count the proficient competencies in a course module for one user.
     *
     * @param mixed $cmorid The course module, or its ID.
     * @param int $userid The id of the user to check.
     * @return int
     */
    public static function count_proficient_competencies_in_coursemodule_for_user($cmorid, $userid) {
        core_api::require_enabled();
        $cm = $cmorid;
        if (!is_object($cmorid)) {
            $cm = get_coursemodule_from_id('', $cmorid, 0, true, MUST_EXIST);
        }

        // Check the user have access to the course module.
        self::validate_course_module($cm);
        $context = context_module::instance($cm->id);

        $capabilities = array('moodle/competency:coursecompetencyview', 'moodle/competency:coursecompetencymanage');
        if (!has_any_capability($capabilities, $context)) {
             throw new required_capability_exception($context, 'moodle/competency:coursecompetencyview', 'nopermissions', '');
        }

        // OK - all set.
        return user_competency_coursemodule::count_proficient_competencies($cm->id, $userid);
    }

    /**
     * Count all the competencies in a course module.
     *
     * @param mixed $cmorid The course module, or its ID.
     * @return int
     */
    public static function count_competencies_in_coursemodule($cmorid) {
        core_api::require_enabled();
        $cm = $cmorid;
        if (!is_object($cmorid)) {
            $cm = get_coursemodule_from_id('', $cmorid, 0, true, MUST_EXIST);
        }

        // Check the user have access to the course module.
        self::validate_course_module($cm);
        $context = context_module::instance($cm->id);

        $capabilities = array('moodle/competency:coursecompetencyview', 'moodle/competency:coursecompetencymanage');
        if (!has_any_capability($capabilities, $context)) {
             throw new required_capability_exception($context, 'moodle/competency:coursecompetencyview', 'nopermissions', '');
        }

        // OK - all set.
        return course_module_competency::count_competencies($cm->id);
    }

    /**
     * Get a user competency in a course module.
     *
     * @param int $cmid The id of the course module to check.
     * @param int $userid The id of the user to check.
     * @param int $competencyid The id of the competency.
     * @return user_competency_coursemodule
     */
    public static function get_user_competency_in_coursemodule($cmid, $userid, $competencyid) {
        core_api::require_enabled();
        // First we do a permissions check.
        $context = context_module::instance($cmid);
        $cm = get_coursemodule_from_id('', $cmid, 0, true, MUST_EXIST);

        $capabilities = array('moodle/competency:coursecompetencyview', 'moodle/competency:coursecompetencymanage');
        if (!has_any_capability($capabilities, $context)) {
            throw new required_capability_exception($context, 'moodle/competency:coursecompetencyview', 'nopermissions', '');
        } else if (!user_competency::can_read_user_in_course($userid, $cm->course)) {
            throw new required_capability_exception($context, 'moodle/competency:usercompetencyview', 'nopermissions', '');
        }

        // This will throw an exception if the competency does not belong to the course module.
        $competency = course_module_competency::get_competency($cmid, $competencyid);

        $params = array('cmid' => $cmid, 'userid' => $userid, 'competencyid' => $competencyid);
        $exists = user_competency_coursemodule::get_record($params);
        // Create missing.
        if ($exists) {
            $ucc = $exists;
        } else {
            $ucc = user_competency_coursemodule::create_relation($userid, $competency->get('id'), $cmid);
            $ucc->create();
        }

        return $ucc;
    }

    /**
     * List all the user competencies in a course module.
     *
     * @param int $cmid The id of the course module to check.
     * @param int $userid The user id.
     * @return array of user_competency_coursemodule objects
     */
    public static function list_user_competencies_in_coursemodule($cmid, $userid) {
        core_api::require_enabled();
        // First we do a permissions check.
        $context = context_module::instance($cmid);
        $cm = get_coursemodule_from_id('', $cmid, 0, true, MUST_EXIST);
        $contextcourse = context_course::instance($cm->course);

        // Check that the user is enrolled in the course, and is "gradable".
        if (!is_enrolled($contextcourse, $userid, 'moodle/competency:coursecompetencygradable')) {
            throw new coding_exception('The user does not belong to this course.');
        }

        $capabilities = array('moodle/competency:coursecompetencyview', 'moodle/competency:coursecompetencymanage');
        if (!has_any_capability($capabilities, $context)) {
            throw new required_capability_exception($context, 'moodle/competency:coursecompetencyview', 'nopermissions', '');
        } else if (!user_competency::can_read_user_in_course($userid, $cm->course)) {
            throw new required_capability_exception($context, 'moodle/competency:usercompetencyview', 'nopermissions', '');
        }

        // OK - all set.
        $competencylist = course_module_competency::list_competencies($cmid);

        $existing = user_competency_coursemodule::get_multiple($userid, $cmid, $competencylist);
        // Create missing.
        $orderedusercompetencycm = array();

        foreach ($competencylist as $coursecompetency) {
            $found = false;
            foreach ($existing as $usercompetencycm) {
                if ($usercompetencycm->get('competencyid') == $coursecompetency->get('id')) {
                    $found = true;
                    $orderedusercompetencycm[$usercompetencycm->get('id')] = $usercompetencycm;
                    break;
                }
            }
            if (!$found) {
                $ucc = user_competency_coursemodule::create_relation($userid, $coursecompetency->get('id'), $cmid);
                $ucc->create();
                $orderedusercompetencycm[$ucc->get('id')] = $ucc;
            }
        }

        return $orderedusercompetencycm;
    }

    /**
     * List all the evidence for a user competency in a course module.
     *
     * @param int $userid The user ID.
     * @param int $cmid The course module ID.
     * @param int $competencyid The competency ID.
     * @param string $sort The field to sort the evidence by.
     * @param string $order The ordering of the sorting.
     * @param int $skip Number of records to skip.
     * @param int $limit Number of records to return.
     * @return \core_competency\evidence[]
     */
    public static function list_evidence_in_coursemodule($userid = 0, $cmid = 0, $competencyid = 0, $sort = 'timecreated',
                                                   $order = 'DESC', $skip = 0, $limit = 0) {
        core_api::require_enabled();

        $cm = get_coursemodule_from_id('', $cmid, 0, true, MUST_EXIST);

        if (!user_competency::can_read_user_in_course($userid, $cm->course)) {
            $context = context_user::instance($userid);
            throw new required_capability_exception($context, 'moodle/competency:usercompetencyview', 'nopermissions', '');
        }

        $usercompetency = user_competency::get_record(array('userid' => $userid, 'competencyid' => $competencyid));
        if (!$usercompetency) {
            return array();
        }

        $context = context_module::instance($cmid);
        return evidence::get_records_for_usercompetency($usercompetency->get('id'), $context, $sort, $order, $skip, $limit);
    }

    /**
     * Manually grade a user course module competency from the module page.
     * Group grade may apply.
     *
     * @param mixed $cmorid
     * @param int $userid
     * @param int $competencyid
     * @param int $grade
     * @param string $note A note to attach to the evidence
     * @param boolean $group If grade is applied for whole group
     * @return array of evidence
     */
    public static function grade_competency_in_coursemodule($cmorid, $userid, $competencyid, $grade, $note = null, $group = false) {
        core_api::require_enabled();
        if ($group === false) {
            return [$userid => self::grade_user_competency_in_coursemodule($cmorid, $userid, $competencyid, $grade, $note)];
        } else {
            // Loop in the group users and grade each student.
            // Return the evidence for current user.
            $cm = $cmorid;
            if (!is_object($cmorid)) {
                $cm = get_coursemodule_from_id('', $cmorid, 0, true, MUST_EXIST);
            }
            if ($cm->modname == 'assign') {
                $context = context_module::instance($cm->id);
                $assign = new \assign($context, null, null);

                if ($assign->get_instance()->teamsubmission) {
                    $groupid = 0;
                    if ($group = $assign->get_submission_group($userid)) {
                        $groupid = $group->id;
                    }
                    $members = $assign->get_submission_group_members($groupid, true, $assign->show_only_active_users());
                    $evidenceforuserid = [];
                    foreach ($members as $member) {
                        $evidence = self::grade_user_competency_in_coursemodule($cmorid, $member->id, $competencyid, $grade, $note);
                        $evidenceforuserid[$member->id] = $evidence;
                    }
                    return $evidenceforuserid;
                }
            }
            // No group was graded, we grade only the current user.
            return self::grade_user_competency_in_coursemodule($cmorid, $userid, $competencyid, $grade, $note);
        }
    }

    /**
     * Manually grade a user course module competency from the module page.
     *
     * @param mixed $cmorid
     * @param int $userid
     * @param int $competencyid
     * @param int $grade
     * @param string $note A note to attach to the evidence
     * @return evidence
     */
    protected static function grade_user_competency_in_coursemodule($cmorid, $userid, $competencyid, $grade, $note = null) {
        global $USER;
        $cm = $cmorid;
        if (!is_object($cmorid)) {
            $cm = get_coursemodule_from_id('', $cmorid, 0, true, MUST_EXIST);
        }
        $context = context_course::instance($cm->course);
        $contextcm = context_module::instance($cm->id);

        // Check that we can view the user competency details in the course.
        if (!user_competency::can_read_user_in_course($userid, $cm->course)) {
            throw new required_capability_exception($context, 'moodle/competency:usercompetencyview', 'nopermissions', '');
        }

        // Validate the permission to grade.
        if (!user_competency::can_grade_user_in_course($userid, $cm->course)) {
            throw new required_capability_exception($context, 'moodle/competency:competencygrade', 'nopermissions', '');
        }

        $competency = course_module_competency::get_competency($cm->id, $competencyid);
        $competencycontext = $competency->get_context();
        if (!has_any_capability(array('moodle/competency:competencyview', 'moodle/competency:competencymanage'),
                $competencycontext)) {
            throw new required_capability_exception($competencycontext, 'moodle/competency:competencyview', 'nopermissions', '');
        }

        // Check that the user is enrolled in the course, and is "gradable".
        if (!is_enrolled($context, $userid, 'moodle/competency:coursecompetencygradable')) {
            throw new coding_exception('The competency may not be rated at this time.');
        }

        $action = \core_competency\evidence::ACTION_OVERRIDE;
        $desckey = 'evidence_manualoverrideincoursemodule';

        $result = self::add_evidence($userid,
                                $competency,
                                $cm->id,
                                $action,
                                $desckey,
                                'tool_cmcompetency',
                                $contextcm->get_context_name(),
                                false,
                                null,
                                $grade,
                                $USER->id,
                                $note);
        if ($result) {
            $all = user_competency_coursemodule::get_multiple($userid, $cm->id, array($competency->get('id')));
            $uc = reset($all);
            // Use when create the event.
            $event = cmcompetency_rated_event::create_from_user_competency_coursemodule($uc);
            $event->trigger();
        }
        return $result;
    }

    /**
     * Get the most often not completed competency for this course module.
     *
     * Requires moodle/competency:coursecompetencyview capability at the course context.
     *
     * @param int $cmid The course module id
     * @param int $skip The number of records to skip
     * @param int $limit The max number of records to return
     * @return competency[]
     */
    public static function get_least_proficient_competencies_for_coursemodule($cmid, $skip = 0, $limit = 100) {
        core_api::require_enabled();
        $cmcontext = context_module::instance($cmid);

        if (!has_any_capability(array('moodle/competency:competencyview', 'moodle/competency:competencymanage'), $cmcontext)) {
            throw new required_capability_exception($cmcontext, 'moodle/competency:competencyview', 'nopermissions', '');
        }

        return user_competency_coursemodule::get_least_proficient_competencies_for_coursemodule($cmid, $skip, $limit);
    }

    /**
     * Validate if current user have access to the course_module if hidden.
     *
     * @param mixed $cmmixed The cm_info class, course module record or its ID.
     * @param bool $throwexception Throw an exception or not.
     * @return bool
     */
    protected static function validate_course_module($cmmixed, $throwexception = true) {
        $cm = $cmmixed;
        if (!is_object($cm)) {
            $cmrecord = get_coursemodule_from_id(null, $cmmixed);
            $modinfo = get_fast_modinfo($cmrecord->course);
            $cm = $modinfo->get_cm($cmmixed);
        } else if (!$cm instanceof cm_info) {
            // Assume we got a course module record.
            $modinfo = get_fast_modinfo($cm->course);
            $cm = $modinfo->get_cm($cm->id);
        }

        if (!$cm->uservisible) {
            if ($throwexception) {
                throw new require_login_exception('Course module is hidden');
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Create an evidence from a list of parameters.
     *
     * Requires no capability because evidence can be added in many situations under any user.
     *
     * @param int $userid The user id for which evidence is added.
     * @param competency|int $competencyorid The competency, or its id for which evidence is added.
     * @param int  $cmid The ccourse module id.
     * @param int $action The type of action to take on the competency. \core_competency\evidence::ACTION_*.
     * @param string $descidentifier The strings identifier.
     * @param string $desccomponent The strings component.
     * @param mixed $desca Any arguments the string requires.
     * @param bool $recommend When true, the user competency will be sent for review.
     * @param string $url The url the evidence may link to.
     * @param int $grade The grade, or scale ID item.
     * @param int $actionuserid The ID of the user who took the action of adding the evidence. Null when system.
     *                          This should be used when the action was taken by a real person, this will allow
     *                          to keep track of all the evidence given by a certain person.
     * @param string $note A note to attach to the evidence.
     * @return evidence
     */
    public static function add_evidence($userid, $competencyorid, $cmid, $action, $descidentifier, $desccomponent,
                                        $desca = null, $recommend = false, $url = null, $grade = null, $actionuserid = null,
                                        $note = null) {
        core_api::require_enabled();

        // Some clearly important variable assignments right there.
        $competencyid = $competencyorid;
        $competency = null;
        if (is_object($competencyid)) {
            $competency = $competencyid;
            $competencyid = $competency->get('id');
        }

        $cm = get_coursemodule_from_id('', $cmid, 0, true, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $contextid = $context->id;

        $ucproficiency = null;
        $usercompetencycm = null;

        // Fetch or create the user competency.
        $usercompetency = user_competency::get_record(array('userid' => $userid, 'competencyid' => $competencyid));
        if (!$usercompetency) {
            $usercompetency = user_competency::create_relation($userid, $competencyid);
            $usercompetency->create();
        }

        // Grade course module.
        $ucgrade = $grade;
        if (empty($competency)) {
            $competency = new competency($competencyid);
        }
        if ($ucgrade !== null) {
            $ucproficiency = $competency->get_proficiency_of_grade($ucgrade);
        }

        // Add user_competency_coursemodule.
        if ($context->contextlevel == CONTEXT_MODULE) {
            $filterparams = array(
                'userid' => $userid,
                'competencyid' => $competencyid,
                'cmid' => $cmid
            );
            // Fetch or create user competency course module.
            $usercompetencycm = user_competency_coursemodule::get_record($filterparams);
            if (!$usercompetencycm) {
                $usercompetencycm = user_competency_coursemodule::create_relation($userid, $competencyid, $cmid);
                $usercompetencycm->create();
            }
            // Get proficiency.
            $proficiency = $ucproficiency;
            if ($proficiency === null) {
                if (empty($competency)) {
                    $competency = new competency($competencyid);
                }
                $proficiency = $competency->get_proficiency_of_grade($grade);
            }
            // Set grade.
            $usercompetencycm->set('grade', $grade);
            // Set proficiency.
            $usercompetencycm->set('proficiency', $proficiency);
        }

        // Should we recommend?
        if ($recommend && $usercompetency->get('status') == user_competency::STATUS_IDLE) {
            $usercompetency->set('status', user_competency::STATUS_WAITING_FOR_REVIEW);
        }

        // Prepare the evidence.
        $record = new stdClass();
        $record->usercompetencyid = $usercompetency->get('id');
        $record->contextid = $contextid;
        $record->action = $action;
        $record->descidentifier = $descidentifier;
        $record->desccomponent = $desccomponent;
        $record->grade = $grade;
        $record->actionuserid = $actionuserid;
        $record->note = $note;
        $evidence = new evidence(0, $record);
        $evidence->set('desca', $desca);
        $evidence->set('url', $url);

        // Validate both models, we should not operate on one if the other will not save.
        if (!$usercompetency->is_valid()) {
            throw new invalid_persistent_exception($usercompetency->get_errors());
        } else if (!$evidence->is_valid()) {
            throw new invalid_persistent_exception($evidence->get_errors());
        }

        // Save the user_competency_coursemodule record.
        if ($usercompetencycm !== null) {
            // Validate and update.
            if (!$usercompetencycm->is_valid()) {
                throw new invalid_persistent_exception($usercompetencycm->get_errors());
            }
            $usercompetencycm->update();
        }

        // Finally save.
        $usercompetency->update();
        $evidence->create();

        // Trigger the evidence_created event.
        \core\event\competency_evidence_created::create_from_evidence($evidence, $usercompetency, $recommend)->trigger();

        return $evidence;
    }

    /**
     * Validate if current user have acces to the course if hidden.
     *
     * @param mixed $courseorid The course or it ID.
     * @param bool $throwexception Throw an exception or not.
     * @return bool
     */
    protected static function validate_course($courseorid, $throwexception = true) {
        $course = $courseorid;
        if (!is_object($course)) {
            $course = get_course($course);
        }

        $coursecontext = context_course::instance($course->id);
        if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
            if ($throwexception) {
                throw new require_login_exception('Course is hidden');
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Log user competency viewed in course module event.
     *
     * @param user_competency_coursemodule|int $usercmcompetencyorid The user competency course module object or its ID.
     * @return bool
     */
    public static function user_competency_viewed_in_coursemodule($usercmcompetencyorid) {
        core_api::require_enabled();
        $ucc = $usercmcompetencyorid;
        if (!is_object($ucc)) {
            $ucc = new user_competency_coursemodule($ucc);
        }
        $cm = get_coursemodule_from_id('', $ucc->get('cmid'), 0, true, MUST_EXIST);

        if (!$ucc || !user_competency::can_read_user_in_course($ucc->get('userid'), $cm->course)) {
            throw new required_capability_exception($ucc->get_context(), 'moodle/competency:usercompetencyview',
                'nopermissions', '');
        }

        cmcompetency_viewed_event::create_from_user_competency_viewed_in_coursemodule($ucc)->trigger();
        return true;
    }

    /**
     * List course modules having at least one competency.
     *
     * @param int $courseid the course id
     * @param int $selectedcmid the current cmid
     * @param int $defaultcmid the default cmid
     * @return stdClass[] array of course module object
     */
    public static function get_list_course_modules_with_competencies($courseid, $selectedcmid = null, $defaultcmid = null) {
        global $DB;

        $params = array('course' => $courseid);
        $sql = 'SELECT DISTINCT(cm.id)
                  FROM {course_modules} cm
            RIGHT JOIN {' . \core_competency\course_module_competency::TABLE . '} cmcomp
                    ON cm.id = cmcomp.cmid
                 WHERE cm.visible = 1 AND cm.course = :course
              ORDER BY cm.added ASC';

        $cmids = $DB->get_records_sql($sql, $params);
        $modinfo = get_fast_modinfo($courseid);
        $cms = [];
        if ($defaultcmid) {
            $cm = $modinfo->cms[$defaultcmid];
            $cmoutput = new stdClass();
            $cmoutput->id = $cm->id;
            $cmoutput->selected = true;
            $cmoutput->name = $cm->name;
            $cms[$cm->id] = $cmoutput;
        }

        foreach ($cmids as $cmid) {
            $cm = $modinfo->cms[$cmid->id];
            if ($cm->uservisible) {
                $cmoutput = new stdClass();
                $cmoutput->id = $cm->id;
                $cmoutput->selected = ($cmid->id == $selectedcmid) ? true : false;
                $cmoutput->name = $cm->name;
                $cms[$cmid->id] = $cmoutput;
            }
        }

        return $cms;
    }

    /**
     * Checks if the course module is available for this user at this time.
     *
     * @param stdClass $cm
     * @param stdClass $user
     * @return boolean
     */
    public static function is_cm_available_for_user($cm, $user) {
        $modinfo = get_fast_modinfo($cm->course, $user->id);
        $cm = $modinfo->get_cm($cm->id);
        return $cm->uservisible;
    }

    /**
     * Returns all users who can be graded for this course module.
     *
     * @param context_course $context
     * @param stdClass $cm
     * @param int $currentgroup
     * @param boolean $onlyone True if we return only one result, false if we return all of them.
     * @return array
     */
    public static function get_cm_gradable_users($context, $cm, $currentgroup = 0, $onlyone = false) {
        global $CFG;

         // Fetch showactive.
        $defaultgradeshowactiveenrol = !empty($CFG->grade_report_showonlyactiveenrol);
        $showonlyactiveenrol = get_user_preferences('grade_report_showonlyactiveenrol', $defaultgradeshowactiveenrol);
        $showonlyactiveenrol = $showonlyactiveenrol || !has_capability('moodle/course:viewsuspendedusers', $context);

        // Get the users enrolled in the courses and who can see this course module.
        $enrolled = get_enrolled_users($context, 'moodle/competency:coursecompetencygradable', $currentgroup, 'u.*', null, 0, 0,
                $showonlyactiveenrol);
        $gradable = array();
        foreach ($enrolled as $user) {
            if (self::is_cm_available_for_user($cm, $user)) {
                $gradable[$user->id] = $user;
                if ($onlyone) {
                    break;
                }
            }
        }
        return $gradable;
    }
}
