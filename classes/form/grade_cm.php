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
 * Grader.
 *
 * @package    tool_cmcompetency
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cmcompetency\form;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

use moodleform;
use renderable;
use MoodleQuickForm;
require_once($CFG->libdir.'/formslib.php');

/**
 * Grader.
 *
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_cm extends moodleform  implements renderable {

    /**
     * Build the editor options using the given context.
     *
     * @param \context $context A Moodle context
     * @return array
     */
    public static function build_editor_options(\context $context) {
        global $CFG;

        return [
            'context' => $context,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'noclean' => true,
            'autosave' => false
        ];
    }

    /**
     * Editor form definition.
     */
    public function definition() {
        $mform = $this->_form;
        $editoroptions = $this->_customdata['editoroptions'];
        $mform->addElement('hidden', 'contextid', $this->_customdata['contextid']);
        $mform->setType('contextid', PARAM_INT);
        $mform->addElement('html', \html_writer::start_div('content'));
        $mform->addElement('html', \html_writer::start_div('', ['data-region' => 'rating']));
        $label = get_string('rating', 'tool_lp');
        if (!empty($this->_customdata['ratingoptions'])) {
            $optionsdecoded = json_decode($this->_customdata['ratingoptions'], true);
            $options = [];
            foreach ($optionsdecoded as $key => $value) {
                $options[$value['value']] = $value['name'];
            }

            $mform->addElement('select', 'rating', $label, $options, ['id' => 'rating_' . uniqid()]);
        }
        $mform->addElement('html', \html_writer::end_div());

        if (!empty($this->_customdata['showapplygroup'])) {
            // Assess without submission.
            $mform->addElement('html', \html_writer::start_div('m-t-1', ['data-region' => 'comment']));
            $label = get_string('applytogroup', 'report_cmcompetency');
            $mform->addElement('checkbox', 'applygroup', $label, '', ['id' => 'applygroup_' . uniqid()]);
            $mform->setDefault('applygroup', 1);
            $mform->addElement('html', \html_writer::end_div());
        }
        $mform->addElement('html', \html_writer::start_div('', ['data-region' => 'comment']));
        $mform->addElement('editor', 'comment',
            get_string('ratecomment', 'tool_lp'), array('rows' => 4), $editoroptions, '', ['id' => 'comment_' . uniqid()]);
        $mform->setType('comment', PARAM_CLEANHTML);
        $mform->addElement('html', \html_writer::end_div());

        $buttonarray = [];

        $buttonarray[] = $mform->createElement('submit', '', get_string('rate', 'tool_lp'),
                ['data-action' => 'rate']);
        $buttonarray[] = $mform->createElement('cancel', '', get_string('cancel'),
                ['data-action' => 'cancel']);

        $mform->addElement('html', \html_writer::start_div('', ['data-region' => 'footer']));
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
        $mform->addElement('html', \html_writer::end_div());
        $mform->addElement('html', \html_writer::end_div());
    }
}