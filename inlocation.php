<?php
/*
Plugin Name: InLocation
Text Domain: inlocation
Description: Plugin para criar um Mapa (via GoogleMaps) com seu endereço fisico por CEPs
Author: Gabriel Darezzo
Domain Path: /languages
Version: 1.8
Author URI: http://github.com/gabrieldarezzo
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
add_action( 'admin_menu', 'inlocation_init' );

function inlocation_current_action() {
	if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) {
		return $_REQUEST['action'];
	}

	if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] ) {
		return $_REQUEST['action2'];
	}

	return false;
}

add_filter( 'plugin_action_links', 'inlocation_action_links', 10, 2 );

function inlocation_action_links( $links, $file ) {
	if ( $file != 'inlocation/inlocation.php' )return $links;
	
	$settings_link = '<a href="' . menu_page_url( 'inlocation', false ) . '&action=settings">'
		. esc_html( __( 'Settings', 'inlocation' ) ) . '</a>'
	;
	array_unshift( $links, $settings_link );
	return $links;
}

function inlocation_start(){
	//MENU
	?>
		<div id="inlocationadm-menu">
			<a href="<?php echo admin_url() . 'admin.php?page=inlocation&action=cadastrar'; ?>"><?php echo __( 'Register Location', 'inlocation' ); ?></a>
			| <a href="<?php echo admin_url() . 'admin.php?page=inlocation'; ?>"><?php echo __( 'Listing', 'inlocation' ); ?></a>
			| <a href="<?php echo admin_url() . 'admin.php?page=inlocation&action=simular'; ?> "><?php echo __( 'Simulate Map', 'inlocation' ); ?></a>
			| <a href="<?php echo admin_url() . 'admin.php?page=inlocation&action=settings'; ?> "><?php echo __( 'Settings', 'inlocation' ); ?></a>
		</div>
		<hr />
	<?php
	//Check Current URL
	$action = inlocation_current_action(); 
	
	if(!$action){
		inlocation_listagem_cep();
	} else {
		switch($action):
			
			case 'simular':
				inlocation_mapsimulation();
			break;
			
			case 'cadastrar':
				inlocation_cad_cep();
			break;
			
			case 'settings':
				inlocation_settings();
			break;
			
			default:
				echo __( 'No action correct.', 'inlocation' );
				return;
			break;
			
		endswitch;
	}
}


/**
**Faz a consulta e guarda o 'title, lat, lng 
**chama as libs do googlmaps (googleapis) e executa a correção da chamada 'inlocation_fix_googledefer' #necessario defer/async
**Após carregar popula o mapa (makers)
**/
function inlocation_map_deps(){
	
	global $wpdb;
	$tbl = $wpdb->prefix . 'il_local';
	
	$lugares_map = $wpdb->get_results($sql = "select 
		 CONCAT(logr_end, ' ', logr_bairro, ' (', logr_cidade, ') - ', logr_estado) AS title
		,lat 
		,lng
		,icon_id
	from {$tbl} order by show_order", OBJECT);
	
	
	if(count($lugares_map) == 0){
		echo __( 'You need register a location', 'inlocation') .  ' -> <a href=' . admin_url() . 'admin.php?page=inlocation&action=cadastrar>' . __( 'Register Location', 'inlocation' ) . '</a>';
		return;
	}
	
	if(get_option('inlocation_googleapi')){
		$key = get_option('inlocation_googleapi');
	} else {
		echo __( 'You need a Google API, Go in "Settings"', 'inlocation') .  ' -> <a href=' . admin_url() . 'admin.php?page=inlocation&action=settings>' . __( 'Settings', 'inlocation' ) . '</a>';
		return;
	}
	
	
	wp_enqueue_script('inlocation_gmaps_api', 'https://maps.googleapis.com/maps/api/js?key='. $key .'&callback=initMap', array(), null, false);
	
	wp_enqueue_script('inlocation_map', plugin_dir_url(__FILE__) . 'js/inlocation_map.js', array('inlocation_gmaps_api'));
	
	$defs = array(
		'inwork_img_icon' 	=> plugin_dir_url(__FILE__) . 'images/icon/'
	);
	
	wp_localize_script( 'inlocation_map', 'lugares', $lugares_map );
	wp_localize_script( 'inlocation_map', 'defs', $defs );
	wp_enqueue_script( 'inlocation_map' );
}


//Cria um mapa do GoogleMaps
function inlocation_mapsimulation(){
	inlocation_map_deps();
	
	$short = '[inlocation_map id="1"]';
	echo "
	<div class='wrap'>
		<div id='map' style='width: 600px;height: 600px;'></div> <!-- From JavaScript -->
		<hr />
		<p>ShortCode:<br />{$short}</p>
	</div>
	<hr />
	";
}

//Consulta no banco e traz listagem de lugares (sortable mode)
function inlocation_listagem_cep(){	
	global $wpdb;
	$tbl = $wpdb->prefix . 'il_local';
	
	$lugares = $wpdb->get_results($sql = "select id, CONCAT(logr_end, ' ', logr_bairro, ' (', logr_cidade, ') - ', logr_estado) AS title, logr_nr
	from {$tbl} order by show_order", OBJECT);
	
	if(count($lugares) == 0){
		
		echo __( 'You need register a location', 'inlocation') .  ' -> <a href=' . admin_url() . 'admin.php?page=inlocation&action=cadastrar>' . __( 'Register Location', 'inlocation' ) . '</a>';
		return;
	}
	
	
	
	echo "<div class='wrap'>";
		echo "<h2 id='lugares'>". __('Locations:', 'inlocation' )  ."</h2>";
		echo '<ul id="custom-type-list" class="sortable">';
		foreach($lugares as $lugar){
			echo "<li class='ui-state-default' id='{$lugar->id}'>";
				echo "<span class='ui-icon ui-icon-arrowthick-2-n-s'></span>({$lugar->id}) <strong>{$lugar->title}</strong> | Nr: <strong>{$lugar->logr_nr}</strong> ";
				echo '<div class="menu-expand"><span style="float: right;" title="Excluir localização" class="dashicons dashicons-trash"></span></div> ';
			echo '</li>';
		}
		echo '</ul>';
	echo '</div>';
}

//Cadastra a localização e faz consulta no WebService do Google retornando o geocode correspondente
function inlocation_cad_cep(){	
	$x = get_option('inlocation_googleapi');
	if(!get_option('inlocation_googleapi') || $x == ''){
		echo __( 'You need a Google API, Go in "Settings"', 'inlocation') .  ' -> <a href=' . admin_url() . 'admin.php?page=inlocation&action=settings>' . __( 'Settings', 'inlocation' ) . '</a>';
		return;
	}

	global $wpdb;
	$tbl = $wpdb->prefix . 'il_local';
	
	if($_POST){
		$cep 	 	= @$_POST['cep'];
		$logr_nr 	= @$_POST['logr_nr'];
		$logr_end   = @$_POST['logr_end'];
		$logr_bairro= @$_POST['logr_bairro'];
		$logr_cidade= @$_POST['logr_cidade'];
		$logr_estado= @$_POST['logr_estado'];
		$show_order = @$_POST['show_order'];
		$icon_id 	= @$_POST['icon_id'];
		
		
		
		
		if($cep == ''){
			die(__( 'Zip Code Empty', 'inlocation'));
		}
		
		if($logr_nr == ''){
			die(__( 'Number Empty', 'inlocation'));
		}
		//Fim das validações
		
		//Faz consulta no WebService do Google
		$local = "{$logr_end},{$logr_nr} {$logr_cidade}";
		
		$url="http://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($local)."&sensor=false";
		$json = file_get_contents($url);
		$data = json_decode($json, TRUE);
		$lat = $data['results'][0]['geometry']['location']['lat'];
		$lng = $data['results'][0]['geometry']['location']['lng'];
		
		//Insert
		$sql = "";
		  
		$wpdb->query( $wpdb->prepare("INSERT INTO {$tbl} (cep, logr_nr, logr_end, logr_bairro, logr_cidade, logr_estado, lat, lng, icon_id) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)"
			,array(
				 $cep
				,$logr_nr
				,$logr_end
				,$logr_bairro
				,$logr_cidade
				,$logr_estado
				,$lat
				,$lng
				,$icon_id
			) 
		));
	}


	$pathIcons = plugin_dir_path(__FILE__) . 'images/icon/';	
	$icons = [];
	foreach(glob($pathIcons . '*.*')  as $iconFile) {
		$sanitizedIcon = str_replace($pathIcons, '', $iconFile);		
		$icons[] = $sanitizedIcon;		
	}
	
	
	$select_icons = '<select id="icon_id" name="icon_id" class="image-picker show-html">';
	
	$select_icons .= '<option data-img-src="' .plugin_dir_url(__FILE__) . 'images/icon/spotlight-poi.png" value="spotlight-poi.png">spotlight-poi.png</option>';
	
	foreach($icons as $icon){
		if($icon == '_readme-license.txt') continue;
		
		$select_icons .= '<option data-img-src="' .plugin_dir_url(__FILE__) . 'images/icon/' . $icon . '" value="' . $icon . '">'. $icon . '</option>';
		/*
		if($icon == 'schools_maps.png'){
			$select_icons .= '<option data-img-src="' .plugin_dir_url(__FILE__) . 'images/icon/' . $icon . '" value="' . $icon . '">'. $icon . '</option>';
		} else {
			$select_icons .= '<option selected data-img-src="' .plugin_dir_url(__FILE__) . 'images/icon/' . $icon . '" value="' . $icon . '">'. $icon . '</option>';
		}
		*/
		
	}
	$select_icons .= '</select>';
	
	echo "
	<div class='wrap'>
		<form action='' method='post'>
			<h1>". __( 'Enter the location', 'inlocation' ) ."</h1>
			
			<p>". __( 'Zip code', 'inlocation' )."</p>
			<input type='text' name='cep' id='cep'/>
			
			<p>". __( 'Street Name', 'inlocation' )."</p>
			<input type='text' name='logr_end' id='logr_end' />
			
			<p>". __( 'Neighborhood', 'inlocation' )."</p>
			<input type='text' name='logr_bairro' id='logr_bairro' />
			
			<p>". __( 'City', 'inlocation' )."</p>
			<input type='text' name='logr_cidade' id='logr_cidade' />
			
			<p>". __( 'State', 'inlocation' )."</p>
			<input type='text' name='logr_estado' id='logr_estado' />
			
			<p>". __( 'Number', 'inlocation' )."</p>
			<input type='text' name='logr_nr' id='logr_nr' />
			
			
			<h2>". __( 'Icons', 'inlocation' )."</h2>
			{$select_icons}
			
			<br />
			
			<input value='". __( 'Add Location', 'inlocation' )."' name='bbuinfo_config_submit' type='submit' />
		</form>
	</div>";
}

//Cria Menu lateral e adiciona (estilo contact7)
function inlocation_init() {
	global $_wp_last_object_menu;
	
	add_menu_page( 
		 'In Location'
		,'In Location'
		,'manage_options'
		,'inlocation'
		,'inlocation_start'
		,'dashicons-location-alt'
		,$_wp_last_object_menu++
	);
}


//Pagina para atualizar/criar sua Google-Api
function inlocation_settings(){
	
	if($_POST){
		update_option('inlocation_googleapi', $_POST['inlocation_googleapi']);
	}
	
	if(get_option('inlocation_googleapi')){
		$api = get_option('inlocation_googleapi');
	} else {
		$api = '';
	}
	
	$page = '';
	$page .= '<h3>' . __( 'Enter your Google api-key!', 'inlocation' ) . '</h3>';
	
	
	$page .= '<form action="" method="post">';
		$page .= '<p>Google API</p>';
		$page .= '<input type="text" name="inlocation_googleapi" id="inlocation_googleapi" value="'. $api .'" style="width: 360px;"/><br />';
		$page .= __( 'You can create Google Api-KEY in this link,', 'inlocation' ) . ' <a target="_new" href="https://developers.google.com/maps/documentation/javascript/get-api-key">https://developers.google.com/maps/documentation/javascript/get-api-key</a>';
		$page .= "<br />";
		$page .= '<input type="submit" value="'. __( 'Update API', 'inlocation' ) .'" />';
	
	$page .= '</form>';
	
	echo $page;
	
	// Opções dos icones em breve...
}


//Função de ativação
function inlocation_activate(){
	global $wpdb;
	$tbl = $wpdb->prefix . 'il_local';
	
	$sql = "CREATE TABLE IF NOT EXISTS {$tbl}(
		 id         	INT(8) PRIMARY KEY AUTO_INCREMENT 
		,cep        	varchar(8) NOT NULL 
		,logr_nr    	varchar(30)
		,logr_end    	varchar(30) 
		,logr_bairro    varchar(30)
		,logr_cidade    varchar(30)
		,logr_estado    varchar(30)
		,lat			varchar(200)
		,lng			varchar(200)
		,show_order 	INT(4)
		,icon_id		varchar(200)
	)Engine=InnoDb";
	
	$wpdb->query($sql);
}

//Função de desativação
// ### Drop table! ###
function inlocation_deactivation(){
	global $wpdb;
	$tbl = $wpdb->prefix . 'il_local';
	$sql = "DROP TABLE IF EXISTS {$tbl}";
	$wpdb->query($sql);
}


//Chamado Via ajax, atualiza a ordem, será utilizado no futuro.
function inlocation_save_sort_order(){
	global $wpdb;
	$tbl = $wpdb->prefix . 'il_local';
	
	if( ! current_user_can('manage_options')){
		return wp_send_json_error(__( 'You do not have permission to perform this!', 'inlocation' ));
	}
	
	$i = 0;
	foreach($_POST['order'] as $id_order){
		
		$wpdb->query( $wpdb->prepare("UPDATE {$tbl} SET show_order = %d WHERE id = %d",
			array(
				 $i
				,$id_order
			) 
		));
		
		$wpdb->query($sql);
		$i++;
	}
	wp_send_json_success(__( 'Addresses sequence updated!', 'inlocation' ));
}

add_action('wp_ajax_inlocation_save_sort_order', 'inlocation_save_sort_order');


//Deleta uma localização...
function inlocation_del_location(){
	global $wpdb;
	$tbl = $wpdb->prefix . 'il_local';
	
	if( ! current_user_can('manage_options')){
		return wp_send_json_error(__( 'You do not have permission to perform this!', 'inlocation' ));
	}
	
	$idx = $_POST['idx'];
	
	$wpdb->query( $wpdb->prepare("DELETE FROM {$tbl} WHERE id = %d",
		array(
			$idx
		) 
	));
	
	
	wp_send_json_success('Ordenação dos endereços atualizada.');
}

add_action('wp_ajax_inlocation_del_location', 'inlocation_del_location');


//WebService da KingHost, Pensar em alterar isto caso tenha muitos usuarios.
function inlocation_endbycep(){
	$cep = $_POST['cep'];
	
	$out = @file_get_contents($str="http://webservice.uni5.net/web_cep.php?auth=ba4bd2d71dde71d3824141ac81b94c66&formato=query_string&cep={$cep}");
	
	parse_str($out, $arr);

	$tmp = array();
	foreach($arr as $key => $val){
		$tmp[$key] = utf8_encode($val);
	}
	
	wp_send_json($tmp);
}
add_action('wp_ajax_inlocation_endbycep', 'inlocation_endbycep');


// Exemplo de chamada [inlocation_map id="1"]
function inlocation_map_shortcode( $atts, $content = null ) {
	inlocation_map_deps();
	
	$content = "<div id='map' style='width:500px;height:500px;'></div> <!-- Map from JavaScript, GoogleApi -->";
	return $content;
}

add_shortcode( 'inlocation_map', 'inlocation_map_shortcode' );



function inlocation_enqueue_scripts_styles(){
	wp_enqueue_style('image-picker-css', plugin_dir_url(__FILE__) . 'js/plugins/image-picker/image-picker.css');
	wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css');
	wp_enqueue_style('custom', plugin_dir_url(__FILE__) . 'css/custom.css', array('jquery-ui-css'));

	wp_enqueue_script('image-picker', plugin_dir_url(__FILE__) . 'js/plugins/image-picker/image-picker.min.js', array('jquery'));
	wp_enqueue_script('maskedinput', plugin_dir_url(__FILE__) . 'js/plugins/maskedinput/jquery.maskedinput.js', array('jquery'));
	
	wp_enqueue_script('inlocationscript', plugin_dir_url(__FILE__) . 'js/inlocationscript.js', array('jquery', 'jquery-ui-sortable', 'maskedinput'));
	
	$inlocation_def = array(
		'path_admin' 	=> admin_url()
		,'zip_not_found' => __( 'ZipCode Not Found', 'inlocation' )
		,'error_ajax' => __( 'Error', 'inlocation' )
		,'confirm_delete' => __( 'Are you sure?', 'inlocation' )
	);
	wp_localize_script( 'inlocationscript', 'inlocation_def', $inlocation_def );
	wp_enqueue_script('inlocationscript');
}
add_action ( 'admin_enqueue_scripts', 'inlocation_enqueue_scripts_styles' );


add_filter('clean_url', 'inlocation_fix_googledefer', 99, 3);
function inlocation_fix_googledefer($url, $original_url, $_context) {
	
	//Check if GoogleMapsApi
	if (strstr($url, "callback=initMap") !== false) {
		// $url = str_replace("https", "http", $url); //Caso bug com https
		$url = str_replace("&#038;", "&", $url);
		return "$url' async defer"; //Obrigatorio para funcionar no GoogleAPI. (fica um ' solto, ex: <script...async defer'></script>
    }
	return $url;
}


register_activation_hook( __FILE__, 'inlocation_activate' );
register_deactivation_hook( __FILE__, 'inlocation_deactivation' );



add_action( 'plugins_loaded', 'inlocation_load_textdomain' );

function inlocation_load_textdomain() {
	load_plugin_textdomain( 'inlocation', false, plugin_basename( dirname( __FILE__ ) ) . '/languages/' ); 
}

