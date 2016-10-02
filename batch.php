<?php
/**
*   Batch add quotes to the database.  Similar to glFusion's user import.
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2010 Lee Garner <lee@leegarner.com>
*   @package    dailyquote
*   @version    0.1.2
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/


/**
*   Displays the batch add form.
*   @return string  HTML for the form
*/
function DQ_batch_form(){
    global $_TABLES, $_CONF, $_USER, $LANG_DQ;

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
    $chk = ' checked ';
    if ($result) {
        while ($row = DB_fetchArray($result, false)) {
            $catlist .= '<input type="radio" name="cat" value="'.
                        $row['id'] . '" ' . $chk . '>&nbsp;' . 
                        $row['name']. '&nbsp;';
            $chk = '';
        }
    }

    $T->set_file('page', 'batchaddform.thtml');
    $T->set_var(array(
            'site_url'  => $_CONF['site_url'],
            'action_url' => DQ_ADMIN_URL .'/index.php',
            'mode'      => 'processbatch',
            'catlist'   => $catlist,
    ));
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    $T->set_file('page', 'addformfooter.thtml');
    $T->parse('output','page');
    $retval .= $T->finish($T->get_var('output'));

    return $retval;
}


/**
*   Inserts a batch of quotes into the database
*/
function DQ_process_batch(){
    global $_TABLES, $_CONF, $_USER, $LANG_DQ;

    $verbose_import = 1;

    // First, upload the file
    USES_class_upload();

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

    // Set the category from the radio button                
    $catid = (int)$_POST['cat'];

    // Following variables track import processing statistics
    $successes = 0;
    $failures = 0;
    while ($batchline = fgets($handle,4096)) {
        $singleline = rtrim($batchline);
        list ($quote, $quoted, $title, $src, $srcdate) = 
                split ("\t", $singleline);

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
            $quote = DB_escapeString(strip_tags((COM_checkWords(trim($quote))), 
                    '<strong><em><br><br />'));
            $quoted = DB_escapeString(strip_tags((COM_checkWords((trim($quoted))))));
            $title = DB_escapeString(strip_tags(COM_checkWords(trim($title))));
            $src = DB_escapeString(strip_tags(COM_checkWords(trim($src))));
            $srcdate = DB_escapeString(strip_tags(COM_checkWords(trim($srcdate))));

            // get user info for db
            $uid = $_USER['uid'];

            //insert all data to the db
            $qid = COM_makesid();
            $sql = "INSERT IGNORE INTO {$_TABLES['dailyquote_quotes']} 
                SET 
                    id = '$qid',
                    quote='$quote', 
                    quoted='$quoted', 
                    title='$title', 
                    source='$src', 
                    sourcedate='$srcdate', 
                    dt = UNIX_TIMESTAMP(), 
                    enabled = '1', 
                    uid='$uid'";
            //echo "$sql<br />\n";
            $result = DB_query($sql);
            if ($result) {
                // Successful import.  Now add a lookup entry IF a valid
                // category was selected.
                if ($catid > 0) {
                    DB_query("INSERT INTO {$_TABLES['dailyquote_quoteXcat']} 
                        SET 
                            qid = '$qid', 
                            cid = '$catid'");
                }
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
