<?php
	//NOTE: Run the migrations/demo-migrations.sql first before running this code

	$realPath = realpath(__DIR__ . '/../..');
	require_once $realPath.'/vendor/autoload.php';

	use Geekcow\Dbcore\Entity;

	$config  = parse_ini_file(__DIR__."/config.ini");
	$server  = $config['dbcore.server'];
	$db_user = $config['dbcore.db_user'];
	$db_pass = $config['dbcore.db_pass'];
	$db_database = $config['dbcore.database'];

	class UserType extends Entity{
	  private $user_type = [
	      'id' => [ 'type' => 'int', 'unique' => true, 'pk' => true ],
	      'name' => [ 'type' => 'string', 'length' => 32, 'unique' => true ]
	  ];

	  public function __construct(){
	    parent::__construct($this->user_type, get_class($this), __DIR__."/config.ini");
	  }
	}

	class DemoUser extends Entity{
	  private $demo_user;

	  public function __construct(){
	    $this->demo_user = [
	        'username' => [ 'type' => 'string', 'length' => 70, 'unique' => true, 'pk' => true ],
	        'name' => [ 'type' => 'string', 'length' => 45, 'unique' => true ],
	        'lastname' => [ 'type' => 'string', 'length' => 45, 'unique' => true ],
	        'email' => [ 'type' => 'string', 'length' => 70, 'unique' => true ],
	        'password' => [ 'type' => 'string', 'length' => 32 ],
	        'enabled' => [ 'type' => 'boolean'],
	        'type' => [ 'type' => 'int', 'foreign' => array('id', new UserType())]
	    ];
	    parent::__construct($this->demo_user, get_class($this), __DIR__."/config.ini");
	  }
	}

	echo 'INSERTING Type';

	$userType = new UserType();
	if (!$userType->fetch_id(array('id'=>1))){
		$userType->columns['id'] = 1;
		$userType->columns['name'] = 'Test User';
		$userType->insert();
	}

	$demoUser = new DemoUser();

	$demoUser->columns['username'] = 'oleche';
	$demoUser->columns['name'] = 'Oscar';
	$demoUser->columns['lastname'] = 'Leche';
	$demoUser->columns['email'] = 'notreal@email.com';
	$demoUser->columns['password'] = '123';
	$demoUser->columns['enabled'] = TRUE;
	$demoUser->columns['type'] = 1;

	echo "\nINSERTING USER: ".$demoUser->columns['username'];
	$demoUser->insert();
	echo "\n";

	echo 'FETCHING WITH ONE RESULT';
	echo "\n";
	$result = $demoUser->fetch();
	print_r($demoUser->fetched_result);

	$demoUser1 = new DemoUser();
	$demoUser1->columns['username'] = 'fperez';
	$demoUser1->columns['name'] = 'Francisco';
	$demoUser1->columns['lastname'] = 'Perez';
	$demoUser1->columns['email'] = 'notfrancisco@email.com';
	$demoUser1->columns['password'] = '123';
	$demoUser1->columns['enabled'] = TRUE;
	$demoUser1->columns['type'] = 1;

	echo "\n";
	echo 'INSERTING USER: '.$demoUser1->columns['username'];
	$demoUser1->insert();
	echo "\n";

	echo 'FETCHING WITH TWO RESULT';
	echo "\n";
	$result = $demoUser->fetch();
	print_r($demoUser->fetched_result);

	echo 'FETCHING WITH ID: oleche';
	echo "\n";
	if ($demoUser->fetch_id(array('username'=>'oleche'))){
		print_r($demoUser->columns);
	}else{
		echo 'error: '.$demoUser->err_data;
	}

	echo 'FETCHING NO EXISTING ID: operez';
	echo "\n";
	if ($demoUser->fetch_id(array('username'=>'operez'))){
		print_r($demoUser->columns);
	}else{
		echo 'error: '.$demoUser->err_data;
		echo "\n";
	}

	echo 'UPDATING ID: oleche';
	echo "\n";
	if ($demoUser->fetch_id(array('username'=>'oleche'))){
		print_r($demoUser->columns);
		$demoUser->columns['name'] = 'Oscarinox';
		$demoUser->columns['type'] = $demoUser->columns['type']['id'];
		$demoUser->update();
		echo 'FETCHING AFTER UPDATE: oleche';
		echo "\n";
		if ($demoUser->fetch_id(array('username'=>'oleche'))){
			print_r($demoUser->columns);
		}else{
			echo 'error: '.$demoUser->err_data;
		}
	}else{
		echo 'error: '.$demoUser->err_data;
	}

	echo 'DELETING WITH ID: oleche';
	echo "\n";
	if ($demoUser->fetch_id(array('username'=>'oleche'))){
		echo 'FOUND ID: oleche';
		echo "\n";
		print_r($demoUser->columns);
		$demoUser->delete();
		echo 'FETCHING WITH ONE RESULT AFTER DELETE';
		echo "\n";
		$result = $demoUser->fetch();
		print_r($demoUser->fetched_result);
	}else{
		echo 'error: '.$demoUser->err_data;
	}

	echo "\nDELETING Last USER";
	$demoUser1->delete();

	echo "\nDELETING USER TYPE";
	$userType->delete();
	echo "\n";
?>
