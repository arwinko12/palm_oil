<?php
/**
 * 
 */
include 'connection.php';
class Farmer extends Database
{
	
	function __construct()
	{
        parent:: __construct();
    
        $this->conn = $this->connect();
	}

	public function update_hauling_info($preferred_date,
		$preferred_time,
		$farm_location,
		$estimated_fresh_weight,
		$requested_truck_type,
		$distance_to_mill,
		$request_id){
		$sql = "UPDATE `hauling_request` SET `preferred_date`= ?, `preferred_time`= ?,`farm_location`= ?,`estimated_fresh_weight`= ?,`requested_truck_type`= ?,`distance_to_mill`= ?  WHERE `request_id`= ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("ssssssi", $preferred_date,
		$preferred_time,
		$farm_location,
		$estimated_fresh_weight,
		$requested_truck_type,
		$distance_to_mill,
		$request_id);
		$stmt->execute();
		$stmt_result = $stmt->get_result();
	}

	public function fetch_personal_farm()
	{

		$id = $_SESSION['farmer_id'];
		$sql = "SELECT 
		farmers.farmer_id,
		farmers.user_id,
		farmers.fullname,
		farmers.farm_location,
		farmers.contact_no,
		farmers.distance_to_mill,
		farmers.estimated_tons,
		farmers.last_harvest_date,
		farmers.status,
		farmers.created_at,
		farmers.updated_at,
		user.full_name
		FROM farmers LEFT JOIN user ON farmers.user_id = user.user_id WHERE  farmers.user_id = ? ";

		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$stmt_result = $stmt->get_result();

		return $stmt_result;
	}


public function update_farm(
    $farmer_id,
    $farm_location,
    $contact_no,
    $distance_to_mill,
    $estimated_tons,
    $last_harvest_date
) {
    $sql = "UPDATE farmers
            SET farm_location = ?,
                contact_no = ?,
                distance_to_mill = ?,
                estimated_tons = ?,
                last_harvest_date = ?,
                updated_at = NOW()
            WHERE farmer_id = ?";

    $stmt = $this->conn->prepare($sql);

    $stmt->bind_param(
        "ssddsi",
        $farm_location,
        $contact_no,
        $distance_to_mill,
        $estimated_tons,
        $last_harvest_date,
        $farmer_id
    );

    return $stmt->execute();
}

		public function add_user_farm(
			$user_id,
			$fullname,
			$farm_location,
			$contact_no,
			$distance_to_mill,
			$estimated_tons,
			$last_harvest_date)
	{
		$sql = "INSERT INTO `farmers`( `user_id`, `fullname`, `farm_location`, `contact_no`, `distance_to_mill`, `estimated_tons`, `last_harvest_date`) VALUES (?, ?, ?, ?, ?, ?, ?)";

		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("isssiis",
			$user_id,
			$fullname,
			$farm_location,
			$contact_no,
			$distance_to_mill,
			$estimated_tons,
			$last_harvest_date);
		$stmt->execute();
		$stmt_result = $stmt->get_result();

		return $stmt_result;
	}
	public function fetch_hauling_request($id)
	{
		$sql = "SELECT 
		hauling_request.request_id,
		hauling_request.farmer_id,
		hauling_request.preferred_date,
		hauling_request.preferred_time,
		hauling_request.farm_location,
		hauling_request.estimated_fresh_weight,
		hauling_request.requested_truck_type,
		hauling_request.distance_to_mill,
		hauling_request.notes,
		hauling_request.status,
		hauling_request.created_at,
		farmers.fullname,
		farmers.user_id


		FROM `hauling_request` 
		LEFT JOIN farmers ON hauling_request.farmer_id = farmers.farmer_id 
		LEFT JOIN user ON farmers.user_id = user.user_id WHERE farmers.user_id = ?";

		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$stmt_result = $stmt->get_result();

		return $stmt_result;
	}

		public function fetch_farmer_id()
		{

		$user_id = $_SESSION['farmer_id'];
		$sql = "SELECT * FROM `farmers` WHERE user_id = ?";
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("i", $user_id);
		$stmt->execute();
		$stmt_result = $stmt->get_result();

		return $stmt_result;
		
		}

		public function add_hauling_request($id,
		$preferred_date,
		$preferred_time,
		$farm_location,
		$estimated_fresh_weight,
		$requested_truck_type,
		$distance_to_mill)
	{
		$sql = "INSERT INTO `hauling_request`(`farmer_id`, `preferred_date`, `preferred_time`, `farm_location`, `estimated_fresh_weight`, `requested_truck_type`, `distance_to_mill`) VALUES (?, ?, ?, ?, ?, ?, ?)";

		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param("issssss", $id,
		$preferred_date,
		$preferred_time,
		$farm_location,
		$estimated_fresh_weight,
		$requested_truck_type,
		$distance_to_mill);
		$stmt->execute();
		$stmt_result = $stmt->get_result();

		return $stmt_result;
	}
}
 ?>