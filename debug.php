<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection settings
$servername = "localhost";
$dbUsername = "aih_asapps_tt";
$dbPassword = "kA@cmF8o6!jl1cGv";
$dbname = "aih_asapps_tt";

// Establish database connection
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the selected class size (default: 35)
$class_size = isset($_POST['class_size']) ? intval($_POST['class_size']) : 35;

// Define MBIS courses and prerequisites
$courses = [
    "Master of Business Information Systems" => [
        "required_units" => [
            "MBIS4001", "MBIS4002", "MBIS4003", "MBIS4004",
            "MBIS4009", "MBIS4007", "MBIS4006", "MBIS4008",
            "MBIS5020", "MBIS5009", "MBIS5014", "MBIS5011",
            "MBIS5013", "MBIS5012", "MBIS5015"
        ],
        "electives" => []
    ],
    "Master of Business Information Systems (Cyber Security)" => [
        "required_units" => [
            "MBIS4001", "MBIS4002", "MBIS4003", "MBIS4004",
            "MBIS4009", "MBIS4017", "MBIS4006", "MBIS4008",
            "MBIS5004", "MBIS5005", "MBIS5006", "MBIS5007",
            "MBIS5015"
        ],
        "electives" => [
            "MBIS5021", "MBIS5022", "MBIS5019", "MBIS5016"
        ]
    ],
    "Master of Business Information Systems (Data Analytics)" => [
        "required_units" => [
            "MBIS4001", "MBIS4002", "MBIS4003", "MBIS4004",
            "MBIS4009", "MBIS4007", "MBIS4016", "MBIS4008",
            "MBIS5002", "MBIS5009", "MBIS5001", "MBIS5003",
            "MBIS5015"
        ],
        "electives" => [
            "MBIS5008", "MBIS5018", "MBIS5017", "MBIS5023"
        ]
    ],
    "Master of Project Management" => [
        "required_units" => [], // Placeholder: Add units if applicable
        "electives" => []      // Placeholder: Add electives if applicable
    ],
    "Master of Business Administration (Business Analytics)" => [
        "required_units" => [], // Placeholder: Add units if applicable
        "electives" => []      // Placeholder: Add electives if applicable
    ],
    "Graduate Diploma of Business Information Systems (Cyber Security)" => [
        "required_units" => ["MBIS4001", "MBIS4002", "MBIS4003", "MBIS4004",
            "MBIS4009", "MBIS4007", "MBIS4006", "MBIS4008"], // Placeholder: Add units if applicable
        "electives" => []      // Placeholder: Add electives if applicable
    ],
    "Graduate Diploma of Business Information Systems (Data Analytics)" => [
        "required_units" => ["MBIS4001", "MBIS4002", "MBIS4003", "MBIS4004",
            "MBIS4009", "MBIS4007", "MBIS4006", "MBIS4008"], // Placeholder: Add units if applicable
        "electives" => []      // Placeholder: Add electives if applicable
    ],
    "Graduate Diploma of Business Information Systems" => [
        "required_units" => ["MBIS4001", "MBIS4002", "MBIS4003", "MBIS4004",
            "MBIS4009", "MBIS4007", "MBIS4006", "MBIS4008"], // Placeholder: Add units if applicable
        "electives" => []      // Placeholder: Add electives if applicable
    ],
    "Graduate Diploma of Project Management" => [
        "required_units" => [], // Placeholder: Add units if applicable
        "electives" => []      // Placeholder: Add electives if applicable
    ],
    "Graduate Diploma of Business Administration (Business Analytics)" => [
        "required_units" => [], // Placeholder: Add units if applicable
        "electives" => []      // Placeholder: Add electives if applicable
    ],
	    "Graduate Certificate of Business Administration" => [
        "required_units" => [], // Placeholder: Add units if applicable
        "electives" => []      // Placeholder: Add electives if applicable
    ],
	
		    "Graduate Certificate of of Business Information Systems" => [
        "required_units" => ["MBIS4001", "MBIS4002", "MBIS4003", "MBIS4004"], // Placeholder: Add units if applicable
        "electives" => []      // Placeholder: Add electives if applicable
    ]
];

// Function to map term periods to block start dates
function getBlockStartDate($term_period) {
    $blockDates = [
		'2024 - Summer School' => '2024-01-15',
        '2024 - Block 1' => '2024-02-19',
        '2024 - Block 2' => '2024-03-18',
        '2024 - Block 3' => '2024-04-29',
        '2024 - Block 4' => '2024-05-27',
        '2024 - Block 5' => '2024-07-01',
		'2024 - Winter School' => '2024-08-05',
        '2024 - Block 6' => '2024-09-09',
        '2024 - Block 7' => '2024-10-14',
        '2024 - Block 8' => '2024-11-11',
        // Add additional blocks as needed
    ];

    return isset($blockDates[$term_period]) ? $blockDates[$term_period] : null;
}

// Fetch all students and their completed units
$query = "
    SELECT 
        student_id,
        course_name,
        unit_code,
        campus,
        unit_enrolment_status,
        term_period
    FROM 
        timetable_data
    WHERE 
        unit_enrolment_status = 'Completed' OR unit_enrolment_status = 'Enrolled';
";

$result = $conn->query($query);

if (!$result) {
    die("Error executing query: " . $conn->error);
}

$students = [];
$already_enrolled_in_block_8 = []; // Track students already enrolled in Block 8

while ($row = $result->fetch_assoc()) {
    $student_id = $row['student_id'];
    $course = $row['course_name'];

    if (!isset($courses[$course])) {
        die("Error: Undefined course name '{$course}' for student ID {$student_id}");
    }

    $blockStartDate = getBlockStartDate($row['term_period']);
    if ($row['term_period'] === '2024 - Block 8' && $row['unit_enrolment_status'] === 'Enrolled') {
        $already_enrolled_in_block_8[] = $student_id; // Mark student as already enrolled in Block 8
        continue; // Skip processing for these students
    }

    if (!isset($students[$student_id])) {
        $students[$student_id] = [
            'course' => $course,
            'completed_units' => [],
            'credit_points' => 0,
            'campus' => ($row['campus'] === 'AIHE') ? 'Sydney' : 'Melbourne'
        ];
    }

    $students[$student_id]['completed_units'][] = $row['unit_code'];
    $unit_credits = ($row['unit_code'] === 'MBIS5015') ? 20 : 10;
    $students[$student_id]['credit_points'] += $unit_credits;
}

// Assign electives for MBIS CS and MBIS DA
foreach ($students as $student_id => &$data) {
    $course = $data['course'];
    $completed_units = $data['completed_units'];
    $credit_points = $data['credit_points'];

    // Validate total credit points
    if ($credit_points < 160) {
        $electives = $courses[$course]['electives'] ?? [];
        if (!empty($electives)) {
            // Assign 2 electives randomly if needed
            $remaining_electives = array_diff($electives, $completed_units);
            $selected_electives = array_slice($remaining_electives, 0, 2);
            $students[$student_id]['completed_units'] = array_merge(
                $completed_units,
                $selected_electives
            );
        }
    }
}

// Determine next eligible units for each student
$forecasts = [];
foreach ($students as $student_id => $data) {
    if (in_array($student_id, $already_enrolled_in_block_8)) {
        continue; // Skip forecasting for students already enrolled in Block 8
    }

    $course = $data['course'];
    $completed_units = $data['completed_units'];
    $campus = $data['campus'];

    $required_units = $courses[$course]['required_units'];
    foreach ($required_units as $unit) {
        if (!in_array($unit, $completed_units)) {
            $forecasts[$unit][$campus][] = $student_id;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unit Forecast</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Unit Forecast for 2025 - Summer Block Onward</h1>
        
        <!-- Class Size Selection -->
        <form method="POST" class="mb-4">
            <div class="row mb-3">
                <label for="class_size" class="col-sm-2 col-form-label">Class Size:</label>
                <div class="col-sm-4">
                    <select class="form-select" id="class_size" name="class_size">
						<option value="50" <?php if ($class_size == 35) echo "selected"; ?>>50</option>
						<option value="45" <?php if ($class_size == 35) echo "selected"; ?>>45</option>
						<option value="40" <?php if ($class_size == 35) echo "selected"; ?>>40</option>
                        <option value="35" <?php if ($class_size == 35) echo "selected"; ?>>35</option>
                        <option value="30" <?php if ($class_size == 30) echo "selected"; ?>>30</option>
                        <option value="25" <?php if ($class_size == 25) echo "selected"; ?>>25</option>
                        <option value="20" <?php if ($class_size == 20) echo "selected"; ?>>20</option>
						<option value="15" <?php if ($class_size == 20) echo "selected"; ?>>15</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Generate Forecast</button>
        </form>

        <!-- Forecasted Units -->
        <?php if (!empty($forecasts)): ?>
            <?php foreach ($forecasts as $unit => $campuses): ?>
                <?php foreach ($campuses as $campus => $student_ids): ?>
                    <?php $class_count = ceil(count($student_ids) / $class_size); ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Unit: <?php echo $unit; ?> | Campus: <?php echo $campus; ?> | Groups Needed: <?php echo $class_count; ?></h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Group</th>
                                        <th>Student IDs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $chunked_students = array_chunk($student_ids, $class_size);
                                    foreach ($chunked_students as $group_num => $students_in_group): ?>
                                        <tr>
                                            <td>Group <?php echo $group_num + 1; ?></td>
                                            <td><?php echo implode(", ", $students_in_group); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-danger">No forecast data available.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
