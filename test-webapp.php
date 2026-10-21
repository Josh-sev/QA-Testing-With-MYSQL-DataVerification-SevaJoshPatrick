<?php
// ---------------------------------------------------------------
// Database connection (local MariaDB / MySQL)
// ---------------------------------------------------------------
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "birthday_wishnote";

// Make mysqli return false/errors instead of throwing exceptions,
// so our fallback error messages below can be displayed.
mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli($servername, $username, $password, $dbname);

$successMessage = "";
$errorMessage   = "";

if ($conn->connect_error) {
    $errorMessage = "Database connection failed: " . htmlspecialchars($conn->connect_error);
}

// ---------------------------------------------------------------
// Handle the POST request
// ---------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && !$conn->connect_error) {
    // Capture all four variables from the form
    $FirstName   = trim($_POST["FirstName"] ?? "");
    $LastName    = trim($_POST["LastName"] ?? "");
    $GovPosition = trim($_POST["GovPosition"] ?? "");
    $Location    = trim($_POST["Location"] ?? "");

    // ===========================================================
    // INTENTIONAL FLAW (for QA testing purposes):
    // The Location value is captured above, but it is deliberately
    // OMITTED from both the INSERT statement (no Location column)
    // and from bind_param(). Only FirstName, LastName and
    // GovPosition are saved, so Location data is silently lost.
    // Testers should catch this when comparing the form entries
    // against the database contents.
    // ===========================================================
    $sql  = "INSERT INTO main_corrupt_politicians (FirstName, LastName, GovPosition) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        // Fallback error message if the query fails to prepare
        $errorMessage = "Error: could not prepare the query. " . htmlspecialchars($conn->error);
    } else {
        $stmt->bind_param("sss", $FirstName, $LastName, $GovPosition); // Location intentionally not bound
        if ($stmt->execute()) {
            $successMessage = "Success! The wish note has been sent.";
        } else {
            $errorMessage = "Error: could not save the wish note. " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kara David's Wish Note</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: #f0f2f5;
            font-family: Arial, Helvetica, sans-serif;
            color: #2c3e50;
        }
        .card {
            width: 100%;
            max-width: 460px;
            background: #ffffff;
            border-radius: 8px;
            padding: 28px 24px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
        }
        h2 {
            margin: 0 0 20px;
            text-align: center;
            color: #1e88e5;
        }
        label {
            display: block;
            margin: 14px 0 6px;
            font-weight: bold;
            font-size: 14px;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cfd8dc;
            border-radius: 4px;
            font-size: 15px;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #1e88e5;
            box-shadow: 0 0 0 2px rgba(30, 136, 229, 0.2);
        }
        .btn {
            border: none;
            border-radius: 4px;
            padding: 11px 18px;
            font-size: 15px;
            cursor: pointer;
            color: #fff;
            transition: background 0.2s;
        }
        .btn-primary { background: #1e88e5; width: 100%; margin-top: 22px; }
        .btn-primary:hover { background: #1565c0; }
        .btn-cancel { background: #90a4ae; }
        .btn-cancel:hover { background: #78909c; }
        .btn-confirm { background: #1e88e5; }
        .btn-confirm:hover { background: #1565c0; }
        .msg {
            padding: 12px 14px;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .msg-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .msg-error { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }

        /* Modal */
        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 100;
        }
        .overlay.show { display: flex; }
        .modal {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
        }
        .modal h3 { margin: 0 0 14px; color: #1e88e5; }
        .modal p { margin: 8px 0; font-size: 14px; word-wrap: break-word; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Kara David's Wish Note</h2>

        <?php if ($successMessage): ?>
            <div class="msg msg-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="msg msg-error"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <form id="wishForm" method="POST" action="">
            <label for="FirstName">Target's First Name</label>
            <input type="text" id="FirstName" name="FirstName" maxlength="30" required>

            <label for="LastName">Target's Last Name</label>
            <input type="text" id="LastName" name="LastName" maxlength="30" required>

            <label for="GovPosition">Target's Government Position</label>
            <input type="text" id="GovPosition" name="GovPosition" maxlength="20" required>

            <label for="Location">Target's Location</label>
            <input type="text" id="Location" name="Location" maxlength="40">

            <button type="submit" class="btn btn-primary">Send Wish Note</button>
        </form>
    </div>

    <!-- Verification modal -->
    <div class="overlay" id="modalOverlay">
        <div class="modal">
            <h3>Verify Target Data</h3>
            <p><strong>First Name:</strong> <span id="mFirstName"></span></p>
            <p><strong>Last Name:</strong> <span id="mLastName"></span></p>
            <p><strong>Government Position:</strong> <span id="mGovPosition"></span></p>
            <p><strong>Location:</strong> <span id="mLocation"></span></p>
            <div class="modal-actions">
                <button type="button" class="btn btn-cancel" id="cancelBtn">Cancel</button>
                <button type="button" class="btn btn-confirm" id="confirmBtn">Confirm &amp; Send</button>
            </div>
        </div>
    </div>

    <script>
        var form    = document.getElementById("wishForm");
        var overlay = document.getElementById("modalOverlay");

        // Intercept the submission and show the verification modal
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            document.getElementById("mFirstName").textContent   = document.getElementById("FirstName").value;
            document.getElementById("mLastName").textContent    = document.getElementById("LastName").value;
            document.getElementById("mGovPosition").textContent = document.getElementById("GovPosition").value;
            document.getElementById("mLocation").textContent    = document.getElementById("Location").value || "(none)";
            overlay.classList.add("show");
        });

        // Cancel closes the modal
        document.getElementById("cancelBtn").addEventListener("click", function () {
            overlay.classList.remove("show");
        });

        // Confirm bypasses the interception and posts the form
        document.getElementById("confirmBtn").addEventListener("click", function () {
            form.submit();
        });
    </script>
</body>
</html>
<?php
if (isset($conn) && !$conn->connect_error) {
    $conn->close();
}
?>
