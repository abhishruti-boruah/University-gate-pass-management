<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Gate Pass Management System</title>
    <style>
        :root {
            --primary-color: #002147;
            --accent-color: #3b82f6;
            --text-dark: #222222;
        }

        body {
            margin: 0; padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: url('gate.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex; justify-content: center; align-items: center;
        }

        .blur-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.55); z-index: 1;
        }

        .container {
            position: relative; z-index: 5; width: 480px;
            background: rgba(255, 255, 255, 0.95); padding: 40px 35px;
            border-radius: 20px; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            box-sizing: border-box;
        }

        h2 { text-align: center; margin-top: 0; margin-bottom: 30px; color: var(--primary-color); font-size: 26px; font-weight: 800; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-dark); font-size: 14px; }
        
        input[type="text"], input[type="tel"], input[type="password"], textarea {
            width: 100%; padding: 12px 15px; border: 1px solid #ccc;
            border-radius: 8px; font-size: 15px; box-sizing: border-box;
        }

        .submit-btn {
            width: 100%; background: var(--primary-color); color: white; border: none;
            padding: 14px; font-size: 16px; font-weight: bold; border-radius: 8px;
            cursor: pointer; transition: background 0.3s; margin-top: 10px;
        }
        .submit-btn:hover { background: #001127; }

        .security-trigger {
            position: absolute; top: 20px; right: 20px; z-index: 10;
            background: #e11d48; color: white; border: none;
            padding: 12px 24px; border-radius: 20px; cursor: pointer;
            font-weight: 600; font-size: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .modal {
            display: none; position: fixed; z-index: 100;
            left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.6); justify-content: center; align-items: center;
        }
        .modal-content {
            background-color: white; padding: 35px 30px; border-radius: 16px;
            width: 440px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); position: relative;
        }
        .close { position: absolute; top: 15px; right: 20px; color: #aaa; font-size: 28px; cursor: pointer; }
        
        .tab-bar { display: flex; margin-bottom: 25px; border-bottom: 2px solid #eee; }
        .tab-btn { flex: 1; padding: 12px; background: none; border: none; font-weight: bold; font-size: 14px; cursor: pointer; text-align: center; }
        .active-tab { border-bottom: 3px solid var(--accent-color); color: var(--accent-color); }
        .inactive-tab { border-bottom: 3px solid transparent; color: #777; }
        
        .modal-form-btn {
            width: 100%; padding: 14px; background: var(--accent-color); color: white; 
            border: none; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 10px;
        }

        .recovery-link {
            display: block; text-align: center; margin-top: 22px; font-size: 14px;
            color: var(--accent-color); font-weight: 600; text-decoration: none;
        }
        .recovery-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="blur-overlay"></div>
    
    <button class="security-trigger" onclick="openModal()">⚙ Security Login</button>

    <div class="container">
        <h2>University Gate Pass Request</h2>
        <form action="submit.php" method="POST">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" placeholder="Enter Proper Name" required autocomplete="off">
            </div>
            
            <div class="form-group">
                <label>Phone Number *</label>
                <input 
                    type="tel" 
                    name="Phone" 
                    placeholder="Enter 10-Digit Contact Number" 
                    pattern="[0-9]{10}" 
                    maxlength="10" 
                    required 
                    autocomplete="off"
                    title="Phone number must be exactly 10 digits without any spaces or specialized characters."
                >
            </div>
            
            <div class="form-group">
                <label>Vehicle No (If Any)</label>
                <input 
                    type="text" 
                    name="Vehicle_No" 
                    placeholder="e.g., AS20SA2222" 
                    pattern="[A-Za-z]{2}[0-9]{2}[A-Za-z]{2}[0-9]{4}" 
                    maxlength="10" 
                    autocomplete="off"
                    style="text-transform: uppercase;"
                    title="Please input in standard state registration layout format: 2 letters, 2 digits, 2 letters, 4 digits (e.g., AS20SA2222)"
                >
            </div>
            
            <div class="form-group">
                <label>Visitor From *</label>
                <input type="text" name="visitor_from" placeholder="Organization / Address Location" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Purpose of Visit *</label>
                <textarea name="purpose" placeholder="State specific reason for entering campus..." required></textarea>
            </div>
            <button type="submit" class="submit-btn">Submit Request</button>
        </form>

        <a href="get_qr.php" class="recovery-link">Already requested? Retrieve QR Code here →</a>
    </div>

    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            
            <div class="tab-bar">
                <button id="tab-login" type="button" class="tab-btn active-tab" onclick="switchOption('login')">Security Login</button>
                <button id="tab-register" type="button" class="tab-btn inactive-tab" onclick="switchOption('register')">Register Security Guard</button>
            </div>

            <div id="optionInterfaceContainer">
                <form action="login.php" method="POST">
                    <input type="hidden" name="action_type" value="guard_login">
                    <div style="margin-bottom: 15px;">
                        <input type="text" name="guard_name" placeholder="Registered Guard Full Name" required autocomplete="off">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <input type="password" name="guard_password" placeholder="Enter Security Password" required>
                    </div>
                    <button type="submit" class="modal-form-btn">Login to Duty Dashboard</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('loginModal');
        function openModal() { modal.style.display = 'flex'; }
        function closeModal() { modal.style.display = 'none'; }
        window.onclick = function(e) { if (e.target == modal) closeModal(); }

        function switchOption(option) {
            const loginTab = document.getElementById('tab-login');
            const registerTab = document.getElementById('tab-register');
            const contentArea = document.getElementById('optionInterfaceContainer');

            if (option === 'login') {
                loginTab.className = "tab-btn active-tab";
                registerTab.className = "tab-btn inactive-tab";
                contentArea.innerHTML = `
                    <form action="login.php" method="POST">
                        <input type="hidden" name="action_type" value="guard_login">
                        <div style="margin-bottom: 15px;">
                            <input type="text" name="guard_name" placeholder="Registered Guard Full Name" required autocomplete="off">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <input type="password" name="guard_password" placeholder="Enter Security Password" required>
                        </div>
                        <button type="submit" class="modal-form-btn">Login to Duty Dashboard</button>
                    </form>
                `;
            } else {
                registerTab.className = "tab-btn active-tab";
                loginTab.className = "tab-btn inactive-tab";
                contentArea.innerHTML = `
                    <form action="login.php" method="POST">
                        <input type="hidden" name="action_type" value="guard_register">
                        <div style="margin-bottom: 15px;">
                            <input type="text" name="guard_name" placeholder="Create Guard Username" required autocomplete="off">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <input type="password" name="guard_password" placeholder="Create Secure Password" required>
                        </div>
                        <button type="submit" class="modal-form-btn" style="background:#10b981;">Register New Guard Account</button>
                    </form>
                `;
            }
        }
    </script>
</body>
</html>