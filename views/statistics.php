<?php
//if( ! class_exists( 'WP_List_Table' ) ) {
    //require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    require_once( ABSPATH . 'wp-admin/includes/class-wp-screen.php' );//added
    require_once( ABSPATH . 'wp-admin/includes/screen.php' );//added
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    require_once( ABSPATH . 'wp-admin/includes/template.php' );
//}



class RenderStatistics extends WP_List_Table{
    
    function __construct(){
        
        parent::__construct( array(
      'singular'=> 'cp_statistics', //Singular label
      'plural' => 'cp_statistics', //plural label, also this well be one of the table css class
      'ajax'   => false //We won't support Ajax for this table
      ) );
        $this->dataK = array();
        $this->per_page = 10;
    }
    
    function get_columns(){
          $columns = array(
              'cb' => '<input type="checkbox" />', 
            'uuid' => 'Cookie Id',
            'visit_time'    => 'DATE TIME',
            'meta'      => 'JSON Data'
          );
          return $columns;
    }
    
    function extra_tablenav( $which ) {
      if ( $which == "top" ){
        
     }
    }
    function column_uuid($item){
        $actions = array(
            'delete'    => sprintf('<a href="?page=%s&action=%s&uuid=%s">Delete</a>',$_REQUEST['page'],'delete',$item->id),
        );
        return sprintf('%1$s %2$s', $item->uuid,$this->row_actions($actions));
    }
    function column_visit_time($item){
        $stamp = strtotime($item->visit_time);
        $formatted = date('d/m/Y H:i:s T',$stamp);
        return sprintf('%s', $formatted);
    }
    function column_meta($item){
        return sprintf('%s', $item->meta);
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
        return array( 'uuid', 'visit_time');
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

        
        $sql_1 = "SELECT * FROM ".$table_csi." tc, ".$table_csi_data." tcd WHERE tcd.ip_id = tc.id ORDER BY tcd.visit_time DESC LIMIT $offset , $limit";
        return $wpdb->get_results($sql_1);
    }
    
    function get_bulk_actions() {
  $actions = array(
    'delete'    => 'Delete'
  );
  return $actions;
}


public function process_bulk_action() {

        // security check!
       
        // if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {
            
        //     $nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
        //     echo $action = 'bulk-' . $this->_args['plural'];

        //     if ( ! wp_verify_nonce( $nonce, $action ) )
        //         wp_die( 'Nope! Security check failed!' );

        // }
        $action = $this->current_action();
        
        global $wpdb;
        $table_csi_data = $wpdb->prefix . 'cp_statistics_ip_data';
        switch ( $action ) {

            case 'delete':
            
                foreach($_POST['bulk-delete'] as $id) {

                    $sql_del = "DELETE from ".$table_csi_data." WHERE id='".$id."'";
                    $wpdb->query($sql_del);
                }
                break;

            default:
                // do nothing or something else
                return;
                break;
        }

        return;
    }    


}
        

?>
<div class="wrap">
   <?php  echo '<h1 class="wp-heading-inline">Dashboard</h1>';
        //echo '<a href="http://localhost/testwp/wp-admin/post-new.php?post_type=page" class="page-title-action">Add New</a>';
          //echo '<div style="clear:both; margin:10px; height:100px;float:left;">Hello</div>';
          echo '<div style="border:1px solid #999; background-color:#eee;min-height:100px;padding:5px; float:left; width:95%; margin:10px;">Area for Description (Will be updated later).</div>';
    ?>
    <br/>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=cp-visit-statistics">
    <?php 
            $RS = new RenderStatistics();
            $RS->prepare_items();
            $RS->display();
    ?>
    </form>
    </div>