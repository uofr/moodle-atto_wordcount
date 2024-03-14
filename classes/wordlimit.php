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
    protected static function get_wordlimits_for_essay_in_quiz($quizid, $page, $attemptid) {
        global $DB, $USER;

        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid, 'userid' => $USER->id]);
        if (empty($attempt) || !($attempt instanceof \stdClass)) {
            return [];
        }

        $slots = self::get_slots_from_attempt($attempt);
        if (empty($slots) || !array_key_exists($page, $slots)) {
            return [];
        }

        $pageslots = $slots[$page];
        if (empty($pageslots) || !is_array($pageslots)) {
            return [];
        }

        $qattempts = self::get_question_attempts_by_slot($attempt, $pageslots);

        $wordlimits = [];
        foreach ($pageslots as $slot) {
            if (array_key_exists($slot, $qattempts)) {
                $qattempt = $qattempts[$slot];
                if (!empty($qattempt) && ($qattempt instanceof \stdClass) && property_exists($qattempt, 'questionid')) {
                    $essayquestion = $DB->get_record('qtype_essay_options', ['questionid' => $qattempt->questionid]);
                    if (!empty($essayquestion) && ($essayquestion instanceof \stdClass) && property_exists($essayquestion, 'maxwordlimit')) {
                        $wordlimits[] = $essayquestion->maxwordlimit;
                    }
                }
            }
        }

        return $wordlimits;
    }


    /**
     * Get the wordlimit depending on the type of page which is beein edited.
     *
     * @return  array|int $wordlimits
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

        if (strpos($PAGE->url->get_path(), '/mod/quiz/attempt.php') !== false && "mod-quiz-attempt" === $PAGE->pagetype) {
            // The quiz-id is the current course-module id.
            $quizid = intval($PAGE->cm->instance);
            // See on which page of the quiz we are.
            $page = $PAGE->url->get_param('page');
            $page = empty($page) ? 0 : intval($page);
            $attemptid = $PAGE->url->get_param('attempt');
            $wordlimits = self::get_wordlimits_for_essay_in_quiz($quizid, $page, $attemptid);
            return $wordlimits;
        }

        return 0;
    }

    /**
     * Returns slots associated with a given quiz attempt.
     * Slots can be used to find questions (and therefore, the word limit!).
     * 
     * @param \stdClass $attempt - Row from quiz_attempts table.
     * @return array[] - Array of slots indexed by page number. In the order they appear to the user.
     */
    private static function get_slots_from_attempt(\stdClass $attempt) {
        if (!property_exists($attempt, 'layout')) {
            return [];
        }
        else if (empty($attempt->layout) || !is_string($attempt->layout)) {
            return [];
        }
        else if (strpos($attempt->layout, ',') === false) {
            return [];
        }

        $layout = explode(',', $attempt->layout);
        $slots = [];
        $page = 0;

        foreach ($layout as $slotnumber) {
            if ($slotnumber == 0) { // A page break is indicated by a slot number of 0.
                $page = $page + 1;
            }
            else { // Otherwise, append $slotnumber to $slots[$page]
                $slots[$page][] = $slotnumber;
            }
        }

        return $slots;
    }

    private static function get_question_attempts_by_slot(\stdClass $attempt, array $slots) {
        global $DB;

        if (!property_exists($attempt, 'uniqueid') || empty($attempt->uniqueid)) {
            return [];
        }

        list($insql, $inparams) = $DB->get_in_or_equal($slots);
        $sqlparams = array_merge($inparams, [$attempt->uniqueid]);
        $sqlquery = "SELECT * FROM {question_attempts} qa
                        WHERE qa.slot $insql
                        AND qa.questionusageid = ?";

        $questionattempts = $DB->get_records_sql($sqlquery, $sqlparams);
        if (empty($questionattempts)) {
            return [];
        }

        $questionattemptsbyslot = [];
        foreach ($questionattempts as $qattempt) {
            if (($qattempt instanceof \stdClass) && property_exists($qattempt, 'slot') && !empty($qattempt->slot)) {
                $questionattemptsbyslot[$qattempt->slot] = $qattempt;
            }
        }

        return $questionattemptsbyslot;
    }

}
