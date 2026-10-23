<?php
session_start();
include '../../class/users.php';
	$user = new Register_Users();

/**
 * 
 */
	// if (isset($_POST['login_farmer'])) {
		
	// 	$user = new Register_Users();

	//     $email = $_POST['email'];
	//     $password = $_POST['password'];
	//     $role = "farmers";
	//     $result = $this->$user = fetch_users_credentials($email, $role);

	//     if ($result->num_rows > 0) {

	//         $row = $result->fetch_assoc();

	//         if (password_verify($password, $row['password'])) {

	//             // $_SESSION['user'] = $row['user_id'];
	//             // $_SESSION['role'] = $row['role'];

	//             echo "success";

	//         } else {
	//             echo "password not match!";
	//         }

	//     } else {
	//         echo "error!";
	//     }

	//     exit;
	// }
	if (isset($_POST['login_farmer'])) {

	$email = $_POST['email'];
	$password = $_POST['password'];
	$role = "farmers";
	// $verify = new Users();
	$result = $user->fetch_users_credentials($email, $role);
	if ($result->num_rows > 0) {

		$row = $result->fetch_assoc();
		if (password_verify($password, $row['password'])) {
			$_SESSION['farmer_id'] = $row['user_id'];
			$_SESSION['farmer_name'] = $row['full_name'];
			echo "success!";
		}else{
			echo "password not mactch!";
		}

	}else{
		echo "error!";
	}
	
}

	if (isset($_POST['register_users'])) {
			$user = new Register_Users();
				$full_name = strtoupper($_POST['full_name']);
				$email = strtoupper($_POST['email']);
				$contact_no = $_POST['contact_no'];
				$role = $_POST['role'];
				$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

				$fetch_users = $user->fetch_Users(
				$full_name,
				$email,
				$password,
				$role,
				$contact_no
			);

				if (!$fetch_users) {
					echo "Success!";
				}else{
					echo "Error!";
				}

	}






 ?>