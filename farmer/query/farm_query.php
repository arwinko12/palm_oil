<?php
session_start();
include '../../class/farmer.php';
$farm = new Farmer();




if (isset($_POST['updating_farm_info'])) {

    $u_farmer_id = $_POST['u_farmer_id'];
    $u_farm_location = $_POST['u_farm_location'];
    $u_contact_no = $_POST['u_contact_no'];
    $u_distance_mill = $_POST['u_distance_mill'];
    $u_estimated_tons = $_POST['u_estimated_tons'];
    $u_harvest_date = $_POST['u_harvest_date'];

    $result = $farm->update_farm(
        $u_farmer_id,
        $u_farm_location,
        $u_contact_no,
        $u_distance_mill,
        $u_estimated_tons,
        $u_harvest_date
    );

    if ($result) {
        echo "Farm information updated successfully!";
    } else {
        echo "Failed to update farm information.";
    }
}


if (isset($_POST['adding_new_farm'])) {
	$id = $_SESSION['farmer_id'];
	$full_name = strtoupper($_POST['full_name']);
	$farm_location = $_POST['farm_location'];
	$contact_no = $_POST['contact_no'];
	$distance_mill = $_POST['distance_mill'];
	$estimated_tons = $_POST['estimated_tons'];
	$harvest_date = $_POST['harvest_date'];


	$result = $farm->add_user_farm(
	$id,
	$full_name,
	$farm_location,
	$contact_no,
	$distance_mill,
	$estimated_tons,
	$harvest_date);

	if ($result) {
		echo "success!";
	}else{
		echo "error!";
	}

}

if (isset($_POST['loading_farm'])) {
	// code...

	$fetch = $farm->fetch_personal_farm();



echo 
'
<table id="mytable" class="table table-inverse table-striped table-sm">
	<thead>
		<tr>
			<th>FARM LOCATION</th>
			<th>CONTACT NO.</th>
			<th>DISTANCE TO MILL</th>
			<th>ESTIMATED TONS</th>
			<th>LAST HARVEST</th>
			<th>STATUS</th>
			<th>ACTION</th>
		</tr>
	</thead>
	<tbody>
';

	foreach ($fetch as $key => $row) {
		switch ($row['status']) {
			case 'active':
				$status = '<span class="badge text-bg-success">Active</span>';
				break;
			case 'inactive':
				$status = '<span class="badge text-bg-danger">Inactive</span>';
				break;
			
			default:
				$status = '<span class="badge text-bg-secondary">Secondary</span>';
				break;
		}
	 	echo 
	 	'	
	 		<tr>
			<td>'.$row['farm_location'].'</td>
			<td>'.$row['contact_no'].'</td>
			<td>'.$row['distance_to_mill'].'</td>
			<td>'.$row['estimated_tons'].'</td>
			<td>'.$row['last_harvest_date'].'</td>
			<td>'.$status.'</td>
			<td>
			<div class="dropdown">
				  <button class="btn btn-dark btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
				    Action
				  </button>
				  <ul class="dropdown-menu">
				    <li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#exampleModal" onclick="edit_farm(
				     \''.$row['farmer_id'].'\',
				    \''.$row['farm_location'].'\',
				    \''.$row['contact_no'].'\',
				    \''.$row['distance_to_mill'].'\',
				    \''.$row['estimated_tons'].'\',
				    \''.$row['last_harvest_date'].'\')"><i class="bi bi-pencil-square"></i> Edit</a></li>
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

 ?>