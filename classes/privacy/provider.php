<?php

namespace tool_cmcompetency\privacy;
defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;

class provider implements 
    \core_privacy\local\metadata\provider, 
    \core_privacy\local\metadata\null_provider {
    
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table('tool_cmcompetency_usercompcm', [
            'userid' => 'privacy:metadata:cmcompetency:userid',
            'proficiency' => 'privacy:metadata:cmcompetency:proficiency',
            'grade' => 'privacy:metadata:cmcompetency:grade',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
            'usermodified' => 'privacy:metadata:usermodified',
        ], 'privacy:metadata:tool_cmcompetency_usercompcm');

        return $collection;
    }

    /**
     * Get the language string identifier with the component's language
     * file to explain why this plugin stores no data.
     *
     * @return  string
     */
    public static function get_reason() : string {
        return 'privacy:metadata';
    }

}
