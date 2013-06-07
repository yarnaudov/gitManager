<?php

if($_REQUEST['action'] == 'create'){

  $cmd = 'git clone '.$_POST['repo_url'].' '.$_POST['repo_name'];
  echo $cmd."<br/>";
  echo shell_exec($cmd);

}
elseif($_REQUEST['action'] == 'pull'){

  $cd = 'cd '.$_REQUEST['repo'].';';

  $branch = end(explode('/', trim($_REQUEST['branch'])));
  
  echo $branch." - branch <br/>\n";
  
  $cmd = 'git pull origin '.$branch.';';
  
  echo $cmd."<br/>\n";
  echo shell_exec($cd.$cmd );
  
  exit;

}
elseif($_REQUEST['action'] == 'switch'){

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
  
  echo $branch."\n";
  
  if(in_array($branch, $branches)){
    $cmd = 'git checkout '.$branch.';';
  }
  else{
    $cmd = 'git checkout -b '.$branch.';';
  }
  
  echo $cmd."\n";
  
  echo shell_exec($cd.$cmd );
  
  exit;

}
elseif($_REQUEST['action'] == 'info'){

  $cd = 'cd '.$_REQUEST['repo'].';';

  $cmd = 'git branch -a;';
  $branches = shell_exec($cd.$cmd );
  
  $repo_data['branches'] = explode("\n", $branches);
  
  $repo_data['info'] = nl2br($branches)."<br/>";
  
  $repo_info = shell_exec($cd.'git remote show origin');
  $repo_data['info'] .= nl2br($repo_info)."<br/>";
  
  echo json_encode($repo_data);
  
  exit;

}

$cmd = 'git clone https://github.com/yarnaudov/yvaCMS.git opala;';
$cmd .= 'git origin master;';

//$cmd = 'cd test;git branch -a;';

//echo exec($cmd);

$git_projects = shell_exec('find ./ -type d -name ".git";');
$git_projects = explode("/.git", $git_projects);


echo "<!DOCTYPE html>\n";
echo "<html>\n";

echo "  <head>\n";

echo "    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8;\" />\n";

echo "    <script src=\"//ajax.googleapis.com/ajax/libs/jquery/1.10.0/jquery.min.js\"></script>\n";
echo "    <script type=\"text/javascript\" >
          
            $(function(){
            
              var repo;
          	
              $('select[name=project]').on('change', function(){
		
		repo = $(this).val();
		
                $.get('git.php', {action: 'info',repo: repo}, function(data){
                
                  data = JSON.parse(data);
                  $('#repo_info').html(data['info']);
                  
                   
                  $('#repo_info').append('<button id=\"pull\" >Pull from branch</button>');
                  $('#pull').on('click', function(){
              	
	              	$.get('git.php', {action: 'pull', repo: repo, branch: $('#remote_branch').val()}, function(data){
	              	  console.log(data);
	              	  $('select[name=project]').trigger('change');
	              	});
	              	
	          });
	          
                  $('#repo_info').append('<select id=\"remote_branch\" ></select>');
                  $(data['branches']).each(function(index){
                    if(data['branches'][index].search(/remotes/) != -1){
                      $('#remote_branch').append('<option value=\"'+data['branches'][index]+'\" >'+data['branches'][index]+'</option>');
                    }
                  });
                  
                  $('#repo_info').append('<br/><br/><button id=\"switch\" >Switch to branch</button>');
                  $('#switch').on('click', function(){
              	
	              	$.get('git.php', {action: 'switch', repo: repo, branch: $('#branch').val()}, function(data){
	              	  console.log(data);
	              	  $('select[name=project]').trigger('change');
	              	});
	              	
	          });
                  
                  $('#repo_info').append('<select id=\"branch\" ></select>');                  
                  $(data['branches']).each(function(index){
                    $('#branch').append('<option value=\"'+data['branches'][index]+'\" >'+data['branches'][index]+'</option>');
                  });
                  
                  
                });
                
              });
              
            });
          
          </script>\n";
echo "  </head>\n";

echo "  <body>\n";

echo "    <form method=\"post\" action=\"\" >\n";
echo "      <input type=\"hidden\" name=\"action\" value=\"create\" >\n";
echo "      <h3>Create new repository</h3>\n";
echo "      <input type=\"text\" name=\"repo_url\" style=\"width: 400px;\" >\n";
echo "      <input type=\"text\" name=\"repo_name\" style=\"width: 80px;\" >\n";
echo "      <button type=\"submit\" id=\"create\" >Create</button>";
echo "    </form>\n";

echo "    <h3>Browse repositories</h3>\n";
echo "    <label>Available git repos:</label><br/>\n";
echo "    <select name=\"project\" style=\"float: left;width: 300px;height: 150px;\" size=\"5\" >\n";
foreach($git_projects as $git_project){
    echo "<option value=\"".trim($git_project)."\" >".trim($git_project)."</option>\n";
}
echo "    </select>\n";

echo "<div id=\"repo_info\" style=\"float: left;width: 500px;min-height: 400px;border: 1px solid #aaa;padding: 10px;margin-left: 10px;\" ></div>\n";

echo "  </body>\n";

echo "</html>\n";
