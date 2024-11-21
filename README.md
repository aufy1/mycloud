Follow these steps:

- Use this command to render CSS `./tailwindcss -i input.css -o output.css --watch --minify`
- Create new file "config.php" with variables:
  `$database = mysqli_connect('IP:PORT', 'DB_USER', 'DB_PASS', 'DB_NAME');`
  `$secretKey = "your secret";`
  `$recaptcha_secret = "captcha secret"`

For now there is no .env file.
