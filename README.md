Simple Mail Optin
-----------------

Simple Mail Optin é um plugin básico para seu tema WordPress para capturar emails.

Para utilizar o plugin é bem simples:

1. 	Insira o arquivo simple-mail-optin.php na pasta do seu tema

2. 	Carregue o arquivo pelo functions.php do seu tema inserindo a seguinte linha: include_once('simple-mail-optin.php');

3. 	Ative seu tema pelo painel do WordPress. Se já estiver ativo, desative e ative novamente para que a tabela do banco de email seja criada.

4. 	Adicione a função smo_call_form(); em seu tema, no local que preferir.

5. 	Adicione algum estilo em seu arquivo style.css para que o formulário 
	fique legal. basicamente você vai querer estilizar:
	* 	formulário #email_optin
	*	inputs	#name, #email
	*	botão #email_optin > input[type="submit"]
	
6.	Pronto! Seu formulário já estará apto a cadastrar emails.

7.	Acesse o menu "E-mails Cadastrados" para visualizar e exportar os emails.