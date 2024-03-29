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
 * Step definition to generate database fixtures for course module competencies report.
 *
 * @package    tool_cmcompetency
 * @category   test
 * @author     Marie-Eve Lévesque <marie-eve.levesque.8@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode;
use Behat\Behat\Tester\Exception\PendingException as PendingException;
use core_competency\api as core_competency_api;
use tool_cmcompetency\api as tool_cmcompetency_api;
use tool_cohortroles\api as tool_cohortroles_api;


/**
 * Step definition to generate database fixtures for course module competencies report.
 *
 * @package    tool_cmcompetency
 * @category   test
 * @author     Marie-Eve Lévesque <marie-eve.levesque.8@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_cmcompetency_data_generators extends behat_base {

    /**
     * Creates the specified element. More info about available elements in http://docs.moodle.org/dev/Acceptance_testing#Fixtures.
     *
     * @Given /^the cmcompetency fixtures exist$/
     *
     * @throws Exception
     * @throws PendingException
     */
    public function the_cmcompetency_fixtures_exist() {
        // Now that we need them require the data generators.
        require_once(__DIR__.'/../../../../../lib/phpunit/classes/util.php');

        $datagenerator = testing_util::get_data_generator();
        $cpg = $datagenerator->get_plugin_generator('core_competency');

        // Create category.
        $cat1 = $datagenerator->create_category(['name' => 'Medicine']);
        $cat1ctx = context_coursecat::instance($cat1->id);

        // Create course.
        $course1 = $datagenerator->create_course(['shortname' => 'Anatomy', 'fullname' => 'Anatomy', 'category' => $cat1->id]);
        $course2 = $datagenerator->create_course(['shortname' => 'Genetic', 'fullname' => 'Genetic', 'category' => $cat1->id]);

        // Create templates.
        $template1 = $cpg->create_template(['shortname' => 'Medicine', 'contextid' => $cat1ctx->id]);

        // Create scales.
        $scale1 = $datagenerator->create_scale(['name' => 'Scale default', 'scale' => 'not good, good, very good']);
        $scale2 = $datagenerator->create_scale(['name' => 'Scale specific', 'scale' => 'not qualified, qualified']);

        $scaleconfiguration1 = '[{"scaleid":"'.$scale1->id.'"},' .
                '{"name":"not good","id":1,"scaledefault":1,"proficient":0},' .
                '{"name":"good","id":2,"scaledefault":0,"proficient":1},' .
                '{"name":"very good","id":3,"scaledefault":0,"proficient":1}]';
        $scaleconfiguration2 = '[{"scaleid":"'.$scale2->id.'"},' .
                '{"name":"not qualified","id":1,"scaledefault":1,"proficient":0},' .
                '{"name":"qualified","id":2,"scaledefault":0,"proficient":1}]';

        // Create the competency framework.
        $framework = [
            'shortname'          => 'Framework Medicine',
            'idnumber'           => 'fr-medicine',
            'scaleid'            => $scale1->id,
            'scaleconfiguration' => $scaleconfiguration1,
            'visible'            => true,
            'contextid'          => $cat1ctx->id,
        ];
        $framework = $cpg->create_framework($framework);
        $c1        = $cpg->create_competency([
            'competencyframeworkid' => $framework->get('id'),
            'shortname'             => 'Competency A', ]
        );

        $c2 = $cpg->create_competency([
            'competencyframeworkid' => $framework->get('id'),
            'shortname'             => 'Competency B',
            'scaleid'               => $scale2->id,
            'scaleconfiguration'    => $scaleconfiguration2, ]
        );

        // Create course competency.
        $cpg->create_course_competency(['courseid' => $course1->id, 'competencyid' => $c1->get('id')]);
        $cpg->create_course_competency(['courseid' => $course1->id, 'competencyid' => $c2->get('id')]);

        $cpg->create_course_competency(['courseid' => $course2->id, 'competencyid' => $c1->get('id')]);
        $cpg->create_course_competency(['courseid' => $course2->id, 'competencyid' => $c2->get('id')]);

        // Create template competency.
        $cpg->create_template_competency(['templateid' => $template1->get('id'), 'competencyid' => $c1->get('id')]);
        $cpg->create_template_competency(['templateid' => $template1->get('id'), 'competencyid' => $c2->get('id')]);

        $user1 = $datagenerator->create_user([
            'firstname' => 'Rebecca',
            'lastname'  => 'Armenta',
            'username'  => 'rebeccaa',
            'password'  => 'rebeccaa', ]
        );
        $user2 = $datagenerator->create_user([
            'firstname' => 'Pablo',
            'lastname'  => 'Menendez',
            'username'  => 'pablom',
            'password'  => 'pablom', ]
        );
        $user3 = $datagenerator->create_user([
            'firstname' => 'Stepanie',
            'lastname'  => 'Grant',
            'username'  => 'stepanieg',
            'password'  => 'stepanieg', ]
        );

        // Enrol users in courses.
        $datagenerator->enrol_user($user1->id, $course1->id);
        $datagenerator->enrol_user($user1->id, $course2->id);

        $datagenerator->enrol_user($user2->id, $course1->id);
        $datagenerator->enrol_user($user2->id, $course2->id);

        $datagenerator->enrol_user($user3->id, $course1->id);
        $datagenerator->enrol_user($user3->id, $course2->id);

        // Create groups of students in course 1.
        $groupingdata = [];
        $groupingdata['courseid'] = $course1->id;
        $groupingdata['name'] = 'Group assignment grouping';

        $grouping = $datagenerator->create_grouping($groupingdata);

        $group1data = [];
        $group1data['courseid'] = $course1->id;
        $group1data['name'] = 'Team 1';
        $group2data = [];
        $group2data['courseid'] = $course1->id;
        $group2data['name'] = 'Team 2';

        $group1 = $datagenerator->create_group($group1data);
        $group2 = $datagenerator->create_group($group2data);

        groups_assign_grouping($grouping->id, $group1->id);
        groups_assign_grouping($grouping->id, $group2->id);

        groups_add_member($group1->id, $user1->id);
        groups_add_member($group1->id, $user2->id);
        groups_add_member($group2->id, $user3->id);

        // Create and enrol teacher in courses.
        $teacher = $datagenerator->create_user(
                [
                    'firstname' => 'Teacher',
                    'lastname'  => 'Test',
                    'username'  => 'teacher',
                    'password'  => 'teacher',
                ]
        );
        $datagenerator->enrol_user($teacher->id, $course1->id, 'editingteacher', 'manual');
        $datagenerator->enrol_user($teacher->id, $course2->id, 'editingteacher', 'manual');

        // Create cohort.
        $cohort = $datagenerator->create_cohort(['contextid' => $cat1ctx->id]);
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);
        cohort_add_member($cohort->id, $user3->id);
        // Generate plans for cohort.
        core_competency_api::create_plans_from_template_cohort($template1->get('id'), $cohort->id);
        $syscontext = context_system::instance();

        // Create modules for course 1.
        $options = ['course' => $course1->id, 'name' => 'Module 1',
            'teamsubmission' => 1, 'teamsubmissiongroupingid' => $grouping->id, ];
        $cm1 = $datagenerator->create_module('assign', $options);
        $cm2 = $datagenerator->create_module('forum', ['course' => $course1->id, 'name' => 'Module 2'],
            ['groupmode' => SEPARATEGROUPS, 'groupingid' => $grouping->id]);
        $cm21 = $datagenerator->create_module('forum', ['course' => $course1->id, 'name' => 'Forum Test']);

        // Create modules for course 2.
        $cm3 = $datagenerator->create_module('quiz', ['course' => $course2->id, 'name' => 'Module 3']);

        // Assign competencies to course modules.
        $cpg->create_course_module_competency(['cmid' => $cm1->cmid, 'competencyid' => $c1->get('id')]);

        $cpg->create_course_module_competency(['cmid' => $cm2->cmid, 'competencyid' => $c1->get('id')]);
        $cpg->create_course_module_competency(['cmid' => $cm2->cmid, 'competencyid' => $c2->get('id')]);

        $cpg->create_course_module_competency(['cmid' => $cm3->cmid, 'competencyid' => $c1->get('id')]);

        // Rate some competencies in modules for Rebecca.
        tool_cmcompetency_api::grade_competency_in_coursemodule($cm1->cmid, $user1->id, $c1->get('id'), 1, "My note for Rebecca");
        tool_cmcompetency_api::grade_competency_in_coursemodule($cm2->cmid, $user1->id, $c2->get('id'), 2);
        tool_cmcompetency_api::grade_competency_in_coursemodule($cm3->cmid, $user1->id, $c1->get('id'), 2);

        // Rate some competencies in modules for Pablo.
        tool_cmcompetency_api::grade_competency_in_coursemodule($cm1->cmid, $user2->id, $c1->get('id'), 2);
        tool_cmcompetency_api::grade_competency_in_coursemodule($cm3->cmid, $user2->id, $c1->get('id'), 1, "My note for Pablo");
    }
}
