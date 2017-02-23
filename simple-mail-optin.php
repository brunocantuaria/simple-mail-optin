<?php
/*
Plugin Name: Simple Mail Optin
Plugin URI: https://cantuaria.net.br
Description: Um simples plugin que grava emails no backend atraves de um formulário no front-end.
Version: 1.4
Author: Bruno Cantuária
Author URI: https://cantuaria.net.br
License: GNU General Public License
*/

//Registrando tabela para guardar emails
function smo_install () {
   global $wpdb;

   $table_name = $wpdb->prefix . "smo_emails";
   if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
	$sql = 	"CREATE TABLE  `$table_name` (
				`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`name` VARCHAR( 255 ) COLLATE utf8_bin NOT NULL ,
				`email` VARCHAR( 255 ) COLLATE utf8_bin NOT NULL
			) ENGINE = MYISAM ;";		
   }
   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   dbDelta($sql);   

}
add_action( "after_switch_theme", "smo_install" );

//Função para tratar o cadastro
//de emails via ajax
add_action('wp_ajax_nopriv_smo_register_mail', 'smo_register_mail');
add_action('wp_ajax_smo_register_mail', 'smo_register_mail');
function smo_register_mail() {
	
	global $wpdb;
	$table_name = $wpdb->prefix . "smo_emails";
	
	//Escape nos dados recebidos que vão pro DB
	$name = $_POST['name'];
	$email = $_POST['email'];
	
	//Verificação basica dos dados
	if (!$name) smo_die(__("Insira um nome!","smo"));
	if (!$email) smo_die(__("Insira um e-mail!","smo"));
	
	//Verificando se o email é realmente um email
	if(!filter_var($email, FILTER_VALIDATE_EMAIL)) smo_die(__("Isso não é um e-mail!","smo"));
	
	//Verificando se o email já está cadastrado
	$check = $wpdb->get_var("SELECT id FROM $table_name WHERE email = '$email'");
	if ($check) die(__("E-mail já cadastrado!","smo"));
	
	//Cadastrando o email
	$check = $wpdb->insert($table_name,array('name'=>$name,'email'=>$email),array('%s'));
	if (!$check) smo_die(__("Ocorreu um erro ao cadastrar!","smo"));
	
	die("Cadastrado com sucesso!");
	
}

//Função para retornar mensagem
//de erro via Ajax
function smo_die($message) {
	
	header('HTTP/1.1 412 Precondition Failed');
	die($message);
	
}

/*	Função para imprimir o script na página, Pode chamar manualmente em qualquer parte
	do seu tema, hookar ela no footer com um add_action('wp_footer', 'smo_insert_script');
	ou simplesmente invocar o caller do formulário, que você confere mais em baixo */
function smo_insert_script() {
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$("#email_optin").submit( function(e) {
				
				e.preventDefault();
				
				//guardando variaveis para facil acesso
				holder = $(this).html();
				holderName = $("#name").val();
				holderEmail = $("#email").val();
				
				//criando uma janela animada de mensagem com jQuery
				$("#email_optin").html("<div class='smo_msg_holder' style='z-index: 9999; padding: 20%; width: 100%; left: 0; position: fixed; top: 0; height: 100%; color: #CCC; font-size: 40px; text-shadow: 2px 2px 2px black; text-align: center; background: #333; background:rgba(0,0,0,0.8); display: none;'>Aguarde...</div>");
				
				//invocando ajax para cadastrar email
				$("#email_optin .smo_msg_holder").fadeTo(500,1, function () {
					$.ajax({
						type: "post",
						url: "<?php echo admin_url('admin-ajax.php'); ?>",
						data: { 
							action: 'smo_register_mail',
							name : holderName,
							email : holderEmail
						},
						beforeSend: function() {},
						complete: function() {},
						success: function(html){
							//Email cadastrado com sucesso
							$("#email_optin .smo_msg_holder").fadeTo(500,0, function () {
								$("#email_optin").html(holder);
								alert(html);
							});
						},
						error: function(request,error){
							//Erro, restaurando dados
							$("#email_optin .smo_msg_holder").fadeTo(500,0, function () {
								$("#email_optin").html(holder);
								alert(request["responseText"]);
								$("#name").val(holderName);
								$("#email").val(holderEmail);
							});
						}
					});
				});
			});
			return false;
		});
	</script>
	<?php
}

/* 	Função para chamar o formulário basta adicionar smo_call_form(); na posição onde deseja
	que o formulário apareça. Note que essa função já invoca o script, se optar por usar ela
	não será necessário invocar o script manualmente. Se optar por criar um formulário manualmente
	será necessário 3 identificações para o script funcionar automaticamente:
	- ID do Form: "email_optin"
	- ID do campo nome: "name"
	- ID do campo email: "email" */
function smo_call_form() {

	//Formulário básico, note que ele não está estilizado
	?>
	<form id="email_optin">
		<div class="form_name">
			<label for="name">nome</label>
			<input type="text" id="name" name="name">
		</div>
		<div class="form_email">
			<label for="email">e-mail</label>
			<input type="text" id="email" name="email">
		</div>
		<input type="submit" value="Cadastrar">
	</form>
	<?php
	
	smo_insert_script();
	
}

//Adicionando pagina no Admin
add_action('admin_menu', 'smo_menu_page');
function smo_menu_page() {
	add_submenu_page( 'tools.php', 'Simple Mail Optin - E-mails Cadastrados', 'E-mails Cadastrados', 'manage_options', 'smo-emails-cadastrados', 'smo_menu_page_callback' ); 
}

function smo_menu_page_callback() {
	//Pagina no admin para mostrar emails
	//cadastrados e exportação
	global $wpdb;
	$table_name = $wpdb->prefix . "smo_emails";
	
	echo '<h1>E-mails Cadastrados</h1>';
	
	$emails = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE id > 0");
	
	echo "<li>Total de E-mails cadastrados: $emails</li>";
	echo "<li>Exportar Emails como: <a href='?page=smo-emails-cadastrados&download=csv' id='smo_download_csv'>CSV</a></li>";
	
	$emails = $wpdb->get_results("SELECT * FROM $table_name WHERE id > 0 ORDER BY id DESC LIMIT 50");
	echo '<h1>Últimos 50 E-mails Cadastrados</h1>';
	echo '<table class="widefat"><thead><tr><th>ID</th><th>Nome</th><th>E-mail</th></tr></thead><tbody>';
	foreach ( $emails as $email )  {
		echo "<tr><td>$email->id</td><td>$email->name</td><td>$email->email</td></tr>";
	}
	echo '</tbody><tfooter><tr><th>ID</th><th>Nome</th><th>E-mail</th></tr></tfooter></table>';
	
}

function smo_export() {
	//Função para exportação dos emails
	if ($_GET['page']=='smo-emails-cadastrados') {
		if ($_GET['download']=='csv') {
			if (current_user_can('manage_options')) {
				global $wpdb;
				$table_name = $wpdb->prefix . "smo_emails";
				
				//Alterando o output pra identificar o CSV
				header('Content-Type: text/csv; charset=utf-8');
				header('Content-Disposition: attachment; filename=data.csv');
				
				//Criando arquivo para download
				$output = fopen('php://output', 'w');
				fputcsv($output, array('ID', 'Nome', 'Email'));

				$rows = $wpdb->get_results("SELECT * FROM $table_name WHERE id > 0 ORDER BY id DESC LIMIT 100");
				foreach ( $rows as $row )  {
					fputcsv($output, array($row->id,$row->name,$row->email));
				}
				
				die();
			}
		}
	}
	
}
add_action('init', 'smo_export');
