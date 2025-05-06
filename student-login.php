<?php
session_start();
include('db-config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, email, password FROM students WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['student_id'] = $row['id'];
            $_SESSION['student_email'] = $row['email'];
            header("Location: student-dashboard.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Login</title>
    <style>
        /* General Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: #f4f4f9;
            font-size: 16px;
            color: #333;
            line-height: 1.6;
            overflow-x: hidden;
        }

        h1, h2, h3, h4 {
            color: #f4f4f9;
        }

        /* Background Video */
        .video-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        #bg-video {
            object-fit: cover;
            width: 100%;
            height: 100%;
        }

        /* Login Form Container */
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            position: relative;
            z-index: 1;
        }

        /* Login Form Styling */
        .login-form {
            background-color: rgba(0, 0, 0, 0.7);
            color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-form h2 {
            margin-bottom: 20px;
            font-size: 2rem;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            font-size: 1rem;
            color: #fff;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }

        button.btn {
            width: 100%;
            padding: 10px;
            background-color: #3498db;
            color: white;
            font-size: 1.2rem;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button.btn:hover {
            background-color: #2980b9;
        }

        /* Error Message */
        .error-message {
            color: red;
            font-size: 1rem;
            margin-top: 10px;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .login-form {
                width: 90%;
                padding: 20px;
            }

            .login-form h2 {
                font-size: 1.8rem;
            }

            .form-group input {
                font-size: 1rem;
            }

            button.btn {
                font-size: 1rem;
            }
        }

        /* Mobile-First Design for Small Screens (max-width: 480px) */
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .login-form {
                width: 100%;
                padding: 20px;
                max-width: 95%;
            }

            .login-form h2 {
                font-size: 1.6rem;
            }

            .form-group input {
                font-size: 14px;
                padding: 12px;
            }

            button.btn {
                padding: 12px;
                font-size: 14px;
            }

            .register-link {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Background Video -->
    <div class="video-background">
        <video autoplay muted loop id="bg-video">
            <source src="video/back.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>
    <!-- Login Form Container -->
    <div class="login-container">
        <div class="login-form">
            <h2>Student Login</h2>
            <?php if (isset($error)) echo "<p class='error-message'>$error</p>"; ?>
            
            <form action="student-login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" required>
                </div>
                
                <button class="btn" type="submit">Login</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
