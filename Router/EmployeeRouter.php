<?php

// Include the component
require_once 'config.inc.php';
require_once __DIR__ . '/../Component/EmployeeComponent.php';

// Instantiate Fat-Free framework's Base class
$f3 = Base::instance();
echo "Inside EmployeeRouter\n";

// Routes for CRUD operations

// Get all employees
$f3->route('GET /employees', 'EmployeeComponent->getEmployees');

// Get a specific employee by empID
$f3->route('GET /employees/@empID', 'EmployeeComponent->getEmployee');

// Add a new employee
$f3->route('POST /employees', 'EmployeeComponent->addEmployee');

// Update an existing employee by empID
$f3->route('PUT /employees/@empID', 'EmployeeComponent->updateEmployee');

// Delete an employee by empID
$f3->route('DELETE /employees/@empID', 'EmployeeComponent->deleteEmployee');

// Define the route for login
$f3->route('GET /login', 'EmployeeComponent->login');  // Login route
var_dump($f3->get('GET'));
EmployeeComponent::login($f3);
// Run the Fat-Free framework
$f3->run();
