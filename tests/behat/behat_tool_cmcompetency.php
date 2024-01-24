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
 * Step definition for tool cmcompetency
 *
 * @package    tool_cmcompetency
 * @category   test
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * Step definition for tool cmcompetency.
 *
 * @package    tool_cmcompetency
 * @category   test
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2019 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_cmcompetency extends behat_base {
    /**
     * Should see text in specific element of competency detail.
     *
     * @Then I should see :arg1 in the :arg2 of the competency :arg3
     * @param string $arg1
     * @param string $arg2
     * @param string $arg3
     */
    public function i_should_see_in_the_of_the_competency($arg1, $arg2, $arg3) {
        $xpathtarget = "//dl[ancestor-or-self::div/div/h4/a[contains(., '$arg3')]]/dt[text()='$arg2']/following-sibling::dd[1]";

        $this->execute("behat_general::assert_element_contains_text",
            [$arg1, $xpathtarget, "xpath_element"]
        );
    }

    /**
     * Should not see text in specific element of competency detail.
     *
     * @Then I should not see :arg1 in the :arg2 of the competency :arg3
     * @param string $arg1
     * @param string $arg2
     * @param string $arg3
     */
    public function i_should_not_see_in_the_of_the_competency($arg1, $arg2, $arg3) {
        $xpathtarget = "//dl[ancestor-or-self::div/div/h4/a[contains(., '$arg3')]]/dt[text()='$arg2']/following-sibling::dd[1]";

        $this->execute("behat_general::assert_element_not_contains_text",
            [$arg1, $xpathtarget, "xpath_element"]
        );
    }
}
