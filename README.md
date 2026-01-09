HydraBruteLab - University Security Project

This project demonstrates the effectiveness of Account Lockout Policies and Password Complexity against automated brute-force attacks using Hydra.
⚠️ Disclaimer

FOR EDUCATIONAL PURPOSES ONLY. This tool and script are designed for use in a controlled, local environment (localhost) for university coursework. I do not take responsibility for any misuse or damage caused by this script outside of this specific lab environment.
🛠 Requirements

    OS: Kali Linux

    Tools: * Hydra (Pre-installed on Kali)

        curl (Pre-installed on Kali)

        XAMPP (For local PHP/MySQL server)

🚀 Setup & Execution
1. Start the XAMPP Server

Before running the script, ensure your Apache and MySQL services are running.
Bash

sudo /opt/lampp/lampp start

2. Configure the Script

If you need to change the target username or adjust configurations, open the script in a text editor:
Bash

sudo nano run.sh

Modify the USER variable or SLEEP_TIME as needed.
3. Set Permissions

Make sure the script is executable:
Bash

sudo chmod +x run.sh

4. Run the Attack

Execute the script and follow the on-screen menu:
Bash

sudo ./run.sh

📂 Project Structure

    run.sh: The main Bash script controlling the Hydra logic and lockout detection.

    simple/login.php: A login page with no security protections.

    advanced/login.php: A login page featuring account lockout and complexity checks.

    rockyou.txt: Standard wordlist for the Simple lab.

    pass.txt: Custom wordlist for the Advanced lab demonstration.

    bruteforce_report.log: (Generated after run) Contains the session logs for the professor.

📊 Lab Scenarios
Scenario 1: Simple Website

    Target: http://localhost/HydraBruteLab/simple/login.php

    Goal: Show how fast a brute force attack succeeds when no rate-limiting is present.

Scenario 2: Advanced Website

    Target: http://localhost/HydraBruteLab/advanced/login.php

    Goal: Demonstrate how the script detects the Account temporary locked message and stops, simulating a failed attack due to security controls.

📝 Recommendations for Defense

    Implement Account Lockout: Prevent automation by freezing accounts after 3-5 failed attempts.

    Enforce Password Complexity: Use RegEx to ensure passwords contain uppercase, numbers, and symbols.

    Use MFA: Multi-factor authentication renders password-only brute forcing useless.
