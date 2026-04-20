$filePath = "c:\xampp\htdocs\teacher-eval\admin\teachers.php"
$content = [System.IO.File]::ReadAllText($filePath, [System.Text.Encoding]::UTF8)
$content = $content -replace '(?s)\s*<style>.*?</style>', ""
[System.IO.File]::WriteAllText($filePath, $content, [System.Text.Encoding]::UTF8)
Write-Host "Fixed teachers.php"
