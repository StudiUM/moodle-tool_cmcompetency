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

    /**
     * Test course module statistics api functions.
     */
    public function test_coursemodule_statistics() {
        $this->resetAfterTest(true);
        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('core_competency');
        $this->setAdminUser();

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $u4 = $dg->create_user();
        $c1 = $dg->create_course();

        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $page = $pagegenerator->create_instance(array('course' => $c1->id));

        $cm = get_coursemodule_from_instance('page', $page->id);

        $framework = $lpg->create_framework();
        // Enrol students in the course.
        $studentarch = get_archetype_roles('student');
        $studentrole = array_shift($studentarch);
        $coursecontext = context_course::instance($c1->id);
        $dg->role_assign($studentrole->id, $u1->id, $coursecontext->id);
        $dg->enrol_user($u1->id, $c1->id, $studentrole->id);
        $dg->role_assign($studentrole->id, $u2->id, $coursecontext->id);
        $dg->enrol_user($u2->id, $c1->id, $studentrole->id);
        $dg->role_assign($studentrole->id, $u3->id, $coursecontext->id);
        $dg->enrol_user($u3->id, $c1->id, $studentrole->id);
        $dg->role_assign($studentrole->id, $u4->id, $coursecontext->id);
        $dg->enrol_user($u4->id, $c1->id, $studentrole->id);

        // Create 6 competencies.
        $comp1 = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id')));
        $comp2 = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id')));
        $comp3 = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id')));
        $comp4 = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id')));
        $comp5 = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id')));
        $comp6 = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id')));

        // Link 6 out of 6 to a course.
        $lpg->create_course_competency(array('competencyid' => $comp1->get('id'), 'courseid' => $c1->id));
        $lpg->create_course_competency(array('competencyid' => $comp2->get('id'), 'courseid' => $c1->id));
        $lpg->create_course_competency(array('competencyid' => $comp3->get('id'), 'courseid' => $c1->id));
        $lpg->create_course_competency(array('competencyid' => $comp4->get('id'), 'courseid' => $c1->id));
        $lpg->create_course_competency(array('competencyid' => $comp5->get('id'), 'courseid' => $c1->id));
        $lpg->create_course_competency(array('competencyid' => $comp6->get('id'), 'courseid' => $c1->id));

        // Link competencies to course module.
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cm->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp2->get('id'), 'cmid' => $cm->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp3->get('id'), 'cmid' => $cm->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp4->get('id'), 'cmid' => $cm->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp5->get('id'), 'cmid' => $cm->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp6->get('id'), 'cmid' => $cm->id));

        // Rate some competencies.
        // User 1.
        api::grade_competency_in_coursemodule($cm, $u1->id, $comp1->get('id'), 1, 'Unit test 1');
        api::grade_competency_in_coursemodule($cm, $u1->id, $comp2->get('id'), 2, 'Unit test 2');
        api::grade_competency_in_coursemodule($cm, $u1->id, $comp3->get('id'), 3, 'Unit test 3');
        api::grade_competency_in_coursemodule($cm, $u1->id, $comp4->get('id'), 4, 'Unit test 4');
        // User 2.
        api::grade_competency_in_coursemodule($cm, $u2->id, $comp1->get('id'), 1, 'Unit test 1');
        api::grade_competency_in_coursemodule($cm, $u2->id, $comp2->get('id'), 2, 'Unit test 2');
        api::grade_competency_in_coursemodule($cm, $u2->id, $comp3->get('id'), 1, 'Unit test 3');
        api::grade_competency_in_coursemodule($cm, $u2->id, $comp4->get('id'), 4, 'Unit test 4');
        // User 3.
        api::grade_competency_in_coursemodule($cm, $u3->id, $comp1->get('id'), 3, 'Unit test 3');
        api::grade_competency_in_coursemodule($cm, $u3->id, $comp2->get('id'), 2, 'Unit test 2');
        // User 4.
        api::grade_competency_in_coursemodule($cm, $u4->id, $comp1->get('id'), 2, 'Unit test 2');
        api::grade_competency_in_coursemodule($cm, $u4->id, $comp2->get('id'), 1, 'Unit test 1');

        // OK we have enough data - lets call some API functions and check for expected results.

        $result = api::count_proficient_competencies_in_coursemodule_for_user($cm->id, $u1->id);
        $this->assertEquals(2, $result);
        $result = api::count_proficient_competencies_in_coursemodule_for_user($cm->id, $u2->id);
        $this->assertEquals(1, $result);
        $result = api::count_proficient_competencies_in_coursemodule_for_user($cm->id, $u3->id);
        $this->assertEquals(1, $result);
        $result = api::count_proficient_competencies_in_coursemodule_for_user($cm->id, $u4->id);
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
        $result = api::list_evidence_in_coursemodule($u1->id, $cm->id, $comp1->get('id'), 'timecreated', 'ASC');
        $this->assertEquals($result[0]->get('descidentifier'), 'evidence_manualoverrideincoursemodule');
        $this->assertEquals($result[0]->get('grade'), '1');
        $this->assertEquals($result[0]->get('note'), 'Unit test 1');
    }

    /**
     * Get a user competency in a course module.
     */
    public function test_get_user_competency_in_coursemodule() {
        $this->resetAfterTest(true);
        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('core_competency');
        $this->setAdminUser();

        $user = $dg->create_user();
        $c1 = $dg->create_course();

        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $page = $pagegenerator->create_instance(array('course' => $c1->id));

        $cm = get_coursemodule_from_instance('page', $page->id);

        // Enrol the user so they can be rated in the course.
        $studentarch = get_archetype_roles('student');
        $studentrole = array_shift($studentarch);
        $coursecontext = context_course::instance($c1->id);
        $dg->role_assign($studentrole->id, $user->id, $coursecontext->id);
        $dg->enrol_user($user->id, $c1->id, $studentrole->id);

        $framework = $lpg->create_framework();
        $comp1 = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id')));
        $comp2 = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id')));
        $lpg->create_course_competency(array('competencyid' => $comp1->get('id'), 'courseid' => $c1->id));
        $lpg->create_course_competency(array('competencyid' => $comp2->get('id'), 'courseid' => $c1->id));
        // Link competencies to course module.
        $lpg->create_course_module_competency(array('competencyid' => $comp1->get('id'), 'cmid' => $cm->id));
        $lpg->create_course_module_competency(array('competencyid' => $comp2->get('id'), 'cmid' => $cm->id));

        // Create a user competency for comp1.
        api::grade_competency_in_coursemodule($cm, $user->id, $comp1->get('id'), 3, 'Unit test');

        // Test for competency already exist in user_competency.
        $uc = api::get_user_competency_in_coursemodule($cm->id, $user->id, $comp1->get('id'));
        $this->assertEquals($comp1->get('id'), $uc->get('competencyid'));
        $this->assertEquals($user->id, $uc->get('userid'));
        $this->assertEquals(3, $uc->get('grade'));
        $this->assertEquals(true, $uc->get('proficiency'));

        // Test for competency does not exist in user_competency.
        $uc2 = api::get_user_competency_in_coursemodule($cm->id, $user->id, $comp2->get('id'));
        $this->assertEquals($comp2->get('id'), $uc2->get('competencyid'));
        $this->assertEquals($user->id, $uc2->get('userid'));
        $this->assertEquals(null, $uc2->get('grade'));
        $this->assertEquals(null, $uc2->get('proficiency'));
    }

    /**
     * Test list courses modules using competency.
     */
    public function test_list_coursesmodules_using_competency() {
        $this->resetAfterTest(true);
        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('core_competency');
        $this->setAdminUser();

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();

        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $page = $pagegenerator->create_instance(array('course' => $c1->id));
        $page1 = $pagegenerator->create_instance(array('course' => $c1->id));

        $cm = get_coursemodule_from_instance('page', $page->id);
        $cm1 = get_coursemodule_from_instance('page', $page1->id);

        $page = $pagegenerator->create_instance(array('course' => $c2->id));
        $page1 = $pagegenerator->create_instance(array('course' => $c2->id));

        $cm2 = get_coursemodule_from_instance('page', $page->id);
        $cm21 = get_coursemodule_from_instance('page', $page1->id);

        $framework = $lpg->create_framework();
        // Create 3 competencies.
        $comp1 = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id')));
        $comp2 = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id')));
        $comp3 = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id')));

        // Link 2 out of 3 to course 1.
        $lpg->create_course_competency(array('competencyid' => $comp1->get('id'), 'courseid' => $c1->id));
        $lpg->create_course_competency(array('competencyid' => $comp2->get('id'), 'courseid' => $c1->id));
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
    }

    /**
     * Test list_user_competencies_in_coursemodule.
     */
    public function test_list_user_competencies_in_coursemodule() {
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('core_competency');

        $c1 = $dg->create_course();
        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $page = $pagegenerator->create_instance(array('course' => $c1->id));

        $cm = get_coursemodule_from_instance('page', $page->id);
        $sysctx = context_system::instance();
        $c1ctx = context_course::instance($c1->id);

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
        $dg->enrol_user($student1->id, $c1->id, $studentrole->id);
        // Give permission to view competencies.
        $dg->role_assign($canviewucrole, $teacher1->id, $c1ctx->id);
        // Give permission to rate.
        $dg->role_assign($cangraderole, $teacher1->id, $c1ctx->id);

        $framework = $lpg->create_framework();
        $comp1 = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id')));
        $comp2 = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id')));
        $lpg->create_course_competency(array('competencyid' => $comp1->get('id'), 'courseid' => $c1->id));
        $lpg->create_course_competency(array('competencyid' => $comp2->get('id'), 'courseid' => $c1->id));
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
            $this->assertContains('The user does not belong to this course.', $e->getMessage());
        }
    }

}
