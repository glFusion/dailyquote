<?php
/**
*   English language file for the DailyQuote plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.1.0
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/


$LANG_DQ= array(
'access_denied'     => 'Access Denied',
'access_denied_msg' => 'You do not have Access to this page. Your user name and IP have been recorded.',
'access_denied_msg1' => 'Only Authorized Users have Access to this Page. Your user name and IP have been recorded.',
'enabled'           => 'Enabled',
'absentfile'        => 'Error: You must specify a file to upload.',
'addformlink'       => 'Add a Quote',
'addquote'          => 'Add Your Quotation Here',
'source'         => 'Source',
'sourcedate'     => 'Source Date',
'addtitle'          => 'Title',
'admin_menu'        => 'Daily Quote',
'adminintro'        => 'From this page you may enable, disable, or configure various dailyquote settings.',
'admintitle'        => 'Quote of the Day Configuration',
'anonymous'         => 'Anonymous',
'ascending'         => 'ascending order',
'descending'        => 'descending order',
'batchadd'          => 'Quotations',
'batchaddlink'      => 'Batch Add Quotes',
'batchaddtitle'     => 'Add Your Quotations Here',
'batchcatinstr'     => 'You may specify a category if you wish it to be applied to all quotes in the batch. Otherwise there will be no category set. This may be altered later from the quote administration page.',
'batchsrcinstr'     => 'Similarily, you may specify a title, source, and a date for that source if you wish it to be applied to all quotes in the batch that have null title, source, and/or source date fields.',
'batchsubmit'       => 'Import Quotes',
'cat'               => 'Category',
'category_name'     => 'Category Name',
'categories'        => 'Categories',
'caterror'          => 'Error occurred while retrieving category list',
'cathead'           => 'The following quotes appear under the &quot;%s&quot; category:',
'catindexlink'      => 'Categories',
'catreadme'         => 'The default category will be &quot;Miscellaneous&quot; if no other category selection is made',
'choosecat'         => 'Choose one or more categories',
'date'              => 'Date',
'dateformat'        => '(Date Format YYYY-MM-DD)',
'disperror'         => 'Error occurred while retrieving quote list',
'editlink'          => 'Edit',
'egtitle'           => 'Example .txt File:',
'enableq'           => 'Enable?',
'glsearchlabel'     => 'Quotes Listing',
'indexintro'        => 'History is full of stories, rants, perspectives, truths, lies, facts, details, opinions, ordinances, etc. The stories told by the men and women who were there, as well as by those who were not, and their comments are items for display in the archives of humankind.',
'indexintro_contrib' => 'Visit the museum and <a href="%s">contribute</a>.',
'indexlink'         => 'Listings',
'indextitle'        => 'Quote of the Day',
'keyall'            => 'all of these words',
'keyany'            => 'any of these words',
'keyphrase'         => 'exact phrase',
'limit'             => 'Limit To',
'line0'             => 'Text file format: quote&lt;tab&gt;person quoted&lt;tab&gt;title&lt;tab&gt;source&lt;tab&gt;source date',
'line1'             => 'Notice line 1 contains values for source and source date. Lines 2 through 5 have only the tabs to indicate the fields. Where the field is left empty nothing will be displayed. This also applies to the title field.',
'line2'             => 'Notice line 2 leaves the category field empty. It will be set to the default.',
'line3'             => 'Notice line 3 leaves the name field empty. It will be set to its default, &quot;Unknown.&quot;',
'line5'             => 'Notice line 5 contains values for the first 3 fields and uses no tabs for the remaining blank fields. This is acceptable as long as those unmarked fields occur at the end of the line.',
'managelink'        => 'Manage Quotes',
'misc'              => 'Miscellaneous',
'missing_req_fields' => 'Required fields are missing',
'msg2'              => 'Done processing. Imported %d and encountered %d failures',
'newempty'              => 'No new quotes',
'newquote'          => 'New Quote',
'nomarks'           => 'Please do not enclose your quotation inside quotation marks. Any quotations within your quotation should be contained within single quotation marks. There should not be any double quotation marks anywhere in the text that you have typed or pasted into this space. The only required field is the quotation field.',
'noquotes'          => 'Number of quotes in our repository',
'numresultstxt'     => 'Results Returned',
'quotation'         => 'Quotation',
'quoted'            => 'Person Quoted',
'quote'             => 'Quote',
'randomboxtitle'    => 'Quote of the Day',
'required'          => 'This item is required',
'searchlabel'       => 'Search For',
'searchlink'        => 'Search Quotes',
'searchmsg1'        => 'No search terms entered. Please try again.',
'searchmsg2'        => 'No results to display. Please try again.',
'searchtitle'       => 'Search the Daily Quote Database',
'sort'              => 'Sort',
'sortby'            => 'Sort Quotations by',
'sortopt3'          => 'category',
'sortopt4'          => 'contributor',
'sortopt5'          => 'source',
'sortopt6'          => 'title',
'sortopt7'          => 'all',
'sortopt8'          => 'date',
'hlp_srcdate'       => 'e.g.: "1817-05-29" or "May 29, 1817" or simply "1817".',
'hlp_source'        => 'Name of book, magazine, etc.',
'hlp_title'         => 'Brief title or summary',
'StatsMsg1'         => 'Top Ten Most Quoted Personalities',
'StatsMsg2'         => 'It appears there are no personalities quoted for the Daily Quote plugin on this site.',
'StatsMsg3'         => 'Most Quoted',
'StatsMsg4'         => 'Quotes',
'subm_by'           => 'Submitted by',
'submitquote'       => 'Submit Quote',
'reset'             => 'Reset',
'tabs'              => 'Notice that the tabs are marked here by the regex \t for the sake of the example. Please use the tab button on your keyboard to create tabs.',
'title'             => 'Title',
'txterror'          => 'You have an error in your text file. Ensure that the &quot;quotation&quot; field is not null on any line, i.e., no line should begin with a tab character.',
'type'              => 'Type',
'unknown'           => 'Unknown',
'user_menu2'        => 'Manage Quotes',
'whatsnewlabel'     => 'Quotes',
'whatsnewperiod'    => ' last %s days',
'hlp_admin_dailyquote' => 'To modify or delete a quotation, click on that item\'s edit icon below. To create a new quotation, click on "New Quote" above.',
'hlp_admin_categories' => 'From this page you can edit and enable/disable categories.',
'hlp_admin_batchform' => 'Upload a text file containing quotes.',
'manage_cats'       => 'Manage Categories',
'submit'            => 'Submit',
'del_quote'         => 'Delete Quote',
'del_item_conf'     => 'Do you really want to delete this item?',
'quotes_in_db'      => 'Quotes in the database',
'any'               => 'Any',
'contributor'       => 'Contributor',
'category'          => 'Category',
'email_subject'     => 'New Daily Quote Notification',
'confirm_delitem'   => 'Are you sure you want to delete this item?',
'err_saving_cat'    => 'Error saving category',
);


// GL Interface Messages
$PLG_dailyquote_MESSAGE01 = 'Your quotation has been queued for administrator approval.';
$PLG_dailyquote_MESSAGE02 = 'Your quotation has been saved.';
$PLG_dailyquote_MESSAGE03 = 'Error retrieving current version number';
$PLG_dailyquote_MESSAGE04 = 'Error performing the plugin upgrade';
$PLG_dailyquote_MESSAGE05 = 'Error upgrading the plugin version number';
$PLG_dailyquote_MESSAGE06 = 'Plugin is already up to date';


// Localization of the Admin Configuration UI
$LANG_configsections['dailyquote'] = array(
    'label' => 'Daily Quotes',
    'title' => 'Daily Quote Configuration'
);

$LANG_configsubgroups['dailyquote'] = array(
    'sg_main' => 'Main Settings'
);

$LANG_fs['dailyquote'] = array(
    'fs_main' => 'General Settings',
    'fs_cblock' => 'CenterBlock settings',
    'fs_pblock' => 'PHP Block settings',
    'fs_rblock' => 'Regular Block settings',
    'fs_permissions' => 'Default Permissions',
);

// Note: entries 0, 1, and 12 are the same as in $LANG_configselects['Core']
$LANG_configselects['dailyquote'] = array(
    0 => array('True' => 1, 'False' => 0),
    1 => array('True' => TRUE, 'False' => FALSE),
    3 => array('Yes' => 1, 'No' => 0),
    4 => array('On' => 1, 'Off' => 0),
    5 => array('Top of Page' => 1, 'Below Featured Article' => 2, 'Bottom of Page' => 3),
    9 => array('Never' => 0, 'If Submission Queue' => 1, 'Always' => 2),
    10 => array('5' => 5, '10' => 10, '25' => 25, '50' => 50),
    12 => array('No access' => 0, 'Read-Only' => 2, 'Read-Write' => 3),
    13 => array('None' => 0, 'Left' => 1, 'Right' => 2, 'Both' => 3),
);

$LANG_confignames['dailyquote'] = array(
    'indexdisplim' => 'Limit display on index page',
    'searchdisplim' => 'Limit search results to',
    'queue' => 'Use submission queue?',
    'anonadd' => 'Allow anonymous users to add quotes?',
    'loginadd' => 'Allow logged-in users to add quotes?',
    'loginaddcat' => 'Allow logged-in users to add categories?',
    'loginbatch' => 'Allow logged-in users to batch-add quotes?',
    'cb_enable' => 'Enable Centerblock?',
    'cb_pos' => 'Centerblock Position',
    'cb_home' => 'Centerblock on home page only?',
    'cb_replhome' => 'Centerblock replaces home page?',
    'google_link' => 'Add Google links to quotes?',
    'google_url' => 'Google URL',
    'whatsnew' => 'Show Quotes in "What\'s New" block?',
    'whatsnewdays' => 'Number of days for a quote to be considered "new"',
    'email_admin' => 'Notification Email to Admin?',
    'displayblocks'  => 'Display glFusion Blocks',
);


?>
