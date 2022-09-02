<?php
if (!defined('ABSPATH'))
    exit;

class VsTr_admin_menu {

    /**
     * The single instance
     * @var 	object
     * @access  private
     * @since 	1.0.0
     */
    private static $_instance = null;

    /**
     * The main plugin object.
     * @var 	object
     * @access  public
     * @since 	1.0.0
     */
    public $parent = null;

    /**
     * Prefix for plugin settings.
     * @var     string
     * @access  publicexport
     *
     * @since   1.0.0
     */
    public $base = '';

    /**
     * Available settings for plugin.
     * @var     array
     * @access  public
     * @since   1.0.0
     */
    public $settings = array();

    /**
     * The version number.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_version;

    /**
     * The token.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_token;

    /**
     * The main plugin file.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $file;

    /**
     * The main plugin directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $dir;

    /**
     * The plugin assets directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_dir;

    /**
     * The plugin assets URL.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_url;

    /**
     * Suffix for Javascripts.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $templates_url;

    public function __construct($parent) {
        $this->_token = 'vstr';
        $this->parent = $parent;
        $this->dir = dirname($parent->file);
        $this->assets_dir = trailingslashit($this->dir) . 'assets';
        $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $parent->file)));
        // $this->templates_url = esc_url(trailingslashit(plugins_url('/templates/', $parent->file)));

        add_action('admin_menu', array($this, 'add_menu_item'));
        add_action('wp_ajax_nopriv_vstr_settings_save', array($this, 'settings_save'));
        add_action('wp_ajax_vstr_settings_save', array($this, 'settings_save'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 10, 1);
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_styles'), 10, 1);
        $this->check_visitsToDelete();
    }

    /**
     * Add menu to admin
     * @return void
     */
    public function add_menu_item() {
        add_menu_page('Visitors Tracker', __('Visitors Tracker','vstr'), 'edit_posts', 'vstr-visits', array($this, 'submenu_visits'), 'dashicons-search');
        add_submenu_page('vstr-visits', 'Settings', __('Settings','vstr'), 'edit_posts', 'vstr-settings', array($this, 'submenu_settings'));
    }


    /**
     * Load admin CSS.
     * @access  public
     * @since   1.0.0
     * @return void
     */
    public function admin_enqueue_styles($hook = '') {
         wp_register_style($this->_token . '-jqueryui', esc_url($this->assets_url) . 'css/jquery-ui-theme/jquery-ui.min.css', array(), $this->_version);
        wp_enqueue_style($this->_token . '-jqueryui');

        wp_register_style($this->_token . '-admin', esc_url($this->assets_url) . 'css/admin.css', array('wp-pointer'), $this->_version);
        wp_enqueue_style($this->_token . '-admin');
    }

// End admin_enqueue_styles()

    /**
     * Load admin Javascript.
     * @access  public
     * @since   1.0.0
     * @return void
     */
    public function admin_enqueue_scripts($hook = '') { 
      
        
 if(isset($_POST["Export"])){ global $wpdb;
         ob_clean();
    ob_start();
      $output = fopen('php://output', 'w');
if ($output) {
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="export.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
      //$output = fopen("php://output", "w");  
      $column_array = array('User ID','User Name','User Role','IP Address','Browser/Operating System', 'Total Session Duration','Last Seen','Login','Country','City',PHP_EOL);
                            

      fputcsv($output,$column_array);
      $table_name = $wpdb->prefix . "vstr_visits";
        
        
        $rows = $wpdb->get_results("SELECT *,sec_to_time(timePast) as timePastNew FROM $table_name",ARRAY_A);
        foreach ($rows as $row) {
            $rowUserTrack = array();
            
            $userID = $row['userID'];
            if(!empty($userID)){

            $user_meta = get_userdata($userID);
            $userName  = $user_meta->user_login;
            $roles     = $user_meta->roles;

            


            $role_current  = $roles[0];
            $role_previous = $roles[1];

            $ip            = $row['ip'];
            $browser       = $row['browser'];

            $timeSpent     = $row['timePastNew'];

            $country       = $row['country'];

            $city          = $row['city'];
            $last_seen     = '';
            $login         = $row['date'];
            $last_seen      = $row['date'];
            $logout        = '';

            $visitID = $row['id'];
           

           $rowUserTrack = array($userID,$userName,$role_current,$ip,$browser,$timeSpent,$last_seen,$login,$country,$city);
           $table_name = $wpdb->prefix . "vstr_steps";
           $query1=$wpdb->query('SET @row_num=0');
           $query2=$wpdb->query('SET @row_num_new=0');
           $query="SELECT  sec_to_time(sum(time_to_sec(timediff(b.date,a.date)))) as timevisit,b.page_name FROM  (SELECT *,@row_num:=@row_num+1 AS row_number from $table_name where visitID=$visitID) b inner join (SELECT *,@row_num_new:=@row_num_new+1 AS row_number from $table_name  where visitID=$visitID) a on b.row_number=a.row_number+1 where b.visitID=$visitID GROUP BY b.page_name";
           $rowsSteps = $wpdb->get_results($query,ARRAY_A);
            $steps = array();
            foreach ($rowsSteps as $row) { //print_r($row);
             $steps[] = $row['page_name'].' - temps passé '.$row['timevisit'];
            }
            $steps[] = PHP_EOL;
           
            $rowUserTrack = array_merge($rowUserTrack,$steps);

           fputcsv($output,$rowUserTrack);  
      }  }
      fclose($output);  
exit;
}
 }  

//communication

if(isset($_POST["ExportNew"])){ global $wpdb;
         ob_clean();
    ob_start();
      $output = fopen('php://output', 'w');
if ($output) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="export.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
      //$output = fopen("php://output", "w");  
      $column_array = array('User ID','User Name','User Role', 
                            'IP Address','Browser/Operating System', 'Total Session Duration',
                            'Last Seen', 'Login','Country','City',
                            'ACCUEIL',
                            'COMMUNICATION & ACTIVATION',
                            //'CORE',
                            'CHAUSSURE CORE',
                            'FOOTBALL','FOOTBALL ITS L1','FOOTBALL ITS L2','FOOTBALL ITS L3','FOOTBALL ITS L4','FOOTBALL S2000 L2','FOOTBALL S2000 L3','FOOTBALL S2000 L4',
                            'HBS',
                            'RUNNING','RUNNING ITS L1','RUNNING ITS L2','RUNNING ITS L3','RUNNING ITS L4','FOOTBALL S2000 L2','FOOTBALL S2000 L3',
                            'TEXTILE ATHLETICS','TEXTILE ATHLETICS ITS L1','TEXTILE ATHLETICS ITS L2','TEXTILE ATHLETICS ITS L3','TEXTILE ATHLETICS ITS L4',
                            'TEXTILE ATHLETICS S000 L2','TEXTILE ATHLETICS S000 L3','TEXTILE ATHLETICS S000 L4');


      //$page_titles = wp_list_pluck( get_pages(), 'post_title' );

     // $column_array = array_merge($column_array,$page_titles);

      fputcsv($output,$column_array);
      $table_name = $wpdb->prefix . "vstr_visits";
        
        
        $rows = $wpdb->get_results("SELECT *,sec_to_time(timePast) as timePastNew FROM $table_name",ARRAY_A);
        foreach ($rows as $row) {
            $rowUserTrack = array();
            
            $userID = $row['userID'];
            if(!empty($userID)){

            $user_meta = get_userdata($userID);
            $userName  = $user_meta->user_login;
            $roles     = $user_meta->roles;

            


            $role_current  = $roles[0];
            $role_previous = $roles[1];

            $ip            = $row['ip'];
            $browser       = $row['browser'];

            $timeSpent     = $row['timePastNew'];

            $country       = $row['country'];

            $city          = $row['city'];
            $last_seen     = '';
            $login         = $row['date'];
            $last_seen      = $row['date'];
            $logout        = '';

            $visitID = $row['id'];
           

           $rowUserTrack = array($userID,$userName,$role_current,$ip,$browser,$timeSpent,$last_seen,$login,$country,$city,
                                 '','','','','','','','','','','','','',
                                 '','','','','','','','','','','','','','');
           $table_name = $wpdb->prefix . "vstr_steps";
           $query1=$wpdb->query('SET @row_num=0');
           $query2=$wpdb->query('SET @row_num_new=0');
           $query="SELECT  sec_to_time(sum(time_to_sec(timediff(b.date,a.date)))) as timevisit,b.page_name FROM  (SELECT *,@row_num:=@row_num+1 AS row_number from $table_name where visitID=$visitID) b inner join (SELECT *,@row_num_new:=@row_num_new+1 AS row_number from $table_name  where visitID=$visitID) a on b.row_number=a.row_number+1 where b.visitID=$visitID GROUP BY b.page_name";
           $rowsSteps = $wpdb->get_results($query,ARRAY_A);
           //$rowsSteps = $wpdb->get_results("SELECT sec_to_time(sum(TIME_TO_SEC(timediff(b.date,a.date)))) as timevisit,b.page_name,b.visitID FROM $table_name b inner join $table_name a on b.id=a.id+1 where b.visitID=$visitID group by b.page_name",ARRAY_A);
            $steps = array();
            foreach ($rowsSteps as $row) { //print_r($row);
             //$steps[] = $row['page_name'].' - temps passé '.$row['timevisit'];
             if($key = array_search(strtoupper($row['page_name']),$column_array)){
                $rowUserTrack[$key]=$row['timevisit'];
             }
            }
           
            //$rowUserTrack = array_merge($rowUserTrack,$steps);
           $rowUserTrack[] = PHP_EOL;

           fputcsv($output,$rowUserTrack);  
      }  }
      fclose($output);  
exit;
}
 }  
            
        if (isset($_GET['page']) && strrpos($_GET['page'], 'vstr') !== false) {
            wp_register_script($this->_token . '-jqueryui', esc_url($this->assets_url) . 'js/jquery-ui-1.9.2.custom.min.js', array('jquery'), $this->_version);
            wp_enqueue_script($this->_token . '-jqueryui');
            
            wp_register_script($this->_token . '-admin', esc_url($this->assets_url) . 'js/admin.js', array($this->_token . '-jqueryui','jquery-ui-tooltip'), $this->_version);
            wp_enqueue_script($this->_token . '-admin');

            $visitDatasArray = "";
            if (isset($_GET['view-visit'])) {
                $visitDatasArray = $this->get_visitArray($_GET['view-visit']);
            }
            wp_localize_script($this->_token . '-admin', 'visitDatas', $visitDatasArray);
            $settings = $this->getSettings();
            wp_localize_script($this->_token . '-admin', 'vstr_settings', array($settings));
        }
    }

    /*
     * display visits page
     */

    public function submenu_visits() {
        global $wpdb;
        if (isset($_GET['remove'])) {
            $this->remove_visit($_GET['remove']);
        }
        if (isset($_GET['remove-group'])) {
            $ids = explode(',', $_GET['remove-group']);
            foreach ($ids as $removeID) {
                $this->remove_visit($removeID);
            }
        }

        $user = 0;
        if (isset($_GET['user']) && $_GET['user'] != '0') {
            $user = $_GET['user'];
        }


            ?>
            <div style="display: none">
                <?php
                $this->check_visitsTime();
                $this->check_visitsCountries();
                $visitsTable = new Visits_List_Table();
                $visitsTable->user = $user;
                $visitsTable->prepare_items();
                ?>
            </div>
            <div class="wrap">
                <div id="icon-users" class="icon32"></div>
                <h2><?php echo __('Last Visits','vstr');?></h2>
                <p>
                    <label><?php echo __('Filter by User','vstr');?> : </label>
                    <?php
                    if (isset($_GET['user']) && $_GET['user'] != '0') {
                        wp_dropdown_users(array('show_option_all' => "All", 'selected' => $_GET['user']));
                    } else {
                        wp_dropdown_users(array('show_option_all' => "All",'show' => 'user_login'));
                    }
                    ?>
                    <label><?php echo __('Filter by IP','vstr');?> : </label>
                    <select id="filter_ip">
                        <option value=""><?php echo __('All','vstr');?></option>
                    <?php

                        $table_name = $wpdb->prefix . "vstr_visits";
                        $ips = $wpdb->get_results("SELECT DISTINCT ip FROM $table_name ORDER BY id DESC");
                        foreach ($ips as $ip){
                            $sel = '';
                            if (isset($_GET['filterIP']) && $_GET['filterIP'] == $ip->ip) {
                                $sel = 'selected';
                            }
                            echo '<option value="'.$ip->ip.'" '.$sel.'>'.$ip->ip.'</option>';
                        }

                    ?>
                    </select>
                    <span>&nbsp;</span>
                    <span>&nbsp;</span>
                    <label><?php echo __('Filter by Role','vstr');?> : </label>
                    <select id="roles">
                        <option value="0"><?php echo __('All','vstr');?></option>
                        <?php
                        if (isset($_GET['role']) && $_GET['role'] != '0') {
                            wp_dropdown_roles($_GET['role']);
                        } else {
                            wp_dropdown_roles();
                        }
                        ?>
                    </select>
                
            <div style="display: inline-block;" >

            <form class="form-horizontal" action="" method="post" name="upload_excel"   
                      enctype="multipart/form-data" style="display: inline-block;">
                
                            <input type="hidden" name="action" value="add_foobar">
  <input type="hidden" name="data" value="foobarid">
                                <input type="submit" name="Export" class="button action" value="Analyse utilisateur" />
                                          
            </form>      

             

            <form class="form-horizontal" action="" method="post" name="upload_excel"   
                      enctype="multipart/form-data"" style="display: inline-block;">
                
                            <input type="hidden" name="action" value="add_foobar">
  <input type="hidden" name="data" value="foobarid">
                                <input type="submit" name="ExportNew" class="button action" value="Communication Analyse" />
                                          
            </form>      
</div>

            </p>
               <?php $visitsTable->display(); ?>
                <select id="bulk_actions">
                    <option value="-1" selected="selected"><?php echo __('Bulk Actions','vstr');?></option>
                    <option value="trash"><?php echo __('Move to Trash','vstr');?></option>
                </select>
                <input type="submit" name="" id="doaction2" class="button action" value="Apply">
            </div>
            <script>
                jQuery('.vstr_allcheck').click(function() {
                    if (jQuery(this).is(':checked')) {
                        jQuery('.wp-list-table .column-checkbox input[type=checkbox],.vstr_allcheck').prop('checked', 'checked');
                    } else {
                        jQuery('.wp-list-table .column-checkbox input[type=checkbox],.vstr_allcheck').removeProp('checked');
                    }
                });
                jQuery('#doaction2').click(function() {
                    if (jQuery('#bulk_actions').val() == 'trash') {
                        var ids = '';
                        jQuery('.wp-list-table .column-checkbox input[type=checkbox][data-id]:checked').each(function() {
                            ids += jQuery(this).data('id') + ',';
                        });
                        document.location.href = 'admin.php?page=vstr-visits&remove-group=' + ids;
                    }
                });

                jQuery('#filter_ip').change(function() {
                    if (jQuery('#filter_ip').val() != '') {
                        document.location.href = 'admin.php?page=vstr-visits&filterIP=' + jQuery('#filter_ip').val();
                    } else {
                        document.location.href = 'admin.php?page=vstr-visits';

                    }
                });
                jQuery('#user').change(function() {
                    if (jQuery('#user').val() != '0') {
                        document.location.href = 'admin.php?page=vstr-visits&user=' + jQuery('#user').val();
                    } else {
                        document.location.href = 'admin.php?page=vstr-visits';
                    }
                });
                jQuery('#roles').change(function() {
                    if (jQuery('#roles').val() != '0') {
                        document.location.href = 'admin.php?page=vstr-visits&role=' + jQuery('#roles').val();
                    } else {
                        document.location.href = 'admin.php?page=vstr-visits';
                    }
                });
                jQuery(document).ready(function() {
                    sessionStorage.vstrAdminStep = 0;
            <?php
            if (isset($_GET['role']) && $_GET['role'] != '0') {
                ?>
                        jQuery('table.wp-list-table td.column-role').each(function() {
                            if (jQuery(this).html() != '<?php echo $_GET['role']; ?>') {
                                jQuery(this).parent('tr').hide();
                            }
                        });
                <?php
            }
            ?>
                    <?php
           if (isset($_GET['filterIP']) && $_GET['filterIP'] != '') {
               ?>
                    jQuery('table.wp-list-table td.column-ip').each(function() {
                        if (jQuery(this).html() != '<?php echo $_GET['filterIP']; ?>') {
                            jQuery(this).parent('tr').hide();
                        }
                    });
                    <?php
                }
                ?>
                });
            </script>
            <?php
        
    }

    /*
     * Remove specific visit
     */

    public function remove_visit($visit_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . "vstr_steps";
        $wpdb->delete($table_name, array('visitID' => $visit_id));

        $table_name = $wpdb->prefix . "vstr_visits";
        $wpdb->delete($table_name, array('id' => $visit_id));
    }

    /*
     * Get specific visit datas
     */

    private function get_visit($visit_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . "vstr_visits";
        $rows = $wpdb->get_results("SELECT * FROM $table_name WHERE id=$visit_id LIMIT 1");
        return $rows[0];
    }

    /*
     * Get specific visit datas
     */

    private function get_visitArray($visit_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . "vstr_visits";
        $rep = array();
        
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE id='%s' LIMIT 1", $visit_id));
        if ($rows[0]->userID > 0) {
            $user = get_userdata($rows[0]->userID);
            $user = $user->user_login;
        } else {
            $user = 'unknow';
        }
        $rows[0]->user = $user;
        foreach ($rows[0] as $column => $value) {
            $rep[$column] = $value;
        }
        $table_name = $wpdb->prefix . "vstr_steps";
        $rowsS = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE visitID=%s ORDER BY id ASC", $visit_id));
        $rep['steps'] = array();
        $lastStep = false;
        foreach ($rowsS as $row) {
            if ($lastStep) {
                $timePast = abs(strtotime($row->date) - strtotime($lastStep->date));
                $row->timePast = $timePast;
            }
            $lastStep = $row;
            $row->dateJS = date(date('D M d Y H:i:s O'), strtotime($row->date));
            $rep['steps'][] = $row;
        }
        return $rep;
    }


    /**
     * display settings page
     * @return void
     */
    public function submenu_settings() {
        global $wpdb;
        $table_name = $wpdb->prefix . "vstr_settings";
        $settings = $wpdb->get_results("SELECT * FROM $table_name WHERE id=1 LIMIT 1");
        $settings = $settings[0];
        ?>
        <div class="wrap wpeSettings">
            <div class="wrap">
                <h2>Settings</h2>
                <div id="vstr_response"></div>
                <form id="form_settings" method="post" action="#" onsubmit="qc_process(this);
                        return false;">
                    <input id="id" type="hidden" name="id" value="1">
                    <table class="form-table">
                        <tbody>
                            
                            <tr>
                                <th scope="row"><?php echo __('Targeted customers','vstr');?> </th>
                                <td>
                                    <select id="customersTarget" name="customersTarget">
                                        <?php
                                        $sel1 = '';
                                        $sel2 = '';
                                        if ($settings->customersTarget == 'registred') {
                                            $sel2 = 'selected';
                                        } else {
                                            $sel1 = 'selected';
                                        }
                                        echo '<option ' . $sel1 . ' value="all">'.__('All','vstr').'</option>';
                                        echo '<option ' . $sel2 . ' value="registred">'.__('Registered','vstr').'</option>';
                                        ?>
                                    </select>
                                    <label for="customersTarget"> <span class="description"><?php echo __('Defines the targeted customers','vstr');?></span> </label></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php echo __('Retain visitor data for','vstr');?> :</th>
                                <td>
                                    <select id="removeDays" name="removeDays">
                                        <?php
                                        $sel1 = '';
                                        $sel2 = '';
                                        $sel3 = '';
                                        $sel4 = '';
                                        $sel5 = '';
                                        $sel6 = '';
                                        $sel7 = '';
                                        if ($settings->removeDays == 3) {
                                            $sel1 = 'selected';
                                        } else if ($settings->removeDays == 7) {
                                            $sel2 = 'selected';
                                        } else if ($settings->removeDays == 30) {
                                            $sel3 = 'selected';
                                        } else if ($settings->removeDays == 60) {
                                            $sel4 = 'selected';
                                        } else if ($settings->removeDays == 90) {
                                            $sel5 = 'selected';
                                        } else if ($settings->removeDays == 0) {
                                            $sel6 = 'selected';
                                        } else if ($settings->removeDays == 1) {
                                            $sel7 = 'selected';
                                        } else {
                                            $sel2 = 'selected';
                                        }
                                        echo '<option ' . $sel7 . ' value="1">'.__('1 day','vstr').'</option>';
                                        echo '<option ' . $sel1 . ' value="3">'.__('3 days','vstr').'</option>';
                                        echo '<option ' . $sel2 . ' value="7">'.__('7 days','vstr').'</option>';
                                        echo '<option ' . $sel3 . ' value="30">'.__('1 month','vstr').'</option>';
                                        echo '<option ' . $sel4 . ' value="60">'.__('2 months','vstr').'</option>';
                                        echo '<option ' . $sel5 . ' value="90">'.__('3 months','vstr').'</option>';
                                        echo '<option ' . $sel6 . ' value="0">'.__('Forever','vstr').'</option>';
                                        ?>
                                    </select>
                                    <label for="removeDays"> <span class="description"><?php echo __('Visits will be deleted after this period','vstr');?></span> </label></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php echo __('Information panel position','vstr');?></th>
                                <td>
                                    <select id="panelPosition" name="panelPosition">
                                        <?php
                                        $sel1 = '';
                                        $sel2 = '';
                                        if ($settings->panelPosition == 'right') {
                                            $sel2 = 'selected';
                                        } else {
                                            $sel1 = 'selected';
                                        }
                                        echo '<option ' . $sel1 . ' value="left">'.__('Left','vstr').'</option>';
                                        echo '<option ' . $sel2 . ' value="right">'.__('Right','vstr').'</option>';
                                        ?>
                                    </select>
                                    <label for="panelPosition"> <span class="description"><?php echo __('Defines the information panel position','vstr'); ?></span> </label></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php echo __('Remove 00:00 duration visits','vstr'); ?> </th>
                                <td>
                                    <select id="noBots" name="noBots">
                                        <?php
                                        $sel1 = '';
                                        $sel2 = '';
                                        if ($settings->noBots) {
                                            $sel2 = 'selected';
                                        } else {
                                            $sel1 = 'selected';
                                        }
                                        echo '<option ' . $sel1 . ' value="0">'.__('No','vstr').'</option>';
                                        echo '<option ' . $sel2 . ' value="1">'.__('Yes','vstr').'</option>';
                                        ?>
                                    </select>
                                    <label for="noBots"> <span class="description"><?php echo __('Remove useless visits from list','vstr'); ?></span> </label></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php echo __("Don't record visits from administrators",'vstr'); ?> :</th>
                                <td>
                                     <select id="dontTrackAdmins" name="dontTrackAdmins">
                                        <?php
                                        $sel1 = '';
                                        $sel2 = '';
                                        if ($settings->dontTrackAdmins) {
                                            $sel2 = 'selected';
                                        } else {
                                            $sel1 = 'selected';
                                        }
                                        echo '<option ' . $sel1 . ' value="0">'.__('No','vstr').'</option>';
                                        echo '<option ' . $sel2 . ' value="1">'.__('Yes','vstr').'</option>';
                                        ?>
                                    </select>
                                    <label for="dontTrackAdmins"> <span class="description"><?php echo __("The users having the admin role will not be tracked",'vstr'); ?></span> </label>

                                </td>
                            </tr>
                             <tr>
                                <th scope="row"><?php echo __("Don't record visits from tactile devices",'vstr'); ?> :</th>
                                <td>
                                     <select id="dontTrackTactile" name="dontTrackTactile">
                                        <?php
                                        $sel1 = '';
                                        $sel2 = '';
                                        if ($settings->dontTrackTactile) {
                                            $sel2 = 'selected';
                                        } else {
                                            $sel1 = 'selected';
                                        }
                                        echo '<option ' . $sel1 . ' value="0">'.__('No','vstr').'</option>';
                                        echo '<option ' . $sel2 . ' value="1">'.__('Yes','vstr').'</option>';
                                        ?>
                                    </select>
                                    <label for="dontTrackTactile"> <span class="description"><?php echo __("Users using mobiles and tablets will not be tracked",'vstr'); ?></span> </label>

                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php echo __("Don't record visits from these IP",'vstr'); ?> :</th>
                                <td>
                                    <textarea id="filterIP" name="filterIP" placeholder="<?php echo __("Enter the IP adresses, separated by commas",'vstr'); ?>"><?php
                                        echo $settings->filterIP;
                                        ?></textarea>
                                    <label for="filterIP"> <span class="description"><?php echo __("Enter the IP adresses, separated by commas",'vstr'); ?></span> </label>

                                </td>
                            </tr>
                            
                           
                            <tr>
                                <th scope="row"></th>
                                <td>
                                    <input type="submit" value="Save" class="button-primary"/>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <script>
                        function qc_process(e) {

                            var error = false;
                            if (!error) {
                                jQuery("#vstr_response").hide();
                                var data = {action: "vstr_settings_save"};
                                jQuery('#form_settings input, #form_settings select, #form_settings textarea').each(function() {
                                    if (jQuery(this).attr('name')) {
                                        eval('data.' + jQuery(this).attr('name') + ' = jQuery(this).val();');
                                    }
                                });
                                jQuery.post(ajaxurl, data, function(response) {
                                    jQuery("#vstr_response").html('<div id="message" class="updated"><p><strong><?php echo __('Settings saved','vstr');?></strong>.</p></div>');

                                    setTimeout(function() {
                                        document.location.href = 'admin.php?page=vstr-settings';
                                    }, 250);
                                });
                            }
                        }
                    </script>
                </form>
            </div>
        </div>
        <?php
    }


    /*
     * Return plugin settings
     */

    private function getSettings() {
        global $wpdb;
        $table_name = $wpdb->prefix . "vstr_settings";
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $rows = $wpdb->get_results("SELECT * FROM $table_name WHERE id=1");
            return $rows[0];
        } else {
            return false;
        }
    }

    /**
     * save settings
     * @return void
     */
    public function settings_save() {
        global $wpdb;
        
        if (current_user_can('edit_posts')) {
        $response = "Error, try again later.";
        $table_name = $wpdb->prefix . "vstr_settings";
        $sqlDatas = array();
        foreach ($_POST as $key => $value) {
            if ($key != 'action') {
                if ($key == 'filterIP') {
                    $value = str_replace(" ", "", $value);
                    $value = trim(preg_replace('/\s\s+/', '', $value));
                }
                $sqlDatas[$key] = stripslashes(sanitize_text_field($value));
            }
        }
        $wpdb->update($table_name, $sqlDatas, array('id' => 1));
        $response = '<div id="message" class="updated"><p>'.__('Settings saved','vstr').'</strong>.</p></div>';

        echo $response;
        }
        die();
    }

    /*
     * Find the country from ip
     */
    private function check_visitsCountries() {
        global $wpdb;
        $table_name = $wpdb->prefix . "vstr_visits";
        $rows = $wpdb->get_results("SELECT * FROM $table_name WHERE country='' ORDER BY id DESC");
        foreach ($rows as $row => $value) {
            try {
                $country = 'unknow';
                $city = 'unknow';
                $xml = simplexml_load_file("http://www.geoplugin.net/xml.gp?ip=$value->ip");
                $country = $xml->geoplugin_countryName;
                $city = $xml->geoplugin_city;

                $wpdb->update($table_name, array('country' => $country, 'city' => $city), array('id' => $value->id));
            } catch (Exception $exc) {

            }
        }
    }

    /*
     * Save the total time of visits
     */

    private function check_visitsTime() {
        global $wpdb;
        $settings = $this->getSettings();
        $table_name = $wpdb->prefix . "vstr_visits";
        $rows = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");
        foreach ($rows as $row => $value) {
            $table_nameS = $wpdb->prefix . "vstr_steps";
            $rowsS = $wpdb->get_results("SELECT * FROM $table_nameS WHERE visitID=$value->id ORDER BY id DESC LIMIT 1");
            $timePast = 0;
            $lastDate = false;
            if (count($rowsS) > 0) {
                $step = $rowsS[0];
                $timePast = (strtotime($step->date) - strtotime($value->date));
                $wpdb->update($table_name, array('timePast' => $timePast), array('id' => $value->id));
            }
        }
        if($settings->noBots){
            $wpdb->delete($table_name, array('timePast' => 0));      
        }
    }

    /*
     * Save the total time of visits
     */

    private function check_visitsToDelete() {
        global $wpdb;
        $settings = $this->getSettings();
        if ($settings) {
            if ($settings->removeDays > 0) {
                $table_name = $wpdb->prefix . "vstr_visits";
                $rows = $wpdb->get_results("SELECT * FROM $table_name  ORDER BY id DESC");
                foreach ($rows as $row) {
                    if (abs(strtotime(date('Ymd h:i:s')) - strtotime($row->date)) > $settings->removeDays * 24 * 60 * 60) {
                        $wpdb->delete($table_name, array('id' => $row->id));
                    }
                }
            }
        }
    }

    /**
     * Main Instance
     *
     *
     * @since 1.0.0
     * @static
     * @return Main instance
     */
    public static function instance($parent) {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($parent);
        }
        return self::$_instance;
    }

    // End instance()

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone() {
        _doing_it_wrong(__FUNCTION__, '', $this->parent->_version);
    }

// End __clone()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup() {
        _doing_it_wrong(__FUNCTION__, '', $this->parent->_version);
    }

// End __wakeup()
}
