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
 * Event tests.
 *
 * @package   tool_cmcompetency
 * @author    Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright 2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/comment/lib.php');

use tool_cmcompetency\api;
use tool_cmcompetency\user_competency_coursemodule;

/**
 * Event tests.
 *
 * @package   tool_cmcompetency
 * @author    Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright 2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_cmcompetency_event_testcase extends advanced_testcase {

    /**
     * Test the user competency viewed event in course module.
     *
     */
    public function test_user_competency_viewed_in_coursemodule() {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $dg = $this->getDataGenerator();
        $lpg = $this->getDataGenerator()->get_plugin_generator('core_competency');
        $user = $dg->create_user();
        $course = $dg->create_course();
        $fr = $lpg->create_framework();

        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $page = $pagegenerator->create_instance(array('course' => $course->id));
        $cm = get_coursemodule_from_instance('page', $page->id);

        $c = $lpg->create_competency(array('competencyframeworkid' => $fr->get('id')));
        $pc = $lpg->create_course_competency(array('courseid' => $course->id, 'competencyid' => $c->get('id')));
        // Link competency to course module.
        $lpg->create_course_module_competency(array('competencyid' => $c->get('id'), 'cmid' => $cm->id));

        $params = array('userid' => $user->id, 'competencyid' => $c->get('id'), 'cmid' => $cm->id);
        $record = (object) $params;
        $uccm = new user_competency_coursemodule(0, $record);
        $uccm->create();

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        api::user_competency_viewed_in_coursemodule($uccm);

        // Get our event event.
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\tool_cmcompetency\event\user_competency_viewed_in_coursemodule', $event);
        $this->assertEquals($uccm->get('id'), $event->objectid);
        $this->assertEquals(context_course::instance($course->id)->id, $event->contextid);
        $this->assertEquals($uccm->get('userid'), $event->relateduserid);
        $this->assertEquals($course->id, $event->courseid);
        $this->assertEquals($cm->id, $event->other['cmid']);
        $this->assertEquals($c->get('id'), $event->other['competencyid']);

        $this->assertEventContextNotUsed($event);
        $this->assertDebuggingNotCalled();

        // Test validation.
        $params = array (
            'objectid' => $uccm->get('id'),
            'contextid' => context_course::instance($course->id)->id,
            'other' => null
        );

        // Missing competencyid.
        try {
            \tool_cmcompetency\event\user_competency_viewed_in_coursemodule::create($params)->trigger();
            $this->fail('The \'competencyid\' value must be set.');
        } catch (coding_exception $e) {
            $this->assertRegExp("/The 'competencyid' value must be set./", $e->getMessage());
        }

        $params['other']['competencyid'] = $c->get('id');
        // Missing relateduserid.
        try {
            \tool_cmcompetency\event\user_competency_viewed_in_coursemodule::create($params)->trigger();
            $this->fail('The \'relateduserid\' value must be set.');
        } catch (coding_exception $e) {
            $this->assertRegExp("/The 'relateduserid' value must be set./", $e->getMessage());
        }
        $params['relateduserid'] = $user->id;
        // Missing cmid.
        try {
            \tool_cmcompetency\event\user_competency_viewed_in_coursemodule::create($params)->trigger();
            $this->fail('The \'cmid\' value must be set.');
        } catch (coding_exception $e) {
            $this->assertRegExp("/The 'cmid' value must be set./", $e->getMessage());
        }
    }

    /**
     * Test the user competency grade rated in course module event.
     *
     */
    public function test_user_competency_rated_in_coursemodule() {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $dg = $this->getDataGenerator();
        $lpg = $this->getDataGenerator()->get_plugin_generator('core_competency');
        $scale = $dg->create_scale(array('scale' => 'A,B,C,D'));
        $course = $dg->create_course();
        $user = $dg->create_user();
        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $page = $pagegenerator->create_instance(array('course' => $course->id));
        $cm = get_coursemodule_from_instance('page', $page->id);
        $studentarch = get_archetype_roles('student');
        $studentrole = array_shift($studentarch);
        $scaleconfig = array(array('scaleid' => $scale->id));
        $scaleconfig[] = array('name' => 'A', 'id' => 1, 'scaledefault' => 0, 'proficient' => 0);
        $scaleconfig[] = array('name' => 'B', 'id' => 2, 'scaledefault' => 1, 'proficient' => 0);
        $scaleconfig[] = array('name' => 'C', 'id' => 3, 'scaledefault' => 0, 'proficient' => 1);
        $scaleconfig[] = array('name' => 'D', 'id' => 4, 'scaledefault' => 0, 'proficient' => 1);
        $fr = $lpg->create_framework();
        $c = $lpg->create_competency(array(
            'competencyframeworkid' => $fr->get('id'),
            'scaleid' => $scale->id,
            'scaleconfiguration' => $scaleconfig
        ));
        // Enrol the user as students in course.
        $dg->enrol_user($user->id, $course->id, $studentrole->id);
        $lpg->create_course_competency(array(
            'courseid' => $course->id,
            'competencyid' => $c->get('id')));
        // Link competency to course module.
        $lpg->create_course_module_competency(array('competencyid' => $c->get('id'), 'cmid' => $cm->id));
        $uc = $lpg->create_user_competency(array(
            'userid' => $user->id,
            'competencyid' => $c->get('id')));

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        api::grade_competency_in_coursemodule($cm->id, $user->id, $c->get('id'), 2, true);

        // Get our event event.
        $events = $sink->get_events();
        // Evidence created.
        $this->assertCount(2, $events);
        $evidencecreatedevent = $events[0];
        $event = $events[1];

        // Check that the event data is valid.
        $this->assertInstanceOf('\core\event\competency_evidence_created', $evidencecreatedevent);
        $this->assertInstanceOf('\tool_cmcompetency\event\user_competency_rated_in_coursemodule', $event);
        $this->assertEquals(context_course::instance($course->id)->id, $event->contextid);
        $this->assertEquals($course->id, $event->courseid);
        $this->assertEquals($cm->id, $event->other['cmid']);
        $this->assertEquals($uc->get('userid'), $event->relateduserid);
        $this->assertEquals($uc->get('competencyid'), $event->other['competencyid']);
        $this->assertEquals(2, $event->other['grade']);
        $this->assertEventContextNotUsed($event);
        $this->assertDebuggingNotCalled();
    }
}
