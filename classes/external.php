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
 * This is the external API for cmcompetency tool.
 *
 * @package    tool_cmcompetency
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cmcompetency;

use core_external\external_api;
use core_competency\external\evidence_exporter;
use core_competency\api as core_api;
use tool_cmcompetency\api;
use tool_cmcompetency\output\user_competency_summary_in_coursemodule;
use tool_cmcompetency\external\user_competency_summary_in_coursemodule_exporter;
use tool_cmcompetency\form\grade_cm;
use core_external\external_function_parameters;
use core_external\external_value;
use context_module;
use context_user;

/**
 * This is the external API for cmcompetency tool.
 *
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * Returns description of user_competency_viewed_in_coursemodule() parameters.
     *
     * @return \external_function_parameters
     */
    public static function user_competency_viewed_in_coursemodule_parameters() {
        $competencyid = new external_value(
            PARAM_INT,
            'The competency id',
            VALUE_REQUIRED
        );
        $userid = new external_value(
            PARAM_INT,
            'The user id',
            VALUE_REQUIRED
        );
        $cmid = new external_value(
            PARAM_INT,
            'The course module id',
            VALUE_REQUIRED
        );
        $params = [
            'competencyid' => $competencyid,
            'userid'       => $userid,
            'cmid'         => $cmid,
        ];
        return new external_function_parameters($params);
    }

    /**
     * Log user competency viewed in course module event.
     *
     * @param int $competencyid The competency ID.
     * @param int $userid The user ID.
     * @param int $cmid The course module ID.
     * @return boolean
     */
    public static function user_competency_viewed_in_coursemodule($competencyid, $userid, $cmid) {
        $params = self::validate_parameters(self::user_competency_viewed_in_coursemodule_parameters(), [
            'competencyid' => $competencyid,
            'userid'       => $userid,
            'cmid'         => $cmid,
        ]);
        $ucc = api::get_user_competency_in_coursemodule($params['cmid'], $params['userid'], $params['competencyid']);
        $result = api::user_competency_viewed_in_coursemodule($ucc);

        return $result;
    }

    /**
     * Returns description of user_competency_viewed_in_coursemodule() result value.
     *
     * @return \external_description
     */
    public static function user_competency_viewed_in_coursemodule_returns() {
        return new external_value(PARAM_BOOL, 'True if the event user competency viewed in course module was logged');
    }

    /**
     * Returns description of grade_competency_in_coursemodule() parameters.
     *
     * @return \external_function_parameters
     */
    public static function grade_competency_in_coursemodule_parameters() {
        $cmid = new external_value(
            PARAM_INT,
            'Course module id',
            VALUE_REQUIRED
        );
        $userid = new external_value(
            PARAM_INT,
            'User id',
            VALUE_REQUIRED
        );
        $competencyid = new external_value(
            PARAM_INT,
            'Competency id',
            VALUE_REQUIRED
        );
        $grade = new external_value(
            PARAM_INT,
            'New grade',
            VALUE_REQUIRED
        );
        $note = new external_value(
            PARAM_RAW,
            'A note to attach to the evidence',
            VALUE_DEFAULT
        );
        $applygroup = new external_value(
            PARAM_BOOL,
            'Apply for whole group',
            VALUE_DEFAULT,
            false
        );

        $params = [
            'cmid'         => $cmid,
            'userid'       => $userid,
            'competencyid' => $competencyid,
            'grade'        => $grade,
            'note'         => $note,
            'applygroup'   => $applygroup,
        ];
        return new external_function_parameters($params);
    }

    /**
     * Grade a competency in a course module.
     *
     * @param int $cmid The course module id
     * @param int $userid The user id
     * @param int $competencyid The competency id
     * @param int $grade The new grade value
     * @param string $note A note to add to the evidence
     * @param bool $applygroup If it is a group grade
     * @return bool
     */
    public static function grade_competency_in_coursemodule($cmid, $userid, $competencyid, $grade, $note = null,
            $applygroup = false) {
        global $USER, $PAGE, $CFG;
        require_once($CFG->libdir."/filelib.php");

        $params = [
            'cmid'         => $cmid,
            'userid'       => $userid,
            'competencyid' => $competencyid,
            'grade'        => $grade,
            'note'         => $note,
            'applygroup'   => $applygroup,
        ];

        $context = context_module::instance($params['cmid']);
        self::validate_context($context);
        $output = $PAGE->get_renderer('core');
        $data = [];
        if ($params['note']) {
            parse_str($params['note'], $data);
        }
        $editornote = api::show_richtext_editor() && isset($data['contextid']) ? true : false;
        if (isset($data['contextid'])) {
            if ($editornote) {
                $notetoadd = '';
            } else {
                $notetoadd = $data['comment'];
            }
        } else {
            $notetoadd = $params['note'];
        }
        $evidences = api::grade_competency_in_coursemodule(
                $params['cmid'],
                $params['userid'],
                $params['competencyid'],
                $params['grade'],
                $notetoadd,
                $params['applygroup']
        );
        $evidence = $evidences[$params['userid']];

        // If editor note.
        if ($editornote) {
            $contexteditor = \context::instance_by_id($data['contextid']);
            $editoroptions = grade_cm::build_editor_options($contexteditor);
            $formoptions = ['editoroptions' => $editoroptions];
            $formoptions['contextid'] = $data['contextid'];
            $mform = new grade_cm(null, $formoptions, 'post', '', null, true, $data);
            if ($validateddata = $mform->get_data()) {
                file_remove_editor_orphaned_files($validateddata->comment);
                foreach ($evidences as $e) {
                    $note = file_save_draft_area_files(
                        $validateddata->comment['itemid'],
                        $data['contextid'],
                        'core_competency',
                        'evidence_note',
                        $e->get('id'),
                        grade_cm::build_editor_options($contexteditor),
                        $validateddata->comment['text']
                    );
                    if ($note) {
                        $e->set('note', $note);
                        var_dump($e);die;
                        $e->update();
                    }
                }
            }
        }

        $competency = core_api::read_competency($params['competencyid']);
        $scale = $competency->get_scale();
        $exporter = new evidence_exporter($evidence, [
            'actionuser'         => $USER,
            'scale'              => $scale,
            'usercompetency'     => null,
            'usercompetencyplan' => null,
            'context'            => $evidence->get_context(),
        ]);

        return $exporter->export($output);
    }

    /**
     * Returns description of grade_competency_in_coursemodule() result value.
     *
     * @return \external_value
     */
    public static function grade_competency_in_coursemodule_returns() {
        return evidence_exporter::get_read_structure();
    }

    /**
     * Returns description of data_for_user_competency_summary_in_coursemodule() parameters.
     *
     * @return \external_function_parameters
     */
    public static function data_for_user_competency_summary_in_coursemodule_parameters() {
        $userid = new external_value(
            PARAM_INT,
            'Data base record id for the user',
            VALUE_REQUIRED
        );
        $competencyid = new external_value(
            PARAM_INT,
            'Data base record id for the competency',
            VALUE_REQUIRED
        );
        $cmid = new external_value(
            PARAM_INT,
            'Data base record id for the course module',
            VALUE_REQUIRED
        );

        $params = [
            'userid'       => $userid,
            'competencyid' => $competencyid,
            'cmid'         => $cmid,
        ];
        return new external_function_parameters($params);
    }

    /**
     * Read a user competency summary.
     *
     * @param int $userid The user id
     * @param int $competencyid The competency id
     * @param int $cmid The course module id
     * @return \stdClass
     */
    public static function data_for_user_competency_summary_in_coursemodule($userid, $competencyid, $cmid) {
        global $PAGE;
        $params = self::validate_parameters(self::data_for_user_competency_summary_in_coursemodule_parameters(), [
            'userid'       => $userid,
            'competencyid' => $competencyid,
            'cmid'         => $cmid,
        ]);
        $context = context_user::instance($params['userid']);
        self::validate_context($context);
        $output = $PAGE->get_renderer('tool_lp');

        $renderable = new user_competency_summary_in_coursemodule($params['userid'], $params['competencyid'], $params['cmid']);
        return $renderable->export_for_template($output);
    }

    /**
     * Returns description of data_for_user_competency_summary_in_coursemodule() result value.
     *
     * @return \external_description
     */
    public static function data_for_user_competency_summary_in_coursemodule_returns() {
        return user_competency_summary_in_coursemodule_exporter::get_read_structure();
    }
}
