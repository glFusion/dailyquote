<?php
/**
 * Batch add quotes to the database.  Similar to glFusion's user import.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2020-2022 Lee Garner <lee@leegarner.com>
 * @package     dailyquote
 * @version     v0.4.0
 * @since       v0.2.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace DailyQuote;
use DailyQuote\Models\Request;
use DailyQuote\Models\DataArray;


/**
 * Handle batch operations.
 * @package dailyquote
 */
class Batch
{
    /**
     * Displays the batch add form.
     *
     * @return  string  HTML for the form
    */
    public static function form()
    {
        global $_TABLES, $_CONF, $LANG_DQ;

        $retval = '';

        $T = new \Template(DQ_PI_PATH . '/templates');

        //retrieve categories from db if any and display
        $result = DB_query(
            "SELECT id, name
            FROM {$_TABLES['dailyquote_cat']}
            WHERE enabled='1'
            ORDER BY name"
);
        $catlist = '';
        $chk = 'checked="checked"';
        if ($result) {
            while ($row = DB_fetchArray($result, false)) {
                $catlist .= '<input type="checkbox" name="cat[]" value="'.
                    $row['id'] . '" ' . $chk . '>&nbsp;' .
                    $row['name']. '&nbsp;';
                $chk = '';
            }
        }
        $T->set_file(array(
            'page' => 'batchaddform.thtml',
            'footer' => 'batchadd_sample.thtml',
        ) );
        $T->set_var(array(
            'action_url' => DQ_ADMIN_URL .'/index.php',
            'catlist'   => $catlist,
            'lang_batchaddtitle' => MO::_('Add Your Quotations Here'),
            'lang_batchadd' => MO::_('Quotations'),
            'lang_batchcatinstr' => MO::_('You may specify a category if you wish it to be applied to all quotes in the batch.') .
                MO::_('Otherwise there will be no category set. This may be altered later from the quote administration page.'),
            'lang_cat' => MO::_('Category'),
            'lang_batchsrcinstr' => MO::_('Similarily, you may specify a title, source, and a date for that source if you wish it to be applied to all quotes in the batch that have null title, source, and/or source date fields.'),
            'lang_addtitle' => MO::_('Title'),
            'lang_source' => MO::_('Source'),
            'lang_sourcedate' => MO::_('Source Date'),
            'lang_dateformat' => MO::_('(Date Format YYYY-MM-DD)'),
            'lang_batchsubmit' => MO::_('Import Quotes'),
            'lang_rule1' => MO::_('Text file format: quote&lt;tab&gt;person quoted&lt;tab&gt;title&lt;tab&gt;source&lt;tab&gt;source date. The file extension must be &quot;.txt&quot;'),
            'lang_rule2' => MO::_('Notice that the tabs are marked here by the regex \t for the sake of the example. Please use the tab button on your keyboard to create tabs.'),
            'lang_rule3' => MO::_('Notice line 1 contains values for source and source date. Lines 2 through 5 have only the tabs to indicate the fields. Where the field is left empty nothing will be displayed. This also applies to the title field.'),
            'lang_rule4' => MO::_('Notice line 3 leaves the name field empty. It will be set to its default, &quot;Unknown.&quot;'),
            'lang_rule5' => MO::_('Notice line 5 contains values for the first 3 fields and uses no tabs for the remaining blank fields. This is acceptable as long as those unmarked fields occur at the end of the line.'),
            'lang_egtitle' => MO::_('Example .txt File'),
        ) );
        $T->parse('output','page');
        $retval .= $T->finish($T->get_var('output'));
        $T->parse('output', 'footer');
        $retval .= $T->finish($T->get_var('output'));
        return $retval;
    }


    /**
     * Inserts a batch of quotes into the database.
    */
    public static function process()
    {
        global $_TABLES, $_CONF;

        $verbose_import = 1;
        $Request = Request::getInstance();

        // First, upload the file
        USES_class_upload();

        $upload = new \upload();
        $upload->setPath($_CONF['path_data']. 'temp');
        $upload->setAllowedMimeTypes(array(
            'text/plain' => '.txt',
            'application/octet-stream' => '.txt',
        ) );
        $upload->setFileNames('DQ_batch_import.txt');
        $upload->setFieldName('batch_import_file');
        if ($upload->uploadFiles()) {
            // Good, file got uploaded, now install everything
            $filename = $_CONF['path_data'] . 'temp/DQ_batch_import.txt';
        } else {
            // A problem occurred, print debug information
            print 'ERRORS<br>';
            $upload->printErrors();
            exit;
        }

        $retval = '';
        $handle = @fopen($filename,'r');
        if (empty($handle)) {
            return MO::_('Error: You must specify a file to upload.');
        }

        // Get categories into a usable array
        $cats = $Request->getArray('cat');

        // Following variables track import processing statistics
        $successes = 0;
        $failures = 0;
        while ($batchline = fgets($handle,4096)) {
            // Clear fields in case of missing trailing values
            $quote = '';
            $quoted = '';
            $title = '';
            $source = '';
            $sourcedate = '';

            $singleline = rtrim($batchline);
            $A = explode("\t", $singleline);
            $quote = isset($A[0]) ? $A[0] : '';
            $quoted = isset($A[1]) ? $A[1] : '';
            $title = isset($A[2]) ? $A[2] : '';
            $source = isset($A[3]) ? $A[3] : '';
            $sourcedate = isset($A[4]) ? $A[4] : '';

            // Fill empty fields with form values, if supplied
            foreach (array('title', 'source', 'sourcedate') as $elem) {
                if (empty($$elem) && !empty($Request[$elem]))
                    $$elem= $Request->getString($elem);
            }

            if ($verbose_import) {
                $msg = "<br><b>Working on quote=$quote, quoted=$quoted, " .
                    "title=$title, source=$source, " .
                    "sourcedate=$sourcedate</b><br>\n";
                $retval .= $msg;
                glFusion\Log\Log::write('system', Log::INFO, $msg);
            }

            // prepare import for database
            if ($quote == '') {
                $retval = '<p align="center" style="font-weight:bold; color:red;">' .
                    MO::_('You have an error in your text file. Ensure that the &quot;quotation&quot; field is not null on any line, i.e., no line should begin with a tab character.') . '</p>';
                if ($verbose_import) {
                    $retval .= '<br>' . MO::_('The &quot;quote&quot; field cannot be blank.') . "<br>\n";
                }
                $failures++;
            } else {
                $Q = new Quote();
                // Convert to hash for $Q->Save() function
                $A = array(
                    'qid' => 0,
                    'quote' => $quote,
                    'quoted' => $quoted,
                    'title' => $title,
                    'source' => $source,
                    'sourcedate' => $sourcedate,
                    'enabled' => 1,
                    'approved' => 1,
                    'categories' => $cats,
                    'uid' => 1,
                );
                $message = $Q->Save(DataArray::fromArray($A));
                if ($message == '') {
                    if ($verbose_import) {
                        $retval .= "<br> $quote by <em>$quoted</em> successfully added.<br>\n";
                    }
                    $successes++;
                } else {
                    if ($verbose_import) {
                        $retval .= "<br>" . $message . "<br>\n";
                    }
                    $failures++;
                }
            }
        }
        fclose($handle);
        unlink($filename);

        $report = sprintf(
            MO::_('Done processing. Imported %d and encountered %d failures'),
            $successes,
            $failures
        );
        $retval .= '<p align="center" style="font-weight:bold; color:red;">' .
            $report . '</p>';
        return $retval;
    }

}
