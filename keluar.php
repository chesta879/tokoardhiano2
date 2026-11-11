<?php
require 'config.php'; unset($_SESSION['pelanggan'], $_SESSION['cart']); session_regenerate_id(true); pindah('index.php');
