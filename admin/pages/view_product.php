<?php
if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}