<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="sample_unemployed_users.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Full Name', 'Email', 'Phone']);
fclose($output);