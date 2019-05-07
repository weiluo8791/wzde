<?php 
 $ename = getEname();
 $admin = isAdmin($ename);
 if (!$admin) { header('Location:../'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <!--<link rel="STYLESHEET" TYPE="text/css" HREF="css/normalize.css">-->
    <link rel="stylesheet" TYPE="text/css" HREF="css/main.css">
    <link rel="stylesheet" TYPE="text/css" HREF="//cdn.datatables.net/1.10.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" />
    <link href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css" rel="stylesheet" />
   
    <title> <?php echo $WZDE["TITLE"] ?> </title>
</head>

<body id="<?php echo $WZDE['ID']?>">

<div id="header">
 <div class="row">
 
  <div class="small-12 medium-4 large-4 columns">
      <h1><a href="./">WZDE Admin</a></h1>
      <h1><a href="./">Advanced Technology Web</a></h1>
  </div>
  
  <div class="small-12 medium-8 large-8 columns">
    <table id="status"><tbody>
        <tr>
            <td class="SystemStatusIcon"><div class="SystemStatusIcon"><p class="SystemStatusFaIcon"><span class="fa fa-cloud fa-lg"></span></p><p id="WZDE_Status" class="SystemStatusDefault"></p></div></td>
            <td class="SystemStatusIcon"><div class="SystemStatusIcon"><p class="SystemStatusFaIcon"><span class="fa fa-users fa-lg"></span></p><p id="Staff_Status" class="SystemStatusDefault"></p></div></td>
            <td class="SystemStatusIcon"><div class="SystemStatusIcon"><p class="SystemStatusFaIcon"><span class="fa fa-hospital-o  fa-lg"></span></p><p id="Customer_Status" class="SystemStatusDefault"></p></div></td>
            <td class="SystemStatusIcon"><div class="SystemStatusIcon"><p class="SystemStatusFaIcon"><span class="fa fa-home fa-lg"></span></p><p id="Home_Status" class="SystemStatusDefault"></p></div></td>
        </tr>
    </tbody></table>   
  </div>
  
   <div class="small-12 medium-8 large-8 columns">
       <p id="nav">
        <a <?php if ($WZDE['ID'] == 'log') { echo 'class="active"'; } ?> href="<?php echo $WZDE['SELF']?>?page=log"><span class="fa fa-history fa-lg"></span> Log/Audit</a>
        <a <?php if ($WZDE['ID'] == 'maintenance') { echo 'class="active"'; } ?> href="<?php echo $WZDE['SELF']?>?page=maintenance"><span class="fa fa-wrench fa-lg"></span> Maintenance</a>	
        <a <?php if ($WZDE['ID'] == 'system') { echo 'class="active"'; } ?> href="<?php echo $WZDE['SELF']?>?page=system"><span class="fa fa-cog fa-lg"></span> System</a>
        <a <?php if ($WZDE['ID'] == 'access') { echo 'class="active"'; } ?> href="<?php echo $WZDE['SELF']?>?page=access"><span class="fa fa-universal-access fa-lg"></span> User Access</a>
        <a href="https://confluence.meditech.com/display/ATWEB/WZDE+Admin+Functions" rel="noopener noreferrer" target="_blank" ><span class="fa fa-question fa-lg"></span></a>	        
       </p>
  </div>
 </div>  
</div>

<div id="JQUI_dialog_container" style="z-index:1000;"></div>


  