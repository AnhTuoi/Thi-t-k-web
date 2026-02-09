<?php
session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode(["login" => false]);
} else {
    echo json_encode(["login" => true]);
}