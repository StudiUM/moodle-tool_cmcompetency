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
 * Module to collapse/expand competency detail.
 *
 * @module     tool_cmcompetency/report_cmcompetency_display
 * @copyright  2019 Université de Montréal
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/notification', 'core/str'], function($, notification, str) {

    /**
     * ReportCmCompetencyDisplay
     *
     * @param {String} colExpAllRegionSelector The selector of region containing collapse/expand all link.
     * @param {String} listRegionSelector The selector of region containing list of collapse/expand links.
     */
    var ReportCmCompetencyDisplay = function(colExpAllRegionSelector, listRegionSelector) {
        // Collapse block panels.
        $(listRegionSelector).on('click', '.collapse-link', function(event) {
            event.preventDefault();
            var e = $(this).closest(".x_panel"),
            t = $(this).find("i"),
            n = e.find(".x_content");
            t.toggleClass("fa-chevron-right fa-chevron-down");
            n.slideToggle();
            e.toggleClass("panel-collapsed");
        });
        // Collapse/Expand all.
        str.get_strings([
            {key: 'collapseall'},
            {key: 'expandall'}
        ]).done(
            function(strings) {
                var collapseall = strings[0];
                var expandall = strings[1];
                $(colExpAllRegionSelector).on('click', '.collapsible-actions a', function(event) {
                    event.preventDefault();
                    if ($(this).hasClass('collapse-all')) {
                        $(this).text(expandall);
                        $(listRegionSelector + ' div.x_panel:not(.panel-collapsed) a.collapse-link').trigger('click');
                    } else {
                        $(this).text(collapseall);
                        $(listRegionSelector + ' div.panel-collapsed a.collapse-link').trigger('click');
                    }
                    $(this).toggleClass("collapse-all expand-all");
                });
            }
        ).fail(notification.exception);
    };

    return ReportCmCompetencyDisplay;

});
