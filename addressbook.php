<?php
/*
Plugin Name: Addressbook
Plugin URI: http://samwilson.id.au/blog/plugins/addressbook
Description: A simple address book plugin.  Embed addresses in a post or page with the &lt;addressbook /&gt; tag.
Version: 1.1.3
Author: Sam Wilson
Author URI: http://samwilson.id.au/
*/

$addressbook_version = '1.1.3';

add_action('admin_head', 'addressbook_adminhead');
function addressbook_adminhead() {
	if ( $_GET['page'] == 'addressbook/addressbook.php' ) {
		?>
		<style type="text/css">
		.wrap h2 {margin:1em 0 0 0}
		form.addressbook div.line {width:95%; margin:auto}
		form.addressbook div.input {float:left}
		form.addressbook div.input label {font-size:smaller; margin:0}
		form.addressbook div.input input, form div.input textarea {width:100%; margin:0}
		form .submit {clear:both;border:0; text-align:right}
		table#addressbook-table {border-collapse:collapse}
		table#addressbook-table th {text-align:left}
		table#addressbook-table tr td {border:2px solid #e5f3ff; margin:0}
		table#addressbook-table tr:hover td {cursor:pointer}
		form.addressbook tr input {width:95%; border-color:#e5f3ff; background-color: white}
		<?php echo _addressbook_getAddressCardStyle() ?>
		</style>
		<?php
	}
}

add_action('admin_menu', 'addressbook_menus');
function addressbook_menus() {
	$toplevelmenu = get_option('addressbook_toplevelmenu');
	// The following menus have to be added in different orders depending on
	// whether the Addressbook is a top-level menu or not.  I'm not sure why!
	if ($toplevelmenu=='yes') {
		add_menu_page(__('Addressbook'), 'Addressbook', 4, 'addressbook/addressbook.php', 'addressbook_main');
	    add_submenu_page('addressbook/addressbook.php', 'Options', 'Options', 4, 'addressbook_options', 'addressbook_options');
	    $addressbook_basefile = "admin.php";
	} else {
	    add_options_page('Addressbook Options', 'Addressbook', 4, 'addressbook/addressbook.php', 'addressbook_options');
	    add_management_page('Addressbook', 'Addressbook', 4, 'addressbook/addressbook.php', 'addressbook_main');
	    $addressbook_basefile = "edit.php";
	}
}

function addressbook_options() {
	$toplevelmenu = get_option('addressbook_toplevelmenu');
	$yes_checked = '';
	$no_checked = '';
	if ($toplevelmenu=='yes') {
		$yes_checked = ' checked';
	} else {
		$no_checked = ' checked';
	}
	?>
	<div class="wrap">
	<form class="addressbook" method="post" action="options.php">
	<?php wp_nonce_field('update-options');
	if ($toplevelmenu=='yes') {
		echo '<p><em>';
		_e("Note: Because changing this option will move this page, after
		changing it you will be presented with an error.  Just click &lsquo;back&rsquo; and then
		navigate to the 'Manage &raquo; Addressbook' or 'Options &raquo; Addressbook' tab.");
		echo '</em></p>';
	} ?>
	<p>
	  <?php _e('Give Addressbook its own top-level menu item? '); ?>
	  <input type="radio" name="addressbook_toplevelmenu" value="yes"<?php echo $yes_checked ?> /><?php _e(Yes) ?>
	  <input type="radio" name="addressbook_toplevelmenu" value="no"<?php echo $no_checked ?> /><?php _e(No) ?>
	  <input type="hidden" name="action" value="update" />
	  <input type="hidden" name="page_options" value="addressbook_toplevelmenu" />
	</p>
	<p class="submit">
	<input type="submit" name="submit" value="<?php _e('Update Options'); ?> &raquo" />
	</p>
	</form>
	</div>
	<?php
}

/**
 * Outputs the main administration screen, and handles installing/upgrading, saving, and deleting.
 */
function addressbook_main() {
    global $wpdb, $addressbook_version, $addressbook_basefile;
    $show_main = true;
    
    if ( $_POST['new'] ) _addressbook_insertNewFromPost();
    if ( $_GET['action'] == 'delete' ) $show_main = _addressbook_deleteAddress( $_GET['id'] );
    if ( $_GET['action'] == 'edit' ) $show_main = _addressbook_editAddress( $_GET['id'] );
    
    if ($show_main) {
    
    	// Make sure Addressbook is installed or upgraded.
        $table_name = $wpdb->prefix."addressbook";
        If ($wpdb->get_var("SHOW TABLES LIKE '$table_name'")!=$table_name
            || get_option("addressbook_version")!=$addressbook_version ) {
            // Call the install function here rather than through the more usual
            // activate_blah.php action hook so the user doesn't have to worry about
            // deactivating then reactivating the plugin.  Should happen seemlessly.
            _addressbook_install();
            _addressbook_outputMessage( sprintf(__('The Addressbook plugin (version %s) has been installed or upgraded.'), get_option("addressbook_version")) );
        } ?>
                
        <div class="wrap">
        <div style="text-align:center; width:47%; float:left">
	        <p style='font-size:smaller'><?php
	       		printf(__("This is version %s of the <strong>Addressbook</strong> plugin."), get_option("addressbook_version"));
	        	echo '<br />';
	        	_e("Please report any bugs and feature requests at <a href='http://samwilson.id.au/blog/plugins/addressbook'>
	        	samwilson.id.au/blog/plugins/addressbook</a>.");
	        	echo '<br />'.__('Thankyou.'); ?>
	        </p>
	        <p style="font-size:110%"><strong><a href="#new"><?php _e('Add new address &darr;'); ?></a></strong></p>
	        <form class="addressbook" action="<?php echo $addressbook_basefile; ?>?page=addressbook/addressbook.php" method="get">
	        	<div style="display:none">
	        		<input type="hidden" name="page" value="addressbook/addressbook.php" />
	        		<input type="hidden" name="action" value="search" />
	        	</div>
	        	<p>
	        		<?php _e("Filter messages by search term:"); ?><br />
	        		<input type="text" name="q" /><input type="submit" value="<?php _e('Search&hellip;'); ?>" />
	        	</p>
	        </form>
        </div>
        <div id="contact-info" style="border:10px solid #E5F3FF; margin:0 0 0 50%; padding:5px; width:47%">
        	<em><?php _e('Select an address from below to see its details displayed here.'); ?></em>
        </div>
        <h2 style="margin-top:0"><?php _e('Addressbook'); ?></h2>
        <script type="text/javascript">
        /* <![CDATA[ */
        function click_contact(row, id) {
            document.getElementById('contact-info').innerHTML=document.getElementById('contact-'+id+'-info').innerHTML;
        }
		/* ]]> */
        </script>
        <table style="width:100%; margin:auto" id="addressbook-table">
            <tr style="background-color:#E5F3FF">
                <?php echo '<th>'.__('Name').'</th><th>'.__('Organisation').'</th><th>'.__('Email address').'</th><th>'.__('Phone number').'</th>'; ?>
            </tr>
            <?php
            if ($_GET['action']=='search') {
	            $sql = "SELECT * FROM ".$wpdb->prefix."addressbook WHERE
	            	first_name LIKE '%".$wpdb->escape($_GET['q'])."%'
	            	OR surname LIKE '%".$wpdb->escape($_GET['q'])."%'
	            	OR organisation LIKE '%".$wpdb->escape($_GET['q'])."%'
	            	OR email LIKE '%".$wpdb->escape($_GET['q'])."%'
	            	OR phone LIKE '%".$wpdb->escape($_GET['q'])."%'
	            	OR notes LIKE '%".$wpdb->escape($_GET['q'])."%'
	            	ORDER BY first_name";
            } else {
	            $sql = "SELECT * FROM ".$wpdb->prefix."addressbook ORDER BY first_name";
            }
            $results = $wpdb->get_results($sql);
            foreach ($results as $row) {
                echo"<tr onclick='click_contact(this, ".$row->id.")'>
                    <td>".stripslashes($row->first_name." ".$row->surname)."&nbsp;</td><!-- nbsp is to stop collapse -->
                    <td>".stripslashes($row->organisation)."</td>
                    <td>".stripslashes($row->email)."</td>
                    <td>".stripslashes($row->phone)."</td>
                </tr>";
            } ?>
        </table>
        <?php foreach ($results as $row) {
            echo "<div class='address-label' id='contact-".$row->id."-info' style='display:none'>\n".
            	 "    <p style='text-align:center'>\n".
            	 "        <a href='$addressbook_basefile?page=addressbook/addressbook.php&action=edit&id=".$row->id."'>".__('[Edit]')."</a>\n".
            	 "        <a href='$addressbook_basefile?page=addressbook/addressbook.php&action=delete&id=".$row->id."'>".__('[Delete]')."</a>\n".
            	 "    </p>\n".
            	 _addressbook_getAddressCard($row, "    ").
            	 "</div>";
        } ?>
        
        <h2 style="margin-bottom:1em"><a name="new"></a>Add Address</h2>
        <form class="addressbook" action="<?php echo $addressbook_basefile; ?>?page=addressbook/addressbook.php" method="post">
        <?php echo _addressbook_getaddressform(); ?>
        <p class="submit">
            <input type="submit" name="new" value="<?php _e('Add Address &raquo;'); ?>" />
        </p>
        </form>
        </div><?php
    }
}

function _addressbook_outputMessage($message) {
	?>
	<div id="message" class="updated fade">
	  <p><strong><?php echo $message ?></strong></p>
	</div>
	<?php
}

function _addressbook_insertNewFromPost() {
	global $wpdb, $addressbook_basefile;
	$sql = "INSERT INTO ".$wpdb->prefix."addressbook SET
		organisation  = '".$wpdb->escape($_POST['organisation'])."',
		first_name    = '".$wpdb->escape($_POST['first_name'])."',
		surname       = '".$wpdb->escape($_POST['surname'])."',
		email         = '".$wpdb->escape($_POST['email'])."',
		website       = '".$wpdb->escape($_POST['website'])."',
		address_line1 = '".$wpdb->escape($_POST['address_line1'])."',
		address_line2 = '".$wpdb->escape($_POST['address_line2'])."',
		suburb        = '".$wpdb->escape($_POST['suburb'])."',
		postcode      = '".$wpdb->escape($_POST['postcode'])."',
		state         = '".$wpdb->escape($_POST['state'])."',
		country       = '".$wpdb->escape($_POST['country'])."',
		phone         = '".$wpdb->escape($_POST['phone'])."',
		notes         = '".$wpdb->escape($_POST['notes'])."'";
	$wpdb->query($sql);
	_addressbook_outputMessage(__('The address has been added.'));
}

/**
 * Edit a single address.
 *
 * @param int $id The ID of the address to be edited.
 * @return bool Whether or not any more content should be added to the page after calling this.
 */
function _addressbook_editAddress($id) {
	global $wpdb, $addressbook_basefile;
	$sql = "SELECT * FROM ".$wpdb->prefix."addressbook WHERE id='".$wpdb->escape($id)."'";
	$row = $wpdb->get_row($sql);
	if ( $_POST['save'] ) {
		$wpdb->query("UPDATE ".$wpdb->prefix."addressbook SET
			first_name    = '".$wpdb->escape($_POST['first_name'])."',
			surname       = '".$wpdb->escape($_POST['surname'])."',
			organisation  = '".$wpdb->escape($_POST['organisation'])."',
			email         = '".$wpdb->escape($_POST['email'])."',
			phone         = '".$wpdb->escape($_POST['phone'])."',
			address_line1 = '".$wpdb->escape($_POST['address_line1'])."',
			address_line2 = '".$wpdb->escape($_POST['address_line2'])."',
			suburb        = '".$wpdb->escape($_POST['suburb'])."',
			postcode      = '".$wpdb->escape($_POST['postcode'])."',
			state         = '".$wpdb->escape($_POST['state'])."',
			country       = '".$wpdb->escape($_POST['country'])."',
			notes         = '".$wpdb->escape($_POST['notes'])."',
			website       = '".$wpdb->escape($_POST['website'])."'
			WHERE id ='".$wpdb->escape($_GET['id'])."'");
		_addressbook_outputMessage(__('The address has been updated.'));
		return true;
	} else {
		?><div class="wrap">
		<h2 style="margin-bottom:1em"><?php _e('Edit Address'); ?></h2>
		<form action="<?php echo $addressbook_basefile; ?>?page=addressbook/addressbook.php&action=edit&id=<?php echo $row->id; ?>"
			  method="post" class="addressbook">
		<?php echo _addressbook_getaddressform($row); ?>
		<p class="submit">
			<a href='<?php echo $addressbook_basefile; ?>?page=addressbook/addressbook.php'><?php _e('[Cancel]'); ?></a>
			<input type="submit" name="save" value="<?php _e('Save &raquo;'); ?>" />
		</p>
		</form>
		</div><?php
		return false;
	}
}

/**
 * Delete a single address from the database.
 *
 * @param int $id The ID of the address to be deleted.
 * @return bool Whether or not any more content should be added to the page after calling this.
 */
function _addressbook_deleteAddress($id) {
	global $wpdb, $addressbook_basefile;
	$sql = "SELECT * FROM ".$wpdb->prefix."addressbook WHERE id='".$wpdb->escape($id)."'";
	$row = $wpdb->get_row($sql);
	if ($_GET['confirm']=='yes') {
		$wpdb->query("DELETE FROM ".$wpdb->prefix."addressbook WHERE id='".$wpdb->escape($id)."'");
		_addressbook_outputMessage(__('The address has been deleted.'));
		return true;
	} else {
		echo  "<div class='wrap'>".
			  "    <p style='text-align:center'>".__('Are you sure you want to delete this address?')."</p>\n".
			  "    <div style='border:1px solid black; width:50%; margin:1em auto; padding:0.7em'>\n".
			  _addressbook_getAddressCard($row, "        ").
			  "    </div>\n".
			  "    <p style='text-align:center; font-size:1.3em'>\n".
			  "        <a href='$addressbook_basefile?page=addressbook/addressbook.php&action=delete&id=".$row->id."&confirm=yes'>\n".
			  "            <strong>".__('[Yes]')."</strong>\n".
			  "        </a>&nbsp;&nbsp;&nbsp;&nbsp;\n".
			  "	       <a href='$addressbook_basefile?page=addressbook/addressbook.php'>".__('[No]')."</a>\n".
			  "    </p>\n".
			  "</div>\n";
		return false;
	}
}

function _addressbook_getaddressform($data='null') {
	// Set default values (the website field is the only one with a default value).
    if (!$data) $website = 'http://'; else $website = $data->website;
    $out = '<div style="width:50%; float:left">
        <div class="line">
            <div class="input" style="width:50%">
                <label for="first_name">'.__('First name:').'</label>
                <input type="text" name="first_name" value="'.stripslashes($data->first_name).'" />
            </div>
            <div class="input" style="width:50%">
                <label for="surname">'.__('Surname:').'</label>
                <input type="text" name="surname" value="'.stripslashes($data->surname).'" />
            </div>
        </div>
        <div class="line">
            <div class="input" style="width:100%">
                <label for="organisation">'.__('Organisation:').'</label>
                <input type="text" name="organisation" value="'.stripslashes($data->organisation).'" />
            </div>
        </div>
        <div class="line">
            <div class="input" style="width:100%">
                <label for="email">'.__('Email Address:').'</label>
                <input type="text" name="email" value="'.stripslashes($data->email).'" />
            </div>
        </div>
        <div class="line">
            <div class="input" style="width:100%">
                <label for="phone">'.__('Phone:').'</label>
                <input type="text" name="phone" value="'.stripslashes($data->phone).'" />
            </div>
        </div>
        <div class="line">
            <div class="input" style="width:100%">
                <label for="website">'.__('Website:').'</label>
                <input type="text" name="website" value="'.stripslashes($website).'" />
            </div>
        </div>
        </div>
        <div style="width:50%; float:right">
            <div class="line">
                <div class="input" style="width:100%">
                    <label for="address_line1">'.__('Address Line 1:').'</label>
                    <input type="text" name="address_line1" value="'.stripslashes($data->address_line1).'" />
                </div>
            </div>
            <div class="line">
                <div class="input" style="width:100%">
                    <label for="address_line2">'.__('Address Line 2:').'</label>
                    <input type="text" name="address_line2" value="'.stripslashes($data->address_line2).'" />
                </div>
            </div>
            <div class="line">
                <div class="input" style="width:70%">
                    <label for="suburb">'.__('Suburb:').'</label>
                    <input type="text" name="suburb" value="'.stripslashes($data->suburb).'" />
                </div>
                <div class="input" style="width:30%">
                    <label for="postcode">'.__('Postcode:').'</label>
                    <input type="text" name="postcode" value="'.stripslashes($data->postcode).'" />
                </div>
            </div>
            <div class="line">
                <div class="input" style="width:100%">
                    <label for="state">'.__('State or Territory:').'</label>
                    <input type="text" name="state" value="'.stripslashes($data->state).'" />
                </div>
            </div>
            <div class="line">
                <div class="input" style="width:100%">
                    <label for="country">'.__('Country:').'</label>
                    <input type="text" name="country" value="'.stripslashes($data->country).'" />
                </div>
            </div>
        </div>
		<div class="line" style="width:97%">
			<div class="input" style="width:100%">
				<label for="notes">'.__('Notes:').'</label>
				<textarea name="notes" rows="3">'.stripslashes($data->notes).'</textarea>
			</div>
        </div>';
    return $out;
}

function _addressbook_install() {
    global $wpdb, $addressbook_version;
    $table_name = $wpdb->prefix."addressbook";
    $sql = "CREATE TABLE " . $table_name . " (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        first_name tinytext NOT NULL,
        surname tinytext NOT NULL,
        organisation tinytext NOT NULL,
        email tinytext NOT NULL,
        phone tinytext NOT NULL,
        address_line1 tinytext NOT NULL,
        address_line2 tinytext NOT NULL,
        suburb tinytext NOT NULL,
        postcode tinytext NOT NULL,
        state tinytext NOT NULL,
        country tinytext NOT NULL, 
        website VARCHAR(55) NOT NULL,
        notes tinytext NOT NULL,
        PRIMARY KEY  (id)
    );";
    require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
    dbDelta($sql);
    update_option('addressbook_version', $addressbook_version);
}

/**
 * For other plugins, etc., to use.
 */
function addressbook_getselect($name, $sel_id=false) {
    global $wpdb;
    $out = "<select name='$name'>";
    $rows = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."addressbook ORDER BY first_name, organisation");
    foreach($rows as $row) {
		if ($row->id==$sel_id) {
			$selected = " selected";
		} else {
			$selected = "";
		}
        $out .= "<option$selected value='$row->id'>$row->first_name $row->surname";
        if (!empty($row->organisation)) {
        	$out .= " ($row->organisation)";
        }
        $out .= "</option>";
    }
    $out .= "</select>";
    return $out;
}

/**
 * For other plugins, etc., to use.
 */
function addressbook_getIdFromEmail($email) {
    global $wpdb;
    $sql = "SELECT id FROM ".$wpdb->prefix."addressbook where email='".$wpdb->escape($email)."'";
    $res = $wpdb->get_var($sql);
    return $res;
}

/**
 * For other plugins, etc., to use.
 */
function addressbook_getFullnameFromId($id) {
    global $wpdb;
	$sql = "SELECT CONCAT(first_name,' ',surname) FROM ".$wpdb->prefix."addressbook WHERE id='".$wpdb->escape($id)."'";
    $res = $wpdb->get_var($sql);
    return $res;
}

add_action('wp_head', 'addressbook_wphead');
function addressbook_wphead() {
	?>
    <style type="text/css">
      ol.addressbook-list {padding:0; margin:0}
      li.addressbook-item {list-style-type:none; border:1px solid #666; padding:3px; margin:0; clear:both}
      <?php echo _addressbook_getAddressCardStyle() ?>
    </style>
    
    <?php
} // end addressbook_wphead()

add_filter('the_content', 'addressbook_list');
function addressbook_list($content) {
    global $wpdb;
    $sql = "SELECT * FROM ".$wpdb->prefix."addressbook ORDER BY first_name";
    $results = $wpdb->get_results($sql);
    $out = "<ol class='addressbook-list'>\n\n";
    foreach ($results as $row) {
        $out .= "  <li class='addressbook-item'>\n"._addressbook_getAddressCard($row, "    ")."  </li>\n\n";
    }
    $out .= "</ol>\n";
    return preg_replace("/<addressbook \/>|<addressbook>.*<\/addressbook>/", $out, $content);
}

function _addressbook_getAddressCardStyle() {
	return "
      .addressbook-card p {margin:3px}
      .addressbook-card .name {font-size:1.2em; font-weight:bolder}
      .addressbook-card .avatar {float:right; margin:0 0 0 1em}
      .addressbook-card .address {display:block; margin:0 0.3em 1em 1em; width:38%; float:left; font-size:smaller}
      .addressbook-card .address span {}
      .addressbook-card .notes {font-size:smaller; padding:4px}
	";
}

/**
 * @param 
 * @return string HTML to go within a containing element.
 */
function _addressbook_getAddressCard($data, $pad="") {
	$out = "$pad<div class='addressbook-card vcard'>\n".
		"$pad    ".get_avatar($data->email)."\n".
		"$pad    <p>\n".
		_addressbook_getIfNotEmpty("$pad        <strong><span class='fn name'>%s</span></strong>\n", stripslashes($data->first_name." ".$data->surname)).
		_addressbook_getIfNotEmpty("$pad        <em><span class='org'>%s</span></em>\n", stripslashes($data->organisation)).
		_addressbook_getIfNotEmpty("$pad        <a class='email' href='mailto:%1\$s'>%1\$s</a><br />\n", stripslashes($data->email)).
		_addressbook_getIfNotEmpty("$pad        <span class='tel phone'>%s</span>\n", stripslashes($data->phone)).
		_addressbook_getIfNotEmpty("$pad        <a class='website url' href='%1\$s'>%1\$s</a>\n", stripslashes($data->website)).
		"$pad    </p>\n";
	if ( !empty($data->address_line1) || !empty($data->suburb) || !empty($data->postcode) || !empty($data->state) || !empty($data->country) ) {
		$out .= "$pad    <div class='address adr'>\n";
		if (!empty($data->address_line1) || !empty($data->address_line2)) {
			$out .= "$pad      <span class='street-address'>\n".
				_addressbook_getIfNotEmpty("$pad        <span class='address-line1'>%s</span><br />\n", stripslashes($data->address_line1)).
				_addressbook_getIfNotEmpty("$pad        <span class='address-line2'>%s</span><br />\n", stripslashes($data->address_line2)).
			"$pad      </span>\n";
		}
		$out .= _addressbook_getIfNotEmpty("$pad      <span class='suburb locality'>%s</span>\n", stripslashes($data->suburb)).
			_addressbook_getIfNotEmpty("$pad      <span class='postcode postal-code'>%s</span><br />\n", stripslashes($data->postcode)).
			_addressbook_getIfNotEmpty("$pad      <span class='state region'>%s</span>\n", stripslashes($data->state)).
			_addressbook_getIfNotEmpty("$pad      <span class='country country-name'>%s</span>\n", stripslashes($data->country)).
		"$pad    </div>\n";
	}
	$out .= _addressbook_getIfNotEmpty("$pad    <div class='notes note'>\n$pad    %s\n$pad    </div>\n", stripslashes($data->notes)).
		 "$pad    <div style='clear:both'></div>\n$pad</div>\n";
	return $out;
}

function _addressbook_getIfNotEmpty($format,$var) {
	if (!empty($var)) {
		return sprintf($format, $var);
	}
}

?>
