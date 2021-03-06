<?php

    session_start();

    # password to use for login
    $password = 'e10adc3949ba59abbe56e057f20f883e';

    if(!isset($_REQUEST['action'])){
        $pwd = shell_exec('pwd;');
    	setcookie('path', trim($pwd));
    	$_COOKIE['path'] = $pwd;
    }

    # get all git projects
    if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == TRUE){
        $git_projects = shell_exec('find ./ -type d -name ".git";');
        $git_projects = explode("/.git", $git_projects);
        $git_projects_texts = $git_projects = array_reverse($git_projects);
		$used_keys = array();
		
		foreach($git_projects as $key1 => $git_project1){
			$git_projects[$key1] = $git_project1 = trim($git_project1);
			if(empty($git_project1)){unset($git_projects[$key1]);continue;}
			$git_project1 = str_replace('/', '\/', $git_project1);
			foreach($git_projects as $key2 => $git_project2){
				$git_project2 = trim($git_project2);
				if(empty($git_project2)){unset($git_projects[$key2]);continue;}
				if(!in_array($key2, $used_keys) && preg_match('/'.$git_project1.'\//', $git_project2)){
					$nbsp = count(explode('/', $git_project1));
					$nbsp_str = '';
					for($i = 0; $i <= $nbsp; $i++){
						$nbsp_str .= '&nbsp;';
					}
					$git_projects_texts[$key2] = preg_replace('/'.$git_project1.'\//', $nbsp_str.'-&nbsp;/', $git_project2);
					$used_keys[] = $key2;
				}
			}
		}
		
		$git_projects = array_reverse($git_projects);
		$git_projects_texts = array_reverse($git_projects_texts);
    }

    # login user
    if(isset($_POST['password'])){

        if(md5($_POST['password']) === $password){
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

        switch($_REQUEST['action']){

            #create repository
            case 'create_repo':

                $cmd = 'git clone '.$_REQUEST['repo_url'].' '.$_REQUEST['repo_name'].' 2>&1;';
                echo '<div><p>'.date('H:i:s')."</p><p>".$cmd."</p></div>";
                echo '<div><p>'.date('H:i:s')."</p>".trim(shell_exec($cmd))."</p></div>";
                echo '<div class="end_command" ><p>'.date('H:i:s')."</p><p>end command</p></div>";

            break;

            #get repository info
            case 'info':
			
                $cd  = 'cd '.$_REQUEST['repo'].';';
                
                $pwd = shell_exec($cd.'pwd;');
				setcookie('path', trim($pwd));
					
                $cmd = 'git branch -a 2>&1;';

                $branches = trim(shell_exec($cd.$cmd));
		        
                $repo_data['output']  = '<div><p>'.date('H:i:s').'</p><p>'.$cmd.'</p></div>';
                $repo_data['output'] .= '<div><p>'.date('H:i:s').'</p><p>'.$branches.'</p></div>';
                $repo_data['output'] .= '<div class="end_command" ><p>'.date('H:i:s').'</p><p>end command</p></div>';

                $repo_data['branches'] = explode("\n", $branches);

                $repo_data['info_branches'] = nl2br($branches)."<br/>";

                $cmd = 'git remote show origin 2>&1;';
                $repo_info = trim(shell_exec($cd.$cmd));
                $repo_data['info'] = nl2br($repo_info)."<br/>";

                $repo_data['output'] .= '<div><p>'.date('H:i:s').'</p><p>'.$cmd.'</p></div>';
                $repo_data['output'] .= '<div><p>'.date('H:i:s').'</p><p>'.$repo_info.'</p></div>';
                $repo_data['output'] .= '<div class="end_command" ><p>'.date('H:i:s').'</p><p>end command</p></div>';

                echo json_encode($repo_data);

            break;

            #pull remote branch
            case 'pull':

                $cd = 'cd '.$_REQUEST['repo'].';';

                $branch = end(explode('/', trim($_REQUEST['branch'])));

                $cmd = 'git pull origin '.$branch.' 2>&1;';

                echo '<div><p>'.date('H:i:s').'</p><p>'.$cmd.'</p></div>';
                echo '<div><p>'.date('H:i:s').'</p><p>'.trim(shell_exec($cd.$cmd)).'</p></div>';
                echo '<div class="end_command" ><p>'.date('H:i:s').'</p><p>end command</p></div>';

            break;

            #pull remote branch
            case 'switch':

                $cd = 'cd '.$_REQUEST['repo'].';';

                $branch = end(explode('/', trim($_REQUEST['branch'])));

                $cmd = 'git branch 2>&1;';
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
                    $cmd = 'git checkout '.$branch.' 2>&1;';
                }
                else{
                    $cmd = 'git checkout -b '.$branch.' 2>&1;';
                }

                $output = trim(shell_exec($cd.$cmd));

                echo '<div><p>'.date('H:i:s').'</p><p>'.$cmd.'</p></div>';
                if(!empty($output)){
                    echo '<div><p>'.date('H:i:s').'</p><p>'.$output.'</p></div>';
                }
                echo '<div class="end_command" ><p>'.date('H:i:s').'</p><p>end command</p></div>';

            break;

            #fetch remote branches
            case 'fetch':

                $cd = 'cd '.$_REQUEST['repo'].';';
                $cmd = 'git fetch 2>&1;';

                echo '<div><p>'.date('H:i:s').'</p><p>'.$cmd.'</p></div>';
                echo '<div><p>'.date('H:i:s').'</p><p>'.trim(shell_exec($cd.$cmd)).'</p></div>';
                echo '<div class="end_command" ><p>'.date('H:i:s').'</p><span>end command</p></div>';

            break;
			
			# exec custom command
			case 'custom_command':
				
				$cd = '';
				if(isset($_COOKIE['path']) && !empty($_COOKIE['path'])){
					$cd = 'cd '.$_COOKIE['path'].';';
				}
				
				$cmd = $_REQUEST['command'];
				
				preg_match('/cd (.*);|cd (.*)/', $cmd, $match);
				if(isset($match[0])){
					$match[0] = preg_replace('/;$/', '', $match[0]);
					$pwd = trim(shell_exec($cd.$match[0].';pwd;'));
					setcookie('path', $pwd);
					$_COOKIE['path'] = $pwd;
				}

				if(preg_match('/(vi |vim |cat |more |edit )/', $cmd)){
				
				    if(preg_match('/(edit )/', $cmd)){
				        $file['name'] = preg_replace('/(edit )/', '', $cmd);
				    }
				    else{
                        $file['name'] = preg_replace('/(cd )/', '', $cd).'/'.preg_replace('/(vi |vim |cat |more |cd )/', '', $cmd);
				    }
				    $file['name'] = preg_replace('/;/', '/', $file['name']);
					$file['name'] = preg_replace('/(\/\/)/', '/', $file['name']);
					
					$cmd = preg_replace('/(vi |vim |edit )/', 'cat ', $cmd);
					
					$file['data'] = shell_exec($cd.$cmd);
					echo json_encode($file);
				}
				else{
                    $cmd = trim($cmd, ';')." 2>&1;";
                    echo '<div><p>'.date('H:i:s').'</p><p>'.$cmd.'</p></div>';
					$cmd_output = shell_exec($cd.$cmd);
					if($cmd_output){
                        
                        $cmd_output = preg_replace('/(d[rwx-]{9}.*[1-9]{2}:[1-9]{2} )(.*\n)/', '$1<span class="dir" data-path="'.$_COOKIE['path'].'" >$2</span>', $cmd_output);
                        $cmd_output = preg_replace('/(l[rwx-]{9}.*[1-9]{2}:[1-9]{2} )(.*\n)/', '$1<span class="link" data-path="'.$_COOKIE['path'].'" >$2</span>', $cmd_output);
                        $cmd_output = preg_replace('/(-[rwx-]{9}.*[1-9]{2}:[1-9]{2} )(.*\n)/', '$1<span class="file" data-path="'.$_COOKIE['path'].'" >$2</span>', $cmd_output);

                        echo '<div><p>'.date('H:i:s').'</p><p>'.$cmd_output.'</p></div>';
					}
					echo '<div class="end_command" ><p>'.date('H:i:s').'</p><p>end command</p></div>'; 
				}
				
			break;
			
			# save file
			case 'save_file':
			
				$name = $_REQUEST['name'];
				$data = $_REQUEST['data'];
				
                if(is_writable($name)){
				    echo file_put_contents($name, $data);
                }
                else{
                    echo 0;//'You do not have permission to write in this file';
                }
			
			break;
        
        }

        exit;

    }

?>

<!DOCTYPE html>
<html>

    <head>
	
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8;" />
        <title>Git Repositories Manager</title>
		
	<link href='http://git-scm.com/favicon.png' rel='shortcut icon' type='image/png'>
		
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.0/jquery.min.js"></script>
        <script src="http://getbootstrap.com/2.3.2/assets/js/bootstrap-modal.js" ></script>
        <script src="http://getbootstrap.com/2.3.2/assets/js/bootstrap-button.js" ></script>
		<script src="http://getbootstrap.com/2.3.2/assets/js/bootstrap-dropdown.js" ></script>
        <link href="http://getbootstrap.com/2.3.2/assets/css/bootstrap.css" rel="stylesheet">
		<script src="http://ace.c9.io/build/src-min-noconflict/ace.js"></script>

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
				background-color: #fff;
            }
            #output_main #output{				
                height: 300px;
                padding: 5px 10px;
                color: #666;
                overflow-y: auto;
                font-size: 12px;                
            }
            #output_main #output div{
                display: table-row;
            }
            #output_main #output div.end_command p{
                padding-bottom: 10px;
            }
            #output_main #output div p{
                display: table-cell;
                white-space: pre-wrap;
            }
            #output_main #output div p:first-child{
                border-right: 1px solid #aaa;
                padding-right: 5px;
            }
            #output_main #output div p:last-child{
                padding-left: 5px;
            }
            #output_main #output div p span.dir{
                font-weight: bold;
                cursor: pointer;
            }
            #output_main #output div p span.link{
                color: #2266FF;
                cursor: pointer;
            }
            #output_main #output div p span.file{
                color: #22AA22;
                cursor: pointer;
            }

            #output_main #header{
                background-color: #eee;
                padding: 2px 10px;
            }
			#output_main #header .input-append{
					margin-bottom: 0 !important;
			}
            #main_content{
                    padding: 0 30px;
            }
            #projects{
                    width: 100%;
                    height: 200px;
            }
            #repo_list{
                    float: left;
                    width: 18%;
            }
            #repo_info{
                float: right;
                border: 1px solid #cccccc;
                height: 200px;
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
                width: 320px;
            }
            #repo_info table .info_branches{
                width: auto;
            }
            #repo_info .btn{
                margin: -10px 0 0 5px;
            }
            #repo_info span{
                display: block;
                border-bottom: 1px solid #bbbbbb;
                margin-bottom: 10px;
            }
            #repo_info table .info_branches div{
                overflow: auto; 
                height: 130px;
            }
			
			#custom_commands{
			    background-color: #eee;
                padding: 2px 10px;
			}
			.input-xxlarge{
				padding: 2px 5px !important;
				width: 600px;
			}
			
			#ModalEditFile{
				width: 1000px;
				margin-left: -500px;
				
			}
			#ModalEditFile .modal-body{
				height: 600px;
			}

			.editor { 
				position: absolute;
				top: 0;
				right: 0;
				bottom: 0;
				left: 0;
			}
			
			#fullscreen{
				cursor: pointer;
			}

            #opened_files{
                float: right;
                min-width: 300px;
                max-width: 800px;
            }
            #opened_files ul{
                display: inline-block;
                margin: 0;
                padding: 0;
            }
            #opened_files li{
                list-style: none;
                display: inline-block;
                margin-right: 10px;
            }

        </style>

    </head>

    <body>

        <?php if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] != TRUE){ ?>

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
                    <?php foreach($git_projects as $key => $git_project){  ?>
                    <option value="<?php echo $git_project; ?>" ><?php echo $git_projects_texts[$key]; ?></option>
                    <?php } ?>
                </select>
                <label>Git repositories</label>
            </div>

            <div id="repo_info" >
                <table>
                    <tr>
                        <td class="info_branches">No repository selected</td>
                        <!--<td class="info"></td>-->
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

		<!-- Modal Edit File-->
		<div id="ModalEditFile" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="Edit file" aria-hidden="true">
            <div class="modal-header">
                <!--<button type="button" class="minimize" data-minimize="modal" aria-hidden="true">_</button>-->
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
                <h3 id="myModalLabel">Edit file</h3>
				<span class="file_name" ></span>
				<span class="message" ></span>
            </div>
            <div class="modal-body">				
                <div id="editor" class="editor" ></div>
            </div>
            <div class="modal-footer">
                <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
                <button class="btn btn-primary saveFileBtn" >Save</button>
            </div>
        </div>

        <div id="output_main">
            <div id="header" >
				Commands Output
				<i class="icon-chevron-up pull-right" id="fullscreen"></i>
			</div>
            <div id="output" ></div>
            <div id="custom_commands" >
                <div id="path" ><?php echo isset($_COOKIE['path']) ? $_COOKIE['path'] : ''; ?></div>
                <div class="input-prepend input-append">
                    <div class="btn-group dropup">
                        <button class="btn btn-small dropdown-toggle" data-toggle="dropdown">
							<span class="caret"></span>
						</button>
						<ul class="dropdown-menu pull-left"></ul>
					</div>
    				<input class="input-xxlarge" id="custom_command" type="text" >		
					<button class="btn btn-small" type="button" id="exec_custom_command" >Exec</button>
				</div>
                <div id="opened_files" >
                    <span>Opened files:</span>
                    <ul></ul>
                </div>
			</div>
        </div>

        <script type="text/javascript" >

            var repo;

            // automaticaly resize output conteiner
            $(window).on('resize load', function() {

                $('div[id^=ModalEditFile_][aria-hidden=false]').each(function(){
				    setFullScreenModal($(this).attr('id'));
                });
			
                var body_height = $(window).height();
				
				if($('#fullscreen').hasClass('icon-chevron-up')){
					var output_height = body_height-430;
					$('#output').css('height', output_height);
				}
				else{
					var output_height = $('#output_main').height()-$('#header').height()-$('#custom_commands').height()-18;
				}
				
				$('#output').css('height', output_height);

            });
			
			// toggle output fullscreen
			$('#fullscreen').on('click', function(){
				if($(this).hasClass('icon-chevron-up')){
					$('#output_main').css('height', '100%');
					$('#fullscreen').removeClass('icon-chevron-up').addClass('icon-chevron-down');					
				}
				else{
					$('#output_main').css('height', 'auto');
					$('#fullscreen').removeClass('icon-chevron-down').addClass('icon-chevron-up');
				}
				$(window).trigger('load');
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
                    
                    $.get('git.php', function(data){
                        var project = $('#projects').val();
                        $('#projects').html($('#projects', data).html()).val(project);                        
                    });

              	});

            });


            // load repository info
            $('#projects').on('change', function(){

                repo = $(this).val();

                $('#repo_info .info_branches').html('');
                $('#repo_info .info').html('');
                $('#repo_info .actions').html('');

                $('#repo_info').addClass('loading');
                $('#repo_info .info_branches').html('Loading please wait...');

                $(this).attr('disabled', 'disabled');
                $.get('git.php', {action: 'info',repo: repo}, function(data){

                    $('#projects').removeAttr('disabled');
                
                    $('#repo_info').removeClass('loading');
                    
                    data = JSON.parse(data);

                    $('#output').html($('#output').html()+data['output']).trigger('change');

                    $('#repo_info .info_branches').html('<span>Branches</span><div>'+data['info_branches']+'</div>');
                    //$('#repo_info .info').html('<span>Info</span><div>'+data['info']+'</div>');


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

                    // add fetch branches button 
                    $('#repo_info .actions').append('<br/><br/><button id="fetch" class="btn btn-info btn-small" data-loading-text="Fetching branches..." >Fetch remote branches</button>');
                    $('#fetch').on('click', function(){
              			
                        $(this).button('loading');
              			
                        $.get('git.php', {action: 'fetch', repo: repo}, function(data){
		              		
                            $('#fetch').button('reset');
                            $('#output').html($('#output').html()+data).trigger('change');
                            $('#projects').trigger('change');
		              	    
                        });
	              	
                    });
                    
                    $('#path').html(getCookie('path'));

                });

            });
		
			// exec custom commands
			var cmd_history = new Array();
			var current_cmd;

            var editors = {};            

			$('#exec_custom_command').on('click', function(){
				
				var cmd = $('#custom_command').val();
				
				if(cmd == ""){
					return;
				}
				
				for(var i in cmd_history){
					if(cmd_history[i] == cmd){
						cmd_history.splice(i, 1);
						$('.dropdown-menu li a[href="'+cmd+'"]').parent().remove();
					}
				}
				
				cmd_history.unshift(cmd);
				$('.dropdown-menu').append('<li><a href="'+cmd+'" >'+cmd+'</a></li>');
				current_cmd = -1;
				
				$.get('git.php', {action: 'custom_command', repo: repo, command: cmd}, function(data){
				
					$('#custom_command').val('');
					
					try{

                        var file = $.parseJSON(data);
						
                        var file_modal = $('#ModalEditFile').clone();
                        var count_file_modals = $('div[id^=ModalEditFile_]').length;

                        var modal_id = 'ModalEditFile_'+count_file_modals;
                        var editor_id = 'editor_'+count_file_modals;

                        $(file_modal).attr('id', modal_id);
                        $(file_modal).find('#editor').attr('id', editor_id);

                        $(file_modal).find('.file_name').html(file['name']);
                        $(file_modal).find('#message').html('');

                        $(file_modal).modal('show');
                        
                        editors[editor_id] = createAceEditor(editor_id);
                        editors[editor_id].setValue(file['data'], -1);
                        
                        var mode = cmd.split('.');
                        mode = mode[mode.length-1];
                        if(mode == 'js'){ mode = 'javascript'; }                        
                        editors[editor_id].getSession().setMode('ace/mode/'+mode);
                        
						setFullScreenModal(modal_id);
                        createFileTabs();

					
					}catch(err){
						$('#output').html($('#output').html()+data).trigger('change');
					}
					
					$('#path').html(getCookie('path'));
				
				});
				
			});
			
            $(document).on('click', '.saveFileBtn', function(){
			
                var editor_id = $(this).parents('.modal').find('div[id^=editor]').attr('id');

				var name = $(this).parents('.modal').find('.file_name').html();
				var data = editors[editor_id].getValue();
				
                var message = $(this).parents('.modal').find('.message');
				$(message).html('');
				
				$.post('git.php', {action: 'save_file', name: name, data: data}, function(data){

					if(data > 0){
						$(message).html('&nbsp;-&nbsp;<span class="alert-success" >File successfully saved!</span>');					
					}
					else{
						$(message).html('&nbsp;-&nbsp;<span class="alert-error" >File could not be saved!</span>');						
					}
				});
				
			});
			
			$('#custom_command').on('keyup', function(e){
				if(e.keyCode == 13){
					$('#exec_custom_command').trigger('click');
				}
				else if(e.keyCode == 38){					
					if(cmd_history[current_cmd+1]){
						current_cmd++;
						$(this).val(cmd_history[current_cmd]);
					}
				}
				else if(e.keyCode == 40){					
					if(cmd_history[current_cmd-1]){
						current_cmd--;			
						$(this).val(cmd_history[current_cmd]);
					}
					else{
						current_cmd = -1;
						$(this).val('');
					}
				}
			});
		
			$('.dropdown-menu').on('click', 'a', function(e){			
				e.preventDefault();
				$('#custom_command').val($(this).html()).focus();
			});
			
            $('#output').on('change', function(){
                $(this).scrollTop(1000000);
            });
            
            $(document).on('dblclick', '#output .dir', function(){   
                var cmd = 'cd '+$(this).data('path')+'/'+$(this).html()+'; ls -l';
                $('#custom_command').val(cmd);
                $('#exec_custom_command').trigger('click');
            });
			
			$(document).on('dblclick', '#output .file', function(){   
                var cmd = 'edit '+$(this).data('path')+'/'+$(this).html();
                $('#custom_command').val(cmd);
                $('#exec_custom_command').trigger('click');
            });
			
            function createAceEditor(editor_id){
                
                var editor = ace.edit(editor_id);
                editor.setTheme('ace/theme/monokai');
                editor.commands.addCommand({
                    name: 'save',
                    bindKey: {
                        win: 'Ctrl-S',
                        mac: 'Command-S',
                        sender: 'editor|cli'
                    },
                    exec: function(env, args, request) {
                        $('#'+editor_id).parents('.modal').find('.saveFileBtn').trigger('click');
                    }
                });

                return editor;

            }

			function setFullScreenModal(modal_id){
				$('#'+modal_id).css('width', '100%').css('height', '100%').css('margin', 0).css('top', 0).css('left', 0);
				$('#'+modal_id+' .modal-body').css('height', '100%').css('max-height', '100%');
				$('#'+modal_id+' .modal-body').height($('#'+modal_id).height()-$('#'+modal_id+' .modal-header').height()-$('#'+modal_id+' .modal-footer').height()-80);
			}

            function createFileTabs(){

                $('#opened_files ul').html('');
                $('div[id^=ModalEditFile_]').each(function(){
                    
                    var full_file_name = $(this).find('.file_name').html();
                    var file = full_file_name.split('/');
                    var short_file_name = file[file.length-1]

                    $('#opened_files ul').append('<li><a href="#'+$(this).attr('id')+'" data-toggle="modal" title="'+full_file_name+'" >'+short_file_name+'</a></li>');
                });

            }
			
			function getCookie(c_name){
			
                var c_value = document.cookie;
                var c_start = c_value.indexOf(" " + c_name + "=");
                if (c_start == -1)
                  {
                  c_start = c_value.indexOf(c_name + "=");
                  }
                if (c_start == -1)
                  {
                  c_value = null;
                  }
                else
                  {
                  c_start = c_value.indexOf("=", c_start) + 1;
                  var c_end = c_value.indexOf(";", c_start);
                  if (c_end == -1)
                  {
                c_end = c_value.length;
                }
                c_value = unescape(c_value.substring(c_start,c_end));
                }
                return c_value;
                
            }
			
        </script>

        <?php } ?>

    </body>

</html>
