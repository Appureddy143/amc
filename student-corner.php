<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Corner</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        // Toggle Navbar visibility
        function toggleNavbar() {
            const navLinks = document.querySelector(".nav-links");
            navLinks.classList.toggle("active");
        }
    </script>
    <style>
        /* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Arial', sans-serif;
    color: #333;
    line-height: 1.6;
    background: #f4f4f9;
    overflow-x: hidden;
}

/* Header Section */
.header {
    background: #007BFF;
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.header .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.header-left h1 {
    font-size: 1.8rem;
    font-weight: bold;
}

.header-right {
    display: flex;
    align-items: center;
    position: relative;
}

.hamburger {
    display: none;
    cursor: pointer;
    flex-direction: column;
    gap: 5px;
}

.hamburger .line {
    width: 25px;
    height: 3px;
    background-color: white;
    border-radius: 2px;
}

.nav-links {
    display: flex;
    list-style: none;
    gap: 20px;
}

.nav-links a {
    color: white;
    text-decoration: none;
    font-size: 1rem;
    padding: 8px 15px;
    transition: background-color 0.3s ease;
}

.nav-links a:hover {
    background-color: #0056b3;
    border-radius: 5px;
}

/* Responsive Navbar */
@media (max-width: 768px) {
    .hamburger {
        display: flex;
    }

    .nav-links {
        display: none;
        flex-direction: column;
        background: #0056b3;
        position: absolute;
        top: 50px;
        right: 10px;
        width: 200px;
        border-radius: 8px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
    }

    .nav-links.active {
        display: flex;
    }

    .nav-links li {
        text-align: center;
        margin: 10px 0;
    }
}

/* Main Content */
.main-content {
    padding: 40px 20px;
    text-align: center;
    background: #f4f4f9;
}

.welcome {
    margin-bottom: 40px;
}

.welcome h2 {
    font-size: 2.5rem;
    margin-bottom: 15px;
}

.welcome p {
    font-size: 1.2rem;
    color: #555;
}

.features {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.feature-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    padding: 20px;
    width: 300px;
    text-align: center;
}

.feature-card h3 {
    font-size: 1.5rem;
    margin-bottom: 10px;
}

.feature-card p {
    font-size: 1rem;
    margin-bottom: 20px;
    color: #666;
}

.feature-card .button {
    background-color: #007BFF;
    color: white;
    padding: 10px 15px;
    text-decoration: none;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.feature-card .button:hover {
    background-color: #0056b3;
}

/* Responsive Layout */
@media (max-width: 768px) {
    .welcome h2 {
        font-size: 2rem;
    }

    .features {
        flex-direction: column;
        gap: 20px;
    }

    .feature-card {
        width: 90%;
    }
}

        </style>
</head>
<body>
    <!-- Header Section -->
    <header class="header">
        <div class="container">
            <div class="header-left">
                <h1>VVIT Student Corner</h1>
            </div>
            <div class="header-right">
                <div class="hamburger" onclick="toggleNavbar()">
                    <div class="line"></div>
                    <div class="line"></div>
                    <div class="line"></div>
                </div>
                <ul class="nav-links">
                    <li><a href="#ia-time-tables">IA Time Tables</a></li>
                    <li><a href="#class-time-tables">Class Time Tables</a></li>
                    <li><a href="#events-calendar">Calendar of Events</a></li>
                    <li><a href="index.php">Back to Home</a></li>
                    <li><a href="student-login.php">Login</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <section class="welcome">
            <h2>Welcome to VVIT Student Corner</h2>
            <p>Explore essential resources and updates, including IA schedules, class timetables, and college events.</p>
        </section>

        <section class="features">
            <div class="feature-card">
                <h3>IA Time Tables</h3>
                <p>Access the latest internal assessment schedules.</p>
                <a href="#ia-time-tables" class="button">View IA Time Tables</a>
            </div>
            <div class="feature-card">
                <h3>Class Time Tables</h3>
                <p>Stay updated with your class schedules.</p>
                <a href="#class-time-tables" class="button">View Class Time Tables</a>
            </div>
            <div class="feature-card">
                <h3>Calendar of Events</h3>
                <p>Never miss out on important college events.</p>
                <a href="#events-calendar" class="button">View Events</a>
            </div>
        </section>
    </main>
</body>
</html>
