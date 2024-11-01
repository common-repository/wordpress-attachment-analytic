<?PHP
/*
Plugin Name: Attachment Analytic
Plugin URI: http://www.expbuilder.com/wordpress-attachment-analytic-v1-0.html
Description: Get the views and downloads count for your attachments
Version: 1.0.0
Author: Ahmed Hassan Elkadrey
Author URI: http://www.expbuilder.com
License: A "Slug" license name e.g. GPL2

    Copyright 2011  Ahmed Hassan Elakdrey  (email : webmaster@expbuilder.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
session_start();

function installAna()
{
    global $wpdb;
    mysql_query('ALTER TABLE `'.$wpdb->prefix.'posts` ADD `view` BIGINT(30) NOT NULL,ADD `download` BIGINT(30) NOT NULL ;');
}
function unstallAna()
{
    global $wpdb;
    mysql_query('ALTER TABLE `'.$wpdb->prefix.'posts` DROP `view`, DROP `download`');
}
register_activation_hook(__FILE__, 'installAna');
register_deactivation_hook(__FILE__, 'unstallAna');

add_filter('the_content', 'showAttachment');
function showAttachment($content)
{
    global $id;
    $attachmentTemplate = getAttachment($id);

    return $content.$attachmentTemplate;
}

function getAttachment($POSTID)
{
    global $wpdb;
    $lang = get_bloginfo('language');
    if(!file_exists("wp-content/plugins/attachment_analytic/languages/".$lang.".php"))
    {
        $lang = 'en';
    }
    include  "languages/".$lang.".php";

    $postTable = $wpdb->prefix."posts";
    $siteurl = get_bloginfo('siteurl');
    $sql = mysql_query("SELECT 	ID,view, download, post_title,guid from `".$postTable."` where post_parent='".$POSTID."' and post_type='attachment' ");
    if(mysql_num_rows($sql) > 0)
    {
        while($rs = mysql_fetch_array($sql))
        {
            $guid = explode(".", $rs['guid']);
            $path = end($guid);
            if(!file_exists('wp-content/plugins/attachment_analytic/icons/'.$path.'.png'))
            {
                $path = 'unknow';
            }
            $return .= '<div><img style="vertical-align: middle;" src="'.$siteurl.'/wp-content/plugins/attachment_analytic/icons/'.$path.'.png" alt="" /> <a href="javascript: void(0);" onclick="getAttachmentlink('.$rs['ID'].');">'.$rs['post_title'].'</a> ['.$_langs['views'].': '.$rs['view'].' - '.$_langs['clicks'].': '.$rs['download'].']</div>';
            if($_SESSION['views'][$rs['ID']] != 1)
            {
               $_SESSION['views'][$rs['ID']] = 1;
               $rs['view']++;
               mysql_query("update `".$postTable."` set view='".$rs['view']."' where ID='".$rs['ID']."' ");
            }
        }
        $returns = '<fieldset style="font-size: 90%;padding:5px;margin:5px;"><legend>'.$_langs['attachments'].'</legend>'.$return.'</fieldset>
        <form action="" target="_blank" id="downloadidfrm" method="post"><input type="hidden" id="downloadid" name="downloadid" value=""></form>
        <script type="text/javascript">
            function getAttachmentlink(id)
            {
                 document.getElementById("downloadid").value = id;
                 document.getElementById("downloadidfrm").submit();
            }
        </script>
        ';
        return $returns;
    }
}

if($_POST['downloadid'])
{
    $postTable = $wpdb->prefix."posts";
    $sql = mysql_query("SELECT 	guid,download from `".$postTable."` where ID='".$_POST['downloadid']."' and post_type='attachment' ");
    $rs = mysql_fetch_array($sql);

    if($_SESSION['download'][$_POST['downloadid']] != 1)
    {
       $_SESSION['download'][$_POST['downloadid']] = 1;
       $rs['download']++;
       mysql_query("update `".$postTable."` set download='".$rs['download']."' where ID='".$_POST['downloadid']."' ");
    }
    unset($_POST['downloadid']);
    header("location: ".$rs['guid']);
    die();
}
?>