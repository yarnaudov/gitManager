<?php

	session_start();

	# password to use for login
	$password = '123456';

	# get all git projects
	$git_projects = shell_exec('find ./ -type d -name ".git";');
	$git_projects = explode("/.git", $git_projects);

	# login user
	if(isset($_POST['password'])){

		if($_POST['password'] === $password){
			$_SESSION['logged_in'] = TRUE;
			header('location: git.php');
		}
		else{
			$_SESSION['logged_in'] = FALSE;
			$error = 'Incorect password !';
		}

	}

	# actions
	if(isset($_REQUEST['action'])){

		$date = date('Y-m-d H:i:s');

		switch($_REQUEST['action']){

			#create repository
			case 'create_repo':

				$cmd = 'git clone '.$_REQUEST['repo_url'].' '.$_REQUEST['repo_name'];
  				echo $date." - ".$cmd."<br/>";
                echo $date." - ".shell_exec($cmd)."<br/>";
                echo $date." - "."done<br/><br/>";

			break;

			#get repository info
			case 'info':

				$cd  = 'cd '.$_REQUEST['repo'].';';
				$cmd = 'git branch -a;';

				$branches = shell_exec($cd.$cmd );

				$repo_data['output']  = '';//$date." - ".$cd."<br/>";
				$repo_data['output'] .= $date." - ".$cmd."<br/>";
				$repo_data['output'] .= $date." - ".$branches."<br/>";

				$repo_data['branches'] = explode("\n", $branches);

				$repo_data['info_branches'] = nl2br($branches)."<br/>";

				$repo_info = shell_exec($cd.'git remote show origin');
				$repo_data['info'] .= nl2br($repo_info)."<br/>";

				$repo_data['output'] .= $date." - git remote show origin<br/>";
				$repo_data['output'] .= $date." - ".nl2br($repo_info);
				$repo_data['output'] .= $date." - "."done<br/><br/>";

				echo json_encode($repo_data);

			break;

			#pull remote branch
			case 'pull':

				$cd = 'cd '.$_REQUEST['repo'].';';

				$branch = end(explode('/', trim($_REQUEST['branch'])));

				//echo $branch." - branch <br/>\n";

				$cmd = 'git pull origin '.$branch.';';

				echo $date." - ".$cmd."<br/>";
				echo $date." - ".nl2br(shell_exec($cd.$cmd ));
				echo $date." - "."done<br/><br/>"; 

			break;

			#pull remote branch
			case 'switch':

				$cd = 'cd '.$_REQUEST['repo'].';';

				$branch = end(explode('/', trim($_REQUEST['branch'])));

				$cmd = 'git branch;';
				$branches = shell_exec($cd.$cmd );
				$branches = explode("\n", $branches);
				foreach($branches as $k => $v){

					$v = trim(str_replace("*", "", $v));
					if(empty($v)){
						unset($branches[$k]);
						continue;
					}

					$branches[$k] = $v;

				}

				if(in_array($branch, $branches)){
					$cmd = 'git checkout '.$branch.';';
				}
				else{
					$cmd = 'git checkout -b '.$branch.';';
				}

				echo $date." - ".$cmd."<br/>";

				$output = trim(shell_exec($cd.$cmd));

				if(!empty($output)){
					echo $date." - ".nl2br($output)."<br/>";
				}

				echo $date." - "."done<br/><br/>";

			break;

		}

		exit;

	}


?>

<!DOCTYPE html>
<html>

	<head>
		<title>Git Repositories Manager</title>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.0/jquery.min.js"></script>
		<script src="http://twitter.github.com/bootstrap/assets/js/bootstrap-modal.js" ></script>
		<script src="http://twitter.github.com/bootstrap/assets/js/bootstrap-button.js" ></script>
		<link href="http://twitter.github.com/bootstrap/assets/css/bootstrap.css" rel="stylesheet">

		<style>

			.modal-body{
				padding: 15px 35px;
			}
			#ModalNewRepo{
				width: 462px;
			}
			.left{
				float: left;
			}
			.right{
				float: right;
			}
			.page-header{
				overflow: auto;
				border-bottom: 1px solid #eeeeee;
				padding: 0 20px 10px 20px;
			}
			#output_main{
				border-top: 1px solid #aaaaaa;
				font-family: 'lucida console';								
				position: absolute;
				bottom: 0;
				width: 100%;
			}
			#output_main #output{				
				height: 300px;
				padding: 5px 10px;
				color: #666;
				overflow-y: auto;
				font-size: 12px;
			}
			#output_main #header{
				background-color: #eee;
				padding: 2px 10px;
			}
			#main_content{
				padding: 0 30px;
			}
			#projects{
				width: 100%;
				height: 300px;
			}
			#repo_list{
				float: left;
				width: 18%;
			}
			#repo_info{
				float: right;
				border: 1px solid #cccccc;
				height: 300px;
				width: 80%;
			}
			#repo_info.loading{
				background-color: #eee;
			}
			#repo_info table{
				width: 100%;
			}
			#repo_info table td{
				padding: 20px;
				vertical-align: top;
			}
			#repo_info table .actions{
				width: 300px;
			}
			#repo_info table .info_branches{
				width: 250px;
			}
			#repo_info .btn{
				margin: -10px 0 0 5px;
			}
			#repo_info span{
				display: block;
				border-bottom: 1px solid #bbbbbb;
				margin-bottom: 10px;
			}
			#repo_info table .info div{
				overflow: auto; 
				height: 220px;
			}

		</style>

	</head>

	<body>

		<?php if(@$_SESSION['logged_in'] != TRUE){ ?>

		<!-- Modal Dialog Log in -->
		<div id="LogInModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		  <div class="modal-header">
		    <h3 id="myModalLabel">Please Log in </h3>
		  </div>
		  <div class="modal-body">
		    <p>
		    	<form class="form-inline" method="post">
		    		<label>Password:</label>
		    		<input type="password" name="password" >
		    		<?php if(isset($error)){ ?>
		    			<span class="text-error" ><?php echo $error; ?></span>
		    		<?php } ?>
		    	</form>
		    </p>
		  </div>
		  <div class="modal-footer">
		    <button class="btn btn-primary">Log In</button>
		  </div>
		</div>

		<script type="text/javascript" >

			$('#LogInModal').modal({
  				show: true
			});

			$('.btn-primary').on('click', function(){
				$('.form-inline').submit();
			});

		</script>

	    <?php }else{ ?>		

	    <h3 class="page-header" >
	    	<span class="left" >Git Repository Manager</span>
	    	<a href="#ModalNewRepo" role="button" class="btn btn-primary right create_repo" data-toggle="modal" data-loading-text="Creating repository..." >Create new repository</a>
		</h3>
		
		<div id="main_content" >

			<div id="repo_list">
				<select id="projects" size="10" >
					<?php foreach($git_projects as $git_project){ 
						    $git_project = trim($git_project);
					        if(empty($git_project)){
					            continue;
					        } ?>
					<option value="<?php echo $git_project; ?>" ><?php echo $git_project; ?></option>
					<?php } ?>
				</select>
				<label>Git repositories</label>
			</div>

			<div id="repo_info" >
				<table>
					<tr>
						<td class="info_branches">No repository selected</td>
						<td class="info"></td>
						<td class="actions"></td>
					</tr>
				</table>
			</div>

		</div>

		<!-- Modal Create New Repo-->
		<div id="ModalNewRepo" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
				<h3 id="myModalLabel">Create Repository</h3>
			</div>
			<div class="modal-body">
				<br/>
				<input type="text" id="repo_url" placeholder="URL" class="input-xlarge" >
				<input type="text" id="repo_name" placeholder=" Name" class="input-small" >
			</div>
			<div class="modal-footer">
				<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
				<button class="btn btn-primary" id="createRepoBtn" >Create</button>
			</div>
		</div>


		<div id="output_main">
			<div id="header" >Commands Output</div>
			<div id="output" ></div>
		</div>

		<script type="text/javascript" >

			var repo;

			// automaticaly resize output conteiner
			$(window).on('resize load', function() {

				var body_height = $(window).height();
				var output_height = Math.round((body_height*40)/100);
				output_height = body_height-480;
				$('#output').css('height', output_height);

			});

			// create new repository
			$('#createRepoBtn').on('click', function(){

				$('#ModalNewRepo').modal('hide');
				$('.create_repo').button('loading');

				var repo_url  = $('#repo_url').val();
				var repo_name = $('#repo_name').val();

				$.get('git.php', {action: 'create_repo', repo_url: repo_url, repo_name: repo_name}, function(data){

					$('.create_repo').button('reset');

					$('#output').html($('#output').html()+data).trigger('change');
					$('#projects').append('<option value="'+repo_name+'" >./'+repo_name+'</option>');

              	});

			});


			// load repository info
			$('#projects').on('change', function(){

				repo = $(this).val();

				$('#repo_info .info_branches').html('');
				$('#repo_info .info').html('');
				$('#repo_info .actions').html('');

				$('#repo_info').addClass('loading');
				

				$.get('git.php', {action: 'info',repo: repo}, function(data){
                
                	$('#repo_info').removeClass('loading');

                	data = JSON.parse(data);
               
                	$('#output').html($('#output').html()+data['output']).trigger('change');

                	$('#repo_info .info_branches').html('<span>Branches</span>'+data['info_branches']);
                	$('#repo_info .info').html('<span>Info</span><div>'+data['info']+'</div>');


                	// create list with remote branches
                  	$('#repo_info .actions').append('<span>Actions</span><select id="remote_branch" class="input-large" ></select>');
                    $(data['branches']).each(function(index){
                        if(data['branches'][index].search(/remotes/) != -1){
                            $('#remote_branch').append('<option value="'+data['branches'][index]+'" >'+data['branches'][index]+'</option>');
                        }
                    });
                   

									// add pull from branch button 
                	$('#repo_info .actions').append('<button id="pull" class="btn btn-warning btn-small" data-loading-text="Pulling..." >Pull</button>');
                	$('#pull').on('click', function(){
              			
              			$(this).button('loading');
              			
		              	$.get('git.php', {action: 'pull', repo: repo, branch: $('#remote_branch').val()}, function(data){
		              	    
		              	    $('#pull').button('reset');		              	    
		              	    $('#output').html($('#output').html()+data).trigger('change');
		              	    
		              	});
	              	
	            	});

	            	// create list with all branches
	          		$('#repo_info .actions').append('<select id="branch" class="input-large" ></select>');                  
                    $(data['branches']).each(function(index){
                    	$('#branch').append('<option value="'+data['branches'][index]+'" >'+data['branches'][index]+'</option>');
                    });	            	
                    
                // add switch to branch button 
                    $('#repo_info .actions').append('<button id="switch" class="btn btn-danger btn-small" data-loading-text="Switching..." >Switch</button>');
                    $('#switch').on('click', function(){
              			
              			$(this).button('loading');
              			
		              	$.get('git.php', {action: 'switch', repo: repo, branch: $('#branch').val()}, function(data){
		              		
		              	    $('#switch').button('reset');
		              	    $('#output').html($('#output').html()+data).trigger('change');
		              	    $('#projects').trigger('change');
		              	    
		              	});
	              	
	          		});


				});

			});
		
			$('#output').on('change', function(){
				$(this).scrollTop(1000000);
			});

		</script>

	    <?php } ?>

	</body>

</html>
