<?php
// /api/members/download_sample_csv.php

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="sample_members_template.csv"');

$output = fopen('php://output', 'w');

// Define header row
$header = ['full_name', 'email', 'phone'];
fputcsv($output, $header);

// Add some example data to guide the user
$example1 = ['John Doe', 'john.doe@example.com', '123456789'];
$example2 = ['Jane Smith', 'jane.smith@example.com', '987654321'];
fputcsv($output, $example1);
fputcsv($output, $example2);

fclose($output);
exit;