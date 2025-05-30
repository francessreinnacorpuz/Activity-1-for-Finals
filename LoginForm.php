<?php
session_start();

// Path to user data file
define('USER_DATA_FILE', __DIR__ . '/users.txt');

// Helper function to get existing users
function get_users() {
    $users = [];
    if (file_exists(USER_DATA_FILE)) {
        $lines = file(USER_DATA_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            list($username, $hash) = explode(':', trim($line), 2);
            $users[$username] = $hash;
        }
    }
    return $users;
}

// Helper function to save new user
function save_user($username, $password_hash) {
    file_put_contents(USER_DATA_FILE, "$username:$password_hash\n", FILE_APPEND | LOCK_EX);
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: auth.php');
    exit;
}

$errors = [];
$messages = [];
$users = get_users();

// Handle Sign Up
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (!$username || !$password || !$password_confirm) {
        $errors[] = "Please fill in all sign-up fields.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = "Username should be 3-20 chars, letters, numbers or underscore only.";
    } elseif ($password !== $password_confirm) {
        $errors[] = "Passwords do not match.";
    } elseif (isset($users[$username])) {
        $errors[] = "Username already exists.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        save_user($username, $password_hash);
        $messages[] = "Sign-up successful! You can now login.";
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $errors[] = "Please fill in all login fields.";
    } elseif (!isset($users[$username]) || !password_verify($password, $users[$username])) {
        $errors[] = "Invalid username or password.";
    } else {
        $_SESSION['username'] = $username;
        header('Location: auth.php');
        exit;
    }
}

// If logged in
$logged_in_user = $_SESSION['username'] ?? null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Sign Up & Login</title>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #74ABE2, #5563DE);
        margin: 0; padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        color: #333;
    }
    .container {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        padding: 30px 40px;
        max-width: 400px;
        width: 100%;
        box-sizing: border-box;
    }
    h2 {
        text-align: center;
        color: #33475b;
        margin-bottom: 20px;
    }
    form {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    input[type="text"], input[type="password"] {
        padding: 10px 15px;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s ease;
    }
    input[type="text"]:focus, input[type="password"]:focus {
        outline: none;
        border-color: #5563DE;
        box-shadow: 0 0 8px #5563de88;
    }
    button {
        background: #5563DE;
        color: white;
        border: none;
        padding: 12px;
        font-size: 18px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: bold;
        transition: background-color 0.3s ease;
    }
    button:hover {
        background: #3445a1;
    }
    .messages, .errors {
        margin-bottom: 20px;
        padding: 10px 15px;
        border-radius: 8px;
    }
    .messages {
        background: #daf5da;
        color: #256029;
        border: 1px solid #81c784;
    }
    .errors {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .toggle-forms {
        text-align: center;
        margin-top: 20px;
        color: #33475b;
        font-size: 14px;
    }
    .toggle-link {
        color: #5563DE;
        cursor: pointer;
        font-weight: bold;
        text-decoration: none;
    }
    .toggle-link:hover {
        text-decoration: underline;
    }
    .logout {
        text-align: center;
        margin-top: 20px;
    }
    .logout a {
        color: #cc4444;
        font-weight: bold;
        text-decoration: none;
        transition: color 0.3s ease;
    }
    .logout a:hover {
        color: #882222;
    }
</style>
<script>
function showForm(formId) {
    document.getElementById('login-form').style.display = (formId === 'login-form') ? 'block' : 'none';
    document.getElementById('signup-form').style.display = (formId === 'signup-form') ? 'block' : 'none';
}
window.onload = function() {
    // Show login form by default
    showForm('login-form');
};
</script>
</head>
<body>

<div class="container">

<?php if ($logged_in_user): ?>
    <h2>Welcome, <?php echo htmlspecialchars($logged_in_user); ?>!</h2>
    <div class="logout">
        <a href="auth.php?action=logout">Logout</a>
    </div>
<?php else: ?>

    <?php if ($errors): ?>
    <div class="errors">
        <ul style="margin:0; padding-left: 20px;">
            <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if ($messages): ?>
    <div class="messages">
        <ul style="margin:0; padding-left: 20px;">
            <?php foreach ($messages as $msg): ?>
            <li><?php echo htmlspecialchars($msg); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="post" id="login-form" style="display:none;">
        <h2>Login</h2>
        <input type="text" name="username" placeholder="Username" required autocomplete="username" />
        <input type="password" name="password" placeholder="Password" required autocomplete="current-password" />
        <button type="submit" name="login">Log In</button>
        <div class="toggle-forms">
            Don't have an account? <a class="toggle-link" onclick="showForm('signup-form')">Sign Up</a>
        </div>
    </form>

    <form method="post" id="signup-form" style="display:none;">
        <h2>Sign Up</h2>
        <input type="text" name="username" placeholder="Choose Username" required autocomplete="username" pattern="[a-zA-Z0-9_]{3,20}" title="3-20 chars: letters, numbers or underscore" />
        <input type="password" name="password" placeholder="Password" required autocomplete="new-password" />
        <input type="password" name="password_confirm" placeholder="Confirm Password" required autocomplete="new-password" />
        <button type="submit" name="signup">Sign Up</button>
        <div class="toggle-forms">
            Already have an account? <a class="toggle-link" onclick="showForm('login-form')">Log In</a>
        </div>
    </form>

<?php endif; ?>

</div>

</body>
</html>

