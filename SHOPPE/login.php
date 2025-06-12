<?php
include 'config.php'; // Make sure this file properly initializes session and PDO

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect logged-in users
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
// Check if the form was submitted using POST method
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and trim user inputs
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // Password will be hashed, so no sanitization here

    // Basic validation
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            // Prepare and execute the SQL statement to fetch user data
            // It's good practice to ensure $pdo is available from config.php
            if (!isset($pdo)) {
                 throw new Exception("PDO connection not available.");
            }
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch as associative array

            if ($user) {
                // Verify the submitted password against the hashed password in the database
                if (password_verify($password, $user['password'])) {
                    // Password is correct. Regenerate session ID to prevent fixation attacks.
                    session_regenerate_id(true);

                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['last_login'] = time(); // Record last login time

                    // Redirect to the intended page or home page
                    header("Location: index.php");
                    exit();
                }
            }
            // Generic error message to prevent user enumeration (don't reveal if email exists)
            $error = "Invalid email or password.";
        } catch (PDOException $e) {
            // Log the error for debugging purposes (e.g., to a file)
            error_log("Database error: " . $e->getMessage());
            $error = "An unexpected error occurred. Please try again later.";
        } catch (Exception $e) {
            error_log("Application error: " . $e->getMessage());
            $error = "An unexpected error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ShopeeClone</title>
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif; /* Use Roboto for a modern look */
        }

        body {
            background-color: var(--gray-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Ensure full viewport height */
            width: 100vw; /* Ensure full viewport width */
            padding: 20px; /* Padding for small screens, ensures content isn't flush with edges */
            overflow-x: hidden; /* Prevent horizontal scrollbar */
        }

        .main-content-wrapper {
            display: flex;
            background-color: var(--light-color);
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1); /* Enhanced shadow for better depth */
            overflow: hidden;
            width: 90%; /* Occupy a large percentage of width on large screens */
            max-width: 1200px; /* Max width for the entire container */
            min-height: 500px; /* Ensure a decent height for the whole form */
        }

        .image-container {
            flex: 1.2; /* Give more space to the image on larger screens */
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); /* Gradient background */
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            position: relative; /* For potential absolute positioning of elements */
        }

        .image-container img {
            max-width: 90%; /* Slightly smaller to give padding effect */
            height: auto;
            display: block;
            border-radius: 5px; /* Slight roundness for the image */
        }

        .login-container {
            flex: 1; /* Less space for login on larger screens */
            padding: 3rem; /* Increased padding for better spacing */
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem; /* More space below header */
        }

        .login-header h1 {
            color: var(--primary-color);
            margin-bottom: 0.75rem; /* More space below title */
            font-size: 2.5rem; /* Larger heading */
        }

        .login-header p {
            color: var(--text-color);
            font-size: 1.1rem;
        }

        .error-message {
            color: var(--error-red);
            background-color: var(--error-bg);
            padding: 1rem; /* Increased padding */
            border-radius: 4px;
            margin-bottom: 1.5rem; /* More space below error */
            text-align: center;
            font-weight: bold;
            border: 1px solid var(--error-red); /* Add a subtle border */
        }

        .login-form input {
            width: 100%;
            padding: 14px; /* Larger input fields */
            margin-bottom: 1.25rem; /* More space between inputs */
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1.05rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .login-form input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(238, 77, 45, 0.2); /* Soft shadow on focus */
            outline: none;
        }

        .login-button {
            width: 100%;
            padding: 14px; /* Larger button */
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1.1rem; /* Larger font on button */
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .login-button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px); /* Slight lift effect */
        }

        .login-button:active {
            transform: translateY(0); /* Return to original position on click */
        }

        .register-link {
            text-align: center;
            margin-top: 2rem; /* More space above register link */
            color: var(--text-color);
            font-size: 1rem;
        }

        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        /* Media query for smaller screens */
        @media (max-width: 768px) {
            body {
                padding: 15px; /* Less padding on very small screens */
            }
            .main-content-wrapper {
                flex-direction: column; /* Stack elements vertically on smaller screens */
                max-width: 450px; /* Constrain width for mobile forms */
                width: 95%; /* Use more width on mobile */
                min-height: auto; /* Allow height to adjust */
            }
            .image-container {
                min-height: 250px; /* Smaller height for image on mobile */
                padding: 1.5rem;
                order: -1; /* Place image on top for mobile */
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
                border-top-left-radius: 8px; /* Maintain rounded corners on top */
                border-top-right-radius: 8px;
            }
            .login-container {
                padding: 2rem; /* Adjusted padding for mobile */
                border-top-left-radius: 0;
                border-top-right-radius: 0;
                border-bottom-left-radius: 8px; /* Maintain rounded corners on bottom */
                border-bottom-right-radius: 8px;
            }
            .login-header h1 {
                font-size: 2rem; /* Smaller heading on mobile */
            }
            .login-header {
                margin-bottom: 2rem;
            }
            .error-message {
                padding: 0.8rem;
                margin-bottom: 1rem;
            }
            .login-form input,
            .login-button {
                padding: 12px;
                font-size: 1rem;
            }
            .register-link {
                margin-top: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-content-wrapper">
        <div class="image-container">
            <img src="https://i.pinimg.com/736x/10/aa/12/10aa12fb1a2fbd2cd2a02fc9dc72d615.jpg" alt="Welcome to ShopeeClone">
        </div>
        <div class="login-container">
            <div class="login-header">
                <h1>Welcome Back!</h1>
                <p>Login to your account to continue shopping.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form" novalidate> <input type="email" name="email" placeholder="Email Address" required autocomplete="email"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

                <input type="password" name="password" placeholder="Password" required autocomplete="current-password">

                <button type="submit" class="login-button">Login</button>
            </form>

            <div class="register-link">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</body>
</html>