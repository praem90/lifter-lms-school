<?php

use Lifter\MT\StudentListTable;
use Lifter\MT\Student;


$students = new Student();

var_dump( $students->get() );
$list = new StudentListTable();
$list->search_box( 'search', 'search_id' );

$list->prepare_items();

$list->display();

