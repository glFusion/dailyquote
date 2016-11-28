<?php
/**
*   Batch add quotes to the database.  Similar to glFusion's user import.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2016 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.2.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/


/**
*   Displays the batch add form.
*   @return string  HTML for the form
*/
function DQ_batch_form(){
    global $_TABLES, $_CONF, $LANG_DQ, $_CONF_DQ;

    $retval = '';

    $T = new Template($_CONF['path'] . 'plugins/dailyquote/templates');
    $T->set_file('page', 'addformheader.thtml');
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    //retrieve categories from db if any and display
    $result = DB_query("SELECT id, name
                        FROM {$_TABLES['dailyquote_cat']}
                        WHERE enabled='1'
                        ORDER BY name");
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

    if ($_CONF_DQ['_is_uikit']) {
        $tpl = 'batchaddform.uikit.thtml';
    } else {
        $tpl = 'batchaddform.thtml';
    }
    $T->set_file(array(
        'page' => $tpl,
        'footer' => 'batchadd_sample.thtml',
    ) );
    $T->set_var(array(
        'action_url' => DQ_ADMIN_URL .'/index.php',
        'catlist'   => $catlist,
    ) );
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    $T->parse('output', 'footer');
    $retval .= $T->finish($T->get_var('output'));

    return $retval;
}


/**
*   Inserts a batch of quotes into the database
*/
function DQ_process_batch(){
    global $_TABLES, $_CONF, $LANG_DQ;

    $verbose_import = 1;

    // First, upload the file
    USES_class_upload();
    USES_dailyquote_class_quote();

    $upload = new upload();
    $upload->setPath($_CONF['path_data']. 'temp');
    $upload->setAllowedMimeTypes(array(
            'text/plain' => '.txt',
            'application/octet-stream' => '.txt',
    ));
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
        return $LANG_DQ['absentfile'];
    }

    // Get categories into a usable array
    $cats = array();
    foreach ($_POST['cat'] as $key=>$val) {
        $cats[$val] = '';
    }

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
        list ($quote, $quoted, $title, $source, $sourcedate) =
                explode("\t", $singleline);

        // Fill empty fields with form values, if supplied
        foreach (array('title', 'source', 'sourcedate') as $elem) {
            if (empty($$elem) && !empty($_POST[$elem]))
                $$elem= $_POST[$elem];
        }

        if ($verbose_import) {
            $msg = "<br><b>Working on quote=$quote, quoted=$quoted, " .
                    "category=$cat, title=$title, source=$source, " .
                    "and sourcedate=$sourcedate</b><br>\n";
            $retval .= $msg;
            COM_errorLog($msg, 1);
        }

        // prepare import for database
        if ($quote == '') {
            $retval = "<p align=\"center\" style=\"font-weight: bold; color: red;\">" . $LANG_DQ['txterror'] . "</p>";
            if ($verbose_import) {
                $retval .= "<br>The &quot;quote&quot; field cannot be blank.<br>\n";
            }
            $failures++;
        } else {
            $Q = new dqQuote();
            // Convert to hash for $Q->Save() function
            $A = array(
                'quote' => $quote,
                'quoted' => $quoted,
                'title' => $title,
                'source' => $source,
                'sourcedate' => $sourcedate,
                'enabled' => 1,
                'categories' => $cats,
            );
            $message = $Q->Save($A);
            if ($message == '') {
                if ($verbose_import) {
                    $retval .= "<br> $quote by <em>$quoted</em> successfully added.<br>\n";
                }
                $successes++;
            } else {
                if ($verbose_import) {
                    $retval .= "<br>The quote, &quot;$quote,&quot; already exists in our database.<br>\n";
                }
                $failures++;
            }
        }
    }

    fclose($handle);
    unlink($filename);

    $report = sprintf($LANG_DQ['msg2'], $successes, $failures);

    $retval .= "<p align=\"center\" style=\"font-weight: bold; color: red;\">" .
             $report . "</p>";
    return $retval;
}

?>
