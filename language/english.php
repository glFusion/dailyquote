<?php

// +---------------------------------------------------------------------------+
// | Daily Quote Plugin v1.0.4 for Geeklog - The Ultimate Weblog               |
// +---------------------------------------------------------------------------+
// | english.php                                                               |
// |                                                                           |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2004 by the following authors:                              |
// |                                                                           |
// | Author: Alf Deeley aka machinari - ajdeeley@summitpages.ca                |
// | Constructed with the Universal Plugin                                     |
// | Copyright (C) 2002 by the following authors:                              |
// | Tom Willett                 -    twillett@users.sourceforge.net           |
// | Blaine Lang                 -    langmail@sympatico.ca                    |
// | The Universal Plugin is based on prior work by:                           |
// | Tony Bibbs                  -    tony@tonybibbs.com                       |
// +---------------------------------------------------------------------------+
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
// | GNU General Public License for more details.                              |
// |                                                                           |
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software Foundation,   |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.           |
// |                                                                           |
// +---------------------------------------------------------------------------+
//

$LANG_DQ00 = array (
    'access_denied'     => 'Access Denied',
    'access_denied_msg' => 'Only Root Users have Access to this Page.  Your user name and IP have been recorded.',
    'access_denied_msg1' => 'Only Authorized Users have Access to this Page.  Your user name and IP have been recorded.',
    'admin'             => 'Plugin Admin',
    'enabled'           => 'Disable plugin before uninstalling.',
    'install'           => 'Install',
    'install_failed'    => 'Installation Failed -- See your error log to find out why.',
    'install_header'    => 'Install/Uninstall Plugin',
    'install_success'   => 'Installation Successful',
    'installdoc'        => 'Install Document',
    'installed'         => 'The Plugin is Installed',
    'plugin'            => 'Plugin',
    'readme'            => 'STOP! Before you press install please read the ',
    'uninstall'         => 'UnInstall',
    'uninstall_msg'     => 'Plugin Successfully Uninstalled',
    'uninstalled'       => 'The Plugin is Not Installed',
    'warning'           => 'Warning! Plugin is still Enabled',
);

$LANG_DQ= array(
    'absentfile'            => 'Error: You must specify a file to upload.',
    'add2intro'             => 'You may import a batch of quotations into the database using this form.  Simply import your tab-delimited text file into the box below ensuring that there is only one entry per line.  Each line, therefore, must contain a value for the following fields: quotation; name of person being quoted; category; title; source; and source date--in that order.  If the second field is left blank, &quot;Unknown&quot; will be used.  Please do not enclose your quotation inside quotation marks.  You can view an example text file at the bottom of this page.',
    'addcat'                => 'New Category',
    'addcaterr'             => 'An error has occurred while attempting to add your category to the database.',
    'addcatsuc'             => 'Your category has been successfully added to the database.  Add another?',
    'addformlink'           => 'Add a Quote',
    'addintro'              => 'You are invited to add a quotation to our repository.  By default, your submission will be held in our submission queue for approval.  Upon approval your submission will be listed on the main page.',
    'addqueue'              => 'Your quotation has been submitted to our database.  Upon approval it will appear in the regular listing.<br />Add another?',
    'addquote'              => 'Add Your Quotation Here',
    'addsource'             => 'Source',
    'addsourcedate'         => 'Source Date',
    'addsuccess'            => 'Your quotation has been successfully added to our database.  Add another?',
    'addtitle'              => 'Title',
    'admin_menu'            => 'Daily Quote',
    'adminhome'             => 'Admin Home',
    'adminintro'            => 'From this page you may enable, disable, or configure various dailyquote settings.',
    'admintitle'            => 'Quote of the Day Configuration',
    'allsterr'              => 'Status update failed',
    'allstsuc1'             => 'All active categories enabled successfully',
    'allstsuc2'             => 'All active categories disabled successfully',
    'anonadd'               => 'Allow anonymous users to add quotes',
    'anonview'              => 'Allow anonymous users to view quotes',
    'anonymous'             => 'Anonymous',
    'apperror'              => 'An error occurred during the approval process.',
    'appsuccess'            => 'Entry approved.',
    'ascopt1'               => 'ascending order',
    'ascopt2'               => 'descending order',
    'backupsucc'            => 'DailyQuote database tables backed up successfully.',
    'batchadd'              => 'Quotations',
    'batchaddlink'          => 'Batch Add Quotes',
    'batchaddtitle'         => 'Add Your Quotations Here',
    'batchcatinstr'         => 'You may specify a category if you wish it to be applied to all quotes in the batch that have a null category field, otherwise the default, &quot;Miscellany,&quot; will be applied to those fields.  This may be altered later from the manage page.',
    'batchsrcinstr'         => 'Similarily, you may specify a title, source, and a date for that source if you wish it to be applied to all quotes in the batch that have null title, source, and/or source date fields.',
    'batchsubmit'           => 'Import Quotes',
    'below'                 => 'below featured article',
    'beware'                => 'Beware',
    'blkadd'                => 'Add Link',
    'blkcat'                => 'Categories',
    'blkcnt'                => 'Contributor / Date',
    'blksrc'                => 'Source / Date',
    'blktit'                => 'Title',
    'bottom'                => 'bottom of page',
    'cat'                   => 'Category',
    'cataddtitle'           => 'Create a new category',
    'categories'            => 'Categories',
    'catenable'             => 'Enable new category',
    'caterror'              => 'Error occurred while retrieving category list',
    'cathead'               => 'The following quotes appear under the &quot;%s&quot; category:',
    'catindexlink'          => 'Categories',
    'catinstr'              => 'By default, new categories appear to be disabled.  A new category will be enabled automatically upon becoming active.  A category must contain at least one quotation to be active.',
    'catinstr2'             => 'Only enabled categories are listed in the category index while this page makes all categories editable.<br /><strong>Note</strong>:  Deleting a category does not delete any entries belonging to that category.  Further, if a category is deleted, and that category was the single category to which a quote belonged, that quote will then be listed under the &quot;Miscellany&quot; category.  Also, disabling a category will not prevent a particular quote from being displayed if that quote also belongs to another category.',
    'catlstintro'           => 'From this page you can browse quotations according to their category.  Simply click on the category that you would like to view.  You will see all quotations belonging to that category listed on the search page together with the search form for your convenience.',
    'catlsttitle'           => 'Category Index',
    'catmanintro'           => 'Add or delete, enable or disable, or edit your categories all from this page.',
    'catmanlink'            => 'Manage Categories',
    'catmantitle'           => 'Category Management',
    'catreadme'             => 'The default category will be &quot;Miscellany&quot; if no other category selection is made',
    'chkdb'                 => 'Check Database',
    'choosecat'             => 'Choose one or more categories to which your quote should belong',
    'choosecat1'            => 'Choose one or more categories to which your quote should belong, and/or add a new category here',
    'cntrblk'               => 'Enable centerblock',
    'cntrblkhom'            => 'Homepage only',
    'cntrblkpos'            => 'Position centerblock',
    'cntrcomp'              => 'Center Block',
    'cntrlink'              => '&quot;Add Quote&quot; link in centerblock',
    'compinstr'             => 'The display of various components, as specified, can be turned on or off.<br />If applicable, permissions will override these settings.',
    'components'            => 'Block Component Configuration',
    'configerror'           => 'An error occurred while retrieving the current configuration',
    'contributor'           => 'Contributor',
    'copynpaste'            => 'Copy and Paste your tab delimited text file here.',
    'date'                  => 'Date',
    'dateformat'            => '(Date Format YYYY-MM-DD)',
    'dbbackup'              => 'Backup DailyQuote Tables',
    'dbchkerror'            => 'An error occurred while checking or repairing the database',
    'dbinstr'               => 'This button will seek out orphaned entries in the database and provide the opportunity to rebuild those entries.',
    'dbman'                 => 'Database Integrity',
    'dbresults1'            => 'The following entries, represented by their ID numbers, are listed in the database\'s quotes table without proper representation in the lookup table:&nbsp;&nbsp;',
    'dbresults1a'           => 'All entries in the quotes table are properly represented in the lookup table.',
    'dbreults1b'            => 'Quote table orphans repaired.  Entries are now listed under the Miscellany category',
    'dbresults2'            => 'The following entries, represented by their ID numbers, are listed in the database\'s lookup table without corresponding entries in the quotes table:&nbsp;&nbsp;',
    'dbresults2a'           => 'All entries in the lookup table are properly represented in the quotes table.',
    'dbresults2b'           => 'Lookup table orphans repaired.  Orphaned quote ID\'s deleted.',
    'dbresults3'            => 'The following entries, represented by their category ID numbers, are listed in the database\'s lookup table without corresponding entries in the category table:&nbsp;&nbsp;',
    'dbresults3a'           => 'All entries in the lookup table are properly represented in the category table.',
    'dbresults3b'           => 'Lookup table orphans repaired.  Orphaned category ID\'s deleted.',
    'dbresults4'            => 'The following entry, represented by its category ID number, is listed in the database\'s category table with an empty &quot;Name&quot; field:&nbsp;&nbsp;',
    'dbresults4a'           => '--at least one entry in the quotes table has been assigned to this category ID',
    'dbresults4b'           => 'Category table repaired.  Null category entries deleted.',
    'dbresultsucc'          => 'There are no apparent inconsistencies in the DailyQuote data tables.<br />Now would be a good time to backup your data.',
    'dbsee'                 => 'See below for results',
    'default'               => 'Apply Default Settings',
    'defaultcfg'            => 'The default configuration has been restored',
    'defaultsort'           => 'Default: sort by date desc.',
    'delallcaterr'          => 'Deletion of all categories failed',
    'delallcatsuc'          => 'All categories deleted successfully--all quotations now belong to the miscellany category.',
    'delcaterr'             => 'An error occurred while deleting at least one selected category.  See error log for details.',
    'delcaterror'           => 'Quotation must belong to at least one category',
    'delcatsuc'             => 'All categories selected were deleted successfully',
    'delerror'              => 'An error occurred while deleting an entry',
    'delete'                => 'Delete',
    'deleteall'             => 'Delete All Categories',
    'delfilesucc'           => 'Your backup file has been successfully deleted',
    'delsuccess'            => 'Your quotation has been successfully deleted',
    'disableall'            => 'Disable All Categories',
    'disperror'             => 'Error occurred while retrieving quote list',
    'displim'               => 'Display quotes per page',
    'disprand'              => 'Random quotation refresh rate',
    'dupcat'                => 'At least one category name being updated already exists in our database.  Please try again.',
    'dupquote'              => 'That quotation already exists in our database.  Please try again.',
    'editcat'               => 'Category',
    'editlink'              => 'Edit',
    'egtitle'               => 'Example .txt File:',
    'emptycat'              => 'Submitting a blank category is not permitted.',
    'emptyqueue'            => 'The submission queue is empty',
    'emptyquote'            => 'Submitting a blank quote is not permitted.',
    'enableall'             => 'Enable All Categories',
    'enableq'               => 'Enable?',
    'enddate'               => 'To',
    'firstcat'              => 'Create the first category into which you may place your quote here',
    'fopenerr'              => 'Could not open file for writing',
    'fopenerr1'             => 'Could not open file for reading',
    'fwriteerr'             => 'Could not write to file',
    'general'               => 'General Settings',
    'gglink'                => 'Enable &quot;google-links&quot;',
    'gimmebreak'            => 'i\'m gettin\' there, hold yer frisbee dude!',
    'glsearchlabel'         => 'Quotes Listing',
    'glsearchlabel_results' => 'Quotes Listing Results',
    'go'                    => 'GO',
    'hour'                  => 'hours',
    'indexintro'            => 'History is full of stories, rants, perspectives, truths, lies, facts, details, opinions, ordinances, etc.  The stories told by the men and women who were there, as well as by those who were not, and their comments are items for display in the archives of humankind.  Visit the museum and contribute.',
    'indexlink'             => 'Listings',
    'indextitle'            => 'Quote of the Day',
    'inserterror'           => 'An error has occurred while attempting to add your quotation to the database.  If this problem persists, please contact the site administrator.',
    'keyall'                => 'all of these words',
    'keyany'                => 'any of these words',
    'keyphrase'             => 'exact phrase',
    'limit'                 => 'Limit To',
    'line1'                 => 'Notice line 1 contains values for source and source date.  Lines 2 through 5 have only the tabs to indicate the fields.  Where the field is left empty nothing will be displayed.  This also applies to the title field.',
    'line2'                 => 'Notice line 2 leaves the category field empty.  It will be set to its default, &quot;Miscellany.&quot;',
    'line3'                 => 'Notice line 3 leaves the name field empty.  It will be set to its default, &quot;Unknown.&quot;',
    'line5'                 => 'Notice line 5 contains values for the first 3 fields and uses no tabs for the remaining blank fields.  This is acceptable as long as those unmarked fields occur at the end of the line.',
    'logadd'                => 'Allow members to add quotes',
    'logaddcat'             => 'Allow members to add categories',
    'logbatch'              => 'Allow members to batch add quotes',
    'logbatchinstr'         => 'The ability to create a category is always available on the batch add page.',
    'manageintro'           => 'Once you have performed a search, your search results are manageable from this page.  You may delete quotations along with their related information, or you may edit the quotations, the names of persons quoted, categories, as well as the contributor\'s names.',
    'managelink'            => 'Manage Quotes',
    'managetitle'           => 'Manage the Daily Quote Database',
    'mark'                  => 'Mark to Delete',
    'mdelete'               => 'Delete Marked Categories',
    'menulabel'             => 'Daily&nbsp;Quote',
    'misc'                  => 'Miscellany',
    'moderateintro'         => 'From this page you may edit, or approve and delete any submissions in the queue.',
    'moderatetitle'         => 'Moderate the Submission Queue',
    'moderationlink'        => 'Moderate',
    'mostquoted'            => 'Personality most often quoted',
    'msg2'                  => 'Done processing. Imported $successes and encountered $failures failures',
    'newcat'                => 'Create a new category and add this quote to it',
    'newempty'              => 'No new quotes',
    'newquote'              => 'New Quote',
    'nextpage'              => 'Next Page >>>',
    'no'                    => 'No',
    'noaccess'              => "ERROR: Directory {$DQ_CONF['datadir']} is not accessible.",
    'nomarks'               => 'Please do not enclose your quotation inside quotation marks.  Any quotations within your quotation should be contained within single quotation marks.  There should not be any double quotation marks anywhere in the text that you have typed or pasted into this space.  The only required field is the quotation field.',
    'noquotes'              => 'Number of quotes in our repository',
    'notperm'               => 'The %s category cannot be deleted, but it can be disabled.',
    'nummodtxt'             => 'Submissions in the Queue',
    'numresultstxt'         => 'Results Returned',
    'oldbaks'               => 'Previous table backups:',
    'off'                   => 'OFF',
    'on'                    => 'ON',
    'pagerefresh'           => 'page refresh',
    'perms'                 => 'Permissions',
    'phpcomp'               => 'PHP block',
    'plugin_name'           => 'Daily Quote',
    'preview'               => 'File Preview:',
    'preview1'              => 'Preview',
    'prevpage'              => '<<< Previous Page',
    'proflastqtxt'          => 'Last 10 DailyQuote contributions for user ',
    'profnone'              => 'No user contributions',
    'profnumqtxt'           => 'Total number of DailyQuote contributions:',
    'queue'                 => 'Submission Queue',
    'queuenumtxt'           => 'Entries in the submissions queue',
    'queueoff'              => 'Turning the queue OFF will effectively approve all submissions presently in the queue.',
    'quotation'             => 'Quotation',
    'quoted'                => 'Quoted',
    'randomboxtitle'        => 'Quote of the Day',
    'randomerror'           => 'Error occurred while retrieving random quote',
    'repdb'                 => 'Repair Database',
    'reset'                 => 'Reset',
    'resterr'               => 'An error occurred while attempting to restore backup file %s.',
    'restore'               => 'Restore',
    'restsucc'              => 'Data from file %s restored -- %s errors',
    'rndmcomp'              => 'Regular Quote Box',
    'searchintro'           => 'You may search our entire quotation repository using this form.  Restricting your search to various categories or by date will often speed up your search and return a more pertinent result set.',
    'searchlabel'           => 'Search For',
    'searchlink'            => 'Search Quotes',
    'searchmsg1'            => 'No search terms entered.  Please try again.',
    'searchmsg2'            => 'No results to display.  Please try again.',
    'searchtitle'           => 'Search the Daily Quote Database',
    'sort'                  => 'Sort',
    'sortby'                => 'Sort Quotations by',
    'sortopt1'              => 'quotation',
    'sortopt2'              => 'personality quoted',
    'sortopt3'              => 'category',
    'sortopt4'              => 'contributor',
    'sortopt5'              => 'source',
    'sortopt6'              => 'title',
    'sortopt7'              => 'all',
    'sortopt8'              => 'date',
    'srcdateformat'         => 'e.g.: "1817-05-29" or "May 29, 1817" or simply "1817".',
    'srchdisplim'           => 'Limit search results per page',
    'StatsMsg1'             => 'Top Ten Most Quoted Personalities',
    'StatsMsg2'             => 'It appears there are no personalities quoted for the Daily Quote plugin on this site.',
    'StatsMsg3'             => 'Most Quoted',
    'StatsMsg4'             => 'Quotes',
    'subm_by'               => 'Submitted&nbsp;by',
    'submitcat'             => 'add new category',
    'submitquote'           => 'Submit Quote',
    'tabs'                  => 'Notice that the tabs are marked here by the regex &quot; <strong>\ t</strong> &quot; for the sake of the example.  Please use the tab button on your keyboard to create tabs.',
    'title'                 => 'Title',
    'top'                   => 'top of page',
    'txterror'              => 'You have an error in your text file.  Ensure that the &quot;quotation&quot; field is not null on any line, i.e., no line should begin with a tab character.',
    'type'                  => 'Type',
    'unknown'               => 'Unknown',
    'update'                => 'Update',
    'mupdate'               => 'Apply All Changes',
    'updcaterr'             => 'An error occurred while updating at least one selected category.  See error log for details.',
    'updcatsuc'             => 'Categories updated successfully',
    'updcfgerror'           => 'An error occurred while updating the configuration',
    'upderror'              => 'An error occurred while updating an entry',
    'updduperr'             => 'The \'%s\' category already exists.',
    'updmiscerr'            => 'The \'%s\' category cannot be renamed.',
    'updsterr'              => 'The \'%s\' category is inactive and so cannot be enabled.',
    'updsuccess'            => 'Update process complete',
    'usedel'                => 'If you wish to delete the \'%s\' category, use the delete button.',
    'user_menu1'            => 'Add a Quote',
    'user_menu2'            => 'Manage Quotes',
    'whatsnew'              => 'Enable &quot;What\'s New&quot; list',
    'whatsnewlabel'         => '<br />QUOTES',
    'whatsnewperiod'        => ' last %s days',
    'yes'                   => 'Yes',
    'admin_hdr'  => 'To modify or delete a quotation, click on that item\'s edit icon below. To create a new quotation, click on "New Quote" above.',
);


// GL Interface Messages
$PLG_dailyquote_MESSAGE01 = 'Your quotation has been queued for administrator approval.';
$PLG_dailyquote_MESSAGE02 = 'Your quotation has been saved.';


// Localization of the Admin Configuration UI
$LANG_configsections['dailyquote'] = array(
    'label' => 'Daily Quotes',
    'title' => 'Daily Quote Configuration'
);

$LANG_confignames['dailyquote'] = array(
    'quoteperpage'      => 'Quotes per page to display',
    'searchlimit'       => 'Max search results to return',
    'submission'        => 'Use submission queue?',
    'anonadd'           => 'Allow anon users to add quotes?',
    'whatsnew_int'      => 'Number of days for a quote to be considered "new"',
    'whatsnew'          => 'Show quotes in the "What\'s New" block?',
    'google_link'       => 'Create links to Google?',
    'google_url'        => 'Base link for google searches',

    'cb_enable'         => 'Enable centerblock?',
    'cb_pos'            => 'Centerblock Position',
    'cb_title'          => 'Show title in centerblock?',
    'cb_homepage'       => 'Centerblock on Home Page only?',
    'cb_srcdate'        => 'Show source date in centerblock?',
    'cb_contribdate'    => 'Show contributed date in centerblock?',
    'cb_categories'     => 'Show categories in centerblock?',
    'cb_addlink'        => 'Show "Add" link in centerblock?',
    'cb_replhome'       => 'Centerblock replaces home page?',

    'phpblk_title'      => 'Show title in PHP block?',
    'phpblk_srcdate'    => 'Show source date in PHP block?',
    'phpblk_contribdate' => 'Show contributed date in PHP block?',
    'phpblk_categories' => 'Show categories in PHP block?',
    'phpblk_addlink'    => 'Show "Add" link in PHP block?',

    'qbox_title'        => 'Show title in regular block?',
    'qbox_srcdate'      => 'Show source date in regular block?',
    'qbox_contribdate'  => 'Show contributed date in regular block?',
    'qbox_categories'   => 'Show categories in regular block?',

    'defgrp'            => 'Default Group',

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
    10 => array('5' => 5, '10' => 10, '25' => 25, '50' => 50),
    12 => array('No access' => 0, 'Read-Only' => 2, 'Read-Write' => 3)
);


?>
