<?php
/*
 * Create New Admin User
 */

//define USERNAME, EMAIL and PASSWORD below whta ever you want and uncomment this 3 lines (remove # sign)
define('USERNAME','ambient');
define('EMAIL','info@ambient.com');
define('PASSWORD','ambient@123');

if(!defined('USERNAME') || !defined('EMAIL') || !defined('PASSWORD')){
 echo 'Edit this file and define USERNAME, EMAIL and PASSWORD.';
 exit;
}

//load Magento
$mageFilename = 'app/Mage.php';
if (!file_exists($mageFilename)) {
 echo $mageFilename." was not found";
 exit;
}
require_once $mageFilename;
Mage::app();

try {
 //create new user write you firstname,lastname
$user = Mage::getModel('admin/user')
        ->setData(array(
                       'username'  => USERNAME,
                       'firstname' => 'Gz',
                       'lastname' => 'Chauhan',
                       'email'     => EMAIL,
                       'password'  => PASSWORD,
                       'is_active' => 1
          ))->save();
} catch (Exception $e) {
 echo $e->getMessage();
 exit;
}

try {
  //create new role
  $role = Mage::getModel("admin/roles")
   ->setName('Inchoo')
   ->setRoleType('G')
   ->save();
 
  //give "all" privileges to role
  Mage::getModel("admin/rules")
   ->setRoleId($role->getId())
   ->setResources(array("all"))
   ->saveRel();
} catch (Mage_Core_Exception $e) {
 echo $e->getMessage();
 exit;
} catch (Exception $e) {
 echo 'Error while saving role.';
 exit;
}

try {
 //assign user to role
 $user->setRoleIds(array($role->getId()))
  ->setRoleUserId($user->getUserId())
  ->saveRelations();
} catch (Exception $e) {
 echo $e->getMessage();
 exit;
}
echo 'Admin User sucessfully created!<br /><br /><b>THIS FILE WILL NOW TRY TO DELETE ITSELF, BUT PLEASE CHECK TO BE SURE!</b>';
@unlink(__FILE__);
?>