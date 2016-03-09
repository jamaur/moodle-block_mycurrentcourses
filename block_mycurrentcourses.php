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
 * My Current Courses block
 *
 * This block lists users' "current" courses and shows progress.
 * As such, not all courses will be included in this block. Here are
 * the rules:
 *
 *   - The user must be enrolled in the course.
 *   - The course must have completion enabled.
 *   - If the user has completed the course, it is not shown.
 *
 * This block is heavily based off Moodle's self completion block.
 * See the self completion block for copyright info.
 *
 * @package   block_mycurrentcourses
 * @copyright 2015 onwards Jason Maur
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/completionlib.php');

/**
 * Self course completion marking
 * Let's a user manually complete a course
 *
 * Will only display if the course has completion enabled,
 * the user is enrolled, and the user is yet to complete the course.
 */
class block_mycurrentcourses extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_mycurrentcourses');
    }

    function applicable_formats() {
        return array('all' => true, 'mod' => false, 'tag' => false, 'my' => false);
    }
    
    public function get_content() {
        global $CFG, $USER;


        ob_start();

        $user = $USER;
        $userid = $USER->id;

        // Print header.
        $page = get_string('completionprogressdetails', 'block_completionstatus');
        $title = $page;

        // Display completion status.
        echo html_writer::start_tag('table', array('class' => 'mycurrentcourses'));

        echo html_writer::start_tag('thead');
        echo html_writer::start_tag('tr');

        echo html_writer::tag('th', get_string('course'));
        echo html_writer::tag('th', get_string('status'));

        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('thead');

        echo html_writer::start_tag('tbody');


        if($courses = enrol_get_my_courses(NULL , 'fullname ASC')) {

            $outputflag = false;

            $count = 0;

            foreach ($courses as $course) {
                $id = $course->id;

                if (!completion_can_view_data($user->id, $course)) {
                    continue;
                }

                // Load completion data.
                $info = new completion_info($course);

                // Don't display if completion isn't enabled.
                if (!$info->is_enabled()) {
                    continue;
                }

                // Check this user is enroled.
                if (!$info->is_tracked_user($user->id)) {
                    continue;
                }
                
                // Is course complete?
                $coursecomplete = $info->is_course_complete($user->id);
                if ($coursecomplete) {
                    continue;
                }

                // Has this user completed any criteria?
                $criteriacomplete = $info->count_course_user_data($user->id);

                // Load course completion.
                $params = array(
                    'userid' => $user->id,
                    'course' => $course->id,
                );
                $ccompletion = new completion_completion($params);

                if ($count % 2 == 1) {
                    echo html_writer::start_tag('tr', array('class' => 'even'));
                }
                else {
                    echo html_writer::start_tag('tr', array('class' => 'odd'));
                }
                $count++;
                $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
                echo html_writer::start_tag('td');
                echo html_writer::link($courseurl, format_string($course->fullname));
                echo html_writer::end_tag('td');
                echo html_writer::start_tag('td');

                echo html_writer::tag('i', " " . get_string('inprogress', 'completion'));
                echo html_writer::end_tag('td');
                echo html_writer::end_tag('tr');
                $outputflag = true;
            }
        }

        if (!$outputflag) {
            echo html_writer::start_tag('tr');
            echo html_writer::start_tag('td', array('colspan' => 2));
            echo html_writer::tag('em', get_string('noprogressdata', 'local_transcript'));
            echo html_writer::end_tag('td');
            echo html_writer::end_tag('tr');
        }

        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');

        $this->content = new stdClass;
        $this->content->text = ob_get_clean();

        $progressurl = new moodle_url('/local/transcript/completion.php');

        $this->content->footer = html_writer::start_tag('p', array('class' => 'fulllink'));
        $this->content->footer .= html_writer::link($progressurl, get_string('fulllist', 'block_mycurrentcourses'));
        $this->content->footer .= html_writer::end_tag('p');

        return $this->content;
    }
}
