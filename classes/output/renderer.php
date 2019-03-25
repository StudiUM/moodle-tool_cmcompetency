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
 * Renderer class for tool_cmcompetency
 *
 * @package    tool_cmcompetency
 * @copyright  2019 Université de Montréal
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_cmcompetency\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use renderable;

/**
 * Renderer class for course module competency tool.
 *
 * @package    tool_cmcompetency
 * @copyright  2019 Université de Montréal
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Defer to template.
     *
     * @param coursemodule_navigation $nav
     * @return string html for the page
     */
    public function render_coursemodule_navigation(coursemodule_navigation $nav) {
        $data = $nav->export_for_template($this);
        return parent::render_from_template('tool_cmcompetency/coursemodule_navigation', $data);
    }

    /**
     * Defer to template.
     *
     * @param report $report
     * @return string html for the page
     */
    public function render_report(report $report) {
        $data = $report->export_for_template($this);
        return parent::render_from_template('tool_cmcompetency/report', $data);
    }
}
