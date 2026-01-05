<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="sample_employed_users.csv"');

$output = fopen('php://output', 'w');
// Include company_id in the header
fputcsv($output, ['Full Name', 'Email', 'Phone']);
fclose($output);