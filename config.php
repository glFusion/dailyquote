<?php
/****************************************************************************
*   Daily Quote Plugin for Geeklog - The Ultimate Weblog
*****************************************************************************
*   $Id$
*****************************************************************************
*   Copyright (C) 2004 by the following authors:

*   Author: Alf Deeley aka machinari - ajdeeley@summitpages.ca
*   Constructed with the Universal Plugin
*   Copyright (C) 2002 by the following authors:
*   Tom Willett                 -    twillett@users.sourceforge.net
*   Blaine Lang                 -    langmail@sympatico.ca
*   The Universal Plugin is based on prior work by:
*   Tony Bibbs                  -    tony@tonybibbs.com
*
*   Adapted for glFusion 1.1 by Lee Garner (lee@leegarner.com) 2008
*****************************************************************************
*   This program is free software; you can redistribute it and/or
*   modify it under the terms of the GNU General Public License
*   as published by the Free Software Foundation; either version 2
*   of the License, or (at your option) any later version.
*
*   This program is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program; if not, write to the Free Software Foundation,
*   Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*****************************************************************************/

// set Plugin Table Prefix the Same as GLFusion's
global $_TABLES;
global $_DB_table_prefix;

// Tables to define as Global
$_TABLES['dailyquote_quotes']      = $_DB_table_prefix . 'dailyquote_quotes';
$_TABLES['dailyquote_submission']  = $_DB_table_prefix . 'dailyquote_submission';
$_TABLES['dailyquote_cat']         = $_DB_table_prefix . 'dailyquote_category';
$_TABLES['dailyquote_cat_sub']     = $_DB_table_prefix . 'dailyquote_category_sub';
$_TABLES['dailyquote_lookup']      = $_DB_table_prefix . 'dailyquote_lookup';
$_TABLES['dailyquote_settings']    = $_DB_table_prefix . 'dailyquote_settings';

// String length for quoted personalities in WhatsNewBlock
$_CONF_DQ['whatsnewnamelength'] = 20;

//directory to use for batch import and backup storage
//do not change!  at least until I've made this var available to all scripts using the dir.
$_CONF_DQ['datadir'] = $_CONF['path_html'] . 'dailyquote/data/';
$_CONF_DQ['disp_limit'] = '4';

?>
