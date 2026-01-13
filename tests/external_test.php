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
 * External course module competency webservice API tests.
 *
 * @package   tool_cmcompetency
 * @author    Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright 2019 Université de Montréal
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cmcompetency;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * External course module competency webservice API tests.
 *
 * @package   tool_cmcompetency
 * @author    Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright 2019 Université de Montréal
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\tool_cmcompetency\external::class)]
final class external_test extends \externallib_advanced_testcase {
    /** @var stdClass $creator User with enough permissions to create insystem context. */
    protected $creator = null;

    /** @var stdClass $user User with enough permissions to view insystem context */
    protected $user = null;

    /** @var int Creator role id */
    protected $creatorrole = null;

    /** @var int User role id */
    protected $userrole = null;

    /** @var stdClass $scale1 Scale */
    protected $scale1 = null;

    /** @var string scaleconfiguration */
    protected $scaleconfiguration1 = null;

    /**
     * Setup function.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        // Create some users.
        $creator = $this->getDataGenerator()->create_user();
        $user = $this->getDataGenerator()->create_user();

        $syscontext = \context_system::instance();

        // Fetching default authenticated user role.
        $userroles = get_archetype_roles('user');
        $this->assertCount(1, $userroles);
        $authrole = array_pop($userroles);

        // Reset all default authenticated users permissions.
        unassign_capability('moodle/competency:competencygrade', $authrole->id);
        unassign_capability('moodle/competency:competencymanage', $authrole->id);
        unassign_capability('moodle/competency:competencyview', $authrole->id);
        unassign_capability('moodle/competency:coursecompetencyconfigure', $authrole->id);

        // Creating specific roles.
        $this->creatorrole = create_role('Creator role', 'creatorrole', 'learning plan creator role description');
        $this->userrole = create_role('User role', 'userrole', 'learning plan user role description');

        assign_capability('moodle/competency:competencymanage', CAP_ALLOW, $this->creatorrole, $syscontext->id);
        assign_capability('moodle/competency:coursecompetencyconfigure', CAP_ALLOW, $this->creatorrole, $syscontext->id);
        assign_capability('moodle/competency:competencyview', CAP_ALLOW, $this->userrole, $syscontext->id);
        assign_capability('moodle/competency:competencygrade', CAP_ALLOW, $this->creatorrole, $syscontext->id);

        role_assign($this->creatorrole, $creator->id, $syscontext->id);
        role_assign($this->userrole, $user->id, $syscontext->id);

        $this->creator = $creator;
        $this->user = $user;

        $this->scale1 = $this->getDataGenerator()->create_scale(["scale" => "value1, value2"]);

        $this->scaleconfiguration1 = '[{"scaleid":"' . $this->scale1->id . '"},' .
            '{"name":"value1","id":1,"scaledefault":1,"proficient":0},' .
            accesslib_clear_all_caches_for_unit_testing();
    }

    public function test_grade_competency_in_coursemodule(): void {
        $this->setUser($this->creator);
        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('core_competency');

        $course = $dg->create_course(['fullname' => 'Evil course']);

        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $page = $pagegenerator->create_instance(['course' => $course->id]);
        $cm = get_coursemodule_from_instance('page', $page->id);

        $dg->enrol_user($this->creator->id, $course->id, 'editingteacher');
        $dg->enrol_user($this->user->id, $course->id, 'student');

        $f1 = $lpg->create_framework();
        $c1 = $lpg->create_competency(['competencyframeworkid' => $f1->get('id')]);
        $lpg->create_course_competency(['courseid' => $course->id, 'competencyid' => $c1->get('id')]);
        // Link competency to course module.
        $lpg->create_course_module_competency(['competencyid' => $c1->get('id'), 'cmid' => $cm->id]);

        $evidence = external::grade_competency_in_coursemodule($cm->id, $this->user->id, $c1->get('id'), 1, 'Evil note', false);

        $this->assertEquals(
            get_string(
                'evidence_manualoverrideincoursemodule',
                'tool_cmcompetency',
                'Page: Page 1'
            ),
            $evidence->description
        );
        $this->assertEquals('A', $evidence->gradename);
        $this->assertEquals('Evil note', $evidence->note);

        $this->setUser($this->user);

        $this->expectException('required_capability_exception');
        external::grade_competency_in_coursemodule($cm->id, $this->user->id, $c1->get('id'), 1);
    }

    public function test_data_for_user_competency_summary_in_coursemodule(): void {
        $this->setUser($this->creator);
        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('core_competency');

        $course = $dg->create_course(['fullname' => 'Evil course']);

        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $page = $pagegenerator->create_instance(['course' => $course->id, 'name' => 'Page 1']);
        $cm = get_coursemodule_from_instance('page', $page->id);

        $dg->enrol_user($this->creator->id, $course->id, 'editingteacher');
        $dg->enrol_user($this->user->id, $course->id, 'student');

        $f1 = $lpg->create_framework();
        $c1 = $lpg->create_competency(['competencyframeworkid' => $f1->get('id')]);
        $lpg->create_course_competency(['courseid' => $course->id, 'competencyid' => $c1->get('id')]);
        // Link competency to course module.
        $lpg->create_course_module_competency(['competencyid' => $c1->get('id'), 'cmid' => $cm->id]);

        external::grade_competency_in_coursemodule($cm->id, $this->user->id, $c1->get('id'), 1, 'Evil note', false);

        $summary = external::data_for_user_competency_summary_in_coursemodule($this->user->id, $c1->get('id'), $cm->id);
        $this->assertTrue($summary->usercompetencysummary->cangrade);
        $this->assertEquals('A', $summary->usercompetencysummary->usercompetencycm->gradename);
        $this->assertEquals('No', $summary->usercompetencysummary->usercompetencycm->proficiencyname);
        $this->assertEquals('A', $summary->usercompetencysummary->evidence[0]->gradename);

        $this->assertEquals($cm->id, $summary->coursemodule->id);
        $this->assertEquals('Page 1', $summary->coursemodule->name);
    }
}
