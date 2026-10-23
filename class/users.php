
<?php

require_once 'connection.php';

class Register_Users extends Database
{
    function __construct()
    {
        parent:: __construct();
    
        $this->conn = $this->connect();
    }

    public function fetch_Users(
        $full_name,
        $email,
        $password,
        $role,
        $contact_no
    ) {
    

        

        $stmt = $this->conn->prepare("INSERT INTO `user`(`full_name`, `email`, `password`, `role`, `contact_no`) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss",  
            $full_name,
            $email,
            $password,
            $role,
            $contact_no);
        $stmt->execute();
        $stmt_result = $stmt->get_result();
        return $stmt_result;
    }



    public function fetch_users_credentials($email, $role)
    {
        $stmt = $this->conn->prepare("SELECT * FROM `user` WHERE email = ? AND role = ?  limit 1");
        $stmt->bind_param("ss",  $email, $role);
        $stmt->execute();
        $stmt_result = $stmt->get_result();
        return $stmt_result;
    }
}

?>
