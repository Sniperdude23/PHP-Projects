<?php
include 'config.php'; // Make sure this file properly initializes the PDO connection

// Start the session if not already started (useful for future features like flash messages)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect logged-in users from the registration page
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
// Check if the form was submitted using POST method
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and trim user inputs
    $username = htmlspecialchars(trim($_POST['username']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // Password will be hashed, no direct sanitization here

    // Basic validation
    if (empty($username) || empty(html_entity_decode($email)) || empty($password)) { // Use html_entity_decode for email check
        $error = "Please fill in all fields.";
    } elseif (!filter_var(html_entity_decode($email), FILTER_VALIDATE_EMAIL)) { // Use html_entity_decode for email validation
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) { // Example: enforce minimum password length
        $error = "Password must be at least 6 characters long.";
    } else {
        // Hash the password securely using BCRYPT
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        try {
            // Check if email already exists to prevent duplicate registrations
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt_check->execute([$email]);
            if ($stmt_check->fetchColumn() > 0) {
                $error = "This email is already registered. Please use a different email or log in.";
            } else {
                // Insert new user into the database, explicitly assigning 'customer' role
                $stmt_insert = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'customer')"); // Changed 'user' to 'customer'
                $stmt_insert->execute([$username, $email, $hashed_password]);

                // Redirect to login page upon successful registration
                // Optionally, you could set a success message in session here
                $_SESSION['registration_success'] = "Registration successful! Please log in.";
                header("Location: login.php");
                exit();
            }
        } catch (PDOException $e) {
            // Log the error for debugging purposes
            error_log("Registration Database Error: " . $e->getMessage());
            // Generic error message for the user
            $error = "Registration failed. An unexpected error occurred. Please try again later.";
        } catch (Exception $e) {
            error_log("Registration Application Error: " . $e->getMessage());
            $error = "Registration failed. An unexpected error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ShopeeClone</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ee4d2d;
            --secondary-color: #ff7337;
            --light-color: #fff;
            --dark-color: #333;
            --gray-color: #f5f5f5;
            --text-color: #555;
            --border-color: #ddd;
            --error-red: #d32f2f;
            --error-bg: #fde0e0;
            --success-green: #388e3c;
            --success-bg: #e8f5e9;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background-color: var(--gray-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            width: 100vw;
            padding: 20px;
            overflow-x: hidden;
        }

        .main-content-wrapper {
            display: flex;
            background-color: var(--light-color);
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 90%;
            max-width: 1200px;
            min-height: 500px;
        }

        .image-container {
            flex: 1.2;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            position: relative;
        }

        .image-container img {
            max-width: 90%;
            height: auto;
            display: block;
            border-radius: 5px;
        }

        .register-container {
            flex: 1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .register-header h1 {
            color: var(--primary-color);
            margin-bottom: 0.75rem;
            font-size: 2.5rem;
        }

        .register-header p {
            color: var(--text-color);
            font-size: 1.1rem;
        }

        .error-message {
            color: var(--error-red);
            background-color: var(--error-bg);
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: bold;
            border: 1px solid var(--error-red);
        }

        .register-form input {
            width: 100%;
            padding: 14px;
            margin-bottom: 1.25rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1.05rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .register-form input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(238, 77, 45, 0.2);
            outline: none;
        }

        .register-button {
            width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .register-button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .register-button:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-color);
            font-size: 1rem;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        /* Media query for smaller screens */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            .main-content-wrapper {
                flex-direction: column;
                max-width: 450px;
                width: 95%;
                min-height: auto;
            }
            .image-container {
                min-height: 250px;
                padding: 1.5rem;
                order: -1;
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
                border-top-left-radius: 8px;
                border-top-right-radius: 8px;
            }
            .register-container {
                padding: 2rem;
                border-top-left-radius: 0;
                border-top-right-radius: 0;
                border-bottom-left-radius: 8px;
                border-bottom-right-radius: 8px;
            }
            .register-header h1 {
                font-size: 2rem;
            }
            .register-header {
                margin-bottom: 2rem;
            }
            .error-message {
                padding: 0.8rem;
                margin-bottom: 1rem;
            }
            .register-form input,
            .register-button {
                padding: 12px;
                font-size: 1rem;
            }
            .login-link {
                margin-top: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-content-wrapper">
        <div class="image-container">
            <img src="https://i.pinimg.com/736x/3f/28/30/3f2830240e9e391ef8b3290c1dcdf371.jpg" alt="Join ShopeeClone">
        </div>
        <div class="register-container">
            <div class="register-header">
                <h1>Create Your Account</h1>
                <p>Join us and start shopping!</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="register-form" novalidate>
                <input type="text" name="username" placeholder="Username" required autocomplete="username"
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">

                <input type="email" name="email" placeholder="Email Address" required autocomplete="email"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

                <input type="password" name="password" placeholder="Password (min 6 characters)" required autocomplete="new-password">

                <button type="submit" class="register-button">Register</button>
            </form>

            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</body>
</html>