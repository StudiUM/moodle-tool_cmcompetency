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
 * Module to navigate between course modules.
 *
 * @package    tool_cmcompetency
 * @copyright  2019 Université de Montréal
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    /**
     * CourseModuleNavigation
     *
     * @param {String} cmSelector The selector of the course module element.
     * @param {String} baseUrl The base url for the page (no params).
     * @param {Number} cmId The course module id
     * @param {Number} courseId The course id
     */
    var CourseModuleNavigation = function(cmSelector, baseUrl, cmId, courseId) {
        this._baseUrl = baseUrl;
        this._cmid    = cmId;
        this._courseId = courseId;

        $(cmSelector).on('change', this._cmChanged.bind(this));
    };

    /**
     * The course module was changed in the select list.
     *
     * @method _cmChanged
     * @param {Event} e the event
     */
    CourseModuleNavigation.prototype._cmChanged = function(e) {
        var newCmId = $(e.target).val();
        var queryStr = '?id=' + newCmId + '&courseid=' + this._courseId;
        document.location = this._baseUrl + queryStr;
    };

    /** @type {Number} The id of the course module. */
    CourseModuleNavigation.prototype._cmid = null;
    /** @type {Number} The id of the course. */
    CourseModuleNavigation.prototype._courseId = null;
    /** @type {String} Plugin base url. */
    CourseModuleNavigation.prototype._baseUrl = null;

    return CourseModuleNavigation;

});
