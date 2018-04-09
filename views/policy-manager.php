<?php
    require_once( ABSPATH . 'wp-admin/includes/class-wp-screen.php' );//added
    require_once( ABSPATH . 'wp-admin/includes/screen.php' );//added
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    require_once( ABSPATH . 'wp-admin/includes/template.php' );


global $wpdb;
$sql_1 = "SELECT * FROM ".$wpdb->prefix."pps_popup_show_pages psp, ".$wpdb->prefix."pps_popup pp WHERE pp.id=psp.popup_id"; 
$data = $wpdb->get_results($sql_1);

function get_num($popup,$page){
    global $wpdb;
$table_policy = $wpdb->prefix . 'cp_statistics_policy';
$sql_2 = "SELECT num FROM ".$table_policy." WHERE popup_id='".$popup."' AND page_id='".$page."'";
 $data = $wpdb->get_results($sql_2);
    //var_dump($data['num']);
  if($data[0]->num){
      return $data[0]->num;
  }else{
      return 0;
  }
}
$sql_pages = "SELECT ID,post_title FROM ".$wpdb->prefix."posts WHERE post_type in ('post','page') AND post_status='publish'";
$posts = $wpdb->get_results($sql_pages);
$sql_popups = "SELECT * FROM ".$wpdb->prefix."pps_popup WHERE id >= 100";
$pops = $wpdb->get_results($sql_popups);


class PolicyManagerTable extends WP_List_Table{
    
    function __construct(){
        
        parent::__construct( array(
      'singular'=> 'cp_policy_manager', //Singular label
      'plural' => 'cp_policy_manager', //plural label, also this well be one of the table css class
      'ajax'   => false //We won't support Ajax for this table
      ) );
        $this->dataK = array();
        $this->per_page = 10;
    }
    function get_columns(){
          $columns = array(
              'cb' => '<input type="checkbox" />', 
            'popup' => 'POP UP',
            'page'    => 'PAGE',
            'action_filter'      => 'ACTION FILTER',
              'action' =>'ACTION'
          );
          return $columns;
    }
    function extra_tablenav( $which ) {
        global $wpdb;
        $sql_pages = "SELECT ID,post_title FROM ".$wpdb->prefix."posts WHERE post_type in ('post','page') AND post_status='publish'";
        $posts = $wpdb->get_results($sql_pages);
        $sql_popups = "SELECT * FROM ".$wpdb->prefix."pps_popup WHERE id >= 100";
        $pops = $wpdb->get_results($sql_popups);
        
      if ( $which == "top" ){

      }
    }
    function column_popup($item){
        global $wpdb;
        $sql_popups = "SELECT * FROM ".$wpdb->prefix."pps_popup WHERE id >= 100";
                $pops = $wpdb->get_results($sql_popups);
?>
        <select name="popups" id="xviewAfter_<?php echo $item->id;?>_popup_id">
                <option value="">Select Popup</option>
                <?php foreach($pops as $p){
                ?>
                    <option value="<?php echo $p->id;?>" <?php if($item->popup_id==$p->id){echo 'selected';}?> ><?php echo $p->label;?></option>
                <?php }?>
                </select>
                <input type="hidden" name="xviewAfter_<?php echo $item->id;?>_old_popup_id" id="xviewAfter_<?php echo $item->id;?>_old_popup_id" value="<?php echo $item->popup_id;?>" />
        <?php
        return ob_get_clean();
    }
    function column_page($item){
        global $wpdb;
        $sql_pages = "SELECT ID,post_title FROM ".$wpdb->prefix."posts WHERE post_type in ('post','page') AND post_status='publish'";
        $posts = $wpdb->get_results($sql_pages);
        ?>
                <select name="page" id="xviewAfter_<?php echo $item->id;?>_page_id">
                    <option value="">Select Page</option>
                <?php foreach($posts as $pg){?>
                    <option value="<?php echo $pg->ID;?>" <?php if($item->page_id==$pg->ID){echo 'selected';}?>><?php echo $pg->post_title;?></option>
                <?php }?>    
                    
                </select>
                <input type="hidden" name="xviewAfter_<?php echo $item->id;?>_old_page_id" id="xviewAfter_<?php echo $item->id;?>_old_page_id" value="<?php echo $item->page_id;?>" />
<?php
        
    }
    function column_action_filter($item){ ob_start();?>
        <select name="num" id="xviewAfter_<?php echo $item->id;?>_num">
            <option value="">Select</option>
            <option value="0" <?php if($item->num==0){echo 'selected';}?>>On Load</option>
            <option value="7" <?php if($item->num==7){echo 'selected';}?>> > Week</option>
            <option value="30" <?php if($item->num==30){echo 'selected';}?>> > Month</option>    
            <option value="365" <?php if($item->num==365){echo 'selected';}?>> > Year</option>        
            </select>
        <?php 
        return ob_get_clean();
    }
    function column_action($item){ 
        ob_start();?>
                <input type="button" value="Update" onclick="xviewActionEdit(<?php echo $item->id;?>)" />
                <input type="button" value="Delete" onclick="xviewActionDelete(<?php echo $item->id.','.$item->popup_id.','.$item->page_id;?>)" />
                
        <?php 
        return ob_get_clean();
    }
    function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="bulk-delete[]" value="%s" />', $item->id);
    }
    public static function record_count()
    {
        global $wpdb;
        $table_csi_data = $wpdb->prefix . 'cp_statistics_ip_data';
        $sql = "SELECT COUNT(*) FROM ".$table_csi_data;
        return floor($wpdb->get_var($sql));
    }
    
    function get_hidden_columns(){
        return array();
    }
    
    function get_sortable_columns(){
        return array();
    }
    function prepare_items() {
        
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array(
        $columns,
        $hidden,
        $sortable
        );
        /** Process bulk action */
        $this->process_bulk_action();
        //$this->get_items_per_page('records_per_page', 10);
        $per_page = 10;
        $current_page = $this->get_pagenum();
        $total_items = self::record_count();
        $data = self::get_records($per_page, $current_page);
        $this->set_pagination_args(
                      ['total_items' => $total_items, //WE have to calculate the total number of items
                   'per_page' => $per_page // WE have to determine how many items to show on a page
                  ]);
        
        
        
        
        
        
        $this->items = $data;
    }
    
    public function get_pagenum(){
        return isset($_GET['paged'])?$_GET['paged']:1;
    }
    public static function get_records($per_page = 10, $page_number = 1)
    {
        global $wpdb;
        $limit = $per_page;
        $paged = isset($_GET['paged'])?$_GET['paged']:1;
        $offset = ($paged==1)?0: ($limit * ($paged-1));
        $table_csi = $wpdb->prefix . 'cp_statistics_ip';
        $table_csi_data = $wpdb->prefix . 'cp_statistics_ip_data';

        
        //$sql_1 = "SELECT * FROM ".$table_csi." tc, ".$table_csi_data." tcd WHERE tcd.ip_id = tc.id ORDER BY tcd.visit_time DESC LIMIT $offset , $limit";
         //$wpdb->get_results($sql_1);
        
        
        global $wpdb;
$table_policy = $wpdb->prefix . 'cp_statistics_policy';
$sql_2 = "SELECT * FROM ".$table_policy." WHERE 1";
 return $wpdb->get_results($sql_2);
    }
    
function get_bulk_actions() {
  $actions = array(
    'delete'    => 'Delete'
  );
  return $actions;
}    

}



?>

<style>
    .cp-stat-policy{ position: absolute; top:0px; left:0px; background-color: rgba(200,200,200,0.4); width:100%; height:100%; text-align: center; }
    .cp-stat-policy img{margin: 100px auto}
    .add_section{ width:100%; text-align: right; padding: 20px;}
    #add_policy{ padding: 10px; text-align: right; border:1px solid #999; margin-right: 20px; text-decoration: none; font-weight: bold; color:#fff; background: #555;}
    .add_form{ background: #ddd; margin-bottom: 20px; padding: 20px;}
    .add_form select{ width:100%;}
    .add_form input[type='text']{width:100%;}
    .add_form .col{ width:20%; display: inline-block; vertical-align: top; padding: 10px;}
    .add_form input[type='button']{ width:50%;}
</style>
<div class="wrap">
<h1 class="wp-heading-inline">Policy Manager</h1>
        <a class="pm page-title-action" href="#">Add Policy</a><div style="width:100%; float:left;margin:10px;"></div>
              <div class="add_form alignright" style="display:none; width:95%;">
        <div class="col">
                <select name="popups" id="xviewAfter_popup_id">
                <option value="">Select Popup</option>
                <?php foreach($pops as $p){?>
                    <option value="<?php echo $p->id;?>"><?php echo $p->label;?></option>
                <?php }?>
                </select>
        </div>
        <div class="col">
                <select name="page" id="xviewAfter_page_id">
                    <option value="">Select Page</option>
                <?php foreach($posts as $pg){?>
                    <option value="<?php echo $pg->ID;?>" ><?php echo $pg->post_title;?></option>
                <?php }?>    
                    
                </select>
        </div>
        <div class="col">
            <select name="num" id="xviewAfter_num">
            <option value="">Select</option>
            <option value="0">On Load</option>
            <option value="7"> > Week</option>
            <option value="30"> > Month</option>    
            <option value="365"> > Year</option>        
            </select>
        </div>
        <div class="col"> 
            <input type="button" id="addNewRule" value="Add"/>
        </div>
    </div>
    <?php 
            $RS = new PolicyManagerTable();
            $RS->prepare_items();
            $RS->display();
    ?>
    
    <?php /*?>
    <table width="100%" border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th width="20%">POPUP</th>
            <th width="40%">PAGE</th>
            <th width="20%">Action Filter</th>
            <th width="20%">Action</th>
        </tr>
        <?php $i=0;foreach($data  as $d){?>
        <tr>
            
            <td><?php //echo $d->label;?> 
                <select name="popups" id="xviewAfter_<?php echo $i;?>_popup_id">
                <?php foreach($pops as $p){?>
                    <option value="<?php echo $p->id;?>" <?php if($d->popup_id==$p->id){echo 'selected';}?>><?php echo $p->label;?></option>
                <?php }?>
                    </select>
                <input type="hidden" name="xviewAfter[<?php echo $i;?>]['old_popup_id']" id="xviewAfter_<?php echo $i;?>_old_popup_id" value="<?php echo $d->popup_id;?>" />
            </td>
            <td>
                <select name="page" id="xviewAfter_<?php echo $i;?>_page_id">
                <?php foreach($posts as $pg){?>
                    <option value="<?php echo $pg->ID;?>" <?php if($d->post_id==$pg->ID){echo 'selected';}?>><?php echo $pg->post_title;?></option>
                <?php }?>    
                    <option value="0" <?php if($d->post_id==0){echo 'selected';}?>>Home</option>
                </select>
            <input type="hidden" name="xviewAfter[<?php echo $i;?>]['old_page_id']" id="xviewAfter_<?php echo $i;?>_old_page_id" value="<?php echo $d->post_id;?>" />
            </td>
            <td><input type="text" name="xviewAfter[<?php echo $i;?>]['num']" id="xviewAfter_<?php echo $i;?>_num"  value="<?php echo get_num($d->popup_id,$d->post_id);?>"/></td>
            <td>
                <input type="button" value="Update" onclick="xviewActionEdit(<?php echo $i;?>)" />
                <input type="button" value="Delete" onclick="xviewActionDelete(<?php echo $i;?>)" />
                
            </td>
               
        </tr>
        <?php $i++;}?>
    </table>
    <?php */?>
</div>
<div class="cp-stat-policy ajax-loading-panel" style="display:none;">
<img src="<?php echo plugins_url('../assets/images/ajax-loader.gif',__FILE__);?>" />
</div>
<script>
    /*
    jQuery( document ).ajaxStart(function() {
  jQuery(".ajax-loading-panel").css('display','block');
}).ajaxStop(function() {
  jQuery(".ajax-loading-panel").css('display','none');
});
    */
    
function xviewActionEdit(rowID){
    var popupid = jQuery('#xviewAfter_'+rowID+'_popup_id').val();
    var pageid = jQuery('#xviewAfter_'+rowID+'_page_id').val();
    var afternum = jQuery('#xviewAfter_'+rowID+'_num').val();
    var oldpageid = jQuery('#xviewAfter_'+rowID+'_old_page_id').val();
    var oldpopupid = jQuery('#xviewAfter_'+rowID+'_old_popup_id').val();
    jQuery.ajax({
        method:'post',
        url:'<?php echo admin_url('admin-ajax.php');?>?action=xviewUpdate',
        data:{'popupid':popupid,'pageid':pageid,'num':afternum,'oldpopupid':oldpopupid,'oldpageid':oldpageid}
    }).done(function(){
        window.location.reload();
    });
}

function xviewActionDelete(rowID,popup,page){
    var s = confirm('Are you sure?');
    if(s == 1){
    jQuery.ajax({
        method:'post',
        url:'<?php echo admin_url('admin-ajax.php');?>?action=xviewDelete',
        data:{'id':rowID,'popup_id':popup,'page_id':page}
    }).done(function(e){
        window.location.reload();
    });
    }
}
    jQuery(function(){
       jQuery('.pm.page-title-action').on('click',function(e){
           e.preventDefault();
           jQuery('.add_form').toggle();
       });
        
    jQuery('#addNewRule').click(function(){
        var popup = jQuery('.add_form #xviewAfter_popup_id').val();
        var page =  jQuery('.add_form #xviewAfter_page_id').val();
        var num = jQuery('.add_form #xviewAfter_num').val();
        
        jQuery.ajax({
            'url':'<?php echo admin_url('admin-ajax.php');?>?action=xaddRule',
            'data':{'popup':popup,'page':page,'num':num},
            'method':'post'
            
        }).done(function(data){
            var d = confirm(data); 
           if(d==1)window.location.reload();
        });
    });
        
    });
</script>