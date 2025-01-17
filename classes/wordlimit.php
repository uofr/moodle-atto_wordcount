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
 * atto_wordcount extensions for fetching wordlimits.
 *
 * @package    atto_wordcount
 * @copyright  2022 André Menrath <andre.menrath@uni-graz.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace atto_wordcount;

/**
 * Collection of functions thich get the wordlimit for the text written in atto if it is set.
 *
 * @copyright  2022 André Menrath <andre.menrath@uni-graz.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wordlimit {

    /**
     * Get the wordlimit for an onlinesubmission in an essay.
     *
     * @param   string  $assignmentid the instance-id of the assignment
     * @return  string  $wordlimit
     */
    protected static function get_wordlimit_for_onlinesubmission($assignmentid) {
        // Get settings from onlinepage submission plugin: Check if the wordlimit is enabled.
        global $DB;
        $wordlimitenabled = $DB->get_record(
            'assign_plugin_config',
            array(
                'assignment' => $assignmentid,
                'name'       => 'wordlimitenabled'
            ),
            'value',
            MUST_EXIST,
        );
        // If the wordlimit is enabled get the word limit and pass it to the javascript module.
        if ( '1' === $wordlimitenabled->value ) {
            $wordlimit = $DB->get_record(
                'assign_plugin_config',
                array(
                    'assignment' => $assignmentid,
                    'name'       => 'wordlimit'
                ),
                'value',
                MUST_EXIST,
            );
            return $wordlimit->value;
        }
        return null;
    }


    /**
     * Get the wordlimits for an essay of a certain page inside a quiz.
     *
     * @param int     $quizid the instance-id of the quiz
     * @param string  $page the number of the page as in the database, offset +1 in the frontend.
     * @return array  $wordlimits
     */
    protected static function get_wordlimits_for_essay_in_quiz($quizid, $page) {
        global $DB;
        // Make a database query to see if a maxwordlimit is set on this question.
        // We need to also select slot, because the slot is unique here: there might be multiple
        // editors with the same wordlimit on the same page, and then only the first would be returned.
		
		// This needs to be FIXED. {quiz_slots}.questionid no longer exists in Moodle 4
		
        $sql = "SELECT slot, maxwordlimit
                FROM {qtype_essay_options}
                INNER JOIN {quiz_slots}
                ON {qtype_essay_options}.questionid = {quiz_slots}.questionid
                WHERE quizid = ? AND page = ?
                ORDER BY slot";
        $wordlimits = $DB->get_records_sql( $sql, [$quizid, $page] );
        $wordlimits = array_column( $wordlimits, 'maxwordlimit' );
        return $wordlimits;
    }


    /**
     * Get the wordlimit depending on the type of page which is beein edited.
     *
     * @return  array $wordlimits
     */
    public static function get_wordlimits() {

        global $PAGE;

        // Define the parameter array which is served to the javascript of the plugin.
        $wordlimits = array( null );

        // Check if we are on a page where the users submits/edits an onlinetext for an assignment.
        if ( strpos($PAGE->url->get_path(), '/mod/assign/view.php')!== false && 'editsubmission' === $PAGE->url->get_param('action') ) {
            $id = $PAGE->cm->instance;
            $wordlimit = self::get_wordlimit_for_onlinesubmission($id);
            // We have to pass wordlimits as an array.
            $wordlimits = array( 0 => $wordlimit);
            // We can return now and don't need to check for a quiz page.
            return $wordlimits;
        }
		// Quiz changed in moodle 4. This has to be re-engineered
		/*
        if (strpos($PAGE->url->get_path(), '/mod/quiz/attempt.php')!== false && "mod-quiz-attempt" === $PAGE->pagetype ) {
            // The quiz-id is the current course-module id.
            $quizid = intval( $PAGE->cm->instance );
            // See on which page of the quiz we are.
            $page = $PAGE->url->get_param( 'page' );
            // The page in the URL Params is starting with zero, in the database they start with 1. So there is an offset.
            ( null === $page ) ? $page = "1" : $page = intval( $page ) + 1;
            $wordlimits = self::get_wordlimits_for_essay_in_quiz( $quizid, $page );
            return $wordlimits;
        }
		*/
        return 0;
    }

}
