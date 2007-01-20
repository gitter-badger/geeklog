<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Geeklog 1.4                                                               |
// +---------------------------------------------------------------------------+
// | Story.class.php                                                           |
// |                                                                           |
// | Geeklog Story Abstraction.                                                |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2006 by the following authors:                              |
// |                                                                           |
// | Authors: Michael Jervis, mike AT fuckingbrit DOT com                      |
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
// $Id: story.class.php,v 1.3 2007/01/17 09:22:59 ospiess Exp $

/**
 * This file provides a class to represent a story, or article. It provides a
 * finite state machine for articles. Switching them between the various states:
 *  1) Post Data
 *  2) Display Mode
 *  3) Edit Mode
 *  4) Database Mode
 *
 * @package Geeklog
 * @filesource
 * @version 0.1
 * @since GL 1.4.2
 * @copyright Copyright &copy; 2006
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Michael Jervis <mike AT fuckingbrit DOT com>
 *
 */

/**
 * Constants for stories:
 * Loading from database:
 */
define('STORY_INVALID_SID', -1);
define('STORY_PERMISSION_DENIED', -2);
define('STORY_EDIT_DENIED', -3);
define('STORY_LOADED_OK', 1);
/**
 * Constants for Stories:
 * Saving to database
 */
define('STORY_SAVED', 1);
define('STORY_SAVED_SUBMISSION', 2);
/**
 * Constants for Stories:
 * Loading from request.
 */
define('STORY_DUPLICATE_SID', -1);
define('STORY_EXISTING_NO_EDIT_PERMISSION', -2);
define('STORY_NO_ACCESS_PARAMS', -3);
define('STORY_EMPTY_REQUIRED_FIELDS', -4);
define('STORY_NO_ACCESS_TOPIC', -5);

/**
  * Constants for our magic loader
  */
define('STORY_AL_ALPHANUM', 0);
define('STORY_AL_NUMERIC', 1);
define('STORY_AL_CHECKBOX', 2);


class Story
{
    //*************************************************************************/
    // Variables:

    // Public
    /**
     * Mode, either 'admin' for in the admin screens, or submission, for
     * when the user is using submit.php. Controls tons of stuff.
     */
    var $mode = 'admin';

    /**
     * Type of story. User submission or normal editor entered stuff.
     * Will be 'submission' if it's from the submission queue.
     */
    var $type = 'article';

    //Private

    /**
     * PRIVATE MEMBER VARIABLES: Things that make up a story.
     */
    var $_sid;
    var $_title;
    var $_introtext;
    var $_bodytext;
    var $_postmode;
    var $_uid;
    var $_draft;
    var $_tid;
    var $_date;
    var $_hits;
    var $_numemails;
    var $_comments;
    var $_trackbacks;
    var $_related;
    var $_featured;
    var $_show_topic_icon;
    var $_commentcode;
    var $_trackbackcode;
    var $_statuscode;
    var $_expire;
    var $_advanced_editor_mode;
    var $_frontpage;
    var $_in_transit;
    var $_owner_id;
    var $_group_id;
    var $_perm_owner;
    var $_perm_group;
    var $_perm_members;
    var $_perm_anon;

    /* Misc display fields we also load from the database for a story: */
    var $_username;
    var $_fullname;
    var $_photo;
    var $_topic;
    var $_imageurl;

    /**
     * The original SID of the article, cached incase it's changed:
     */
    var $_originalSid;

    /**
     * The access level.
     */
    var $_access;

    /**
     * Magic array used for cheating when loading/saving stories from/to db.
     */
    var $_dbFields = array( 'sid', 'uid', 'draft_flag', 'tid', 'date', 'title',
                            'introtext', 'bodytext', 'hits', 'numemails',
                            'comments', 'trackbacks', 'related', 'featured',
                            'show_topic_icon', 'commentcode', 'trackbackcode',
                            'statuscode', 'expire', 'postmode',
                            'advanced_editor_mode', 'frontpage', 'in_transit',
                            'owner_id', 'group_id', 'perm_owner', 'perm_group',
                            'perm_members', 'perm_anon' );
    /**
     * Magic array used for loading basic data from posted form. Of form:
     * postfield -> numeric, target, used with COM_applyFilter. Some fields
     * have exceptions applied
     */
    var $_postFields = array(
                              'uid' => array(STORY_AL_NUMERIC, '_uid'),
                              'draft' => array(STORY_AL_NUMERIC, '_draft_flag'),
                              'tid' => array(STORY_AL_ALPHANUM, '_tid'),
                              'show_topic_icon' => array(STORY_AL_CHECKBOX, '_show_topic_icon'),
                              'draft_flag' => array(STORY_AL_CHECKBOX, '_draft'),
                              'statuscode' => array(STORY_AL_NUMERIC, '_statuscode'),
                              'featured' => array(STORY_AL_NUMERIC, '_featured'),
                              'frontpage' => array(STORY_AL_NUMERIC, '_frontpage'),
                              'commentcode' => array(STORY_AL_NUMERIC, '_commentcode'),
                              'trackbackcode' => array(STORY_AL_NUMERIC, '_trackbackcode'),
                              'postmode' => array(STORY_AL_ALPHANUM, '_postmode'),
                              'story_hits' => array(STORY_AL_NUMERIC, '_hits'),
                              'story_comments' => array(STORY_AL_NUMERIC, '_comments'),
                              'story_emails' => array(STORY_AL_NUMERIC, '_numemails'),
                              'story_trackbacks' => array(STORY_AL_NUMERIC, '_trackbacks'),
                              'owner_id' => array(STORY_AL_NUMERIC, '_owner_id'),
                              'group_id' => array(STORY_AL_NUMERIC, '_group_id'),
                              'type' => array(STORY_AL_ALPHANUM, '_type'),
                              'hits' => array(STORY_AL_NUMERIC, '_hits'),
                              'comments' => array(STORY_AL_NUMERIC, '_comments'),
                              'trackbacks' => array(STORY_AL_NUMERIC, '_trackbacks')
                            );

    //End Private

    // End Variables.
    /**************************************************************************/

    /**************************************************************************/
    // Public Methods:
    /**
     * Constructor, creates a story, taking a (geeklog) database object.
     * @param $mode   string    Story class mode, either 'admin' or 'submission'
     */
    function Story($mode='admin')
    {
        $this->mode = $mode;
    }

    /**
      * Loads a story object from an array (that's come back from the db..)
      *
      * Used from loadFromDatabase, and used on it's own from story list
      * pages.
      * @param  $story  array   Story array from db
      * @return nowt?
      */
    function loadFromArray($story)
    {
        /* Use the magic cheat array to quickly reload the whole story
         * from the database result array, doing the quick stripslashes.
         */
        reset( $this->_dbFields );
        while( $fieldname = each( $this->_dbFields ) )
        {
            $varname = '_'.$fieldname[1];
            if( array_key_exists( $fieldname[1], $story ) )
            {
                $this->{$varname} = stripslashes( $story[$fieldname[1]] );
            }
        }
        // Overwrite the date with the timestamp.
        $this->_date = $story['unixdate'];
        // Store the original SID
        $this->_originalSid = $this->_sid;
    }

    /**
     * Load a Story object from the sid specified, returning a status result.
     * The result will either be a permission denied message, invalid SID
     * message, or a loaded ok message. If it's loaded ok, then we've got all
     * the exciting gubbins here.
     *
     * Only used from story admin and submit.php!
     *
     * @param $sid  string  Story Identifier, valid geeklog story id from the db.
     * @return Integer from a constant.
     */
    function loadFromDatabase($sid, $mode='edit')
    {
        global $_TABLES, $_CONF, $_USER;
        $sid = addslashes(COM_applyFilter($sid));

        if( !empty( $sid ) && ( ( $mode == 'edit' ) || ( $mode == 'view' ) ) )
        {
            $sql = array();

            $sql['mysql'] = "SELECT STRAIGHT_JOIN s.*, UNIX_TIMESTAMP(s.date) AS unixdate, "
              . "u.username, u.fullname, u.photo, u.email, t.topic, t.imageurl "
              . "FROM {$_TABLES['stories']} AS s, {$_TABLES['users']} AS u, {$_TABLES['topics']} AS t "
              . "WHERE (s.uid = u.uid) AND (s.tid = t.tid) AND (sid = '$sid')";

            $sql['mssql'] = "SELECT STRAIGHT_JOIN s.sid, s.uid, s.draft_flag, s.tid, s.date, s.title, CAST(s.introtext AS text) AS introtext, CAST(s.bodytext AS text) AS bodytext, s.hits, s.numemails, s.comments, s.trackbacks, s.related, s.featured, s.show_topic_icon, s.commentcode, s.trackbackcode, s.statuscode, s.expire, s.postmode, s.frontpage, s.in_transit, s.owner_id, s.group_id, s.perm_owner, s.perm_group, s.perm_members, s.perm_anon, s.advanced_editor_mode, "
              . " UNIX_TIMESTAMP(s.date) AS unixdate, "
              . "u.username, u.fullname, u.photo, u.email, t.topic, t.imageurl "
              . "FROM {$_TABLES['stories']} AS s, {$_TABLES['users']} AS u, {$_TABLES['topics']} AS t "
              . "WHERE (s.uid = u.uid) AND (s.tid = t.tid) AND (sid = '$sid')";
        } else if( !empty( $sid ) && ( $mode == 'editsubmission' ) ) {
            $sql = 'SELECT STRAIGHT_JOIN s.*, UNIX_TIMESTAMP(s.date) AS unixdate, '
                 .'u.username, u.fullname, u.photo, t.topic, t.imageurl, t.group_id, '
                 .'t.perm_owner, t.perm_group, t.perm_members, t.perm_anon '
                 .'FROM '.$_TABLES['storysubmission'].' AS s, '.$_TABLES['users']
                 .' AS u, '.$_TABLES['topics'].' AS t WHERE (s.uid = u.uid) AND'
                 .' (s.tid = t.tid) AND (sid = \''.$sid.'\')';
        } else if( $mode == 'edit' ) {
            $this->_sid = COM_makesid();
            $this->_old_sid = $this->_sid;
            if (isset ($_CONF['draft_flag'])) {
                $this->_draft = $_CONF['draft_flag'];
            } else {
                $this->_draft = 0;
            }
            if (isset ($_CONF['show_topic_icon'])) {
                $this->_show_topic_icon = $_CONF['show_topic_icon'];
            } else {
                $this->_show_topic_icon = 1;
            }
            $this->_uid = $_USER['uid'];
            $this->_date = time();
            $this->_expire = time();
            $this->_commentcode = $_CONF['comment_code'];
            $this->_trackbackcode = $_CONF['trackback_code'];
            $this->_title = '';
            $this->_introtext = '';
            $this->_bodytext = '';
            if (isset ($_CONF['frontpage']) )
            {
                $this->_frontpage = $_CONF['frontpage'];
            } else {
                $this->_frontpage = 1;
            }
            $this->_hits = 0;
            $this->_comments = 0;
            $this->_trackbacks = 0;
            $this->_numemails = 0;

            if (isset ($_CONF['advanced_editor']) &&
                $_CONF['advanced_editor'] &&
                ($_CONF['postmode'] != 'plaintext')) {
                $this->_advanced_editor_mode = 1;
                $this->_postmode = 'adveditor';
            } else {
                $this->_postmode = $_CONF['postmode'];
                $this->_advanced_editor_mode = 0;
            }

            $this->_statuscode = 0;
            $this->_featured = 0;
            $this->_owner_id = $_USER['uid'];
            if (isset ($_GROUPS['Story Admin'])) {
                $this->_group_id = $_GROUPS['Story Admin'];
            } else {
                $this->_group_id = SEC_getFeatureGroup ('story.edit');
            }
            $array = array();
            SEC_setDefaultPermissions( $array, $_CONF['default_permissions_story'] );
            $this->_perm_owner = $array['perm_owner'];
            $this->_perm_group = $array['perm_group'];
            $this->_perm_anon = $array['perm_anon'];
            $this->_perm_members = $array['perm_members'];
        } else {
            $this->loadFromRequest();
        }

        /* if we have SQL, load from it */
        if( !empty( $sql ) )
        {
            $result = DB_query( $sql );
            if( $result )
            {
                $story = DB_fetchArray( $result );
                $this->loadFromArray($story);
                $access = SEC_hasAccess( $story['owner_id'], $story['group_id'],
                                $story['perm_owner'], $story['perm_group'],
                                $story['perm_members'], $story['perm_anon'] );
                $this->_access = min ($access, SEC_hasTopicAccess ($this->_tid));
                if( $this->_access == 0 )
                {
                    return STORY_PERMISSION_DENIED;
                } else if( $this->_access == 2 && $mode != 'view' ) {
                    return STORY_EDIT_DENIED;
                }
            } else {
                return STORY_INVALID_SID;
            }
        }

        if( $mode == 'editsubmission' )
        {
            if (isset ($_CONF['draftflag'])) {
                $this->_draft = $_CONF['draftflag'];
            } else {
                $this->_draft = 1;
            }
            if (isset ($_CONF['show_topic_icon'])) {
                $this->_show_topic_icon = $_CONF['show_topic_icon'];
            } else {
                $this->_show_topic_icon = 1;
            }
            $this->_commentcode = $_CONF['comment_code'];
            $this->_trackbackcode = $_CONF['trackback_code'];
            $this->_featured = 0;
            $this->_expire = '0000-00-00 00:00:00';
            $this->_expiredate = 0;
            if (DB_getItem ($_TABLES['topics'], 'archive_flag',
                    "tid = '{$this->_tid}'") == 1) {
                $this->_frontpage = 0;
            } else if (isset($_CONF['frontpage'])) {
                $this->_frontpage = $_CONF['frontpage'];
            } else {
                $this->_frontpage = 1;
            }
            $this->_comments = 0;
            $this->_trackbacks = 0;
            $this->_numemails = 0;
            $this->_statuscode = 0;
            $this->_owner_id = $this->_uid;
        }
        $this->_sanitizeData();
        return STORY_LOADED_OK;
    }

    /**
     * Saves the story in it's final state to the database.
     *
     * Handles all the SID magic etc.
     * @return Integer status result from a constant list.
     */
    function saveToDatabase()
    {
        global $_TABLES;

        if (DB_getItem ($_TABLES['topics'], 'tid', "archive_flag=1") == $this->_tid) {
            $this->_featured = 0;
            $this->_frontpage = 0;
            $this->_statuscode = STORY_ARCHIVE_ON_EXPIRE;
        }

        /* if a featured, non-draft, that goes live straight away, unfeature
         * other stories:
         */
        if ($this->_featured == '1') {
            // there can only be one non-draft featured story
            if ($this->_draft_flag == 0 AND $this->_date <= time()) {
                $id[1] = 'featured';
                $values[1] = 1;
                $id[2] = 'draft_flag';
                $values[2] = 0;
                DB_change($_TABLES['stories'],'featured','0',$id,$values);
            }
        }

        $oldArticleExists = false;
        $currentSidExists = false;

        /* Fix up old sid => new sid stuff */
        if( $this->_sid != $this->_originalSid )
        {
            /* The sid has changed. Load from request will have
             * ensured that if the new sid exists an error has
             * been thrown, but we need to know if the old sid
             * actually existed (as opposed to being a generated
             * sid that was then thrown away) to reduce the sheer
             * number of SQL queries we do.
             */
            $checksid = addslashes($this->_originalSid);
            $newsid = addslashes($this->_sid);

            $sql = "SELECT 1 FROM {$_TABLES['stories']} WHERE sid='{$checksid}'";
            $result = DB_query( $sql );
            if( $result && ( DB_numRows( $result ) > 0 ) )
            {
                $oldArticleExists = true;
            }


            if( $oldArticleExists )
            {
                /* Move Comments */
                $sql = "UPDATE {$_TABLES['comments']} SET sid='$newsid' WHERE type='article' AND sid='$checksid'";
                DB_query( $sql );

                /* Move Images */
                $sql = "UPDATE {$_TABLES['article_images']} SET ai_sid = '{$newsid}' WHERE ai_sid = '{$checksid}'";
                DB_query( $sql );

                /* Move trackbacks */
                $sql = "UPDATE {$_TABLES['trackback']} SET sid='{$newsid}' WHERE sid='{$checksid}' AND type='article'";
                DB_query( $sql );
            }

        }

        /* Acquire Comment Count */
        $sql = "SELECT count(1) FROM {$_TABLES['comments']} WHERE type='article' AND sid='{$this->_sid}'";
        $result = DB_query($sql);
        if( $result && ( DB_numRows( $result) == 1 ) )
        {
            $array = DB_fetchArray( $result );
            $this->_comments = $array[0];
        } else {
            $this->_comments = 0;
        }

        /* Format dates for storage: */
        $this->_date = date( 'Y-m-d H:i:s', $this->_date );
        $this->_expire = date( 'Y-m-d H:i:s', $this->_expire );

        // Get the related URLs
        $this->_related = implode ("\n",
                        STORY_extractLinks ("{$this->_introtext} {$this->_bodytext}"));
        $this->_in_transit = 1;
        $sql = 'REPLACE INTO '.$_TABLES['stories'].' (';
        $values = ' VALUES (';
        reset( $this->_dbFields );
        while( $fieldname = each( $this->_dbFields ) )
        {
            $varname = '_'.$fieldname[1];
            $sql .= $fieldname[1].', ';
            $values .= '\''.addslashes($this->{$varname}).'\', ';
        }
        $sql = substr($sql, 0, strlen($sql) - 2 );
        $values = substr($values, 0, strlen($values) - 2);
        $sql .= ') '.$values.')';

        DB_query( $sql );

        /* Clean up the old story */
        if( $oldArticleExists )
        {
            $sql = "DELETE FROM {$_TABLES['stories']} WHERE sid='$checksid'";
            DB_query( $sql );
        }

        if( $this->_type == 'submission' )
        {
            /* there might be a submission, clean it up */
            DB_delete ($_TABLES['storysubmission'], 'sid', $checksid);
        }

        return STORY_SAVED;
    }

    /**
     * Loads a story from the post data. This is the most exciting function in
     * the whole entire world. First it'll clean up that horrible Magic Quotes
     * crap. Then it'll do all Geeklog's funky security stuff, anti XSS, anti
     * SQL Injection. Yay.
     */
    function loadFromRequest($post=true)
    {
        global $_TABLES;
        // Acquire source of post:
        if( $post )
        {
            $array = $_POST;
        } else {
            $array = $_GET;
        }

        // Handle Magic GPC Garbage:
        while( list($key, $value) = each($array) )
        {
            $array[$key] = COM_stripslashes($value);
        }

        /* Load the trivial stuff: */
        $this->_loadBasics($array);

        /* Check to see if we have permission to edit this sid, and that this
         * sid is not a duplicate or anything horrible like that. ewww.
         */
        $sql = 'SELECT owner_id, group_id, perm_owner, perm_group, perm_members, perm_anon '
                .' FROM '.$_TABLES['stories'].' WHERE sid=\''.$this->_sid.'\'';
        $result = DB_Query( $sql );
        if( $result && ( DB_numRows($result) > 0 ) )
        {
            /* Sid exists! Is it our article? */
            if( $this->_sid != $this->_originalSid )
            {
                return STORY_DUPLICATE_SID;
            }

            $article = DB_fetchArray( $result );
            /* Check Security */
            if( SEC_hasAccess( $result['owner_id'], $result['group_id'],
                                $result['perm_owner'], $result['perm_group'],
                                $result['perm_members'], $result['perm_anon'] ) < 3 )
            {
                return STORY_EXISTING_NO_EDIT_PERMISSION;
            }
        }

        $access = SEC_hasAccess( $this->_owner_id, $this->_group_id,
                                $this->_perm_owner, $this->_perm_group,
                                $this->_perm_members, $this->_perm_anon );
        if( ( $access < 3 ) || !SEC_hasTopicAccess( $this->_tid )
            || !SEC_inGroup( $this->_group_id) )
        {
            return STORY_NO_ACCESS_PARAMS;
        }

        //$title = COM_stripSlashes( $array['title'] );
        //$intro = COM_stripSlashes( $array['introtext'] );
        //$body = COM_stripSlashes( $array['bodytext'] );

        /* Then load the title, intro and body */
        if( ( $array['postmode'] == 'html' ) ||
            ( $array['postmode'] == 'adveditor' ) ||
            ( $array['postmode'] == 'wikitext' ))
        {
            $this->_htmlLoadStory( $array['title'], $array['introtext'], $array['bodytext'] );
            if( $this->_postmode == 'adveditor' )
            {
                $this->_advanced_editor_mode = 1;
                $this->_postmode = 'html';
            } else {
                $this->_advanced_editor_mode = 0;
            }
        } else {
            $this->_advanced_editor_mode = 0;
            $this->_plainTextLoadStory( $array['title'], $array['introtext'], $array['bodytext'] );
        }

        if( empty( $this->_title ) || empty( $this->_introtext ) )
        {
            return STORY_EMPTY_REQUIRED_FIELDS;
        }

        $this->_sanitizeData();

        return STORY_LOADED_OK;
    }

    /**
     * Sets up basic data for a new user submission story
     *
     * @param   string   Topic the user picked before heading to submission
     */
    function initSubmission($topic)
    {
        global $_USER, $_CONF, $_TABLES;
        if( isset( $_USER['uid'] ) && ( $_USER['uid'] > 1 ) )
        {
            $this->_uid = $_USER['uid'];
        } else {
            $this->_uid = 1;
        }

        $this->_postmode = $_CONF['postmode'];
        // If a topic has been specified, use it, if permitted
        // otherwise, fall back to the default permitted topic.
        // if we still don't have one...

        // Have we specified a permitted topic?
        if (!empty ($topic)) {
            $allowed = DB_getItem ($_TABLES['topics'], 'tid',
                "tid = '" . addslashes ($topic) . "'" . COM_getTopicSql ('AND'));

            if ($allowed != $topic)
            {
                $topic = '';
            }
        }

        // Do we now not have a topic?
        if( empty( $topic ) )
        {
          // Get default permitted:
          $topic = DB_getItem( $_TABLES['topics'], 'tid', 'is_default = 1' . COM_getPermSQL('AND') );
        }

        // Use what we have:
        $this->_tid = $topic;
    }

    /**
     * Loads a submitted story from postdata
     */
    function loadSubmission()
    {
        $array = $_POST;
        // Handle Magic GPC Garbage:
        while( list($key, $value) = each($array) )
        {
            $array[$key] = COM_stripslashes($value);
        }

        $this->_postmode = COM_applyFilter( $array['postmode'] );
        $this->_sid = COM_applyFilter( $array['sid'] );
        $this->_uid = COM_applyFilter( $array['uid'], true );
        $this->_unixdate = COM_applyFilter( $array['date'], true );

        /* Then load the title, intro and body */
        if( ( $array['postmode'] == 'html' ) || ( $array['postmode'] == 'adveditor' ) )
        {
            $this->_htmlLoadStory( $array['title'], $array['introtext'], $array['bodytext'] );
            if( $this->_postmode == 'adveditor' )
            {
                $this->_advanced_editor_mode = 1;
                $this->_postmode = 'html';
            } else {
                $this->_advanced_editor_mode = 0;
            }
        } else {
            $this->_advanced_editor_mode = 0;
            $this->_plainTextLoadStory( $array['title'], $array['introtext'], $array['bodytext'] );
        }

        $this->_tid = COM_applyFilter( $array['tid'] );

        if( empty( $this->_title ) || empty( $this->_introtext ) )
        {
            return STORY_EMPTY_REQUIRED_FIELDS;
        }

        return STORY_LOADED_OK;
    }

    /**
     * Returns a story formatted for spam check:
     *
     * @return  string Story formatted for spam check.
     */
    function GetSpamCheckFormat()
    {
        return "<h1>{$this->_title}</h1><p>{$this->_introtext}</p><p>{$this->_bodytext}</p>";
    }

    /**
     * Saves a story submission.
     *
     * @return  integer result code explaining behaviour.
     */
    function saveSubmission()
    {
        global $_USER, $_CONF, $_TABLES;
        $this->_sid = COM_makeSid();

        if( isset( $_USER['uid'] ) && ( $_USER['uid'] > 1 ) )
        {
            $this->_uid = $_USER['uid'];
        } else {
            $this->_uid = 1;
        }

        $tmptid = addslashes( COM_sanitizeID( $this->_tid ) );

        $result = DB_query ("SELECT group_id,perm_owner,perm_group,perm_members,perm_anon FROM {$_TABLES['topics']} WHERE tid = '{$tmptid}'" . COM_getTopicSQL ('AND'));
        if (DB_numRows ($result) == 0) {
            // user doesn't have access to this topic - bail
            return STORY_NO_ACCESS_TOPIC;
        }
        $T = DB_fetchArray ($result);

        if (($_CONF['storysubmission'] == 1) && !SEC_hasRights ('story.submit')) {
            $this->_sid = addslashes( $this->_sid );
            $this->_tid = $tmptid;
            $this->_title = addslashes( $this->_title );
            $this->_introtext = addslashes( $this->_introtext );
            $this->_bodytext = addslashes( $this->_bodytext );
            $this->_postmode = addslashes( $this->_postmode );
            DB_save ($_TABLES['storysubmission'],
                'sid,tid,uid,title,introtext,bodytext,date,postmode',
                "{$this->_sid},'{$this->_tid}',{$this->_uid},'{$this->_title}','{$this->_introtext}','{$this->_bodytext}',NOW(),'{$this->_postmode}'");

            return STORY_SAVED_SUBMISSION;
        } else {
            // post this story directly. First establish the necessary missing data.
            $this->_sanitizeData();

            if (!isset ($_CONF['show_topic_icon'])) {
                $_CONF['show_topic_icon'] = 1;
            }
            if (DB_getItem ($_TABLES['topics'], 'archive_flag',
                    "tid = '{$tmptid}'") == 1) {
                $this->_frontpage = 0;
            } else if (isset ($_CONF['frontpage'])) {
                $this->_frontpage = $_CONF['frontpage'];
            } else {
                $this->_frontpage = 1;
            }

            $this->_oldsid = $this->_sid;
            $this->_date = mktime();
            $this->_commentcode = $_CONF['comment_code'];
            $this->_trackbackcode = $_CONF['trackback_code'];
            $this->_show_topic_icon = $_CONF['show_topic_icon'];
            $this->_group_id = $T['group_id'];
            $this->_perm_owner = $T['perm_owner'];
            $this->_perm_group = $T['perm_group'];
            $this->_perm_members = $T['perm_members'];
            $this->_perm_anon = $T['perm_anon'];

            $this->saveToDatabase();

            COM_rdfUpToDateCheck ();
            COM_olderStuff ();

            return STORY_SAVED;
        }
    }

    /**
     * Inserts image HTML into the place of Image Placeholders for stories
     * with images.
     *
     * @return array    containing errors, or empty.
     */
    function insertImages()
    {
        global $_CONF, $_TABLES, $LANG24;

        $result = DB_query("SELECT ai_filename FROM {$_TABLES['article_images']} WHERE ai_sid = '{$this->_sid}' ORDER BY ai_img_num");
        $nrows = DB_numRows($result);
        $errors = array();
        $stdImageLoc = true;
        if( !strstr( $_CONF['path_images'], $_CONF['path_html'] ) )
        {
            $stdImageLoc = false;
        }

        for( $i = 1; $i <= $nrows; $i++ )
        {
            $A = DB_fetchArray($result);

            $lLinkPrefix = '';
            $lLinkSuffix = '';
            if ($_CONF['keep_unscaled_image'] == 1) {
                $lFilename_large = substr_replace ($A['ai_filename'], '_original.',
                        strrpos ($A['ai_filename'], '.'), 1);
                $lFilename_large_complete = $_CONF['path_images'] . 'articles/'
                                          . $lFilename_large;
                if ($stdImageLoc) {
                    $imgpath = substr ($_CONF['path_images'],
                                       strlen ($_CONF['path_html']));
                    $lFilename_large_URL = $_CONF['site_url'] . '/' . $imgpath
                                         . 'articles/' . $lFilename_large;
                } else {
                    $lFilename_large_URL = $_CONF['site_url']
                        . '/getimage.php?mode=show&amp;image=' . $lFilename_large;
                }
                if (file_exists ($lFilename_large_complete)) {
                    $lLinkPrefix = '<a href="' . $lFilename_large_URL
                                 . '" title="' . $LANG24[57] . '">';
                    $lLinkSuffix = '</a>';
                }
            }

            $sizeattributes = COM_getImgSizeAttributes ($_CONF['path_images'] . 'articles/' . $A['ai_filename']);

            $norm  = '[image' . $i . ']';
            $left  = '[image' . $i . '_left]';
            $right = '[image' . $i . '_right]';

            $unscalednorm  = '[unscaled' . $i . ']';
            $unscaledleft  = '[unscaled' . $i . '_left]';
            $unscaledright = '[unscaled' . $i . '_right]';

            // Grab member vars into locals:
            $intro = $this->_introtext;
            $body = $this->_bodytext;
            $fulltext = "$intro $body";

            $icount = substr_count($fulltext, $norm) + substr_count($fulltext, $left) + substr_count($fulltext, $right);
            $icount = $icount + substr_count($fulltext, $unscalednorm) + substr_count($fulltext, $unscaledleft) + substr_count($fulltext, $unscaledright);
            if ($icount == 0)
            {
                // There is an image that wasn't used, create an error
                $errors[] = $LANG24[48] . " #$i, {$A['ai_filename']}, " . $LANG24[53];
            } else {
                // Only parse if we haven't encountered any error to this point
                if (count($errors) == 0) {
                    if ($stdImageLoc)
                    {
                        $imgpath = substr ($_CONF['path_images'],
                                           strlen ($_CONF['path_html']));
                        $imgSrc = $_CONF['site_url'] . '/' . $imgpath . 'articles/'
                                . $A['ai_filename'];
                    } else {
                        $imgSrc = $_CONF['site_url'] . '/getimage.php?mode=articles&amp;image=' . $A['ai_filename'];
                    }
                    $intro = str_replace($norm, $lLinkPrefix . '<img ' . $sizeattributes . 'src="' . $imgSrc . '" alt="">' . $lLinkSuffix, $intro);
                    $body = str_replace($norm, $lLinkPrefix . '<img ' . $sizeattributes . 'src="' . $imgSrc . '" alt="">' . $lLinkSuffix, $body);
                    $intro = str_replace($left, $lLinkPrefix . '<img ' . $sizeattributes . 'align="left" src="' . $imgSrc . '" alt="">' . $lLinkSuffix, $intro);
                    $body = str_replace($left, $lLinkPrefix . '<img ' . $sizeattributes . 'align="left" src="' . $imgSrc . '" alt="">' . $lLinkSuffix, $body);
                    $intro = str_replace($right, $lLinkPrefix . '<img ' . $sizeattributes . 'align="right" src="' . $imgSrc . '" alt="">' . $lLinkSuffix, $intro);
                    $body = str_replace($right, $lLinkPrefix . '<img ' . $sizeattributes . 'align="right" src="' . $imgSrc . '" alt="">' . $lLinkSuffix, $body);

                    if (($_CONF['allow_user_scaling'] == 1) and
                        ($_CONF['keep_unscaled_image'] == 1)) {

                        if (file_exists ($lFilename_large_complete)) {
                            $imgSrc = $lFilename_large_URL;
                            $sizeattributes = COM_getImgSizeAttributes ($lFilename_large_complete);
                        }
                        $intro = str_replace($unscalednorm, '<img ' . $sizeattributes . 'src="' . $imgSrc . '" alt="">', $intro);
                        $body = str_replace($unscalednorm, '<img ' . $sizeattributes . 'src="' . $imgSrc . '" alt="">', $body);
                        $intro = str_replace($unscaledleft, '<img ' . $sizeattributes . 'align="left" src="' . $imgSrc . '" alt="">', $intro);
                        $body = str_replace($unscaledleft, '<img ' . $sizeattributes . 'align="left" src="' . $imgSrc . '" alt="">', $body);
                        $intro = str_replace($unscaledright, '<img ' . $sizeattributes . 'align="right" src="' . $imgSrc . '" alt="">', $intro);
                        $body = str_replace($unscaledright, '<img ' . $sizeattributes . 'align="right" src="' . $imgSrc . '" alt="">', $body);
                    }

                }
            }
        }

        return $errors;
    }

    /**
     * Return the SID in a clean way
     *
     * @param $fordb    boolean True if we want an 'addslashes' version for the db
     */
    function getSid( $fordb=false )
    {
        if( $fordb )
        {
            return addslashes($this->_sid);
        } else {
            return $this->_sid;
        }
    }

    /**
     * Get the access level
     */
    function getAccess()
    {
        return $this->_access;
    }

    /**
     * Provide access to story elements. For the editor.
     *
     * This is a peudo-property, implementing a getter for story
     * details as if as an associative array. Personally, I'd
     * rather be able to assign getters and setters to actual
     * properties to mask controlled access to private member
     * variables. But, you get what you get with PHP. So here it
     * is in all it's nastyness.
     *
     * @param   string  $item   Item to fetch.
     * @return  mixed   The clean and ready to use (in edit mode) value requested.
     */
    function EditElements($item='title')
    {
        if (empty ($this->_expire) || (date('Y', strtotime($this->_expire)) < 2000)) {
            $this->_expire = time();
        }
        switch(strtolower($item))
        {
            case 'unixdate':
                $return = strtotime($this->_date);
                break;
            case 'expirestamp':
                $return = strtotime($this->_expire);
                break;
            case 'publish_hour':
                $return = date('H', $this->_date);
                break;
            case 'publish_month':
                $return = date('m', $this->_date);
                break;
            case 'publish_day':
                $return = date('d', $this->_date);
                break;
            case 'publish_year':
                $return = date('Y', $this->_date);
                break;
            case 'public_hour':
                $return = date('H', $this->_date);
                break;
            case 'publish_minute':
                $return = date('i', $this->_date);
                break;
            case 'publish_second':
                $return = date('s', $this->_date);
                break;
            case 'expire_second':
                $return = date('s', $this->_expire);
                break;
            case 'expire_minute':
                $return = date('i', $this->_expire);
                break;
            case 'expire_hour':
                $return = date('H', $this->_expire);
                break;
            case 'expire_day':
                $return = date('d', $this->_expire);
                break;
            case 'expire_month':
                $return = date('m', $this->_expire);
                break;
            case 'expire_year':
                $return = date('Y', $this->_expire);
                break;
            case 'title':
                $return = $this->_title;//htmlspecialchars($this->_title);
                break;
            case 'draft':
                if(isset($this->_draft) && ($this->_draft == 1))
                {
                    $return = true;
                } else {
                    $return = false;
                }
                break;
            case 'introtext':
                $return = $this->_editText($this->_introtext);
                break;
            case 'bodytext':
                $return = $this->_editText($this->_bodytext);
                break;
            default:
                $varname = '_'.$item;
                if( isset($this->{$varname}) )
                {
                    $return = $this->{$varname};
                } else {
                    $return = '';
                }
                break;
        }
        return $return;
    }


    /**
     * Provide access to story elements. For display.
     *
     * This is a peudo-property, implementing a getter for story
     * details as if as an associative array. Personally, I'd
     * rather be able to assign getters and setters to actual
     * properties to mask controlled access to private member
     * variables. But, you get what you get with PHP. So here it
     * is in all it's nastyness.
     *
     * @param   string  $item   Item to fetch.
     * @return  mixed   The clean and ready to use value requested.
     */
    function DisplayElements($item='title')
    {
        global $_CONF;

        $return = '';
        switch(strtolower($item))
        {
            case 'introtext':
                if( $this->_postmode == 'plaintext' )
                {
                    $return = nl2br( $this->_introtext );
                } else if ( $this -> _postmode == 'wikitext' ) {
                    require_once 'Text/Wiki.php';
                    $wiki =& new Text_Wiki();
                    $wiki->disableRule('wikilink');
                    $wiki->disableRule('freelink');
                    $wiki->disableRule('interwiki');
                    $return = $this->_editUnescape($this->_introtext);
                    $return = $wiki->transform($return, 'Xhtml');
                } else {
                    $return = $this->_introtext;
                }
                $return = PLG_replaceTags( $this->_displayEscape($return) );
                break;
            case 'bodytext':
                if( ( $this->_postmode == 'plaintext' ) && !( empty( $this->_bodytext ) ) )
                {
                    $return = nl2br( $this->_bodytext );
                } else if ( ( $this->_postmode == 'wikitext' ) && !( empty( $this->_bodytext ) ) ) {
                    require_once 'Text/Wiki.php';
                    $wiki =& new Text_Wiki();
                    $wiki->disableRule('wikilink');
                    $wiki->disableRule('freelink');
                    $wiki->disableRule('interwiki');
                    $return = $this->_editUnescape($this->_bodytext);
                    $return = $wiki->transform($return, 'Xhtml');
                } else if ( !empty( $this->_bodytext )) {
                    $return = $this->_displayEscape( $this->_bodytext );
                }
                $return = PLG_replaceTags($return);
                break;
            case 'title':
                $return = $this->_displayEscape( $this->_title );
                break;
            case 'shortdate':
                $return = strftime( $_CONF['shortdate'], $this->_date );
                break;
            case 'dateonly':
                $return = strftime( $_CONF['dateonly'], $this->_date );
                break;
            case 'date':
                $return = COM_getUserDateTimeFormat( $this->_date );
                $return = $return[0];
                break;
            case 'hits':
                $return = COM_NumberFormat( $this->_hits );
                break;
            case 'topic':
                $return = htmlspecialchars( $this->_topic );
                break;
            case 'expire':
                if( empty($this->_expire) )
                {
                    $return = time();
                } else {
                    // Need to convert text date/time to a timestamp
                    $return = explode( ' ', $this->_expire );
                    $return = COM_convertDate2Timestamp( $return[0], $return[1] );
                }
                break;
            default:
                $varname = '_'.$item;
                if( isset($this->{$varname}) )
                {
                    $return = $this->{$varname};
                }
                break;
        }

        return $return;
    }

    /**
     * Set the TID to a new value.
     *
     * @param   $tid    int ID of the topic to set
     */
    function setTid($tid)
    {
        $this->_tid = $tid;
    }

    /**
     * Perform a security check and return permission level.
     *
     * saves the bother of accessing dozen's of vars.
     *
     * @return  int access level for this story
     */
    function checkAccess()
    {
        global $_CONF;
        require_once($_CONF['path_system'] .'lib-security.php');
        return SEC_hasAccess($this->_owner_id, $this->_group_id,
                $this->_perm_owner, $this->_perm_group, $this->_perm_members,
                $this->_perm_anon);
    }


    // End Public Methods.

    // Private Methods:

    /**
     * Escapes certain HTML for nicely encoded HTML.
     *
     * @access Private
     * @param   string     $in      Text to escpae
     * @return  string     escaped string
     */
    function _displayEscape($in)
    {
        $return = str_replace( '$', '&#36;', $in );
        $return = str_replace( '{', '&#123;', $return );
        $return = str_replace( '}', '&#125;', $return );
        return $return;
    }

    /**
     * Unescapes certain HTML for editing again.
     *
     * @access Private
     * @param   string  $in Text escaped to unescape for editing
     * @return  string  Unescaped string
     */
    function _editUnescape($in)
    {
        if( ($this->_postmode == 'html') || ($this->_postmode == 'wikitext'))
        {
            // Standard named items, plus the three we do in _displayEscape and
            // others I know off-hand.
            //$replacefrom = array('&lt;', '&gt;', '&amp;', '&#36;', '&#123;', '&#125', '&#92;');
            //$replaceto = array('<', '>', '&', '$', '{', '}', '\\');
            //$return = str_replace($replacefrom, $replaceto, $in);
            //return $return;
            return html_entity_decode($in);
        } else {
            // advanced editor or plaintext can handle themselves...
            return $in;
        }
    }

    /**
     * Returns text ready for the edit fields.
     *
     * @access Private
     * @param   string  $in Text to prepare for editing
     * @return  string  Escaped String
     */
    function _editText($in)
    {
        $out = '';
        if($this->_postmode == 'plaintext')
        {
            $out = COM_undoClickableLinks($in);
            $out = $this->_displayEscape($out);
        } else if ($this->_postmode == 'wikitext') {
            $out = $this->_editUnescape($in);
        } else {
            // html
            $out = str_replace('<pre><code>','[code]', $in);
            $out = str_replace('</code></pre>','[/code]', $out);
            $out = $this->_editUnescape($out);
            $out = $this->_displayEscape(htmlspecialchars($out));
        }

        return $out;
    }

    /**
     * Loads the basic details of an article into the internal
     * variables, cleaning them up nicely.
     * @access Private
     * @param $array Array of POST/GET data (by ref).
     * @return Nothing.
     */
    function _loadBasics( &$array )
    {
        /* For the really, really basic stuff, we can very easily load them
         * based on an array that defines how to COM_applyFilter them.
         */
        while( list($key, $value) = each($this->_postFields) )
        {
            $varname = $value[1];
            // If we have a value
            if( array_key_exists($key, $array) )
            {
                // And it's alphanumeric or numeric, filter it and use it.
                if( ($value[0] == STORY_AL_ALPHANUM) || ($value[0] == STORY_AL_NUMERIC) )
                {
                    $this->{$varname} = COM_applyFilter( $array[$key], $value[0] );
                } else if( $array[$key] == 'on' ) {
                    // If it's a checkbox that is on
                    $this->{$varname} = 1;
                } else {
                    // Otherwise, it must be a checkbox that is off:
                    $this->{$varname} = 0;
                }
            } else if( ($value[0] == STORY_AL_NUMERIC) || ($value[0] == STORY_AL_CHECKBOX) ) {
                // If we don't have a value, and have a numeric or text box, default to 0
                $this->{$varname} = 0;
            }
        }
        /* SID's are a specialcase: */
        $sid = COM_sanitizeID( $array['sid'] );
        $oldsid = COM_sanitizeID( $array['old_sid'] );
        if( empty( $sid ) )
        {
            $sid = $oldsid;
        }
        if( empty( $sid ) )
        {
            $sid = COM_makeSid();
        }

        $this->_sid = $sid;
        $this->_originalSid = $oldsid;

        /* Need to deal with the postdate and expiry date stuff */
        $publish_ampm = COM_applyFilter ($array['publish_ampm']);
        $publish_hour = COM_applyFilter ($array['publish_hour'], true);
        $publish_minute = COM_applyFilter ($array['publish_minute'], true);
        $publish_second = COM_applyFilter ($array['publish_second'], true);
        if ($publish_ampm == 'pm') {
            if ($publish_hour < 12) {
                $publish_hour = $publish_hour + 12;
            }
        }
        if ($publish_ampm == 'am' AND $publish_hour == 12) {
            $publish_hour = '00';
        }
        $publish_year = COM_applyFilter ($array['publish_year'], true);
        $publish_month = COM_applyFilter ($array['publish_month'], true);
        $publish_day = COM_applyFilter ($array['publish_day'], true);
        $this->_date = strtotime("$publish_month/$publish_day/$publish_year $publish_hour:$publish_minute:$publish_second");

        $archiveflag = 0;
        if (isset ($array['archiveflag'])) {
            $archiveflag = COM_applyFilter ($array['archiveflag'], true);
        }
        /* Override status code if no archive flag is set: */
        if ($archiveflag != 1) {
            $this->_statuscode = 0;
        }

        $expire_ampm = COM_applyFilter ($array['expire_ampm']);
        $expire_hour = COM_applyFilter ($array['expire_hour'], true);
        $expire_minute = COM_applyFilter ($array['expire_minute'], true);
        $expire_second = COM_applyFilter ($array['expire_second'], true);
        $expire_year = COM_applyFilter ($array['expire_year'], true);
        $expire_month = COM_applyFilter ($array['expire_month'], true);
        $expire_day = COM_applyFilter ($array['expire_day'], true);

        if (isset ($expire_hour))  {
            if ($expire_ampm == 'pm') {
                if ($expire_hour < 12) {
                    $expire_hour = $expire_hour + 12;
                }
            }
            if ($expire_ampm == 'am' AND $expire_hour == 12) {
                $expire_hour = '00';
            }
            $expiredate = strtotime("$expire_month/$expire_day/$expire_year $expire_hour:$expire_minute:$expire_second");
        } else {
            $expiredate = time();
        }
        $this->_expire = $expiredate;

        /* Then grab the permissions */
        // Convert array values to numeric permission values
        list( $this->_perm_owner, $this->_perm_group, $this->_perm_members,
                $this->_perm_anon) = SEC_getPermissionValues(
                    $array['perm_owner'], $array['perm_group'],
                    $array['perm_members'], $array['perm_anon'] );
    }

    /**
     * This is the importantest bit. This function must load the title, intro
     * and body of the article from the post array, providing all appropriate
     * conversions of HTML mode content into the nice safe form that geeklog
     * can then (simply) spit back out into the page on render. After doing a
     * magic tags replacement.
     *
     * This DOES NOT ADDSLASHES! We do that on DB store, because we want to
     * keep our internal variables in "display mode", not in db mode or anything.
     *
     * @param $title    string  posttitle, only had stripslashes if necessary
     * @param $intro    string  introtext, only had stripslashes if necessary
     * @param $body     string   bodytext, only had stripslashes if necessary
     * @return nothing
     * @access private
     */
    function _htmlLoadStory( $title, $intro, $body )
    {
        global $_CONF;
        // fix for bug in advanced editor
        if ($_CONF['advanced_editor'] && ($body == '<br>')) {
            $body = '';
        }
        $this->_title = htmlspecialchars( strip_tags( COM_checkWords( $title ) ) );
        $this->_introtext = COM_checkHTML( COM_checkWords( $intro ) );
        $this->_bodytext = COM_checkHTML( COM_checkWords( $body ) );
    }


    /**
     * This is the second most importantest bit. This function must load the
     * title, intro and body of the article from the post array, removing all
     * HTML mode content into the nice safe form that geeklog can then (simply)
     * spit back out into the page on render. After doing a magic tags
     * replacement. And nl2br.
     *
     * This DOES NOT ADDSLASHES! We do that on DB store, because we want to
     * keep our internal variables in "display mode", not in db mode or anything.
     *
     * @param $title    string  posttitle, only had stripslashes if necessary
     * @param $intro    string  introtext, only had stripslashes if necessary
     * @param $body     string   bodytext, only had stripslashes if necessary
     * @return nothing
     * @access private
     */
    function _plainTextLoadStory( $title, $intro, $body )
    {
        $this->_title = htmlspecialchars(strip_tags(COM_checkWords($title)));
        $this->_introtext = COM_makeClickableLinks( htmlspecialchars( COM_checkWords( $intro ) ) ) ;
        $this->_bodytext = COM_makeClickableLinks( htmlspecialchars( COM_checkWords( $body ) ) ) ;
    }

    /**
     * Perform some basic cleanups of data, dealing with empty required,
     * defaultable fields.
     */
    function _sanitizeData()
    {
        if( empty( $this->_hits ) )
        {
            $this->_hits = 0;
        }
        if( empty( $this->_commentcount ) )
        {
            $this->_comments = 0;
        }
        if( empty( $this->_numemails ) )
        {
            $this->_numemails = 0;
        }
        if( empty( $this->_trackbacks ) )
        {
            $this->_trackbacks = 0;
        }
        if( $this->_draft == 'on' )
        {
            $this->_draft = 1;
        } else if( empty( $this->_draft ) )
        {
            $this->_draft = 0;
        }
        if( $this->_show_topic_icon == 'on' )
        {
            $this->_show_topic_icon = 1;
        } elseif( empty( $this->_show_topic_icon ) )
        {
            $this->_show_topic_icon = 0;
        }
    }
    // End Private Methods.

    /**************************************************************************/
}

if (!function_exists('html_entity_decode')) {
    /**
     * html_entity_decode()
     *
     * Convert all HTML entities to their applicable characters
     * This function is a fallback if html_entity_decode isn't defined
     * in the PHP version used (i.e. PHP < 4.3.0).
     * Please note that this function doesn't support all parameters
     * of the original html_entity_decode function.
     *
     * Function borrowed from postnuke, under the GPL.
     *
     * @param  string $string the this function converts all HTML entities to their applicable characters from string.
     * @return the converted string
     * @link http://php.net/html_entity_decode The documentation of html_entity_decode
     **/
    function html_entity_decode($string)
    {
        $trans_tbl = get_html_translation_table(HTML_ENTITIES);
        $trans_tbl = array_flip($trans_tbl);
        return (strtr($string, $trans_tbl));
    }
}
?>