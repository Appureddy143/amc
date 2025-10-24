<?php
// session_start(); // Enable this line if you implement sessions later
require_once('db.php'); // Use the correct database connection file (PDO)

// --- Security Check (Placeholder) ---
// Uncomment and adapt this section when you have a login system
/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Redirect to your login page
    exit;
}
*/

// --- Handle Subject Allocation ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['allocate_subjects'])) {
    // Basic validation
    if (empty($_POST['staff_id']) || empty($_POST['subject_ids']) || !is_array($_POST['subject_ids'])) {
        $error = "Please select a staff member and at least one subject.";
    } else {
        $staff_id = filter_input(INPUT_POST, 'staff_id', FILTER_VALIDATE_INT);
        $subject_ids = filter_input(INPUT_POST, 'subject_ids', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);

        if ($staff_id === false || $subject_ids === false || in_array(false, $subject_ids, true)) {
             $error = "Invalid input data.";
        } else {
            // Prepare the statement for inserting allocations (PostgreSQL compatible)
            // ON CONFLICT DO NOTHING prevents duplicates
            $sql = "INSERT INTO subject_allocation (staff_id, subject_id) VALUES (:staff_id, :subject_id) ON CONFLICT (staff_id, subject_id) DO NOTHING";
            $stmt = $pdo->prepare($sql);

            $allocated_count = 0;
            foreach ($subject_ids as $subject_id) {
                // Ensure subject_id is a valid integer before binding
                 if ($subject_id > 0) {
                     $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
                     $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
                     $stmt->execute();
                     if ($stmt->rowCount() > 0) {
                         $allocated_count++;
                     }
                 }
            }
             $message = $allocated_count . " subject(s) allocated successfully!";
        }
    }
}

// --- Fetch Data for Dropdowns ---
try {
    // Fetch staff members
    $staff_stmt = $pdo->query("SELECT id, first_name, surname FROM users WHERE role = 'staff' ORDER BY first_name");
    $staff_members = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch subjects not yet allocated to *any* staff member
    $subject_stmt = $pdo->query("
        SELECT s.id, s.name AS subject_name, s.subject_code
        FROM subjects s
        LEFT JOIN subject_allocation sa ON s.id = sa.subject_id
        WHERE sa.subject_id IS NULL
        ORDER BY s.subject_code
    ");
    $subjects = $subject_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch *all* subjects for potential re-allocation viewing (optional)
    // $all_subjects_stmt = $pdo->query("SELECT id, name AS subject_name, subject_code FROM subjects ORDER BY subject_code");
    // $all_subjects = $all_subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // In a real application, log this error instead of dying
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Allocation</title>
    <style>
        /* Reusing styles from admin.php for consistency */
        :root { --space-cadet: #2b2d42; --cool-gray: #8d99ae; --antiflash-white: #edf2f4; --red-pantone: #ef233c; --fire-engine-red: #d90429; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 20px; background: var(--space-cadet); color: var(--antiflash-white); }
        .back-link { display: block; max-width: 860px; margin: 0 auto 20px auto; text-align: right; font-weight: bold; color: var(--antiflash-white); text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .container { max-width: 800px; margin: 20px auto; padding: 30px; background: rgba(141, 153, 174, 0.1); border-radius: 15px; border: 1px solid rgba(141, 153, 174, 0.2); }
        h1, h2 { text-align: center; }
        form { display: flex; flex-direction: column; gap: 1em; }
        label { display: block; margin-bottom: 5px; font-weight: 600; }
        select { width: 100%; padding: 10px; margin-bottom: 5px; border-radius: 5px; border: 1px solid var(--cool-gray); background: rgba(43, 45, 66, 0.5); color: var(--antiflash-white); box-sizing: border-box; }
        select[multiple] { height: 150px; } /* Make multiple select taller */
        button { padding: 12px 20px; border: none; border-radius: 5px; background-color: var(--fire-engine-red); color: var(--antiflash-white); font-weight: bold; cursor: pointer; width: 100%; font-size: 1.1em; }
        button:hover { background-color: var(--red-pantone); }
        .message { padding: 10px; border-radius: 5px; margin-bottom: 1em; text-align: center; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { font-size: 0.9em; color: var(--cool-gray); }
    </style>
</head>
<body>
    <a href="/admin" class="back-link">&laquo; Back to Admin Dashboard</a>

    <div class="container">
        <h2>Allocate Subjects to Staff</h2>

        <?php if (isset($message)): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="subject-allocation.php" method="POST">
            <label for="staff_id">Select Staff:</label>
            <select name="staff_id" id="staff_id" required>
                <option value="">-- Select Staff --</option>
                <?php foreach ($staff_members as $staff): ?>
                    <option value="<?= htmlspecialchars($staff['id']) ?>">
                        <?= htmlspecialchars($staff['first_name'] . ' ' . $staff['surname']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="subject_ids">Select Subjects (Only Unallocated Shown):</label>
            <select name="subject_ids[]" id="subject_ids" multiple required>
                <?php if (empty($subjects)): ?>
                    <option value="" disabled>No unallocated subjects found.</option>
                <?php else: ?>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= htmlspecialchars($subject['id']) ?>">
                            <?= htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <p class="info">Hold Ctrl (or Cmd on Mac) to select multiple subjects.</p>

            <button type="submit" name="allocate_subjects">Allocate Subjects</button>
        </form>
    </div>
</body>
</html>
