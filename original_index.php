<?php
// Latest version on 04 Dec 2024 at 11:27 PM by Mehedi Hasan, Program Manager, Information Systems, Australian Institute of Higher Education

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection details
$servername = "localhost";
$dbUsername = "aih_asapps_tt";
$dbPassword = "kA@cmF8o6!jl1cGv";
$dbname = "aih_asapps_tt";

// Create connection
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Step 1: Handle course selection
$selectedCourse = 'Master of Business Information Systems';
if (isset($_GET['course'])) {
    $selectedCourse = $_GET['course'];
}

// List of available courses for the dropdown
$availableCourses = [
    'Master of Business Information Systems',
    'Master of Business Information Systems (Cyber Security)',
    'Master of Business Information Systems (Data Analytics)',
    'Master of Project Management',
    'Master of Business Administration (Business Analytics)'
];

// Step 2: Define course-specific units, including electives
$courseUnits = [
    'Master of Business Information Systems' => [
        'Graduate Certificate' => ['MBIS4001', 'MBIS4002', 'MBIS4003', 'MBIS4004'],
        'Graduate Diploma' => ['MBIS4009', 'MBIS4007', 'MBIS4006', 'MBIS4008'],
        'Masters' => ['MBIS5020', 'MBIS5009', 'MBIS5014', 'MBIS5011', 'MBIS5013', 'MBIS5012', 'MBIS5015'],
        'Electives' => []
    ],
    'Master of Business Information Systems (Cyber Security)' => [
        'Graduate Certificate' => ['MBIS4001', 'MBIS4002', 'MBIS4003', 'MBIS4004'],
        'Graduate Diploma' => ['MBIS4009', 'MBIS4017', 'MBIS4006', 'MBIS4008'],
        'Masters' => ['MBIS5004', 'MBIS5005', 'MBIS5006', 'MBIS5007', 'MBIS5015'],
        //'Electives' => ['MBIS5019', 'MBIS5021', 'MBIS5022', 'MBIS5018', 'MBIS5016'],
        //'FutureElectives' => ['MBIS5019', 'MBIS5022']
		'Electives' => ['MBIS5019', 'MBIS5022']
    ],
    'Master of Business Information Systems (Data Analytics)' => [
        'Graduate Certificate' => ['MBIS4001', 'MBIS4002', 'MBIS4003', 'MBIS4004'],
        'Graduate Diploma' => ['MBIS4009', 'MBIS4007', 'MBIS4016', 'MBIS4008'],
        'Masters' => ['MBIS5002', 'MBIS5009', 'MBIS5001', 'MBIS5003', 'MBIS5015'],
        //'Electives' => ['MBIS5018', 'MBIS5023', 'MBIS5017', 'MBIS5008'],
        //'FutureElectives' => ['MBIS5018', 'MBIS5023']
		'Electives' => ['MBIS5018', 'MBIS5023']
    ],
    'Master of Project Management' => [
        'Graduate Certificate' => ['MBA4002', 'MBA4004', 'MPM4001', 'MPM4003'],
        'Graduate Diploma' => ['MPM4002', 'MPM4006', 'MPM4005', 'MPM4004'],
        'Masters' => ['MPM5003', 'MPM5001', 'MPM5002', 'MPM5004', 'MPM5005', 'MPM5006', 'MPM5007', 'MPM5008'],
    ],
    'Master of Business Administration (Business Analytics)' => [
        'Graduate Certificate' => ['MBA4002', 'MBA4004', 'MPM4001', 'MBA4001'],
        'Graduate Diploma' => ['MBA4006', 'MBIS4007', 'MBA4007', 'MBA4008'],
        'Masters' => ['MBA5001', 'MBA5002', 'MBA5003', 'MBA5004', 'MBA5005', 'MBA5006', 'MBA5007', 'MBA5008'],
    ]
];

// Step 3: Map Old Unit Codes to New Unit Codes
$unitCodeMapping = [
    'MBIS5010' => 'MBIS4009', // MBIS4009 is the new name for MBIS5010
    'MBIS4005' => 'MBIS5020', // MBIS5020 is the new name for MBIS4005
];

// Step 4: Extract Student Data and Units Data for the selected course from the Database
$studentsQuery = "SELECT student_id, course_name, campus 
                  FROM timetable_data 
                  WHERE course_name = '$selectedCourse'
                  GROUP BY student_id";
$studentsResult = $conn->query($studentsQuery);

if (!$studentsResult) {
    die("Student Query Error: " . $conn->error);
}

// Updated query to retrieve completed, exempt, or enrolled units for each student
$unitsQuery = "SELECT student_id, unit_code, term_period, unit_enrolment_status 
               FROM timetable_data 
               WHERE course_name = '$selectedCourse' 
                 AND unit_enrolment_status IN ('Completed', 'Exempt', 'Enrolled')";
$unitsResult = $conn->query($unitsQuery);

if (!$unitsResult) {
    die("Units Query Error: " . $conn->error);
}

// Step 5: Initialize Data Structures to hold results
$studentsData = [];

// Populate student details into an array for the selected course
if ($studentsResult->num_rows > 0) {
    while ($row = $studentsResult->fetch_assoc()) {
        $studentId = $row['student_id'];
        $courseName = $row['course_name'];
        
        // Use the units specific to this student's course
        $qualificationsUnits = $courseUnits[$courseName];
        $allQualificationUnits = array_merge(...array_values($qualificationsUnits));
        
        $studentsData[$studentId] = [
            'student_id' => $studentId,
            'course' => $courseName,
            'campus' => $row['campus'],
            'units' => [],
            'completed_units' => array_fill_keys($allQualificationUnits, false) // Initially mark all units as incomplete
        ];
    }
}

// Step 6: Populate unit details into student-specific blocks and mark completed/exempt/enrolled units
if ($unitsResult->num_rows > 0) {
    while ($row = $unitsResult->fetch_assoc()) {
        $studentId = $row['student_id'];
        $unit_code = $row['unit_code'];

        // Map old unit code to new unit code if applicable
        if (isset($unitCodeMapping[$unit_code])) {
            $unit_code = $unitCodeMapping[$unit_code];
        }

        // Mark the unit as completed/exempt/enrolled for that student
        if (isset($studentsData[$studentId]['completed_units'][$unit_code])) {
            $studentsData[$studentId]['completed_units'][$unit_code] = true;
        }
    }
}

// Step 7: Assign the next available incomplete units to the correct blocks in progression
$blockOrder = [
    "Block 1", "Block 2", 
    "Block 3", "Block 4", 
    "Block 5", "Block 6", 
    "Block 7", "Block 8"
];
$startingYear = 2025;

foreach ($studentsData as $studentId => &$student) {
    $incompleteUnits = [];
    $completedElectives = 0;

    // Use the units specific to this student's course
    $courseName = $student['course'];
    $qualificationsUnits = $courseUnits[$courseName];
    $allQualificationUnits = array_merge(...array_values($qualificationsUnits));

    // Count how many electives are completed, regardless of future availability
    foreach ($allQualificationUnits as $unit) {
        if (in_array($unit, $qualificationsUnits['Electives'] ?? []) && $student['completed_units'][$unit]) {
            $completedElectives++;
        }
    }

    // Collect incomplete units from qualifications
    foreach ($allQualificationUnits as $unit) {
        // Skip adding more electives if two are completed
        if (in_array($unit, $qualificationsUnits['Electives'] ?? []) && $completedElectives >= 2) {
            continue;
        }

        // Add to the list of incomplete units if it's not completed
        if (!$student['completed_units'][$unit]) {
            // Prioritize only specific electives for future scheduling if it is an elective
            if (in_array($unit, $qualificationsUnits['Electives'] ?? []) && isset($qualificationsUnits['FutureElectives'])) {
                if (in_array($unit, $qualificationsUnits['FutureElectives'])) {
                    $incompleteUnits[] = $unit;
                    $completedElectives++;
                }
            } else {
                $incompleteUnits[] = $unit;
            }
        }
    }

    // Assign incomplete units to blocks with a progressing year/block label
    $currentYear = $startingYear;
    $blockIndex = 0;

    while ($blockIndex < count($blockOrder) && count($incompleteUnits) > 0) {
        $unit = array_shift($incompleteUnits);
        if ($unit == 'MBIS5015') {
            // Handle MBIS5015 spanning across the correct consecutive block pairs
            if (($blockIndex % 2) == 0 && $blockIndex < count($blockOrder) - 1) {
                // It is the start of a block pair
                $blockName1 = "{$currentYear} - {$blockOrder[$blockIndex]}";
                $blockName2 = "{$currentYear} - {$blockOrder[$blockIndex + 1]}";
                $student['units'][$blockName1] = 'MBIS5015';
                $student['units'][$blockName2] = 'MBIS5015';

                // Move two blocks forward since MBIS5015 takes two consecutive blocks
                $blockIndex += 2;
            } else {
                // If it's not starting on a valid pair, skip the current block to align correctly
                $blockName = "{$currentYear} - {$blockOrder[$blockIndex]}";
                $student['units'][$blockName] = "wait for MBIS5015";
                
                // Move to the next block to align correctly
                $blockIndex++;
                array_unshift($incompleteUnits, $unit); // Put MBIS5015 back to reassign in the next valid pair
            }
        } else {
            $blockName = "{$currentYear} - {$blockOrder[$blockIndex]}";
            $student['units'][$blockName] = $unit;

            // Move to the next block
            $blockIndex++;
        }

        // If we reach the end of the block order, move to the next year and reset the block index
        if ($blockIndex >= count($blockOrder)) {
            $blockIndex = 0;
            $currentYear++;
        }
    }
}

// Step 8: Prepare the HTML for the forecast table for the selected course
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forecast Table for <?php echo htmlspecialchars($selectedCourse); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .table-container {
            width: 100%;
            overflow-x: auto;
            max-height: 80vh; /* Set maximum height to 80% of the viewport */
            overflow-y: auto;  /* Allow scrolling within the container */
        }
        .table {
            width: 100%;
            min-width: 1200px; /* Adjusted width for compactness */
            font-size: 0.85em;  /* Smaller but readable font size */
        }
        th, td {
            padding: 6px 8px;  /* Compact padding for more data visibility */
            text-align: center;
            vertical-align: middle;
        }
        thead th {
            position: sticky;  /* Makes header row sticky */
            top: 0;            /* Position the header at the top */
            background-color: #343a40; /* Ensure background color to prevent blending */
            color: white;      /* Text color to be readable */
            z-index: 5;        /* High z-index to stay above other table rows */
        }
        tbody tr {
            background-color: white;
        }
        th.sortable:hover {
            cursor: pointer;
            background-color: #f0f0f0;
        }
        .pagination-container {
            margin-top: 20px;
            margin-bottom: 20px; /* Added margin-bottom for breathing space */
            display: flex;
            justify-content: center;
        }
        .pagination-container button {
            margin: 0 5px;
            padding: 5px 10px;
            border: 1px solid #ddd;
            background-color: #f8f9fa;
            cursor: pointer;
        }
        .pagination-container button.active {
            background-color: #007bff;
            color: white;
        }
        /* Custom styles for columns */
        .graduate-certificate {
            background-color: #cce5ff; /* Light Blue for Graduate Certificate */
        }
        .graduate-diploma {
            background-color: #d4edda; /* Light Green for Graduate Diploma */
        }
        .masters {
            background-color: #fff3cd; /* Light Yellow for Masters */
        }
        .electives {
            background-color: #f8d7da; /* Light Pink for Electives */
        }
    </style>
</head>
<body>
	
<nav class="navbar navbar-expand-lg" style="background-color: #036A37;">
  <div class="container-fluid">
    <a class="navbar-brand text-white" href="index.php">AIHE Dashboard</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link text-white" href="index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="display.php">Forecast</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

	
<div class="container-fluid mt-5">
    <form method="GET" class="mb-4">
        <label for="courseSelect" class="form-label">Select Course:</label>
        <select id="courseSelect" name="course" class="form-select" onchange="this.form.submit()">
            <?php
            foreach ($availableCourses as $course) {
                $selected = ($course === $selectedCourse) ? 'selected' : '';
                echo "<option value=\"{$course}\" {$selected}>{$course}</option>";
            }
            ?>
        </select>
    </form>
    
    <h1 class="text-center mb-4">Forecast of Units for <?php echo htmlspecialchars($selectedCourse); ?></h1>
    <div class="table-container">
        <table id="forecastTable" class="table table-bordered table-striped">
            <thead class="table-dark">
            <tr>
                <?php
                // Generate headers for blocks dynamically based on the longest sequence found in the student data
                $maxBlocks = 0;
                foreach ($studentsData as $student) {
                    $maxBlocks = max($maxBlocks, count($student['units']));
                }

                $currentYear = $startingYear;
                $blockIndex = 0;

                // Generate block headers
                for ($i = 0; $i < $maxBlocks; $i++) {
                    $blockName = "{$currentYear} - {$blockOrder[$blockIndex]}";
                    echo "<th class=\"sortable\" onclick=\"sortTable($i)\">{$blockName}</th>";

                    // Move to the next block
                    $blockIndex++;
                    if ($blockIndex >= count($blockOrder)) {
                        // If we reach the end of the block order, move to the next year and reset the block index
                        $blockIndex = 0;
                        $currentYear++;
                    }
                }

                // Static headers for Student ID, Course, Campus
                echo "<th class=\"sortable\" onclick=\"sortTable($maxBlocks)\">Student ID</th>";
                echo "<th class=\"sortable\" onclick=\"sortTable(" . ($maxBlocks + 1) . ")\">Course</th>";
                echo "<th class=\"sortable\" onclick=\"sortTable(" . ($maxBlocks + 2) . ")\">Campus</th>";

                // Generate headers for qualifications dynamically with inline styles for color coding
                $colIndex = $maxBlocks + 3; // Start after the fixed columns
                $qualificationsUnits = $courseUnits[$selectedCourse]; // Ensure units are fetched based on selected course

                foreach ($qualificationsUnits as $qualification => $units) {
                    // Skip displaying the "FutureElectives" key; it's only used for backend logic
                    if ($qualification === 'FutureElectives') {
                        continue;
                    }

                    $class = '';
                    if ($qualification === 'Graduate Certificate') {
                        $class = 'graduate-certificate';
                    } elseif ($qualification === 'Graduate Diploma') {
                        $class = 'graduate-diploma';
                    } elseif ($qualification === 'Masters') {
                        $class = 'masters';
                    } elseif ($qualification === 'Electives') {
                        $class = 'electives';
                    }
                    foreach ($units as $unit) {
                        // Dynamically set the correct unit code for the selected course in the header
                        echo "<th class=\"sortable {$class}\" onclick=\"sortTable({$colIndex})\">{$qualification}<br>{$unit}</th>";
                        $colIndex++;
                    }
                }
                ?>
            </tr>
            </thead>
            <tbody>
            <?php
            // Step 9: Populate the Table Rows for the selected course
            $rowIndex = 0;
            foreach ($studentsData as $studentId => $student) {
                echo "<tr class='table-row' data-row-index='{$rowIndex}'>";

                // Populate units for each block dynamically
                foreach ($student['units'] as $blockName => $unit) {
                    echo "<td>" . $unit . "</td>";
                }

                // Fill empty cells if the student has fewer blocks than the max
                $emptyCells = $maxBlocks - count($student['units']);
                for ($i = 0; $i < $emptyCells; $i++) {
                    echo "<td></td>";
                }

                // Add student details
                echo "<td>{$student['student_id']}</td>";
                echo "<td>{$student['course']}</td>";
                echo "<td>{$student['campus']}</td>";

                // Qualification rows (conditionally show "X" based on whether the student has completed the unit)
                foreach ($qualificationsUnits as $qualification => $units) {
                    // Skip displaying the "FutureElectives" key; it's only used for backend logic
                    if ($qualification === 'FutureElectives') {
                        continue;
                    }
                    foreach ($units as $unit) {
                        $class = '';
                        if ($qualification === 'Graduate Certificate') {
                            $class = 'graduate-certificate';
                        } elseif ($qualification === 'Graduate Diploma') {
                            $class = 'graduate-diploma';
                        } elseif ($qualification === 'Masters') {
                            $class = 'masters';
                        } elseif ($qualification === 'Electives') {
                            $class = 'electives';
                        }
                        if (isset($student['completed_units'][$unit]) && !$student['completed_units'][$unit]) {
                            echo "<td class='{$class}'>X</td>";
                        } else {
                            echo "<td class='{$class}'></td>";
                        }
                    }
                }

                echo "</tr>";
                $rowIndex++;
            }
            ?>
            </tbody>
        </table>
    </div>
    <div class="pagination-container" id="paginationContainer"></div>
</div>
<script>
    // JavaScript for Pagination and Sorting
    const rowsPerPage = 20;
    let currentPage = 1;
    let totalRows = document.querySelectorAll('#forecastTable tbody tr').length;
    let totalPages = Math.ceil(totalRows / rowsPerPage);

    function showPage(pageNumber) {
        let start = (pageNumber - 1) * rowsPerPage;
        let end = start + rowsPerPage;

        document.querySelectorAll('#forecastTable tbody tr').forEach((row, index) => {
            row.style.display = (index >= start && index < end) ? '' : 'none';
        });

        document.querySelectorAll('.pagination-container button').forEach((button, index) => {
            button.classList.toggle('active', index + 1 === pageNumber);
        });

        currentPage = pageNumber;
    }

    function createPagination() {
        const paginationContainer = document.getElementById('paginationContainer');
        paginationContainer.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
            const button = document.createElement('button');
            button.innerText = i;
            button.addEventListener('click', () => {
                showPage(i);
            });

            if (i === currentPage) {
                button.classList.add("active");
            }

            paginationContainer.appendChild(button);
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        createPagination();
        showPage(currentPage);
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Close connection
$conn->close();
?>