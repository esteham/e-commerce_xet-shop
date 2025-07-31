<?php
session_start();
if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}