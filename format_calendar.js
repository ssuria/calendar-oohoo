/**
 * *************************************************************************
 * *                  OOHOO Calendar Course format                        **
 * *************************************************************************
 * @package     format                                                    **
 * @subpackage  calendar                                                  **
 * @name        calendar                                                  **
 * @copyright   oohoo.biz                                                 **
 * @link        http://oohoo.biz                                          **
 * @author      Nicolas Bretin                                            **
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later  **
 * *************************************************************************
 * ************************************************************************ */

YUI().use('node', function(Y)
{
    Y.on("domready", function()
    {
        Y.all('.iconlarge.activityicon').each(function(node)
        {
            node.removeClass('iconlarge').addClass('icon');
        });
    }); 
});


//Date extention to get the week of the year
Date.prototype.getWeek = function()
{
    var onejan = new Date(this.getFullYear(),0,1);
    return Math.ceil((((this - onejan) / 86400000) + onejan.getDay()+1)/7);
};


//Hide or show the weekends
function toggleWeekends()
{
    var displayed = 'none';
    YUI().use('node', function(Y)
    {
        if (Y.one('.weekday-0').getStyle('display') == 'none')
        {
            displayed = 'table-cell';
        }
    
        Y.all('.weekday-0').each(function(node)
        {
            node.setStyle('display', displayed);
        });
    
        Y.all('.weekday-6').each(function(node)
        {
            node.setStyle('display', displayed);
        });
    });
}


var activeMonth;
//Display all data of the course
function displayAllMonths()
{
    YUI().use('node', function(Y)
    {
        activeMonth = '';
        Y.all('.week').each(function(node)
        {
            node.setStyle('display', 'table-row');
        });
        displayPreviousNextMonthButton(false);
        displayPreviousNextWeekButton(false);
    });
}

//Display the selected month
function displayMonth(month)
{
    YUI().use('node', function(Y)
    {
        if(Y.all('.month-'+month).size() > 0)
        {
            Y.all('.week').each(function(node)
            {
                activeMonth = month;
                node.setStyle('display', 'none');
            });
            Y.all('.month-'+month).each(function(node)
            {
                node.setStyle('display', 'table-row');
            });
            displayPreviousNextMonthButton(true);
            displayPreviousNextWeekButton(false);

            return true;
        }
        else
        {
            return false;
        }
    });
}

//Display the month of today
function displayCurrentMonth()
{
    var month = new Date();
    var count = 0;
    month = month.getMonth()+1;
    
    while(!displayMonth(month) && count <= 13)
    {
        month++;
        if (month >= 12)
        {
            month = 1;
        }
        count++;
    }
}

//Display the previous month  function of the activeMonth set
function displayPreviousMonth()
{
    if (activeMonth <= 1)
    {
        displayMonth(12);
    }
    else
    {
        displayMonth(activeMonth-1);
    }
}

//Display the next month  function of the activeMonth set
function displayNextMonth()
{
    if (activeMonth >= 12)
    {
        displayMonth(1);
    }
    else
    {
        displayMonth(activeMonth+1);
    }
}

//Display the action buttons for the next previous month
function displayPreviousNextMonthButton(display)
{
    YUI().use('node', function(Y)
    {
        var attr = '';
        if(display)
        {
            attr = 'inline';
        }
        else
        {
            attr = 'none';
        }
        Y.all('#displayPreviousMonth').setStyle('display', attr);
        Y.all('#displayNextMonth').setStyle('display', attr);
    });
}


var activeWeek;
//Display the selected week
function displayWeek(week)
{
    YUI().use('node', function(Y)
    {
        if(Y.all('.week-'+week).size() > 0)
        {
            Y.all('.week').each(function(node)
            {
                activeWeek = week;
                node.setStyle('display', 'none');
            });
            Y.all('.week-'+week).each(function(node)
            {
                node.setStyle('display', 'table-row');
            });
            displayPreviousNextWeekButton(true);
            displayPreviousNextMonthButton(false);

            return true;
        }
        else
        {
            return false;
        }
    });
}

//Display the week of today
function displayCurrentWeek()
{
    var week = new Date();
    var count = 0;
    week = week.getWeek();
    
    while(!displayWeek(week) && count <= 53)
    {
        week++;
        if (week >= 52)
        {
            week = 1;
        }
        count++;
    }
}

//Display the previous week function of the activeWeek set
function displayPreviousWeek()
{
    if (activeWeek <= 1)
    {
        displayWeek(52);
    }
    else
    {
        displayWeek(activeWeek-1);
    }
}

//Display the next Week  function of the activeWeek set
function displayNextWeek()
{
    if (activeWeek >= 52)
    {
        displayWeek(1);
    }
    else
    {
        displayWeek(activeWeek+1);
    }
}

//Display the action buttons for the next previous week
function displayPreviousNextWeekButton(display)
{
    YUI().use('node', function(Y)
    {
        var attr = '';
        if(display)
        {
            attr = 'inline';
        }
        else
        {
            attr = 'none';
        }
        Y.all('#displayPreviousWeek').setStyle('display', attr);
        Y.all('#displayNextWeek').setStyle('display', attr);
    });
}


// Javascript functions for Days course format

M.course = M.course || {};

M.course.format = M.course.format || {};

/**
 * Get sections config for this format
 *
 * The section structure is:
 * <table class="days">
 *  <td class="section">...</td>
 *  <td class="section">...</td>
 *   ...
 * </table>
 *
 * @return {object} section list configuration
 */
M.course.format.get_config = function() {
    return {
        container_node : 'table',
        container_class : 'days',
        section_node : 'li',
        section_class : 'section'
    };
}

/**
 * Swap section
 *
 * @param {YUI} Y YUI3 instance
 * @param {string} node1 node to swap to
 * @param {string} node2 node to swap with
 * @return {NodeList} section list
 */
M.course.format.swap_sections = function(Y, node1, node2) {
    var CSS = {
        COURSECONTENT : 'course-content',
        SECTIONADDMENUS : 'section_add_menus'
    };

    var sectionlist = Y.Node.all('.'+CSS.COURSECONTENT+' '+M.course.format.get_section_selector(Y));
    // Swap menus
    sectionlist.item(node1).one('.'+CSS.SECTIONADDMENUS).swap(sectionlist.item(node2).one('.'+CSS.SECTIONADDMENUS));
}

/**
 * Process sections after ajax response
 *
 * @param {YUI} Y YUI3 instance
 * @param {array} response ajax response
 * @param {string} sectionfrom first affected section
 * @param {string} sectionto last affected section
 * @return void
 */
M.course.format.process_sections = function(Y, sectionlist, response, sectionfrom, sectionto) {
    var CSS = {
        SECTIONNAME : 'sectionname'
    };

    if (response.action == 'move') {
        // update titles in all affected sections
        for (var i = sectionfrom; i <= sectionto; i++) {
            sectionlist.item(i).one('.'+CSS.SECTIONNAME).setContent(response.sectiontitles[i]);
        }
    }
}