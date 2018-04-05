<?php
/*
Plugin Name: CP Visit Statistics
Plugin URI: #
Description: Customized statistics plugin as per client requirement.
Author: Chandan Pradhan
Version: 1.0.0
Author URI: http://chandan.byethost11.com/
License: MIT
*/

/* create cookie on page load */
if(!defined( 'ABSPATH' )) die( 'Error' );
register_activation_hook( __FILE__, 'cp_stat_install' );
function cp_stat_install(){
    global $wpdb;
    $table_csi = $wpdb->prefix . 'cp_statistics_ip';
    $table_csi_data = $wpdb->prefix . 'cp_statistics_ip_data';
    $table_policy = $wpdb->prefix . 'cp_statistics_policy';
	$charset_collate = $wpdb->get_charset_collate();
    $sql_1 = "CREATE TABLE IF NOT EXISTS $table_csi ( `id` INT(11) NOT NULL AUTO_INCREMENT , `ip` VARCHAR(255) NOT NULL , `email` VARCHAR(255) NOT NULL ,  `num_visit` INT(11) NOT NULL ,  PRIMARY KEY (`id`)) $charset_collate;";

    $sql_2 = "CREATE TABLE IF NOT EXISTS $table_csi_data ( `id` INT(11) NOT NULL AUTO_INCREMENT , `ip_id` INT(11) NOT NULL , `post_id` INT(11) NOT NULL ,`uuid` VARCHAR(255) NOT NULL, `visit_time` DATETIME NOT NULL , `meta` LONGTEXT NOT NULL, PRIMARY KEY (`id`)) $charset_collate;";
    
    $sql_3 = "CREATE TABLE ".$table_policy." ( `id` INT(11) NOT NULL AUTO_INCREMENT , `popup_id` INT(11) NOT NULL , `page_id` INT(11) NOT NULL , `num` INT(11) NOT NULL , PRIMARY KEY (`id`)) $charset_collate;";
    
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    
	dbDelta( $sql_1 );
    dbDelta( $sql_2 );
    dbDelta( $sql_3 );
}

add_action('init','generate_stat');
function generate_stat(){
        
    require_once(__DIR__.'/UUID.php');
        $cookieData = isset($_COOKIE['cpvs']) ? $_COOKIE['cpvs'] :'';
        
        if($cookieData!=''){
            $Cdata = json_decode(base64_decode($cookieData));
            $_SESSION['cpvs_id'] = $Cdata->uuid;
            $_SESSION['first_visit'] = $Cdata->visit;

            //var_dump($Cdata);
        }else{
            
            $UUID_v4 =  UUID::v4();
            $dateVisit = time();

            $_SESSION['cpvs_id'] = $UUID_v4;
            $_SESSION['first_visit'] = $dateVisit;

            $cData = array(
                'uuid' => $UUID_v4 ,
                'visit' => $dateVisit
            );
            $expire = strtotime('+ 2 year');
            setcookie('cpvs',base64_encode(json_encode($cData)),$expire);
        }
    
    
        global $wpdb;
        global $post;
        global $wp_query;
        $table_csi = $wpdb->prefix . 'cp_statistics_ip';
        $table_csi_data = $wpdb->prefix . 'cp_statistics_ip_data';
        $table_policy = $wpdb->prefix . 'cp_statistics_policy';
        
        require_once(__DIR__.'/browser.php');
        $L_browser = new Browser();
        
        $browserPlatform = $L_browser->getPlatform();
        $browserName = $L_browser->getBrowser();
        $browserVersion = $L_browser->getVersion();
        $clientIP = $_SERVER['REMOTE_ADDR']?:($_SERVER['HTTP_X_FORWARDED_FOR']?:$_SERVER['HTTP_CLIENT_IP']);
        
        $cookieValueArr = array();
        $cookieValueArr['browserPlatform'] = $browserPlatform;
        $cookieValueArr['browserName'] = $browserName;
        $cookieValueArr['browserVersion'] = $browserVersion;
        //$cookieValueArr['clientIP'] = $clientIP;
        //$cookieValueArr['numVisit'] = 1;
        //$cookieValueArr['date'] = date('Y-m-d H:i:s');
       
        
        
        if(is_home() || is_front_page()){
            $postID = get_option( 'page_on_front' );
            
        }elseif(isset( $wp_query ) && (bool) $wp_query->is_posts_page){
            $postID = get_option( 'page_for_posts' );
        }else{
          $postID = $post->ID;  
        }
        $cookieValueArr['pageID'] = get_edit_post_link( $postID );
    
            
        $ipid = $wpdb->get_col("SELECT id FROM ".$table_csi." WHERE ip='".$clientIP."'"); 
    
        if(!$ipid){
            
            $sql_1 = "INSERT INTO ".$table_csi." SET ip='".$clientIP."',email='',num_visit='1'";
            $wpdb->query($sql_1);
            $ipid = $wpdb->insert_id;
            
            $sql_2 = "INSERT INTO ".$table_csi_data." SET ip_id='".$ipid."', post_id='".$postID."', uuid='".$_SESSION['cpvs_id']."', visit_time='".date('Y-m-d H:i:s',$_SESSION['first_visit'])."',meta='".json_encode($cookieValueArr)."'";
            $wpdb->query($sql_2);
        }else{
         
        $sql_select = "SELECT * FROM ".$table_csi." WHERE id='".$ipid[0]."'";
        $data = $wpdb->get_row($sql_select,ARRAY_A);
        
        $sql_1 = "UPDATE ".$table_csi." SET num_visit='".($data['num_visit']+1)."' WHERE ip='".$clientIP."'";
            $wpdb->query($sql_1); 
            
         //$sql_2 = "UPDATE ".$table_csi_data." SET ip_id='".$ipid[0]."', post_id='".$postID."',uuid='".$_SESSION['cpvs_id']."', visit_time='".$_SESSION['first_visit']."',meta='".json_encode($cookieValueArr)."'";
            //$wpdb->query($sql_2);   
        }
    
}
add_action('wp_enqueue_scripts','add_frontend_assets');
function add_frontend_assets(){
  wp_enqueue_script('cp-statistics',plugins_url('assets/js/cp-statistics.js', __FILE__),array('jquery'),'2.0.5',true); 
    
}

add_action('admin_enqueue_scripts','add_backend_assets');
function add_backend_assets(){
    wp_enqueue_script('table-shotter',plugins_url('assets/js/jquery.tablesorter.min.js', __FILE__),array('jquery'),'2.0.5');
    wp_enqueue_script('colorbox',plugins_url('assets/js/jquery.colorbox-min.js', __FILE__),array('jquery'),'2.0.5');
    
    wp_enqueue_style('cp-statistics',plugins_url('assets/css/cp-statistics.css', __FILE__));
    wp_enqueue_style('cp-colorbox',plugins_url('assets/css/colorbox.css', __FILE__));
    
    
}
add_action( 'admin_menu', 'admin_menu'  );
function admin_menu() {
        add_menu_page('CDesk', 'CDesk', 'manage_options', 'cp-visit-statistics','cp_statistics_settings_page',plugins_url( 'cp-visit-statistics/assets/images/cdesk-icon.png' ),6);
        add_submenu_page( 'cp-visit-statistics', 'Dashboard', 'Dashboard',
        'manage_options', 'cp-visit-statistics');
        add_submenu_page( 'cp-visit-statistics', 'Policy Manager', 'Policy Manager',
        'manage_options', 'cp-visit-statistics-rule','policy_render');   
	}

    function policy_render(){
        require_once(__DIR__.'/views/policy-manager.php');
    }

	function  cp_statistics_settings_page() {
		require_once(__DIR__.'/views/statistics.php');
	}


// track ajax call
add_action('wp_ajax_updtIPEmail','updateIPEmail');
add_action('wp_ajax_nopriv_updtIPEmail','updateIPEmail');
function updateIPEmail(){
    $email = isset($_REQUEST['email'])?$_REQUEST['email']:'';
    if($email!=''){
            global $wpdb;
            global $post;
            $table_csi = $wpdb->prefix . 'cp_statistics_ip';
            $table_csi_data = $wpdb->prefix . 'cp_statistics_ip_data';
            $clientIP = $_SERVER['REMOTE_ADDR']?:($_SERVER['HTTP_X_FORWARDED_FOR']?:$_SERVER['HTTP_CLIENT_IP']);

            $sql_1 = "UPDATE ".$table_csi." SET email='".$email."' WHERE ip='".$clientIP."'";
            $wpdb->query($sql_1);
        }
    die();
}

add_action('wp_head','add_ajax_url');
add_action('admin_head','add_ajax_url');
function add_ajax_url(){
    echo '<script>var AJAX_URL = "'.admin_url('admin-ajax.php').'";</script>';
}

add_action('wp_ajax_getIPData','getIPData');
add_action('wp_ajax_nopriv_getIPData','getIPData');
function getIPData(){
    $ipid = isset($_REQUEST['ipid'])?$_REQUEST['ipid']:'';
    if($ipid!=''){
        global $wpdb;
            global $post;
            $table_csi = $wpdb->prefix . 'cp_statistics_ip';
            $table_csi_data = $wpdb->prefix . 'cp_statistics_ip_data';
            $clientIP = $_SERVER['REMOTE_ADDR']?:($_SERVER['HTTP_X_FORWARDED_FOR']?:$_SERVER['HTTP_CLIENT_IP']);

            $sql_1 = "SELECT *, count(ip_id) as num FROM ".$table_csi_data." WHERE ip_id='".$ipid."' GROUP BY post_id";
            $data = $wpdb->get_results($sql_1);
           ?>
    <table width="100%;" border="1" cellpadding="10" cellspacing="0">
        <?php foreach($data as $d){?>
        <tr>
            <td><?php echo $d->visit_time;?></td>
            <td><?php if($d->post_id==0){
               echo "Home";
           }else{
               echo get_the_title($d->post_id);
           }?></td>
            <td><?php echo $d->num;?></td>
        </tr>
        <?php }?>
    </table>
 <?php       
        die();
    }else{
        die('ID Missing...');
    }
            
}

add_action('wp_ajax_xviewUpdate','xviewUpdate');
function xviewUpdate(){
    global $wpdb;
    $table_policy = $wpdb->prefix . 'cp_statistics_policy';
    $popupid = isset($_POST['popupid'])?$_POST['popupid']:'';
    $pageid = isset($_POST['pageid'])?$_POST['pageid']:'';
    $oldpageid =isset($_POST['oldpageid'])?$_POST['oldpageid']:'';
    $oldpopupid = isset($_POST['oldpopupid'])?$_POST['oldpopupid']:'';
    $num = isset($_POST['num'])?$_POST['num']:0;
    

    
    if($popupid!='' && $pageid!=''){
        $sql_policy_del = "DELETE FROM ".$table_policy." WHERE popup_id='".$oldpopupid."' AND page_id='".$oldpageid."'";
        $wpdb->query($sql_policy_del);
        
        $sql_policy_ins = "INSERT INTO ".$table_policy." SET popup_id='".$popupid."', page_id='".$pageid."',num='".$num."'";
        $n = $wpdb->query($sql_policy_ins);
        
        $sql_update_popup = "UPDATE ".$wpdb->prefix."pps_popup_show_pages SET post_id='".$pageid."' WHERE popup_id='".$popupid."' AND post_id='".$oldpageid."'";
        $wpdb->query($sql_update_popup);
    }
    die($n);
}


add_action('wp_ajax_xviewDelete','xviewDelete');
function xviewDelete(){
    global $wpdb;
    $table_policy = $wpdb->prefix . 'cp_statistics_policy';
    $id = isset($_POST['id'])?$_POST['id']:'';
    $popup_id = isset($_POST['popup_id'])?$_POST['popup_id']:'';
    $page_id = isset($_POST['page_id'])?$_POST['page_id']:'';
    
    if($id!='' && $id!=''){
        $sql_policy_del = "DELETE FROM ".$table_policy." WHERE id='".$id."'";
        $sql_update_popup_show_pages = "DELETE FROM ".$wpdb->prefix."pps_popup_show_pages WHERE post_id='".$page_id."' AND popup_id='".$popup_id."'";
        
        $wpdb->query($sql_policy_del);
        $wpdb->query($sql_update_popup_show_pages);
    }
    die();
}


add_action('wp_ajax_xaddRule','addRule');
function addRule(){
    global $wpdb;
    $tbl_popup_page = $wpdb->prefix."pps_popup_show_pages";
    $tbl_policy = $wpdb->prefix."cp_statistics_policy";
    
    $popup = isset($_POST['popup'])?$_POST['popup']:'';
    $page = isset($_POST['page'])?$_POST['page']:'';
    $num =  isset($_POST['num'])?$_POST['num']:'';
    
    
    //var_dump($data);
    //die();
    
    if($popup!='' && $page !=''){
        $sql_select_policy = "SELECT * FROM ".$tbl_popup_page." WHERE popup_id='".$popup."' AND post_id='".$page."'";
        $data = $wpdb->get_results($sql_select_policy);
        if(count($data)==0){
            $sql_popup_update = "UPDATE ".$wpdb->prefix."_pps_popup SET show_pages='2' WHERE id='".$popup."'";
            $sql_popup_page = "INSERT INTO ".$tbl_popup_page." SET popup_id='".$popup."', post_id='".$page."',not_show='0'";
            $sql_policy = "INSERT INTO ".$tbl_policy." SET popup_id='".$popup."', page_id='".$page."',num='".$num."'";
            $wpdb->query($sql_popup_update);
            $wpdb->query($sql_popup_page);
            $wpdb->query($sql_policy);    
            
        }else{
            die('Rule Exists');
        }
        die('Rule Added');
    }else{
        die("Select Both Drop Down");
    }
    
    die();
}
