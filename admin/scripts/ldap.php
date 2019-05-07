<?php

Class MeditechLDAP{
    const host = 'meditechadc1';
    const domain = 'meditech.com';
#   const userdn = 'cn=users,dc=meditech,dc=com';	# the chefs are not in users, expanding the AD search below
    const userdn = 'dc=meditech,dc=com';
    const groupsdn = 'ou=groups,dc=meditech,dc=com';
    const bindUser = "CN=CoreAD,OU=Services,DC=meditech,DC=com";//does not have to be DN but cross-compatible
    const bindPass = "smog.hero.silt";

    function __construct(){
        $this->ad = ldap_connect("ldap://".MeditechLDAP::host.".".MeditechLDAP::domain);
        ldap_set_option($this->ad, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->ad, LDAP_OPT_REFERRALS, 0);
        ldap_bind($this->ad, MeditechLDAP::bindUser, MeditechLDAP::bindPass);
    }

    function search($dn,$q,$args=null){
        $res = ldap_search($this->ad,$dn,$q,$args);
        if($res)
            return ldap_get_entries($this->ad, $res);
        else return false;
    }

    function getUser($user){
        $res = $this->search(MeditechLDAP::userdn,"(samaccountname={$user})");
        return $res[0];
    }

    function getGroupDNs($user){ 
        $ret = $this->search(MeditechLDAP::userdn,"(samaccountname={$user})",array('memberof'));
        return $ret[0]['memberof'];
    }

    function getGroups($user){
        $ret = array();
        foreach($this->getGroupDNs($user) as $group){
            preg_match('/CN=(.*?),/',$group,$matches);
            if (count($matches)){
                $ret[] = $matches[1];
            }
        } 
        return $ret;
    }

    function isMember($user,$groups){
        //non-recursively checks the user object if user is a member of a group.
        //note that you are not passing in the DN of a group so there is the possibility of overlap if you have multiple groups of the same CN with different DNs
        $auth_groups = $this->getGroups($user);
        if ($auth_groups) foreach($auth_groups as $ag) {        
        	if (in_array($ag,$groups)) return true;
	}
	return false;	
    }

    function authenticate($user,$pass){
        return ldap_bind($this->ad,$user.'@'.MeditechLDAP::domain,$pass);
    }

    function __destruct(){
        ldap_unbind($this->ad);
    }
}

?>