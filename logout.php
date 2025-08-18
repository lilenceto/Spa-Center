<?php
session_start();
session_unset();    // изчистваме всички сесийни променливи
session_destroy();  // унищожаваме сесията
header("Location: index.php");
exit;
