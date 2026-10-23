<?php
session_start();
include '../../class/farmer.php';

$farm = new Farmer();

if (isset($_POST['updating_hauling_request'])) {
	$update_id = $_POST['update_id'];
	$u_preferred_date = $_POST['u_preferred_date'];
	$u_preferred_time = $_POST['u_preferred_time'];
	$u_farm_location = strtoupper($_POST['u_farm_location']);
	$u_estimated_fresh_weight = $_POST['u_estimated_fresh_weight'];
	$u_requested_truck_type = $_POST['u_requested_truck_type'];
	$u_distance_to_mill = $_POST['u_distance_to_mill'];

	$result = $farm->update_hauling_info(
		$u_preferred_date,
		$u_preferred_time,
		$u_farm_location,
		$u_estimated_fresh_weight,
		$u_requested_truck_type,
		$u_distance_to_mill,
		$update_id);

	if (!$result) {
		echo 'success!';
	}else{
		echo "error!";
	}
}
if (isset($_POST['loading_hauling_request'])) {
	
	$id = intval($_SESSION['farmer_id']);

		echo '	<table id="mytable" class="table table-striped table-inverse table-hover">
				<thead>
					<tr>
						<th>PREFFERED DATE</th>
						<th>PREFFERED TIME</th>
						<th>FARM LOCATION</th>
						<th>ESTIMATED FRESH WEIGHT</th>
						<th>REQUESTED TRUCK</th>
						<th>DISTANCE TO MILL</th>
						<th>ACTION</th>
					</tr>
				</thead>
				<tbody>';
	$result = $farm->fetch_hauling_request($id);
	foreach ($result as $key => $row) {
		echo 
		'
		<tr>
					<td>'.date("Y-m-d", strtotime($row['preferred_date'])).'</td>
					<td>'.date("h:i:s A", strtotime($row['preferred_time'])).'</td>
					<td>'.$row['farm_location'].'</td>
					<td>'.$row['estimated_fresh_weight'].'</td>
					<td>'.$row['requested_truck_type'].'</td>
					<td>'.$row['distance_to_mill'].'</td>
					<td>
					<div class="dropdown">
					  <button class="btn btn-sm btn-dark dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
					    Action
					  </button>
					  <ul class="dropdown-menu">
					    <li>
					    <a class="dropdown-item" href="#" onclick="edit_hauling_info(
					    \''.$row['farmer_id'].'\',
					    \''.$row['preferred_date'].'\',
					    \''.$row['preferred_time'].'\',
					    \''.$row['farm_location'].'\', 
					    \''.$row['estimated_fresh_weight'].'\', 
					    \''.$row['requested_truck_type'].'\', 
					    \''.$row['distance_to_mill'].'\')" 
					    data-bs-toggle="modal" 
					    data-bs-target="#update_hauling_modal">Edit 
					    <i class="bi bi-pencil-square"></i></a>
					    </li>
					  </ul>
					</div>
					</td>
				</tr>
		';
	}

	echo 
	'
		</tbody>
		</table>
	';
}


if (isset($_POST['adding_request'])) {
	$userID = $farm->fetch_farmer_id();
	$row = $userID->fetch_assoc();
	$farmerID = $row['farmer_id'];

	$preferred_date = $_POST['preferred_date'];
	$preferred_time = $_POST['preferred_time'];
	$farm_location = $_POST['farm_location'];
	$estimated_fresh_weight = $_POST['estimated_fresh_weight'];
	$requested_truck_type = $_POST['requested_truck_type'];
	$distance_to_mill = $_POST['distance_to_mill'];


	$result = $farm->add_hauling_request($farmerID, 
	$preferred_date,
	$preferred_time,
	$farm_location,
	$estimated_fresh_weight,
	$requested_truck_type,
	$distance_to_mill);


	if (!$result) {
		echo "success";
	}else{
		echo "error";
	}
}



 ?>