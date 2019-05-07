<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

// REQUIRE
require_once 'helper.php';

// Check that the request that’s being made is an Ajax request 
if (is_ajax()) {
    if (!empty($_POST["resource"]) && !empty($_POST["site"]) && !empty($_POST["type"])) {        
        $resource = $_POST['resource'];        
        $site = $_POST['site'];
        $type = $_POST['type'];
        
        // Change path to the current site directory
        chdir("../../zone/". $resource . "/" . $site);
        
        if ($type == 'create') {
            if (!isGit($resource,$site)) {            
                CreateGit($resource,$site);
            }
            // already exists
            else {
                echo json_encode(999);
            }
        }
        else if ($type == 'query') {
            queryGit($resource,$site);
        }
    }
    else {
        echo json_encode (new stdClass);
        exit;
    }
}
else {
    echo json_encode (new stdClass);
    exit;
}

// Reverse action on checkpoint
// If checkpoint less than 0, do nothing
function reverseCheckpoint($checkpoint,$bitbucketApiUrl="") {
    // Undo local repo
    if ($checkpoint > 0) {
        // Checkpoint 1 is any of the following condition
        // * Can not create local GIT Repo
        // * Can not create .gitignore
        // * Can not git add
        // * Can not commit local repo                
        if (file_exists ( '.git'))
            exec('rmdir /S/Q .git 2>&1');
        if (file_exists('.gitignore'))
            unlink('.gitignore');
    }
    // Undo svn propset commit
    if ($checkpoint > 1) {
        // Checkpoint 2 is any of the following condition
        // * Can not svn proset
        // * Can not commit proset
        exec('"C:\Program Files\TortoiseSVN\bin\svn.exe" propdel svn:global-ignores --recursive 2>&1');
        exec('"C:\Program Files\TortoiseSVN\bin\svn.exe" commit --config-dir E:/Subversion --trust-server-cert --non-interactive --username ratservice --password GEODE -m "revert global-ignores for .git" 2>&1');     
    }
    // Undo remote repo
    if ($checkpoint > 2) {
        // Checkpoint 3 is any of the following condition
        // * Can not create remote git repo 
        // * Can not setup remote git URL        
        // * Can not git push to remote repo
		// * Can not update Git status on sites table
        if ($bitbucketApiUrl) 
            deleteGitRepo($bitbucketApiUrl);
    }       

    echo json_encode("Failed at Checkpoint #" . $checkpoint . " Reversed all previous steps.");
    exit;
}

// Function for running all steps
function CreateGit ($resource,$site) {
    // Change directory
    //chdir("../../zone/". $resource . "/" . $site);

    // Get current directory
    //echo json_encode(getcwd());

    // Array of action status
    // [0] = git init 
    // [1] = get up .gitignore
    // [2] = add everything into local git repo
    // [3] = git commit
    // [4] = ignore .git in SVN
    // [5] = svn commit ignore property
    // [6] = bitbucket remote create repo via API
    // [7] = setup bitbucket remote repo URL
    // [8] = git push everything into bitbucket (via ssh)
	// [9] = update sites table set isgit to Y
    // value of 0 means no error, anything else means 
    $return_var = array();
    //slug need to be lower case so we need to convert resource and site to lowercase
    $bitbucketApiUrl = BITBUCKETAPI . "atweb-wzde-" . strtolower($resource) . "-" . strtolower($site);
    
    // // For debug only this will reverse all git repo operation 
    // reverseCheckpoint(3,$bitbucketApiUrl);
    // exit;
    

    // Create local GIT repo, add .svn to .gitignore
    exec('"C:\Program Files\Git\cmd\git.exe" init 2>&1', $output, $return_var[0]);
    if ($return_var[0] !== 0) reverseCheckpoint(0);
    exec('echo .svn > .gitignore', $output, $return_var[1]);
    if ($return_var[1] !== 0) reverseCheckpoint(1);
    exec('"C:\Program Files\Git\cmd\git.exe" add . 2>&1', $output, $return_var[2]);
    if ($return_var[2] !== 0) reverseCheckpoint(1);
    exec('"C:\Program Files\Git\cmd\git.exe" commit -m "initial git repo"', $output, $return_var[3]);
    if ($return_var[3] !== 0) reverseCheckpoint(1);


    // Ignore .git .gitignore in SVN
    exec('"C:\Program Files\TortoiseSVN\bin\svn.exe" propset --config-dir E:/Subversion --trust-server-cert --non-interactive --username ratservice --password GEODE svn:global-ignores ".git" . 2>&1', $output, $return_var[4]);
    if ($return_var[4] !== 0) reverseCheckpoint(2);
    exec('"C:\Program Files\TortoiseSVN\bin\svn.exe" commit --config-dir E:/Subversion --trust-server-cert --non-interactive --username ratservice --password GEODE -m "add global-ignores for .git" 2>&1', $output, $return_var[5]);
    if ($return_var[5] !== 0) reverseCheckpoint(2);
    
    // Create remote repo on bitbucket cloud
    $return_var[6] = createGitRepo($bitbucketApiUrl);
    if ($return_var[6] !== 0) reverseCheckpoint(3,$bitbucketApiUrl);

    // Add remote repo (ssh)
    $remoteUrl = "git@bitbucket.org:mtdevs/atweb-wzde-" . $resource . "-" . $site . ".git";
    $addRemote = '"C:\Program Files\Git\cmd\git.exe" remote add origin '. $remoteUrl . ' 2>&1' ;
    exec($addRemote, $output, $return_var[7]);
    if ($return_var[7] !== 0) reverseCheckpoint(3,$bitbucketApiUrl);

    // Push local repo to server
    exec('"C:\Program Files\Git\cmd\git.exe" push --set-upstream origin master 2>&1', $output, $return_var[8]);
    if ($return_var[8] !== 0) reverseCheckpoint(3,$bitbucketApiUrl);
	
	// Update git status on sites table
	$return_var[9] = updateGitStatus($resource,$site);
	if ($return_var[9] !== 0) reverseCheckpoint(3,$bitbucketApiUrl);

    // Return the sum of error code, if no error in any of the previous steps the sum equal 0 
    echo json_encode(array_sum($return_var));
}

//in :0=resource 1=site
//out:0 for success, 1 for error
function updateGitStatus($resource,$site) {
	$bindV = array(
        'resource' => $resource,
        'sitename' => $site
    );
    $form_fields = array(
                     'query' => "update webutility.sites set isgit = 'Y' where resource = :resource and sitename = :sitename",
                     'bindV' => $bindV,
                     );
                     
    $query=$form_fields['query'];
    $bindV=$form_fields['bindV'];
    
    $database = new mysql_pdo();
    
    $database->query($query);
    
    foreach ($bindV as $key=>$val) {
        $database->bind(':'.$key, $val);
    }
    $database->execute();  

    //false equal no error
    if (!$database->getLastError()==='00000'){
        return 1;
    }
    else {
        return 0;
    }
}	


//in :0=bitbucket API URL
//out:0 for success, 1 for error
function createGitRepo($bitbucketApiUrl) {
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $bitbucketApiUrl,
        CURLOPT_RETURNTRANSFER => true,
        //SSL
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSL_VERIFYPEER => false,
        //
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "{\"is_private\": true, \"scm\":\"git\", \"project\": {\"key\": \"ATWEB\"}}",
        CURLOPT_HTTPHEADER => array(
        "authorization: Basic aXFib3NzOjZldE5aVXJXemdMS3ZSeXlleXBk",
        "cache-control: no-cache",
        "content-type: application/json"
        )
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    
    if ($err) return 1;
    return 0;
}

//in :0=bitbucket API URL
//out:0 for success, 1 for error
function deleteGitRepo($bitbucketApiUrl) {
$curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $bitbucketApiUrl,
        CURLOPT_RETURNTRANSFER => true,
        //SSL
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSL_VERIFYPEER => false,
        //  
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_HTTPHEADER => array(
            "authorization: Basic aXFib3NzOmQ5Q2h3cUV3ZVpQbkxrVDl4Nlgy",
            "cache-control: no-cache",
            "content-type: application/json"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    
    if ($err) return 1;
    return 0;
}

//end of php