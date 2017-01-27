<?php

/**
 * *************************************************************************
 * *                  OOHOO Calendar Course format                        **
 * *************************************************************************
 * @package     format                                                    **
 * @subpackage  calendar                                                  **
 * @name        calendar                                                  **
 * @copyright   oohoo.biz                                                 **
 * @link        http://oohoo.biz                                          **
 * @author      Nicolas Bretin     Changes by Silvia Suria Torres         **
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later  **
 * *************************************************************************
 * ************************************************************************ */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/completionlib.php');

$PAGE->requires->js('/course/format/calendar/format_calendar.js');

//NUMBER OF DAYS BY ROW
$nbDaysByRow = 7;

//By Default hide the weekend days
$hideWeekend = ' style="display:none;" ';

$day = optional_param('day', -1, PARAM_INT);

$renderer = $PAGE->get_renderer('format_calendar');
$corerenderer = $PAGE->get_renderer('core', 'course');

$streditsummary = get_string('editsummary');
$stradd = get_string('add');
$stractivities = get_string('activities');
$strshowalldays = get_string('showalldays', 'format_calendar');
$strday = get_string('day', 'format_calendar');
$strgroups = get_string('groups');
$strgroupmy = get_string('groupmy');
$editing = $PAGE->user_is_editing();

if ($editing)
{
    $strdayhide = get_string('hidedayfromothers', 'format_calendar');
    $strdayshow = get_string('showdayfromothers', 'format_calendar');
    $strmoveup = get_string('moveup');
    $strmovedown = get_string('movedown');
}

// make sure all sections are created
$course = course_get_format($course)->get_course();
course_create_sections_if_missing($course, range(0, $course->numsections));

//Replace get_context_instance by the class for moodle 2.6+
if(class_exists('context_module'))
{
    $context = context_course::instance($course->id);
}
else
{
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
}

//Print the Your progress icon if the track completion is enabled
$completioninfo = new completion_info($course);
echo $completioninfo->display_help_icon();

echo $OUTPUT->heading(get_string('dailyoutline', 'format_calendar'), 2, 'headingblock header outline');


echo '<button name="toggleWeekends" onclick="toggleWeekends()";/>' . get_string('toggleWeekends', 'format_calendar') . '</button>&nbsp;&nbsp;&nbsp;';
echo '<button name="displayCurrentWeek" onclick="displayCurrentWeek()";/>' . get_string('displayCurrentWeek', 'format_calendar') . '</button>';
echo '<button name="displayCurrentMonth" onclick="displayCurrentMonth()";/>' . get_string('displayCurrentMonth', 'format_calendar') . '</button>';
echo '<button name="displayAllMonths" onclick="displayAllMonths()";/>' . get_string('displayAllMonths', 'format_calendar') . '</button>';


// Note, an ordered list would confuse - "1" could be the clipboard or summary.
echo "<table class='days'>\n";

/// If currently moving a file then show the current clipboard
if (ismoving($course->id))
{
    $stractivityclipboard = strip_tags(get_string('activityclipboard', '', $USER->activitycopyname));
    $strcancel = get_string('cancel');
    echo '<li class="clipboard">';
    echo $stractivityclipboard . '&nbsp;&nbsp;(<a href="mod.php?cancelcopy=true&amp;sesskey=' . sesskey() . '">' . $strcancel . '</a>)';
    echo "</li>\n";
}

/// Print Section 0 with general activities

$section = 0;
$thissection = $sections[$section];
unset($sections[0]);

if ($thissection->summary or $thissection->sequence or $PAGE->user_is_editing())
{

    // Note, 'right side' is BEFORE content.
    echo '<thead><tr><td colspan="' . $nbDaysByRow . '" id="sectiontd-0" class="sectiontd main clearfix" ><ul class="sectionul">';
    echo '<li id="sectiontd-0" class="section main yui3-dd-drop" aria-label="' . $thissection->name . '" role="region">';
    echo '<div class="right side" >&nbsp;</div>';
    echo '<div class="content">';

    if (!empty($thissection->name))
    {
        echo $OUTPUT->heading(format_string($thissection->name, true, array('context' => $context)), 3, 'sectionname');
    }

    echo '<div class="summary">';

    //Replace get_context_instance by the class for moodle 2.6+
    if(class_exists('context_module'))
    {
        $coursecontext = context_course::instance($course->id);
    }
    else
    {
        $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
    }
    $summarytext = file_rewrite_pluginfile_urls($thissection->summary, 'pluginfile.php', $coursecontext->id, 'course', 'section', $thissection->id);
    $summaryformatoptions = new stdClass;
    $summaryformatoptions->noclean = true;
    $summaryformatoptions->overflowdiv = true;
    echo format_text($summarytext, $thissection->summaryformat, $summaryformatoptions);

    if ($PAGE->user_is_editing() && has_capability('moodle/course:update', $coursecontext))
    {
        echo '<p><a title="' . $streditsummary . '" ' .
        ' href="editsection.php?id=' . $thissection->id . '"><img src="' . $OUTPUT->pix_url('t/edit') . '" ' .
        ' class="iconsmall edit" alt="' . $streditsummary . '" /></a></p>';
    }
    echo '</div>';

    echo $corerenderer->course_section_cm_list($course, $thissection);

    if ($PAGE->user_is_editing())
    {
        echo $corerenderer->course_section_add_cm_control($course, $section);
    }

    echo '</div>';
    echo "</li></ul></td></tr>";
    echo '<tr><td class="calendar_btns_left">';
    echo '<button style="display: none" name="displayPreviousWeek" id="displayPreviousWeek" onclick="displayPreviousWeek()";/>&lt;&lt;</button>';
    echo '<button style="display: none" name="displayPreviousMonth" id="displayPreviousMonth" onclick="displayPreviousMonth()";/>&lt;&lt;</button>';
    echo '<td colspan="' . ($nbDaysByRow - 1) . '" class="calendar_btns_right">';
    echo '<button style="display: none" name="displayNextWeek" id="displayNextWeek" onclick="displayNextWeek()";/>&gt;&gt;</button>';
    echo '<button style="display: none" name="displayNextMonth" id="displayNextMonth" onclick="displayNextMonth()";/>&gt;&gt;</button>';
    echo "<td></tr>";
    echo "</thead>\n";
}


/// Now all the normal modules by week
/// Everything below uses "section" terminology - each "section" is a day.

$timenow = time();
$daydate = $course->startdate;    // this should be 0:00 Monday of that day
$daydate += 7200;                 // Add two hours to avoid possible DST problems
$section = 1;
$sectionmenu = array();
$dayofseconds = 86400; //NB of seconds in a day
$course->enddate = $course->startdate + ($dayofseconds * $course->numsections);

$strftimedateshort = ' ' . get_string('strftimedateshort');

//Create a new row
$bNewRow = true;
$nbElemsOnRow = 0;
$bFirstRowMonth = true;

echo '<tbody>';
//Print the days
echo '<tr class="headDays">';
echo '  <th ' . $hideWeekend . ' class="sunday weekday-0">' . get_string('daysunday', 'format_calendar') . '</th>';
echo '  <th class="monday weekday-1">' . get_string('daymonday', 'format_calendar') . '</th>';
echo '  <th class="tuesday weekday-2">' . get_string('daytuesday', 'format_calendar') . '</th>';
echo '  <th class="wednesday weekday-3">' . get_string('daywednesday', 'format_calendar') . '</th>';
echo '  <th class="thursday weekday-4">' . get_string('daythursday', 'format_calendar') . '</th>';
echo '  <th class="friday weekday-5">' . get_string('dayfriday', 'format_calendar') . '</th>';
echo '  <th ' . $hideWeekend . ' class="saturday weekday-6">' . get_string('daysaturday', 'format_calendar') . '</th>';
echo '</tr>';

while ($daydate < $course->enddate)
{
    $nextdaydate = $daydate + ($dayofseconds);
    $dayday = userdate($daydate, $strftimedateshort);

    if (!empty($sections[$section]))
    {
        $thissection = $sections[$section];
    }
    else
    {
        $thissection = $DB->get_record('course_sections', array('course'=>$course->id, 'section' => $section));
        $sections[$section] = $thissection;
    }

    $showsection = (has_capability('moodle/course:viewhiddensections', $context) or $thissection->visible or !$course->hiddensections);

    if ($showsection)
    {

        if ($bNewRow)
        {
            echo '<tr class="week week-' . (int) date('W', $nextdaydate) . ' month-' . date('n', $daydate) . ' ' . ($bFirstRowMonth ? 'firstweek' : '') . '">';
            $bFirstRowMonth = false;
            $bNewRow = false;
            if (date('w', $daydate) != 0)
            {
                //Complete the row before the right day
                for ($i = 0; $i < date('w', $daydate); $i++)
                {
                    if ($i == 0)
                    {
                        echo '<td ' . $hideWeekend . ' class="weekday-' . $i . '"></td>';
                    }
                    else
                    {
                        echo '<td class="weekday-' . $i . '"></td>';
                    }

                    $nbElemsOnRow++;
                }
            }
        }
        $nbElemsOnRow++;

        $currentday = (($daydate <= $timenow) && ($timenow < $nextdaydate));

        $currenttext = '';
        if (!$thissection->visible)
        {
            $sectionstyle = ' hidden';
        }
        else if ($currentday)
        {
            $sectionstyle = ' current';
            $currenttext = get_accesshide(get_string('currentday', 'format_calendar'));
        }
        else
        {
            $sectionstyle = '';
        }
        
        $dayperiod = $dayday;
        
        if (!isset($thissection->name) || ($thissection->name === NULL))
        {
            $thissection->name = $currenttext . $dayperiod ;
        }
        
        if (($nbElemsOnRow - 1) == 0 || $nbElemsOnRow == $nbDaysByRow)
        {
            echo '<td ' . $hideWeekend . ' id="sectiontd-' . $section . '" class="sectiontd main ' . $sectionstyle . ' weekday-' . ($nbElemsOnRow - 1) . '" ><ul class="sectionul">';
            echo '<li id="section-' . $section . '" class="section main yui3-dd-drop" aria-label="' . $thissection->name . '" role="region">';
        }
        else
        {
            echo '<td id="sectiontd-' . $section . '" class="sectiontd main ' . $sectionstyle . ' weekday-' . ($nbElemsOnRow - 1) . '" ><ul class="sectionul">';
          //  echo '<li id="section-' . $section . '" class="section main yui3-dd-drop" aria-label="' . $thissection->name . '" role="region">';
          //echo $thissection->name;
        }

        
        echo '<div class="content">';
        if (!has_capability('moodle/course:viewhiddensections', $context) and !$thissection->visible)
        {   // Hidden for students
            echo $OUTPUT->heading($currenttext . $dayperiod . ' (' . get_string('notavailable') . ')', 3, 'daydates');
        }
        else
        {
            if (isset($thissection->name) && ($thissection->name !== NULL))
            {  // empty string is ok
                echo $OUTPUT->heading(format_string($thissection->name, true, array('context' => $context)), 3, 'daydates');
            }
            else
            {
                echo $OUTPUT->heading($currenttext . $dayperiod, 3, 'daydates');
            }
            echo '<div class="summary">';
            //Replace get_context_instance by the class for moodle 2.6+
            if(class_exists('context_module'))
            {
                $coursecontext = context_course::instance($course->id);
            }
            else
            {
                $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
            }
            $summarytext = file_rewrite_pluginfile_urls($thissection->summary, 'pluginfile.php', $coursecontext->id, 'course', 'section', $thissection->id);
            $summaryformatoptions = new stdClass;
            $summaryformatoptions->noclean = true;
            $summaryformatoptions->overflowdiv = true;
            echo format_text($summarytext, $thissection->summaryformat, $summaryformatoptions);

            if ($PAGE->user_is_editing() && has_capability('moodle/course:update', $coursecontext))
            {
                echo ' <a title="' . $streditsummary . '" href="editsection.php?id=' . $thissection->id . '">' .
                '<img src="' . $OUTPUT->pix_url('t/edit') . '" class="iconsmall edit" alt="' . $streditsummary . '" /></a><br /><br />';
            }
            echo '</div>';

            echo $corerenderer->course_section_cm_list($course, $thissection);

            if ($PAGE->user_is_editing())
            {
                echo $corerenderer->course_section_add_cm_control($course, $section);
            }
        }

        echo '</div>';
        echo "</li></ul></td>\n";

        if ($nbElemsOnRow >= $nbDaysByRow || date('n', $daydate + ($dayofseconds)) != date('n', $daydate))
        {
            //Conplete the row
            for ($nbElemsOnRow; $nbElemsOnRow < $nbDaysByRow; $nbElemsOnRow++)
            {
                if ($nbElemsOnRow == $nbDaysByRow - 1)
                {
                    echo '<td ' . $hideWeekend . ' class="weekday-' . $nbElemsOnRow . '"></td>';
                }
                else
                {
                    echo '<td class="weekday-' . $nbElemsOnRow . '"></td>';
                }
            }

            if (date('n', $daydate + ($dayofseconds)) != date('n', $daydate))
            {
                $bFirstRowMonth = true;
            }

            echo '</tr>';
            $nbElemsOnRow = 0;
            $bNewRow = true;
        }
    }

    unset($sections[$section]);
    $section++;
    $daydate = $nextdaydate;
}

//Replace get_context_instance by the class for moodle 2.6+
if(class_exists('context_module'))
{
    $context_check = context_course::instance($course->id);
}
else
{
    $context_check = get_context_instance(CONTEXT_COURSE, $course->id);
}

if ($PAGE->user_is_editing() && has_capability('moodle/course:update', $context_check))
{
    // print stealth sections if present
    $modinfo = get_fast_modinfo($course);
    foreach ($sections as $section => $thissection)
    {
        if (empty($modinfo->sections[$section]))
        {
            continue;
        }

        echo '<tr>';
        echo '<td id="section-' . $section . '" class="section main clearfix stealth hidden">'; 

        echo '<div class="left side">';
        echo '</div>';
        // Note, 'right side' is BEFORE content.
        echo '<div class="right side">';
        echo '</div>';
        echo '<div class="content">';
        echo $OUTPUT->heading(get_string('orphanedactivities'), 3, 'sectionname');
        echo $corerenderer->course_section_cm_list($course, $thissection);
        echo '</div>';
        echo "</td>\n";
        echo "</tr>\n";
    }
}

echo '</tbody>';
echo "</table>\n";

if (!empty($sectionmenu))
{
    $select = new single_select(new moodle_url('/course/view.php', array('id' => $course->id)), 'day', $sectionmenu);
    $select->label = get_string('jumpto');
    $select->class = 'jumpmenu';
    $select->formid = 'sectionmenu';
    echo $OUTPUT->render($select);
}
