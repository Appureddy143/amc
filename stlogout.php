<?php
session_start();
session_unset(); // Remove all session variables
session_destroy(); // Destroy the session
header("Location: /index.php"); // Redirect to login page
exit;
?>
```

### Important Notes:

* **Password Hashing:** Your `students` table needs passwords. You must hash passwords when students are created (e.g., during registration or manually in the database). Use PHP's `password_hash()` function. For example:
    ```php
    $hashed_password = password_hash('student_password', PASSWORD_DEFAULT);
    // Store $hashed_password in the database
    

