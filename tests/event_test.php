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

namespace tool_cmcompetency;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/comment/lib.php');
use \context_course;

/**
 * Event tests.
 *
 * @covers \tool_cmcompetency\event
 * @package   tool_cmcompetency
 * @author    Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright 2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_test extends \advanced_testcase {

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
        } catch (\coding_exception $e) {
            $this->assertMatchesRegularExpression("/The 'competencyid' value must be set./", $e->getMessage());
        }

        $params['other']['competencyid'] = $c->get('id');
        // Missing relateduserid.
        try {
            \tool_cmcompetency\event\user_competency_viewed_in_coursemodule::create($params)->trigger();
            $this->fail('The \'relateduserid\' value must be set.');
        } catch (\coding_exception $e) {
            $this->assertMatchesRegularExpression("/The 'relateduserid' value must be set./", $e->getMessage());
        }
        $params['relateduserid'] = $user->id;
        // Missing cmid.
        try {
            \tool_cmcompetency\event\user_competency_viewed_in_coursemodule::create($params)->trigger();
            $this->fail('The \'cmid\' value must be set.');
        } catch (\coding_exception $e) {
            $this->assertMatchesRegularExpression("/The 'cmid' value must be set./", $e->getMessage());
        }
    }

    /**
     * Test the user competency viewed event in course module when the course is hidden.
     */
    public function test_user_competency_viewed_in_coursemodule_hidden() {
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

        // Hide the course and test as student.
        course_change_visibility($course->id, false);
        $this->setUser($user);

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
        $user2 = $dg->create_user();
        $user3 = $dg->create_user();
        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $page = $pagegenerator->create_instance(array('course' => $course->id));
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
        // Enrol the users as students in course.
        $dg->enrol_user($user->id, $course->id, $studentrole->id);
        $dg->enrol_user($user2->id, $course->id, $studentrole->id);
        $lpg->create_course_competency(array(
            'courseid' => $course->id,
            'competencyid' => $c->get('id')));

        // Create groups of students.
        $groupingdata = array();
        $groupingdata['courseid'] = $course->id;
        $groupingdata['name'] = 'Group assignment grouping';

        $grouping = self::getDataGenerator()->create_grouping($groupingdata);

        $group1data = array();
        $group1data['courseid'] = $course->id;
        $group1data['name'] = 'Team 1';
        $group2data = array();
        $group2data['courseid'] = $course->id;
        $group2data['name'] = 'Team 2';

        $group1 = self::getDataGenerator()->create_group($group1data);
        $group2 = self::getDataGenerator()->create_group($group2data);

        groups_assign_grouping($grouping->id, $group1->id);
        groups_assign_grouping($grouping->id, $group2->id);

        groups_add_member($group1->id, $user->id);
        groups_add_member($group1->id, $user2->id);
        groups_add_member($group2->id, $user3->id);

        // Create assignment.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $params = array();
        $params['course'] = $course->id;
        $params['teamsubmission'] = 1;
        $params['teamsubmissiongroupingid'] = $grouping->id;
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('assign', $instance->id);

        // Link competency to course module.
        $lpg->create_course_module_competency(array('competencyid' => $c->get('id'), 'cmid' => $cm->id));
        $uc = $lpg->create_user_competency(array(
            'userid' => $user->id,
            'competencyid' => $c->get('id')));
        $uc2 = $lpg->create_user_competency(array(
            'userid' => $user2->id,
            'competencyid' => $c->get('id')));

        // Test for individual evaluations.
        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        api::grade_competency_in_coursemodule($cm->id, $user->id, $c->get('id'), 2, null, false);

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

        // Test for group evaluations.
        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        api::grade_competency_in_coursemodule($cm->id, $user->id, $c->get('id'), 3, null, true);

        // Get our event event.
        $events = $sink->get_events();
        // Evidence created.
        $this->assertCount(4, $events);
        $evidencecreatedevent1 = $events[0];
        $event1 = $events[1];
        $evidencecreatedevent2 = $events[2];
        $event2 = $events[3];

        // We don't know the order of users in events.
        if ($uc->get('userid') == $event1->relateduserid) {
            $uctest1 = $uc;
            $uctest2 = $uc2;
        } else {
            $uctest1 = $uc2;
            $uctest2 = $uc;
        }
        // Check that the event data is valid.
        $this->assertInstanceOf('\core\event\competency_evidence_created', $evidencecreatedevent1);
        $this->assertInstanceOf('\tool_cmcompetency\event\user_competency_rated_in_coursemodule', $event1);
        $this->assertEquals(context_course::instance($course->id)->id, $event1->contextid);
        $this->assertEquals($course->id, $event1->courseid);
        $this->assertEquals($cm->id, $event1->other['cmid']);
        $this->assertEquals($uctest1->get('userid'), $event1->relateduserid);
        $this->assertEquals($uctest1->get('competencyid'), $event1->other['competencyid']);
        $this->assertEquals(3, $event1->other['grade']);
        $this->assertEventContextNotUsed($event1);
        $this->assertDebuggingNotCalled();
        $this->assertInstanceOf('\core\event\competency_evidence_created', $evidencecreatedevent2);
        $this->assertInstanceOf('\tool_cmcompetency\event\user_competency_rated_in_coursemodule', $event2);
        $this->assertEquals(context_course::instance($course->id)->id, $event2->contextid);
        $this->assertEquals($course->id, $event2->courseid);
        $this->assertEquals($cm->id, $event2->other['cmid']);
        $this->assertEquals($uctest2->get('userid'), $event2->relateduserid);
        $this->assertEquals($uctest2->get('competencyid'), $event2->other['competencyid']);
        $this->assertEquals(3, $event2->other['grade']);
        $this->assertEventContextNotUsed($event2);
        $this->assertDebuggingNotCalled();
    }
}
