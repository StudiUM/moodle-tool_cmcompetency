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
 * External course module competency API tests.
 *
 * @package   tool_cmcompetency
 * @author    Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright 2019 Université de Montréal
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

use tool_cmcompetency\api;

/**
 * External course module competency API tests.
 *
 * @package   tool_cmcompetency
 * @author    Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright 2019 Université de Montréal
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_cmcompetency_api_testcase extends externallib_advanced_testcase {

    /** @var stdClass $student1 User for generating plans, student of course1. */
    protected $student1 = null;

    /** @var stdClass $student2 User for generating plans, student of course1. */
    protected $student2 = null;

    /** @var stdClass $student3 User for generating plans, student of course1. */
    protected $student3 = null;

    /** @var stdClass $student4 User for generating plans, student of course1. */
    protected $student4 = null;

    /** @var stdClass $course1 Course that contains the activities to grade. */
    protected $course1 = null;

    /** @var stdClass $page Page for $course1. */
    protected $page = null;

    /** @var stdClass $framework Competency framework. */
    protected $framework = null;

    protected function setUp(): void {
        $this->resetAfterTest(true);
        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('core_competency');
        $this->setAdminUser();

        $this->student1 = $dg->create_user();
        $this->student2 = $dg->create_user();
        $this->student3 = $dg->create_user();
        $this->student4 = $dg->create_user();
        $this->course1 = $dg->create_course();

        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $this->page = $pagegenerator->create_instance(array('course' => $this->course1->id));

        $this->framework = $lpg->create_framework();

        // Enrol students in the course.
        $studentarch = get_archetype_roles('student');
        $studentrole = array_shift($studentarch);
        $coursecontext = context_course::instance($this->course1->id);
        $dg->role_assign($studentrole->id, $this->student1->id, $coursecontext->id);
        $dg->enrol_user($this->student1->id, $this->course1->id, $studentrole->id);
        $dg->role_assign($studentrole->id, $this->student2->id, $coursecontext->id);
        $dg->enrol_user($this->student2->id, $this->course1->id, $studentrole->id);
        $dg->role_assign($studentrole->id, $this->student3->id, $coursecontext->id);
        $dg->enrol_user($this->student3->id, $this->course1->id, $studentrole->id);
        $dg->role_assign($studentrole->id, $this->student4->id, $coursecontext->id);
        $dg->enrol_user($this->student4->id, $this->course1->id, $studentrole->id);
    }

    /**
     * Test course module statistics api functions.
     */
    public function test_coursemodule_statistics() {
        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('core_competency');

        $cm = get_coursemodule_from_instance('page', $this->page->id);
        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $page1 = $pagegenerator->create_instance(array('course' => $this->course1->id));
        $page2 = $pagegenerator->create_instance(array('course' => $this->course1->id));
        $cm1 = get_coursemodule_from_instance('page', $page1->id);
        $cm2 = get_coursemodule_from_instance('page', $page2->id);

        // Create 6 competencies.
        $comp1 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));
        $comp2 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));
        $comp3 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));
        $comp4 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));
        $comp5 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));
        $comp6 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));

        // Link 6 out of 6 to a course.
        $lpg->create_course_competency(array('competencyid' => $comp1->get('id'), 'courseid' => $this->course1->id));
        $lpg->create_course_competency(array('competencyid' => $comp2->get('id'), 'courseid' => $this->course1->id));
        $lpg->create_course_competency(array('competencyid' => $comp3->get('id'), 'courseid' => $this->course1->id));
        $lpg->create_course_competency(array('competencyid' => $comp4->get('id'), 'courseid' => $this->course1->id));
        $lpg->create_course_competency(array('competencyid' => $comp5->get('id'), 'courseid' => $this->course1->id));
        $lpg->create_course_competency(array('competencyid' => $comp6->get('id'), 'courseid' => $this->course1->id));

        // Link competencies to course module.
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cm->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp2->get('id'), 'cmid' => $cm->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp3->get('id'), 'cmid' => $cm->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp4->get('id'), 'cmid' => $cm->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp5->get('id'), 'cmid' => $cm->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp6->get('id'), 'cmid' => $cm->id));
        // Link competency2 to course module cm1.
        $lpg->create_course_module_competency(array('competencyid' => $comp2->get('id'), 'cmid' => $cm1->id));
        // Test count competencies in cm, cm1 and cm2.
        $nbcompcm = api::count_competencies_in_coursemodule($cm);
        $this->assertEquals(6, $nbcompcm);
        $nbcompcm1 = api::count_competencies_in_coursemodule($cm1);
        $this->assertEquals(1, $nbcompcm1);
        $nbcompcm2 = api::count_competencies_in_coursemodule($cm2);
        $this->assertEquals(0, $nbcompcm2);

        // Rate some competencies.
        // User 1.
        api::grade_competency_in_coursemodule($cm, $this->student1->id, $comp1->get('id'), 1, 'Unit test 1');
        api::grade_competency_in_coursemodule($cm, $this->student1->id, $comp2->get('id'), 2, 'Unit test 2');
        api::grade_competency_in_coursemodule($cm, $this->student1->id, $comp3->get('id'), 3, 'Unit test 3');
        api::grade_competency_in_coursemodule($cm, $this->student1->id, $comp4->get('id'), 4, 'Unit test 4');
        // User 2.
        api::grade_competency_in_coursemodule($cm, $this->student2->id, $comp1->get('id'), 1, 'Unit test 1');
        api::grade_competency_in_coursemodule($cm, $this->student2->id, $comp2->get('id'), 2, 'Unit test 2');
        api::grade_competency_in_coursemodule($cm, $this->student2->id, $comp3->get('id'), 1, 'Unit test 3');
        api::grade_competency_in_coursemodule($cm, $this->student2->id, $comp4->get('id'), 4, 'Unit test 4');
        // User 3.
        api::grade_competency_in_coursemodule($cm, $this->student3->id, $comp1->get('id'), 3, 'Unit test 3');
        api::grade_competency_in_coursemodule($cm, $this->student3->id, $comp2->get('id'), 2, 'Unit test 2');
        // User 4.
        api::grade_competency_in_coursemodule($cm, $this->student4->id, $comp1->get('id'), 2, 'Unit test 2');
        api::grade_competency_in_coursemodule($cm, $this->student4->id, $comp2->get('id'), 1, 'Unit test 1');

        // OK we have enough data - lets call some API functions and check for expected results.

        $result = api::count_proficient_competencies_in_coursemodule_for_user($cm->id, $this->student1->id);
        $this->assertEquals(2, $result);
        $result = api::count_proficient_competencies_in_coursemodule_for_user($cm->id, $this->student2->id);
        $this->assertEquals(1, $result);
        $result = api::count_proficient_competencies_in_coursemodule_for_user($cm->id, $this->student3->id);
        $this->assertEquals(1, $result);
        $result = api::count_proficient_competencies_in_coursemodule_for_user($cm->id, $this->student4->id);
        $this->assertEquals(0, $result);

        $result = api::get_least_proficient_competencies_for_coursemodule($cm->id, 0, 2);
        // We should get 5 and 6 in repeatable order.
        $valid = false;
        if (($comp5->get('id') == $result[0]->get('id')) || ($comp6->get('id') == $result[0]->get('id'))) {
            $valid = true;
        }
        $this->assertTrue($valid);
        $valid = false;
        if (($comp5->get('id') == $result[1]->get('id')) || ($comp6->get('id') == $result[1]->get('id'))) {
            $valid = true;
        }
        $this->assertTrue($valid);
        $expected = $result[1]->get('id');
        $result = api::get_least_proficient_competencies_for_coursemodule($cm->id, 1, 1);
        $this->assertEquals($result[0]->get('id'), $expected);

        // Test list evidence.
        $result = api::list_evidence_in_coursemodule($this->student1->id, $cm->id, $comp1->get('id'), 'timecreated', 'ASC');
        $this->assertEquals($result[0]->get('descidentifier'), 'evidence_manualoverrideincoursemodule');
        $this->assertEquals($result[0]->get('grade'), '1');
        $this->assertEquals($result[0]->get('note'), 'Unit test 1');
    }

    /**
     * Get a user competency in a course module.
     */
    public function test_get_user_competency_in_coursemodule() {
        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('core_competency');

        $cm = get_coursemodule_from_instance('page', $this->page->id);

        // Enrol the user so they can be rated in the course.
        $studentarch = get_archetype_roles('student');
        $studentrole = array_shift($studentarch);
        $coursecontext = context_course::instance($this->course1->id);
        $dg->role_assign($studentrole->id, $this->student1->id, $coursecontext->id);
        $dg->enrol_user($this->student1->id, $this->course1->id, $studentrole->id);

        $framework = $lpg->create_framework();
        $comp1 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));
        $comp2 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));
        $lpg->create_course_competency(array('competencyid' => $comp1->get('id'), 'courseid' => $this->course1->id));
        $lpg->create_course_competency(array('competencyid' => $comp2->get('id'), 'courseid' => $this->course1->id));
        // Link competencies to course module.
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cm->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp2->get('id'), 'cmid' => $cm->id));

        // Create a user competency for comp1.
        api::grade_competency_in_coursemodule($cm, $this->student1->id, $comp1->get('id'), 3, 'Unit test');

        // Test for competency already exist in user_competency.
        $uc = api::get_user_competency_in_coursemodule($cm->id, $this->student1->id, $comp1->get('id'));
        $this->assertEquals($comp1->get('id'), $uc->get('competencyid'));
        $this->assertEquals($this->student1->id, $uc->get('userid'));
        $this->assertEquals(3, $uc->get('grade'));
        $this->assertEquals(true, $uc->get('proficiency'));

        // Test for competency does not exist in user_competency.
        $uc2 = api::get_user_competency_in_coursemodule($cm->id, $this->student1->id, $comp2->get('id'));
        $this->assertEquals($comp2->get('id'), $uc2->get('competencyid'));
        $this->assertEquals($this->student1->id, $uc2->get('userid'));
        $this->assertEquals(null, $uc2->get('grade'));
        $this->assertEquals(null, $uc2->get('proficiency'));
    }

    /**
     * Test list courses modules using competency.
     */
    public function test_list_coursesmodules_using_competency() {
        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('core_competency');
        $this->setAdminUser();

        $c2 = $dg->create_course();

        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $page = $pagegenerator->create_instance(array('course' => $this->course1->id));
        $page1 = $pagegenerator->create_instance(array('course' => $this->course1->id));

        $cm = get_coursemodule_from_instance('page', $page->id);
        $cm1 = get_coursemodule_from_instance('page', $page1->id);

        $page = $pagegenerator->create_instance(array('course' => $c2->id));
        $page1 = $pagegenerator->create_instance(array('course' => $c2->id));

        $cm2 = get_coursemodule_from_instance('page', $page->id);
        $cm21 = get_coursemodule_from_instance('page', $page1->id);

        $framework = $lpg->create_framework();
        // Create 3 competencies.
        $comp1 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));
        $comp2 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));
        $comp3 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));

        // Link 2 out of 3 to course 1.
        $lpg->create_course_competency(array('competencyid' => $comp1->get('id'), 'courseid' => $this->course1->id));
        $lpg->create_course_competency(array('competencyid' => $comp2->get('id'), 'courseid' => $this->course1->id));
        // Link 2 out of 3 to course 2.
        $lpg->create_course_competency(array('competencyid' => $comp1->get('id'), 'courseid' => $c2->id));
        $lpg->create_course_competency(array('competencyid' => $comp2->get('id'), 'courseid' => $c2->id));

        // Link competencies cpm1, comp2 to course module cm, cm1.
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cm->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp2->get('id'), 'cmid' => $cm->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cm1->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp2->get('id'), 'cmid' => $cm1->id));
        // Link competencies comp2 to course module cm2, cm21.
        $lpg->create_course_module_competency(array('competencyid' => $comp2->get('id'), 'cmid' => $cm2->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp2->get('id'), 'cmid' => $cm21->id));

        // Test comp3.
        $result = api::list_coursesmodules_using_competency($comp3->get('id'));
        $this->assertEmpty($result);
        // Test comp1.
        $result = api::list_coursesmodules_using_competency($comp1->get('id'));
        $this->assertEquals(2, count($result));
        $this->assertContains($cm->id, $result);
        $this->assertContains($cm1->id, $result);
        $this->assertNotContains($cm2->id, $result);
        $this->assertNotContains($cm21->id, $result);
        // Test comp2.
        $result = api::list_coursesmodules_using_competency($comp2->get('id'));
        $this->assertEquals(4, count($result));
        $this->assertContains($cm->id, $result);
        $this->assertContains($cm1->id, $result);
        $this->assertContains($cm2->id, $result);
        $this->assertContains($cm21->id, $result);

        // Hide the course and check that the modules are listed anyway.
        $student = $dg->create_user();
        course_change_visibility($this->course1->id, false);
        $this->setUser($student);
        $result = api::list_coursesmodules_using_competency($comp1->get('id'));
        $this->assertEquals(2, count($result));
        $result = api::list_coursesmodules_using_competency($comp2->get('id'));
        $this->assertEquals(4, count($result));
    }

    /**
     * Test list_user_competencies_in_coursemodule.
     */
    public function test_list_user_competencies_in_coursemodule() {
        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('core_competency');

        $cm = get_coursemodule_from_instance('page', $this->page->id);
        $sysctx = context_system::instance();
        $c1ctx = context_course::instance($this->course1->id);

        $teacher1 = $dg->create_user();
        $student1 = $dg->create_user();
        $student2 = $dg->create_user();

        $studentarch = get_archetype_roles('student');
        $studentrole = array_shift($studentarch);

        $canviewucrole = $dg->create_role();
        assign_capability('moodle/competency:usercompetencyview', CAP_ALLOW, $canviewucrole, $sysctx->id);

        $cangraderole = $dg->create_role();
        assign_capability('moodle/competency:competencygrade', CAP_ALLOW, $cangraderole, $sysctx->id);

        // Enrol s1 and s2 as students in course 1.
        $dg->enrol_user($student1->id, $this->course1->id, $studentrole->id);
        // Give permission to view competencies.
        $dg->role_assign($canviewucrole, $teacher1->id, $c1ctx->id);
        // Give permission to rate.
        $dg->role_assign($cangraderole, $teacher1->id, $c1ctx->id);

        $framework = $lpg->create_framework();
        $comp1 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));
        $comp2 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));
        $lpg->create_course_competency(array('competencyid' => $comp1->get('id'), 'courseid' => $this->course1->id));
        $lpg->create_course_competency(array('competencyid' => $comp2->get('id'), 'courseid' => $this->course1->id));
        // Link competencies to course module.
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cm->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp2->get('id'), 'cmid' => $cm->id));
        // Set current user to teacher.
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($teacher1);

        $results = api::list_user_competencies_in_coursemodule($cm->id, $student1->id);
        $results = array_values($results);
        $this->assertEquals($comp1->get('id'), $results[0]->get('competencyid'));
        $this->assertEquals($cm->id, $results[0]->get('cmid'));
        $this->assertEquals($student1->id, $results[0]->get('userid'));
        $this->assertNull($results[0]->get('proficiency'));
        $this->assertNull($results[0]->get('grade'));
        $this->assertEquals($comp2->get('id'), $results[1]->get('competencyid'));
        $this->assertEquals($cm->id, $results[1]->get('cmid'));
        $this->assertEquals($student1->id, $results[1]->get('userid'));
        $this->assertNull($results[1]->get('proficiency'));
        $this->assertNull($results[1]->get('grade'));
        api::grade_competency_in_coursemodule($cm, $student1->id, $comp1->get('id'), 3, 'Unit test 3');
        $results = api::list_user_competencies_in_coursemodule($cm->id, $student1->id);
        $results = array_values($results);
        $this->assertEquals($comp1->get('id'), $results[0]->get('competencyid'));
        $this->assertEquals($cm->id, $results[0]->get('cmid'));
        $this->assertEquals($student1->id, $results[0]->get('userid'));
        $this->assertEquals(1, $results[0]->get('proficiency'));
        $this->assertEquals(3, $results[0]->get('grade'));
        $this->assertEquals($comp2->get('id'), $results[1]->get('competencyid'));
        $this->assertEquals($cm->id, $results[1]->get('cmid'));
        $this->assertEquals($student1->id, $results[1]->get('userid'));
        $this->assertNull($results[1]->get('proficiency'));
        $this->assertNull($results[1]->get('grade'));
        // Teacher can not get user competency from user not enrolled in course.
        try {
            api::list_user_competencies_in_coursemodule($cm->id, $student2->id);
            $this->fail('The user does not belong to this course.');
        } catch (coding_exception $e) {
            $this->assertStringContainsString('The user does not belong to this course.', $e->getMessage());
        }
    }

    /**
     * Test grade_competency_in_coursemodule.
     */
    public function test_grade_competency_in_coursemodule() {
        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('core_competency');

        // Create groups of students.
        $groupingdata = array();
        $groupingdata['courseid'] = $this->course1->id;
        $groupingdata['name'] = 'Group assignment grouping';

        $grouping = self::getDataGenerator()->create_grouping($groupingdata);

        $group1data = array();
        $group1data['courseid'] = $this->course1->id;
        $group1data['name'] = 'Team 1';
        $group2data = array();
        $group2data['courseid'] = $this->course1->id;
        $group2data['name'] = 'Team 2';

        $group1 = self::getDataGenerator()->create_group($group1data);
        $group2 = self::getDataGenerator()->create_group($group2data);

        groups_assign_grouping($grouping->id, $group1->id);
        groups_assign_grouping($grouping->id, $group2->id);

        groups_add_member($group1->id, $this->student1->id);
        groups_add_member($group1->id, $this->student2->id);
        groups_add_member($group2->id, $this->student3->id);
        groups_add_member($group2->id, $this->student4->id);

        // Generate a team assignment.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $params = array();
        $params['course'] = $this->course1->id;
        $params['name'] = 'Assign 1';
        $params['teamsubmission'] = 1;
        $params['teamsubmissiongroupingid'] = $grouping->id;
        $instance = $generator->create_instance($params);
        $cm1 = get_coursemodule_from_instance('assign', $instance->id);

        // Generate an individual assignment.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $params = array();
        $params['course'] = $this->course1->id;
        $params['name'] = 'Assign 2';
        $instance = $generator->create_instance($params);
        $cm2 = get_coursemodule_from_instance('assign', $instance->id);

        // Generate a quiz.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $params = array();
        $params['course'] = $this->course1->id;
        $params['name'] = 'Quiz';
        $instance = $generator->create_instance($params);
        $cm3 = get_coursemodule_from_instance('quiz', $instance->id);

        // Create competencies.
        $comp1 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));
        $comp2 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));

        // Link competencies to the course.
        $lpg->create_course_competency(array('competencyid' => $comp1->get('id'), 'courseid' => $this->course1->id));
        $lpg->create_course_competency(array('competencyid' => $comp2->get('id'), 'courseid' => $this->course1->id));

        // Link competencies to course modules.
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cm1->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp2->get('id'), 'cmid' => $cm1->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cm2->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp2->get('id'), 'cmid' => $cm2->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cm3->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp2->get('id'), 'cmid' => $cm3->id));

        // Grade for group when the assign is for team submission.
        api::grade_competency_in_coursemodule($cm1, $this->student1->id, $comp1->get('id'), 1, null, true);
        // Student1 gets the new grade.
        $uc = api::get_user_competency_in_coursemodule($cm1->id, $this->student1->id, $comp1->get('id'));
        $this->assertEquals($comp1->get('id'), $uc->get('competencyid'));
        $this->assertEquals($this->student1->id, $uc->get('userid'));
        $this->assertEquals(1, $uc->get('grade'));
        // Student2 gets the new grade (same team).
        $uc = api::get_user_competency_in_coursemodule($cm1->id, $this->student2->id, $comp1->get('id'));
        $this->assertEquals($comp1->get('id'), $uc->get('competencyid'));
        $this->assertEquals($this->student2->id, $uc->get('userid'));
        $this->assertEquals(1, $uc->get('grade'));
        // Student3 does not get the new grade (other team).
        $uc = api::get_user_competency_in_coursemodule($cm1->id, $this->student3->id, $comp1->get('id'));
        $this->assertEquals($comp1->get('id'), $uc->get('competencyid'));
        $this->assertEquals($this->student3->id, $uc->get('userid'));
        $this->assertEquals(null, $uc->get('grade'));

        // Grade for single student when the assign is for team submission.
        api::grade_competency_in_coursemodule($cm1, $this->student1->id, $comp1->get('id'), 2, null, false);
        // Student1 gets the new grade.
        $uc = api::get_user_competency_in_coursemodule($cm1->id, $this->student1->id, $comp1->get('id'));
        $this->assertEquals($comp1->get('id'), $uc->get('competencyid'));
        $this->assertEquals($this->student1->id, $uc->get('userid'));
        $this->assertEquals(2, $uc->get('grade'));
        // Student2 does not get the new grade (same team) and keeps previous grade.
        $uc = api::get_user_competency_in_coursemodule($cm1->id, $this->student2->id, $comp1->get('id'));
        $this->assertEquals($comp1->get('id'), $uc->get('competencyid'));
        $this->assertEquals($this->student2->id, $uc->get('userid'));
        $this->assertEquals(1, $uc->get('grade'));
        // Student3 does not get the new grade (other team).
        $uc = api::get_user_competency_in_coursemodule($cm1->id, $this->student3->id, $comp1->get('id'));
        $this->assertEquals($comp1->get('id'), $uc->get('competencyid'));
        $this->assertEquals($this->student3->id, $uc->get('userid'));
        $this->assertEquals(null, $uc->get('grade'));

        // Grade for group when the assign is not for team submission.
        api::grade_competency_in_coursemodule($cm2, $this->student1->id, $comp1->get('id'), 3, null, true);
        // Student1 gets the new grade.
        $uc = api::get_user_competency_in_coursemodule($cm2->id, $this->student1->id, $comp1->get('id'));
        $this->assertEquals($comp1->get('id'), $uc->get('competencyid'));
        $this->assertEquals($this->student1->id, $uc->get('userid'));
        $this->assertEquals(3, $uc->get('grade'));
        // Student2 does not get the new grade (same team).
        $uc = api::get_user_competency_in_coursemodule($cm2->id, $this->student2->id, $comp1->get('id'));
        $this->assertEquals($comp1->get('id'), $uc->get('competencyid'));
        $this->assertEquals($this->student2->id, $uc->get('userid'));
        $this->assertEquals(null, $uc->get('grade'));
        // Student3 does not get the new grade (other team).
        $uc = api::get_user_competency_in_coursemodule($cm2->id, $this->student3->id, $comp1->get('id'));
        $this->assertEquals($comp1->get('id'), $uc->get('competencyid'));
        $this->assertEquals($this->student3->id, $uc->get('userid'));
        $this->assertEquals(null, $uc->get('grade'));

        // Grade for group when not of type "assign".
        api::grade_competency_in_coursemodule($cm3, $this->student1->id, $comp1->get('id'), 4, null, true);
        // Student1 gets the new grade.
        $uc = api::get_user_competency_in_coursemodule($cm3->id, $this->student1->id, $comp1->get('id'));
        $this->assertEquals($comp1->get('id'), $uc->get('competencyid'));
        $this->assertEquals($this->student1->id, $uc->get('userid'));
        $this->assertEquals(4, $uc->get('grade'));
        // Student2 does not get the new grade (same team).
        $uc = api::get_user_competency_in_coursemodule($cm3->id, $this->student2->id, $comp1->get('id'));
        $this->assertEquals($comp1->get('id'), $uc->get('competencyid'));
        $this->assertEquals($this->student2->id, $uc->get('userid'));
        $this->assertEquals(null, $uc->get('grade'));
        // Student3 does not get the new grade (other team).
        $uc = api::get_user_competency_in_coursemodule($cm3->id, $this->student3->id, $comp1->get('id'));
        $this->assertEquals($comp1->get('id'), $uc->get('competencyid'));
        $this->assertEquals($this->student3->id, $uc->get('userid'));
        $this->assertEquals(null, $uc->get('grade'));
    }

    /*
     * Test list_user_competencies_in_coursemodule.
     */
    public function test_get_list_course_modules_with_competencies() {
        global $CFG;
        $this->resetAfterTest(true);
        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('core_competency');
        $this->setAdminUser();

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $c1 = $dg->create_course();
        $c2 = $dg->create_course();

        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $page = $pagegenerator->create_instance(array('course' => $c1->id));
        $cmpage1 = get_coursemodule_from_instance('page', $page->id);
        $page = $pagegenerator->create_instance(array('course' => $c1->id, 'visible' => 0));
        $cmpage2 = get_coursemodule_from_instance('page', $page->id);
        $page = $pagegenerator->create_instance(array('course' => $c1->id));
        $cmpage3 = get_coursemodule_from_instance('page', $page->id);
        // Page cm for course 2.
        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $page = $pagegenerator->create_instance(array('course' => $c2->id));
        $cmpage4 = get_coursemodule_from_instance('page', $page->id);

        $framework = $lpg->create_framework();
        // Enrol students in the course 1.
        $studentarch = get_archetype_roles('student');
        $studentrole = array_shift($studentarch);
        $coursecontext = context_course::instance($c1->id);
        $dg->role_assign($studentrole->id, $u1->id, $coursecontext->id);
        $dg->enrol_user($u1->id, $c1->id, $studentrole->id);
        $dg->role_assign($studentrole->id, $u2->id, $coursecontext->id);
        $dg->enrol_user($u2->id, $c1->id, $studentrole->id);
        $dg->role_assign($studentrole->id, $u3->id, $coursecontext->id);
        $dg->enrol_user($u3->id, $c1->id, $studentrole->id);
        // Enrol user1 to the course2.
        $coursecontext = context_course::instance($c2->id);
        $dg->role_assign($studentrole->id, $u1->id, $coursecontext->id);
        $dg->enrol_user($u1->id, $c2->id, $studentrole->id);

        // Turn on availability and a group restriction, and check that it doesn't show users who aren't in the group.
        $CFG->enableavailability = true;

        $specialgroup = $this->getDataGenerator()->create_group(['courseid' => $c1->id]);
        $assigngenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $assign = $assigngenerator->create_instance([
                'course' => $c1->id,
                'grade' => 100,
                'availability' => json_encode(
                    \core_availability\tree::get_root_json([\availability_group\condition::get_json($specialgroup->id)])
                ),
            ]);
        $cmassign = get_coursemodule_from_instance('assign', $assign->id);
        groups_add_member($specialgroup, $u1);
        groups_add_member($specialgroup, $u2);

        // Create a competency.
        $comp1 = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id')));

        // Link competency to a course.
        $lpg->create_course_competency(array('competencyid' => $comp1->get('id'), 'courseid' => $c1->id));

        // Link competency to course modules.
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cmpage1->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cmpage2->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cmassign->id));
        // Test for user1.
        $this->setUser($u1);
        $cms = \tool_cmcompetency\api::get_list_course_modules_with_competencies($c1->id);
        // User1 should see only page1 and assign1.
        $this->assertContains($cmpage1->id, array_keys($cms));
        $this->assertContains($cmassign->id, array_keys($cms));
        // Hidden course module.
        $this->assertNotContains($cmpage2->id, array_keys($cms));
        // No competency linked for page3.
        $this->assertNotContains($cmpage3->id, array_keys($cms));

        // Test for user2.
        $this->setUser($u2);
        $cms = \tool_cmcompetency\api::get_list_course_modules_with_competencies($c1->id);
        // User2 should see only page1 and assign1.
        $this->assertContains($cmpage1->id, array_keys($cms));
        $this->assertContains($cmassign->id, array_keys($cms));
        // Hidden course module.
        $this->assertNotContains($cmpage2->id, array_keys($cms));
        // No competency linked for page3.
        $this->assertNotContains($cmpage3->id, array_keys($cms));

        // Test for user3.
        $this->setUser($u3);
        $cms = \tool_cmcompetency\api::get_list_course_modules_with_competencies($c1->id);
        // User3 should see only page1.
        $this->assertContains($cmpage1->id, array_keys($cms));
        // User3 does not belong to group.
        $this->assertNotContains($cmassign->id, array_keys($cms));
        // Hidden course module.
        $this->assertNotContains($cmpage2->id, array_keys($cms));
        // No competency linked for page3.
        $this->assertNotContains($cmpage3->id, array_keys($cms));

        // Empty course module for user1 for the course2.
        $this->setUser($u1);
        $cms = \tool_cmcompetency\api::get_list_course_modules_with_competencies($c2->id);
        $this->assertEmpty($cms);
    }

    /**
     * Test is_cm_available_for_user.
     */
    public function test_is_cm_available_for_user() {
        global $CFG;

        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('core_competency');

        // Create groups of students.
        $groupingdata = array();
        $groupingdata['courseid'] = $this->course1->id;
        $groupingdata['name'] = 'Group assignment grouping';

        $grouping = self::getDataGenerator()->create_grouping($groupingdata);

        $group1data = array();
        $group1data['courseid'] = $this->course1->id;
        $group1data['name'] = 'Team 1';
        $group2data = array();
        $group2data['courseid'] = $this->course1->id;
        $group2data['name'] = 'Team 2';

        $group1 = self::getDataGenerator()->create_group($group1data);
        $group2 = self::getDataGenerator()->create_group($group2data);

        groups_assign_grouping($grouping->id, $group1->id);
        groups_assign_grouping($grouping->id, $group2->id);

        groups_add_member($group1->id, $this->student1->id);
        groups_add_member($group1->id, $this->student2->id);
        groups_add_member($group2->id, $this->student3->id);
        groups_add_member($group2->id, $this->student4->id);

        // Turn on availability and a group restriction and create a quiz for Team 1.
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $params = array();
        $params['course'] = $this->course1->id;
        $params['name'] = 'Quiz1';
        $params['availability'] = json_encode(
                    \core_availability\tree::get_root_json([\availability_group\condition::get_json($group1->id)])
                );
        $instance = $generator->create_instance($params);
        $cm1 = get_coursemodule_from_instance('quiz', $instance->id);

        // Create a quiz for everybody.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $params = array();
        $params['course'] = $this->course1->id;
        $params['name'] = 'Quiz2';
        $instance = $generator->create_instance($params);
        $cm2 = get_coursemodule_from_instance('quiz', $instance->id);

        // Create a competency and link it to the course and course modules.
        $comp1 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));
        $lpg->create_course_competency(array('competencyid' => $comp1->get('id'), 'courseid' => $this->course1->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cm1->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cm2->id));

        // Checks that only Team 1 sees Quiz1.
        $isavailable = api::is_cm_available_for_user($cm1, $this->student1);
        $this->assertTrue($isavailable);
        $isavailable = api::is_cm_available_for_user($cm1, $this->student2);
        $this->assertTrue($isavailable);
        $isavailable = api::is_cm_available_for_user($cm1, $this->student3);
        $this->assertFalse($isavailable);
        $isavailable = api::is_cm_available_for_user($cm1, $this->student4);
        $this->assertFalse($isavailable);

        // Checks that everybody see Quiz2.
        $isavailable = api::is_cm_available_for_user($cm2, $this->student1);
        $this->assertTrue($isavailable);
        $isavailable = api::is_cm_available_for_user($cm2, $this->student2);
        $this->assertTrue($isavailable);
        $isavailable = api::is_cm_available_for_user($cm2, $this->student3);
        $this->assertTrue($isavailable);
        $isavailable = api::is_cm_available_for_user($cm2, $this->student4);
        $this->assertTrue($isavailable);
    }

    /**
     * Test get_cm_gradable_users when the activity has a group restriction.
     */
    public function test_get_cm_gradable_users_grouprestriction() {
        global $CFG;

        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('core_competency');

        // Create groups of students.
        $groupingdata = array();
        $groupingdata['courseid'] = $this->course1->id;
        $groupingdata['name'] = 'Group assignment grouping';

        $grouping = self::getDataGenerator()->create_grouping($groupingdata);

        $group1data = array();
        $group1data['courseid'] = $this->course1->id;
        $group1data['name'] = 'Team 1';
        $group2data = array();
        $group2data['courseid'] = $this->course1->id;
        $group2data['name'] = 'Team 2';

        $group1 = self::getDataGenerator()->create_group($group1data);
        $group2 = self::getDataGenerator()->create_group($group2data);

        groups_assign_grouping($grouping->id, $group1->id);
        groups_assign_grouping($grouping->id, $group2->id);

        groups_add_member($group1->id, $this->student1->id);
        groups_add_member($group1->id, $this->student2->id);
        groups_add_member($group2->id, $this->student3->id);
        groups_add_member($group2->id, $this->student4->id);

        // Turn on availability and a group restriction and create a quiz for Team 1.
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $params = array();
        $params['course'] = $this->course1->id;
        $params['name'] = 'Quiz1';
        $params['availability'] = json_encode(
                    \core_availability\tree::get_root_json([\availability_group\condition::get_json($group1->id)])
                );
        $instance = $generator->create_instance($params);
        $cm1 = get_coursemodule_from_instance('quiz', $instance->id);

        // Create a quiz for everybody.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $params = array();
        $params['course'] = $this->course1->id;
        $params['name'] = 'Quiz2';
        $instance = $generator->create_instance($params);
        $cm2 = get_coursemodule_from_instance('quiz', $instance->id);

        // Create a competency and link it to the course and course modules.
        $comp1 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));
        $lpg->create_course_competency(array('competencyid' => $comp1->get('id'), 'courseid' => $this->course1->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cm1->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cm2->id));

        // Checks that only Team 1 sees Quiz1.
        $coursecontext = context_course::instance($this->course1->id);
        $students = api::get_cm_gradable_users($coursecontext, $cm1);
        $this->assertEquals(2, count($students));
        $this->assertContains($this->student1->id, array_keys($students));
        $this->assertContains($this->student2->id, array_keys($students));

        // Checks that everybody see Quiz2.
        $coursecontext = context_course::instance($this->course1->id);
        $students = api::get_cm_gradable_users($coursecontext, $cm2);
        $this->assertEquals(4, count($students));
        $this->assertContains($this->student1->id, array_keys($students));
        $this->assertContains($this->student2->id, array_keys($students));
        $this->assertContains($this->student3->id, array_keys($students));
        $this->assertContains($this->student4->id, array_keys($students));
    }

    /**
     * Test get_cm_gradable_users for different groups.
     */
    public function test_get_cm_gradable_users_groups() {
        global $CFG;

        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('core_competency');

        // Create groups of students.
        $groupingdata = array();
        $groupingdata['courseid'] = $this->course1->id;
        $groupingdata['name'] = 'Group assignment grouping';

        $grouping = self::getDataGenerator()->create_grouping($groupingdata);

        $group1data = array();
        $group1data['courseid'] = $this->course1->id;
        $group1data['name'] = 'Team 1';
        $group2data = array();
        $group2data['courseid'] = $this->course1->id;
        $group2data['name'] = 'Team 2';

        $group1 = self::getDataGenerator()->create_group($group1data);
        $group2 = self::getDataGenerator()->create_group($group2data);

        groups_assign_grouping($grouping->id, $group1->id);
        groups_assign_grouping($grouping->id, $group2->id);

        groups_add_member($group1->id, $this->student1->id);
        groups_add_member($group1->id, $this->student2->id);
        groups_add_member($group2->id, $this->student3->id);
        groups_add_member($group2->id, $this->student4->id);

        // Create a quiz.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $params = array();
        $params['course'] = $this->course1->id;
        $params['name'] = 'Quiz1';
        $instance = $generator->create_instance($params);
        $cm1 = get_coursemodule_from_instance('quiz', $instance->id);

        // Create a competency and link it to the course and course modules.
        $comp1 = $lpg->create_competency(array('competencyframeworkid' => $this->framework->get('id')));
        $lpg->create_course_competency(array('competencyid' => $comp1->get('id'), 'courseid' => $this->course1->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cm1->id));

        $coursecontext = context_course::instance($this->course1->id);

        // Check group members of Team 1.
        $students = api::get_cm_gradable_users($coursecontext, $cm1, $group1->id);
        $this->assertEquals(2, count($students));
        $this->assertContains($this->student1->id, array_keys($students));
        $this->assertContains($this->student2->id, array_keys($students));

        // Check group members of Team 2.
        $students = api::get_cm_gradable_users($coursecontext, $cm1, $group2->id);
        $this->assertEquals(2, count($students));
        $this->assertContains($this->student3->id, array_keys($students));
        $this->assertContains($this->student4->id, array_keys($students));
    }
}
